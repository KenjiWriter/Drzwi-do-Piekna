@php
    $clinic = config('clinic');
    $privacyUrl = url($clinic['legal']['privacy']);
    $siteUrl = url('/');
    $signet = resource_path('mail/signet.png');
    // W wiadomości obrazek jest osadzony (cid:), więc nie zależy od pobierania zdjęć z internetu. W podglądzie w przeglądarce data URI.
    $signetSrc = is_file($signet)
        ? ($preview ? 'data:image/png;base64,'.base64_encode(file_get_contents($signet)) : $message->embed($signet))
        : null;
    $serif = "Georgia, 'Times New Roman', serif";
    $sans = "-apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp
<!DOCTYPE html>
<html lang="pl" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>Dziękujemy za zgłoszenie</title>
    <style>
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { color: #5C1F2E; }
        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; }
            .pad { padding-left: 24px !important; padding-right: 24px !important; }
            .h1 { font-size: 28px !important; line-height: 34px !important; }
            .btn a { display: block !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#FAF6F1;">
    {{-- Podgląd w skrzynce odbiorczej (preheader), niewidoczny w treści. --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#FAF6F1;font-size:1px;line-height:1px;">
        Otrzymaliśmy Twoją wiadomość i wkrótce się odezwiemy.&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FAF6F1;">
        <tr>
            <td align="center" style="padding:32px 16px 40px;">
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;">

                    {{-- Nagłówek: znak i nazwa marki --}}
                    <tr>
                        <td align="center" style="padding:8px 0 28px;">
                            @if ($signetSrc)
                                <img src="{{ $signetSrc }}" width="48" height="60" alt="" style="display:block;margin:0 auto 14px;width:48px;height:60px;">
                            @endif
                            <div style="font-family:{{ $serif }};font-size:26px;line-height:30px;color:#5C1F2E;">{{ $clinic['name'] }}</div>
                            <div style="padding-top:8px;font-family:{{ $sans }};font-size:11px;line-height:14px;letter-spacing:4px;text-transform:uppercase;color:#6B5F62;">{{ $clinic['tagline'] }}</div>
                        </td>
                    </tr>

                    {{-- Karta z treścią --}}
                    <tr>
                        <td style="background-color:#FFFFFF;border:1px solid #E6DDD3;border-top:3px solid #5C1F2E;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="pad" style="padding:44px 48px 8px;">
                                        <h1 class="h1" style="margin:0 0 24px;font-family:{{ $serif }};font-weight:400;font-size:34px;line-height:40px;color:#5C1F2E;">Dziękujemy za zgłoszenie</h1>
                                        <p style="margin:0 0 18px;font-family:{{ $sans }};font-size:16px;line-height:26px;color:#3A2E31;">Dzień dobry, {{ $contact->name }},</p>
                                        <p style="margin:0 0 18px;font-family:{{ $sans }};font-size:16px;line-height:26px;color:#3A2E31;">
                                            dziękujemy za wiadomość wysłaną przez formularz na naszej stronie. Otrzymaliśmy ją i odezwiemy się do Ciebie wkrótce, najczęściej telefonicznie, na numer podany w formularzu.
                                        </p>
                                        @if (filled($contact->subject))
                                            <p style="margin:0 0 18px;font-family:{{ $sans }};font-size:16px;line-height:26px;color:#3A2E31;">Twoje zgłoszenie dotyczy obszaru: <strong style="font-weight:600;color:#5C1F2E;">{{ $contact->subject }}</strong>.</p>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Co dalej --}}
                                <tr>
                                    <td class="pad" style="padding:16px 48px 8px;">
                                        <div style="padding-bottom:14px;border-bottom:1px solid #E6DDD3;font-family:{{ $sans }};font-size:12px;line-height:16px;letter-spacing:3px;text-transform:uppercase;color:#6B5F62;">Co dalej</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            @foreach (['Skontaktujemy się z Tobą w godzinach pracy gabinetu.', 'Porozmawiamy o Twoich potrzebach i ustalimy termin konsultacji.', 'Podczas konsultacji dobierzemy rozwiązania dopasowane do Ciebie.'] as $i => $step)
                                                <tr>
                                                    <td width="40" valign="top" style="padding:18px 0 0;font-family:{{ $serif }};font-size:24px;line-height:26px;color:#5C1F2E;">{{ $i + 1 }}</td>
                                                    <td valign="top" style="padding:18px 0 0;font-family:{{ $sans }};font-size:16px;line-height:26px;color:#3A2E31;">{{ $step }}</td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>

                                {{-- Rezerwacja online --}}
                                <tr>
                                    <td class="pad" style="padding:32px 48px 8px;">
                                        <p style="margin:0 0 20px;font-family:{{ $sans }};font-size:16px;line-height:26px;color:#3A2E31;">Wolisz nie czekać? Termin możesz też zarezerwować samodzielnie, online.</p>
                                        <table role="presentation" class="btn" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" bgcolor="#5C1F2E" style="background-color:#5C1F2E;">
                                                    <a href="{{ $clinic['booking_url'] }}" target="_blank" style="display:inline-block;padding:16px 32px;font-family:{{ $sans }};font-size:13px;line-height:16px;font-weight:600;letter-spacing:2px;text-transform:uppercase;text-decoration:none;color:#FFFFFF;">Zarezerwuj termin online</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                {{-- Prośba o niepodawanie danych o zdrowiu i podpis --}}
                                <tr>
                                    <td class="pad" style="padding:32px 48px 44px;">
                                        <p style="margin:0 0 24px;font-family:{{ $sans }};font-size:14px;line-height:22px;color:#6B5F62;">Prosimy, nie przesyłaj w odpowiedzi na tę wiadomość informacji o stanie zdrowia. O sprawach medycznych porozmawiamy podczas konsultacji w gabinecie.</p>
                                        <p style="margin:0;font-family:{{ $serif }};font-size:20px;line-height:28px;color:#5C1F2E;">Do zobaczenia w gabinecie,<br>Zespół {{ $clinic['name'] }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Stopka: dane gabinetu i informacja, skąd ta wiadomość --}}
                    <tr>
                        <td align="center" class="pad" style="padding:32px 24px 0;font-family:{{ $sans }};font-size:13px;line-height:21px;color:#6B5F62;">
                            <div style="color:#3A2E31;">{{ $clinic['name'] }}<br>ul. {{ $clinic['address']['street'] }}, {{ $clinic['address']['postal_code'] }} {{ $clinic['address']['city'] }}</div>
                            <div style="padding-top:6px;">
                                <a href="tel:{{ $clinic['phone_e164'] }}" style="color:#5C1F2E;text-decoration:none;">{{ $clinic['phone'] }}</a>
                                &nbsp;·&nbsp;
                                <a href="mailto:{{ $clinic['email'] }}" style="color:#5C1F2E;text-decoration:none;">{{ $clinic['email'] }}</a>
                            </div>
                            <div style="padding-top:20px;font-size:12px;line-height:19px;color:#8C7B74;">
                                To automatyczna wiadomość: ktoś wypełnił formularz kontaktowy na stronie <a href="{{ $siteUrl }}" style="color:#8C7B74;">{{ parse_url($siteUrl, PHP_URL_HOST) }}</a>, podając ten adres e-mail. Jeśli to nie Ty, zignoruj ją albo napisz do nas, a usuniemy zgłoszenie. Informacje o przetwarzaniu danych znajdziesz w <a href="{{ $privacyUrl }}" style="color:#8C7B74;">polityce prywatności</a>.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
