<?php

namespace App\Support;

use App\Exceptions\InvalidPhoto;
use App\Models\Photo;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Przyjmuje plik (JPEG, PNG lub WebP) i zapisuje go w bibliotece jako kilka szerokości WebP.
 *
 * Każdy wariant powstaje przez ponowne kodowanie, więc znikają metadane (EXIF, w tym współrzędne GPS),
 * a obrót z EXIF jest wypalony w pikselach. Zdjęć się nie powiększa: wariantów jest tyle, ile mieści oryginał.
 */
class PhotoStore
{
    /** Szerokości wariantów, w pikselach. Największy z nich to też limit szerokości w serwisie. */
    public const WIDTHS = [480, 960, 1440, 1920];

    public const MIN_WIDTH = 480;

    public const MAX_SIDE = 6500;

    public const QUALITY = 82;

    private const TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    /** @throws InvalidPhoto */
    public function store(string $file, string $title, string $alt, ?string $originalName = null): Photo
    {
        $image = $this->open($file);
        $width = imagesx($image);
        $height = imagesy($image);

        $widths = $this->variantWidths($width);
        $base = 'photos/'.now()->format('Y/m').'/'.Str::lower((string) Str::ulid());
        $disk = Storage::disk(Photo::DISK);
        $written = [];

        try {
            foreach ($widths as $target) {
                $disk->put("{$base}-{$target}.webp", $this->encode($image, $width, $height, $target));
                $written[] = "{$base}-{$target}.webp";
            }

            return Photo::create([
                'title' => $title,
                'alt' => $alt,
                'path' => $base,
                'widths' => $widths,
                'width' => $width,
                'height' => $height,
                'color' => $this->dominantColor($image, $width, $height),
                'original_name' => $originalName,
            ]);
        } catch (Throwable $e) {
            $disk->delete($written);

            throw $e;
        }
    }

    /** @return array<int, int> */
    public function variantWidths(int $originalWidth): array
    {
        $widths = array_values(array_filter(self::WIDTHS, fn (int $w) => $w < $originalWidth));
        $widths[] = min($originalWidth, max(self::WIDTHS));

        return array_values(array_unique($widths));
    }

    private function open(string $file): GdImage
    {
        $info = is_file($file) ? @getimagesize($file) : false;

        if (! $info || ! in_array($info[2], self::TYPES, true)) {
            throw new InvalidPhoto('Plik nie jest zdjęciem. Wgraj JPEG, PNG lub WebP.');
        }

        [$width, $height] = $info;

        if ($width < self::MIN_WIDTH) {
            throw new InvalidPhoto('Zdjęcie jest za małe: ma mieć co najmniej '.self::MIN_WIDTH.' px szerokości.');
        }

        if ($width > self::MAX_SIDE || $height > self::MAX_SIDE) {
            throw new InvalidPhoto('Zdjęcie jest za duże: najdłuższy bok to najwyżej '.self::MAX_SIDE.' px.');
        }

        $this->ensureMemory($width, $height);

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
            IMAGETYPE_PNG => @imagecreatefrompng($file),
            default => @imagecreatefromwebp($file),
        };

        if (! $image) {
            throw new InvalidPhoto('Nie udało się odczytać zdjęcia. Plik może być uszkodzony.');
        }

        imagepalettetotruecolor($image);

        return $info[2] === IMAGETYPE_JPEG ? $this->applyOrientation($image, $file) : $image;
    }

    /** Dekodowanie 24-megapikselowego zdjęcia potrzebuje około 100 MB, a domyślny limit PHP bywa niższy. */
    private function ensureMemory(int $width, int $height): void
    {
        $needed = memory_get_usage() + $width * $height * 4 * 3 + 64 * 1024 * 1024;
        $limit = ini_parse_quantity((string) ini_get('memory_limit'));

        if ($limit > 0 && $limit < $needed) {
            @ini_set('memory_limit', (string) $needed);

            if (ini_parse_quantity((string) ini_get('memory_limit')) < $needed) {
                throw new InvalidPhoto('Serwer nie ma dość pamięci na to zdjęcie. Zmniejsz je i wgraj jeszcze raz.');
            }
        }
    }

    private function applyOrientation(GdImage $image, string $file): GdImage
    {
        $orientation = function_exists('exif_read_data') ? (@exif_read_data($file)['Orientation'] ?? 1) : 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        return $angle ? (imagerotate($image, $angle, 0) ?: $image) : $image;
    }

    private function encode(GdImage $source, int $width, int $height, int $target): string
    {
        $targetHeight = max(1, (int) round($height * $target / $width));

        $canvas = imagecreatetruecolor($target, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $target, $targetHeight, $width, $height);

        ob_start();
        imagewebp($canvas, null, self::QUALITY);

        return (string) ob_get_clean();
    }

    /** Średni kolor zdjęcia: tło na czas ładowania, żeby układ nie migał. */
    private function dominantColor(GdImage $image, int $width, int $height): string
    {
        $pixel = imagecreatetruecolor(1, 1);
        imagecopyresampled($pixel, $image, 0, 0, 0, 0, 1, 1, $width, $height);
        $rgb = imagecolorat($pixel, 0, 0);

        return sprintf('rgb(%d, %d, %d)', ($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255);
    }
}
