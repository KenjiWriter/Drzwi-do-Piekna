<?php

namespace Tests\Feature;

use App\Exceptions\InvalidPhoto;
use App\Models\Photo;
use App\Support\PhotoStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoStoreTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $temp = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Photo::DISK);
    }

    protected function tearDown(): void
    {
        array_map('unlink', array_filter($this->temp, 'is_file'));

        parent::tearDown();
    }

    /** Prawdziwy JPEG o jednolitym kolorze; opcjonalnie z orientacją EXIF. */
    private function jpeg(int $width, int $height, ?int $orientation = null): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 100, 50));

        ob_start();
        imagejpeg($image, null, 90);
        $bytes = (string) ob_get_clean();

        if ($orientation) {
            // APP1 z minimalnym nagłówkiem TIFF (little endian) i jednym tagiem 0x0112 (Orientation), wstawiony zaraz po SOI.
            $tiff = "II*\x00\x08\x00\x00\x00".pack('v', 1).pack('vvVv', 0x0112, 3, 1, $orientation)."\x00\x00".pack('V', 0);
            $payload = "Exif\x00\x00".$tiff;
            $bytes = substr($bytes, 0, 2)."\xFF\xE1".pack('n', strlen($payload) + 2).$payload.substr($bytes, 2);
        }

        return $this->temp[] = tap(tempnam(sys_get_temp_dir(), 'ddp').'.jpg', fn (string $path) => file_put_contents($path, $bytes));
    }

    public function test_a_large_photo_gets_every_variant_as_webp(): void
    {
        $photo = app(PhotoStore::class)->store($this->jpeg(3000, 2000), 'Gabinet', 'Wnętrze gabinetu', 'gabinet.jpg');

        $this->assertSame([480, 960, 1440, 1920], $photo->sortedWidths());
        $this->assertSame([3000, 2000], [$photo->width, $photo->height]);
        $this->assertMatchesRegularExpression('/^rgb\(\d+, \d+, \d+\)$/', $photo->color);

        foreach ($photo->sortedWidths() as $width) {
            $bytes = Storage::disk(Photo::DISK)->get($photo->variantPath($width));
            $info = getimagesizefromstring($bytes);

            $this->assertSame(IMAGETYPE_WEBP, $info[2]);
            $this->assertSame($width, $info[0]);
            $this->assertSame((int) round(2000 * $width / 3000), $info[1]);
        }
    }

    public function test_photos_are_never_enlarged(): void
    {
        $store = app(PhotoStore::class);

        $this->assertSame([480, 960, 1000], $store->store($this->jpeg(1000, 600), 'A', 'a')->sortedWidths());
        $this->assertSame([480], $store->store($this->jpeg(480, 480), 'B', 'b')->sortedWidths());
    }

    public function test_the_page_gets_srcset_and_sizes_like_the_demo_manifest(): void
    {
        $image = app(PhotoStore::class)->store($this->jpeg(3000, 2000), 'Gabinet', 'Wnętrze gabinetu')->toImage();

        $this->assertSame(['src', 'srcset', 'width', 'height', 'color', 'alt'], array_keys($image));
        $this->assertSame(960, $image['width']);
        $this->assertSame(640, $image['height']);
        $this->assertStringContainsString('-480.webp 480w, ', $image['srcset']);
        $this->assertStringContainsString('-1920.webp 1920w', $image['srcset']);
        $this->assertStringStartsWith('/storage/photos/', $image['src']);
        $this->assertSame('Wnętrze gabinetu', $image['alt']);
    }

    public function test_exif_rotation_is_baked_into_the_pixels(): void
    {
        // Poziomy plik z orientacją 6 to pionowe zdjęcie zrobione telefonem.
        $photo = app(PhotoStore::class)->store($this->jpeg(800, 500, orientation: 6), 'Portret', 'Portret');

        $this->assertSame([500, 800], [$photo->width, $photo->height]);
    }

    public function test_metadata_does_not_survive_the_conversion(): void
    {
        $photo = app(PhotoStore::class)->store($this->jpeg(800, 500, orientation: 6), 'Portret', 'Portret');
        $bytes = Storage::disk(Photo::DISK)->get($photo->variantPath(480));

        $this->assertStringNotContainsString('Exif', $bytes);
    }

    public function test_files_that_are_not_usable_photos_are_rejected(): void
    {
        $store = app(PhotoStore::class);
        $text = tap(tempnam(sys_get_temp_dir(), 'ddp'), fn ($p) => file_put_contents($p, 'to nie jest zdjęcie'));
        $this->temp[] = $text;

        foreach ([
            'Plik nie jest zdjęciem' => $text,
            'za małe' => $this->jpeg(300, 300),
            'za duże' => $this->jpeg(PhotoStore::MAX_SIDE + 1, 10),
        ] as $message => $file) {
            try {
                $store->store($file, 'X', 'x');
                $this->fail("Plik powinien zostać odrzucony: {$message}");
            } catch (InvalidPhoto $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }

        $this->assertSame(0, Photo::count());
        $this->assertSame([], Storage::disk(Photo::DISK)->allFiles());
    }

    public function test_deleting_a_photo_removes_its_files(): void
    {
        $photo = app(PhotoStore::class)->store($this->jpeg(1000, 600), 'A', 'a');
        $this->assertCount(3, Storage::disk(Photo::DISK)->allFiles());

        $photo->delete();

        $this->assertSame([], Storage::disk(Photo::DISK)->allFiles());
    }

    public function test_usage_is_counted_across_categories_treatments_and_team(): void
    {
        $this->seed();
        $photo = app(PhotoStore::class)->store($this->jpeg(1000, 600), 'A', 'a');
        $this->assertFalse($photo->isUsed());

        \App\Models\TreatmentCategory::first()->update(['photo_id' => $photo->id]);
        \App\Models\Treatment::first()->update(['photo_id' => $photo->id]);

        $this->assertTrue($photo->isUsed());
        $this->assertSame(2, $photo->usageCount());
    }
}
