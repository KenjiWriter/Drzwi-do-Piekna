<?php

namespace Tests\Feature;

use App\Mail\ContactRequestReceived;
use App\Models\ContactRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /** @return array<string, mixed> */
    private function valid(array $override = []): array
    {
        return array_merge([
            'name' => 'Maria',
            'phone' => '797 745 224',
            'email' => 'maria@example.com',
            'subject' => 'Twarz',
            'message' => 'Chcę umówić konsultację.',
            'consent' => true,
            'website' => '',
        ], $override);
    }

    public function test_contact_page_lists_the_treatment_areas(): void
    {
        $this->get('/kontakt')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contact')
                ->has('areas', 5)
                ->where('areas.0.name', 'Twarz'));
    }

    public function test_valid_request_is_saved_and_the_clinic_is_notified(): void
    {
        Mail::fake();

        $this->post('/kontakt', $this->valid())
            ->assertRedirect('/kontakt')
            ->assertSessionHas('success');

        $contact = ContactRequest::firstOrFail();
        $this->assertSame('Maria', $contact->name);
        $this->assertSame('797 745 224', $contact->phone);
        $this->assertNotNull($contact->consent_at);
        $this->assertNotNull($contact->notified_at);

        Mail::assertSent(ContactRequestReceived::class, fn ($mail) => $mail->hasTo('gabinet@drzwidopiekna.pl') && $mail->hasReplyTo('maria@example.com'));
    }

    public function test_the_notification_goes_to_the_configured_address_when_set(): void
    {
        Mail::fake();
        config(['clinic.notify_email' => 'test@example.com']);

        $this->post('/kontakt', $this->valid())->assertRedirect('/kontakt');

        Mail::assertSent(ContactRequestReceived::class, fn ($mail) => $mail->hasTo('test@example.com') && ! $mail->hasTo('gabinet@drzwidopiekna.pl'));
    }

    public function test_the_request_survives_a_mail_failure(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->post('/kontakt', $this->valid())->assertRedirect('/kontakt')->assertSessionHas('success');

        $this->assertNull(ContactRequest::firstOrFail()->notified_at);
    }

    public function test_invalid_requests_return_polish_errors(): void
    {
        $this->from('/kontakt')
            ->post('/kontakt', ['name' => '', 'phone' => '12', 'email' => 'nie-email', 'consent' => false])
            ->assertRedirect('/kontakt')
            ->assertSessionHasErrors(['name', 'phone', 'email', 'consent']);

        $this->assertSame(0, ContactRequest::count());
        $this->assertStringContainsString('Wpisz pełny numer telefonu', session('errors')->first('phone'));
    }

    public function test_email_is_optional(): void
    {
        Mail::fake();

        $this->post('/kontakt', $this->valid(['email' => '', 'message' => '']))->assertSessionDoesntHaveErrors();

        $this->assertSame(1, ContactRequest::count());
    }

    public function test_honeypot_hides_the_failure_from_bots_and_saves_nothing(): void
    {
        Mail::fake();

        $this->post('/kontakt', $this->valid(['website' => 'http://spam.example']))
            ->assertRedirect('/kontakt')
            ->assertSessionHas('success');

        $this->assertSame(0, ContactRequest::count());
        Mail::assertNothingSent();
    }

    public function test_the_form_is_rate_limited(): void
    {
        Mail::fake();

        foreach (range(1, 5) as $i) {
            $this->post('/kontakt', $this->valid())->assertRedirect('/kontakt');
        }

        $this->post('/kontakt', $this->valid())->assertStatus(429);
        $this->assertSame(5, ContactRequest::count());
    }
}
