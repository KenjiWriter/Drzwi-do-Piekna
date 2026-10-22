@php($clinic = config('clinic'))
Dziękujemy za zgłoszenie

Dzień dobry, {{ $contact->name }},

dziękujemy za wiadomość wysłaną przez formularz na naszej stronie. Otrzymaliśmy ją i odezwiemy się do Ciebie wkrótce, najczęściej telefonicznie, na numer podany w formularzu.
@if (filled($contact->subject))

Twoje zgłoszenie dotyczy obszaru: {{ $contact->subject }}.
@endif

CO DALEJ
1. Skontaktujemy się z Tobą w godzinach pracy gabinetu.
2. Porozmawiamy o Twoich potrzebach i ustalimy termin konsultacji.
3. Podczas konsultacji dobierzemy rozwiązania dopasowane do Ciebie.

Wolisz nie czekać? Termin możesz też zarezerwować samodzielnie, online:
{{ $clinic['booking_url'] }}

Prosimy, nie przesyłaj w odpowiedzi na tę wiadomość informacji o stanie zdrowia. O sprawach medycznych porozmawiamy podczas konsultacji w gabinecie.

Do zobaczenia w gabinecie,
Zespół {{ $clinic['name'] }}

--
{{ $clinic['name'] }}
ul. {{ $clinic['address']['street'] }}, {{ $clinic['address']['postal_code'] }} {{ $clinic['address']['city'] }}
{{ $clinic['phone'] }} | {{ $clinic['email'] }}

To automatyczna wiadomość: ktoś wypełnił formularz kontaktowy na stronie {{ parse_url(url('/'), PHP_URL_HOST) }}, podając ten adres e-mail. Jeśli to nie Ty, zignoruj ją albo napisz do nas, a usuniemy zgłoszenie. Informacje o przetwarzaniu danych: {{ url($clinic['legal']['privacy']) }}
