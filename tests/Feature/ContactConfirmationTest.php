<?php

namespace Tests\Feature;

use App\Mail\ContactConfirmation;
use App\Mail\ContactRequestReceived;
use App\Models\ContactRequest;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ContactConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function valid(array $override = []): array
    {
        return array_merge([
            'name' => 'Maria',
            'phone' => '600 100 200',
            'email' => 'maria@example.com',
            'subject' => 'Twarz',
            'message' => 'Opis mojego problemu zdrowotnego.',
            'consent' => true,
            'website' => '',
        ], $override);
    }

    private function contact(array $override = []): ContactRequest
    {
        return ContactRequest::create([
            'name' => 'Maria', 'phone' => '600 100 200', 'email' => 'maria@example.com', 'subject' => 'Twarz',
            'message' => 'Opis mojego problemu zdrowotnego.', 'consent_at' => now(), ...$override,
        ]);
    }

    public function test_a_confirmation_goes_to_the_address_given_in_the_form(): void
    {
        Mail::fake();

        $this->post('/kontakt', $this->valid())
            ->assertRedirect('/kontakt')
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Potwierdzenie wysłaliśmy na podany adres e-mail.'));

        Mail::assertSent(ContactConfirmation::class, fn (ContactConfirmation $mail) => $mail->hasTo('maria@example.com')
            && $mail->hasReplyTo(config('clinic.email'))
            && $mail->hasSubject('Dziękujemy za zgłoszenie | Drzwi do Piękna'));
        // Powiadomienie dla gabinetu idzie niezależnie.
        Mail::assertSent(ContactRequestReceived::class, fn ($mail) => $mail->hasTo(config('clinic.email')));

        $this->assertNotNull(ContactRequest::firstOrFail()->confirmation_sent_at);
    }

    public function test_nothing_is_sent_to_the_visitor_without_an_email(): void
    {
        Mail::fake();

        $this->post('/kontakt', $this->valid(['email' => '']))
            ->assertSessionHas('success', fn (string $message) => ! str_contains($message, 'Potwierdzenie'));

        Mail::assertNotSent(ContactConfirmation::class);
        Mail::assertSent(ContactRequestReceived::class);
        $this->assertNull(ContactRequest::firstOrFail()->confirmation_sent_at);
    }

    public function test_bots_get_no_confirmation(): void
    {
        Mail::fake();

        $this->post('/kontakt', $this->valid(['website' => 'http://spam.example']));

        Mail::assertNothingSent();
    }

    public function test_a_failing_confirmation_loses_neither_the_request_nor_the_notification_to_the_clinic(): void
    {
        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')->once();
        Mail::shouldReceive('to')->with(config('clinic.email'))->once()->andReturn($pending);
        Mail::shouldReceive('to')->with('maria@example.com')->once()->andThrow(new RuntimeException('SMTP down'));

        $this->post('/kontakt', $this->valid())
            ->assertRedirect('/kontakt')
            ->assertSessionHas('success', fn (string $message) => ! str_contains($message, 'Potwierdzenie'));

        $contact = ContactRequest::firstOrFail();
        $this->assertNotNull($contact->notified_at);
        $this->assertNull($contact->confirmation_sent_at);
    }

    public function test_one_address_gets_at_most_two_confirmations_an_hour(): void
    {
        Mail::fake();

        foreach (['maria@example.com', 'MARIA@example.com', 'maria@example.com'] as $email) {
            $this->post('/kontakt', $this->valid(['email' => $email]));
        }

        Mail::assertSent(ContactConfirmation::class, 2);
        $this->assertSame(3, ContactRequest::count(), 'Zgłoszenia zapisują się zawsze, limit dotyczy tylko potwierdzeń.');
        Mail::assertSent(ContactRequestReceived::class, 3);

        // Inny adres nie jest ograniczony cudzym limitem.
        $this->post('/kontakt', $this->valid(['email' => 'ola@example.com']));
        Mail::assertSent(ContactConfirmation::class, 3);

        $this->travel(61)->minutes();
        $this->post('/kontakt', $this->valid(['email' => 'maria@example.com']));
        Mail::assertSent(ContactConfirmation::class, 4);
    }

    public function test_the_message_repeats_neither_the_phone_number_nor_the_text_from_the_form(): void
    {
        $mail = new ContactConfirmation($this->contact());

        // Numer i opis są w zgłoszeniu, ale poczta nie jest szyfrowana, a formularz mógł dotyczyć zdrowia.
        $mail->assertDontSeeInHtml('600 100 200');
        $mail->assertDontSeeInHtml('problemu zdrowotnego');
        $mail->assertDontSeeInText('600 100 200');
        $mail->assertDontSeeInText('problemu zdrowotnego');

        $mail->assertSeeInHtml('Dzień dobry, Maria,', false);
        $mail->assertSeeInHtml('Twarz');
        $mail->assertSeeInText('Dzień dobry, Maria,');
    }

    public function test_the_name_cannot_inject_markup_into_the_message(): void
    {
        $mail = new ContactConfirmation($this->contact(['name' => '<b>Hej</b> <a href="https://zlosliwa.example">kliknij</a>']));

        $mail->assertDontSeeInHtml('href="https://zlosliwa.example"', false);
        $mail->assertSeeInHtml('&lt;b&gt;Hej&lt;/b&gt;', false);
    }

    public function test_the_template_carries_the_clinic_data_and_says_what_to_do_when_it_was_not_you(): void
    {
        $mail = new ContactConfirmation($this->contact());
        $html = $mail->render();

        $mail->assertSeeInHtml(config('clinic.booking_url'));
        $mail->assertSeeInHtml('tel:'.config('clinic.phone_e164'), false);
        $mail->assertSeeInHtml(url('/polityka-prywatnosci'));
        $mail->assertSeeInHtml('Jeśli to nie Ty');
        $mail->assertSeeInText(url('/polityka-prywatnosci'));
        $mail->assertSeeInText(config('clinic.phone'));

        // Do klientki nie może trafić pole „do uzupełnienia".
        $this->assertSame([], Page::placeholdersIn($html));
        $this->assertSame([], Page::placeholdersIn(view('mail.contact-confirmation-text', ['contact' => $this->contact()])->render()));
    }

    public function test_the_signet_is_embedded_in_the_message_and_inlined_in_the_preview(): void
    {
        $this->assertFileExists(resource_path('mail/signet.png'));

        // Prawdziwa wysyłka (transport testowy): obrazek jest załącznikiem inline, a w treści odwołaniem cid:.
        config(['mail.default' => 'array']);
        Mail::to('maria@example.com')->send(new ContactConfirmation($this->contact()));

        $email = Mail::mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
        $this->assertMatchesRegularExpression('~<img src="cid:~', $email->getHtmlBody());
        $this->assertDoesNotMatchRegularExpression('~<img src="https?://~', $email->getHtmlBody(), 'Obrazki nie mogą być pobierane z internetu.');
        $this->assertCount(1, $email->getAttachments());
        $this->assertSame('image/png', $email->getAttachments()[0]->getMediaType().'/'.$email->getAttachments()[0]->getMediaSubtype());
        // Wersja tekstowa dla klientów poczty bez HTML i dla filtrów antyspamowych.
        $this->assertStringContainsString('Dziękujemy za zgłoszenie', $email->getTextBody());

        // Podgląd w przeglądarce nie ma załączników, więc obrazek jest wpisany w stronę.
        $this->assertStringContainsString('<img src="data:image/png;base64,', (new ContactConfirmation($this->contact(), preview: true))->render());
    }

    public function test_the_subject_area_is_optional(): void
    {
        $mail = new ContactConfirmation($this->contact(['subject' => null]));

        $mail->assertDontSeeInHtml('dotyczy obszaru');
        $mail->assertDontSeeInText('dotyczy obszaru');
    }

    public function test_the_preview_route_exists_only_locally(): void
    {
        $this->get('/_podglad/email/potwierdzenie')->assertNotFound();
    }

    public function test_the_policy_tells_people_about_the_confirmation_email(): void
    {
        $this->seed();

        $this->assertStringContainsString('automatyczne potwierdzenie', Page::where('slug', Page::PRIVACY)->firstOrFail()->body);
    }
}
