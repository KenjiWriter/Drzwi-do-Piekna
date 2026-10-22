<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use Illuminate\Database\Seeder;

/**
 * Zespół wg obecnej strony. Dorota Kapusta i Mikołaj Retkiewicz nie mają tam żadnego opisu ani roli,
 * więc są niepublikowani, dopóki klientka nie poda danych.
 */
class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'slug' => 'anita-symonowicz',
                'name' => 'dr Anita Symonowicz',
                'role' => 'Lekarz medycyny estetycznej, stomatolog',
                'bio' => 'Absolwentka Wydziału Lekarsko-Dentystycznego Łódzkiego Uniwersytetu Medycznego. Po studiach podyplomowych na Warszawskim Uniwersytecie Medycznym uzyskała certyfikat Lekarza Medycyny Estetycznej. Pierwszy własny gabinet otworzyła w 2014 roku w centrum Łodzi.',
                'is_published' => true,
            ],
            [
                'slug' => 'katya-aleksandrowicz',
                'name' => 'dr Katya Aleksandrowicz',
                'role' => 'Lekarz stomatolog, medycyna estetyczna',
                'bio' => 'Absolwentka Uniwersytetu Medycznego w Lublinie, wydziału lekarsko-dentystycznego. Wieloletnia praca w stomatologii rozwinęła jej zainteresowanie medycyną estetyczną. Stawia na subtelne, naturalne efekty i bezpieczeństwo pacjentów, a kwalifikacje stale podnosi na szkoleniach, warsztatach i kursach.',
                'is_published' => true,
            ],
            ['slug' => 'dorota-kapusta', 'name' => 'Dorota Kapusta', 'role' => null, 'bio' => null, 'is_published' => false],
            ['slug' => 'mikolaj-retkiewicz', 'name' => 'Mikołaj Retkiewicz', 'role' => null, 'bio' => null, 'is_published' => false],
        ];

        foreach ($members as $i => $member) {
            TeamMember::updateOrCreate(['slug' => $member['slug']], $member + ['sort_order' => $i + 1]);
        }
    }
}
