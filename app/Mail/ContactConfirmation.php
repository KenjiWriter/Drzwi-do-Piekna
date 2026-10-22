<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Automatyczne potwierdzenie dla osoby, która wysłała formularz kontaktowy i podała adres e-mail:
 * dziękujemy i zapowiadamy kontakt. Celowo nie zawiera numeru telefonu ani treści wiadomości z formularza
 * (poczta nie jest szyfrowana, a formularz mógł dotyczyć zdrowia), a imię i obszar zabiegu są jedynym,
 * co powtarzamy. Odpowiedź na tę wiadomość trafia do gabinetu.
 */
class ContactConfirmation extends Mailable
{
    /** @param  bool  $preview  podgląd w przeglądarce: obrazek jako data URI zamiast osadzonego załącznika (cid:) */
    public function __construct(public ContactRequest $contact, public bool $preview = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dziękujemy za zgłoszenie | '.config('clinic.name'),
            replyTo: [new Address(config('clinic.email'), config('clinic.name'))],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-confirmation', text: 'mail.contact-confirmation-text');
    }
}
