<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-()]{9,20}$/'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:150'],
            'subject' => ['nullable', 'string', 'max:60'],
            'message' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            // Honeypot: prawdziwa osoba nie widzi tego pola, więc zostaje puste.
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Podaj imię, żebyśmy wiedzieli, jak się do Ciebie zwracać.',
            'name.max' => 'Imię może mieć najwyżej 100 znaków.',
            'phone.required' => 'Podaj numer telefonu, pod którym możemy oddzwonić.',
            'phone.regex' => 'Wpisz pełny numer telefonu, np. 797 745 224.',
            'email.email' => 'Wpisz poprawny adres e-mail albo zostaw to pole puste.',
            'message.max' => 'Wiadomość może mieć najwyżej 2000 znaków.',
            'consent.accepted' => 'Zaznacz zgodę na przetwarzanie danych, żebyśmy mogli odpowiedzieć na zapytanie.',
        ];
    }
}
