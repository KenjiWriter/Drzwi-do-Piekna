<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TreatmentSeeder::class,
            PriceListSeeder::class,
            TeamSeeder::class,
            PageSeeder::class,
            PostSeeder::class,
            TestimonialSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
