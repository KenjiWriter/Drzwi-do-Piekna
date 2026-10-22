<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Powiadomienie dla gabinetu o nowym zapytaniu z formularza. */
class ContactRequestReceived extends Mailable
{
    public function __construct(public ContactRequest $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nowe zapytanie ze strony: '.$this->contact->name,
            replyTo: $this->contact->email ? [new Address($this->contact->email, $this->contact->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.contact-request');
    }
}
