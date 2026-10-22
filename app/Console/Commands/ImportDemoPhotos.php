<?php

namespace App\Console\Commands;

use App\Exceptions\InvalidPhoto;
use App\Models\Photo;
use App\Models\TreatmentCategory;
use App\Support\PhotoStore;
use Illuminate\Console\Command;

/**
 * Wgrywa do biblioteki zdjęcia demonstracyjne, które strona pokazuje dziś z potoku `npm run images`,
 * i przypisuje je obszarom. Dzięki temu redaktorka widzi je w panelu i może podmienić na własne.
 */
class ImportDemoPhotos extends Command
{
    protected $signature = 'photos:import-demo';

    protected $description = 'Wgrywa zdjęcia demonstracyjne obszarów do biblioteki zdjęć';

    public function handle(PhotoStore $store): int
    {
        $manifestFile = resource_path('js/images.json');
        $manifest = is_file($manifestFile) ? (json_decode((string) file_get_contents($manifestFile), true) ?: []) : [];

        $categories = TreatmentCategory::whereNull('photo_id')->whereNotNull('image_key')->get();

        if ($categories->isEmpty()) {
            $this->info('Wszystkie obszary mają już zdjęcie z biblioteki albo wskazują zdjęcie demonstracyjne, którego nie ma.');

            return self::SUCCESS;
        }

        foreach ($categories as $category) {
            $key = $category->image_key;
            $entry = $manifest[$key] ?? null;

            if (! $entry) {
                $this->warn("Obszar „{$category->name}”: brak zdjęcia „{$key}” w manifeście.");

                continue;
            }

            $photo = Photo::firstWhere('original_name', "demo:{$key}") ?? $this->import($store, $key, $entry, $category->name);

            if ($photo) {
                $category->update(['photo_id' => $photo->id]);
                $this->line("Obszar „{$category->name}”: zdjęcie „{$photo->title}”.");
            }
        }

        return self::SUCCESS;
    }

    /** @param  array<string, mixed>  $entry */
    private function import(PhotoStore $store, string $key, array $entry, string $area): ?Photo
    {
        // Ostatni element srcset to najszerszy wariant.
        $largest = trim((string) collect(explode(',', $entry['srcset']))->last());
        $file = public_path(ltrim(explode(' ', $largest)[0], '/'));

        if (! is_file($file)) {
            $this->warn("Brak pliku {$file}. Uruchom najpierw `npm run images`.");

            return null;
        }

        try {
            return $store->store($file, "Demo: {$area}", $entry['alt'], "demo:{$key}");
        } catch (InvalidPhoto $e) {
            $this->warn("Obszar „{$area}”: {$e->getMessage()}");

            return null;
        }
    }
}
