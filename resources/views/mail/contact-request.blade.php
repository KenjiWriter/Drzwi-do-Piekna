Nowe zapytanie ze strony drzwidopiekna.pl

Imię: {{ $contact->name }}
Telefon: {{ $contact->phone }}
E-mail: {{ $contact->email ?: 'nie podano' }}
Obszar: {{ $contact->subject ?: 'nie wybrano' }}

Wiadomość:
{{ $contact->message ?: '(brak)' }}

Zgoda na kontakt: {{ $contact->consent_at->format('d.m.Y H:i') }}
Numer zgłoszenia: {{ $contact->id }}
