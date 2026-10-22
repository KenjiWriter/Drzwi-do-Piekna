<?php

namespace App\Http\Controllers;

use App\Mail\ContactConfirmation;
use App\Models\ContactRequest;

/**
 * Podgląd szablonów e-mail w przeglądarce, do pracy nad ich wyglądem. Trasa jest rejestrowana tylko lokalnie
 * (routes/web.php), więc na produkcji nie istnieje.
 */
class MailPreviewController extends Controller
{
    public function confirmation(): string
    {
        $sample = new ContactRequest(['name' => 'Anna', 'email' => 'anna@example.com', 'subject' => 'Twarz']);

        return (new ContactConfirmation($sample, preview: true))->render();
    }
}
