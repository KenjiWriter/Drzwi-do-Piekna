<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Opinia przykładowa z wizualizacji klienta, żeby pokazać układ sekcji. Tylko poza produkcją:
 * na produkcji sekcja opinii pojawia się dopiero po dodaniu prawdziwej opinii za zgodą pacjentki.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        Testimonial::firstOrCreate(
            ['author' => 'Anna', 'is_demo' => true],
            [
                'quote' => 'Profesjonalizm, ogromna wiedza i cudowne podejście do pacjenta. Efekty są naturalne, dokładnie takie, na jakich mi zależało.',
                'is_published' => true,
                'sort_order' => 1,
            ],
        );
    }
}
