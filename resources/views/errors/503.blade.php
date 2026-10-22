@extends('errors.layout')

{{-- Pokazywany podczas wdrożenia (php artisan down). Strona sama się odświeża, więc po powrocie wyświetli się bez klikania. --}}
@section('head')
    <meta http-equiv="refresh" content="30">
@endsection
@section('title', 'Krótka przerwa techniczna')
@section('code', 'Błąd 503')
@section('heading', 'Krótka przerwa techniczna')
@section('message', 'Aktualizujemy stronę. Wrócimy za chwilę, a ta strona odświeży się sama.')
