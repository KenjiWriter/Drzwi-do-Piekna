<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactConfirmation;
use App\Mail\ContactRequestReceived;
use App\Models\ContactRequest;
use App\Models\TreatmentCategory;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Contact', [
            'content' => trans('pages.contact'),
            'areas' => TreatmentCategory::published()->get(['slug', 'name'])->all(),
            'seo' => Seo::page(
                title: 'Kontakt i umówienie wizyty | Drzwi do Piękna',
                description: 'Umów konsultację w gabinecie medycyny estetycznej Drzwi do Piękna w Łodzi. Zadzwoń, napisz lub zarezerwuj termin online.',
                path: '/kontakt',
                crumbs: [['Kontakt', '/kontakt']],
                type: 'ContactPage',
            ),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $success = 'Dziękujemy za wiadomość. Oddzwonimy w godzinach pracy gabinetu.';

        // Honeypot: udajemy sukces, ale niczego nie zapisujemy.
        if ($request->filled('website')) {
            return redirect()->route('contact')->with('success', $success);
        }

        $contact = ContactRequest::create([
            ...$request->safe()->only(['name', 'phone', 'email', 'subject', 'message']),
            'consent_at' => now(),
        ]);

        try {
            Mail::to(config('clinic.email'))->send(new ContactRequestReceived($contact));
            $contact->forceFill(['notified_at' => now()])->save();
        } catch (Throwable $e) {
            // Zapytanie jest bezpieczne w bazie, więc awaria poczty nie psuje wizyty pacjentki.
            report($e);
        }

        if ($this->sendConfirmation($contact)) {
            $success .= ' Potwierdzenie wysłaliśmy na podany adres e-mail.';
        }

        return redirect()->route('contact')->with('success', $success);
    }

    /**
     * Automatyczne potwierdzenie dla osoby z formularza, jeśli podała adres e-mail. Niezależne od powiadomienia
     * dla gabinetu: awaria poczty nie psuje zgłoszenia. Formularz jest publiczny, a potwierdzenie idzie na adres
     * wpisany przez nieznaną osobę, więc do jednego adresu wysyłamy najwyżej dwa na godzinę (inaczej dałoby się
     * użyć formularza do zasypywania cudzej skrzynki). Klucz to skrót adresu, żeby nie trzymać go w pamięci podręcznej.
     */
    private function sendConfirmation(ContactRequest $contact): bool
    {
        if (blank($contact->email)) {
            return false;
        }

        if (! RateLimiter::attempt('contact-confirmation:'.sha1(Str::lower($contact->email)), 2, fn () => true, 3600)) {
            return false;
        }

        try {
            Mail::to($contact->email)->send(new ContactConfirmation($contact));
            $contact->forceFill(['confirmation_sent_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
