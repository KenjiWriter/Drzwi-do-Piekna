<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/** Zdjęcie z biblioteki: kilka szerokości WebP pod `path`-{szerokość}.webp na dysku `public`. */
class Photo extends Model
{
    public const DISK = 'public';

    /** Szerokość zdjęcia domyślnego w `src`; przeglądarka i tak wybiera z `srcset`. */
    private const DEFAULT_WIDTH = 960;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['widths' => 'array'];
    }

    protected static function booted(): void
    {
        static::deleting(fn (Photo $photo) => $photo->deleteFiles());
    }

    public function categories(): HasMany
    {
        return $this->hasMany(TreatmentCategory::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /** Wpisy bloga, w których to zdjęcie jest zdjęciem głównym. */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** Wpisy bloga, w których zdjęcie jest wstawione w treść (bloki edytora). */
    public function inlinePosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Ile miejsc na stronie używa tego zdjęcia (obszary, zabiegi, zespół, wpisy bloga). Gdy lista załadowała
     * liczniki (`withCount`), korzysta z nich zamiast pytać bazę o każdy wiersz.
     */
    public function usageCount(): int
    {
        return ($this->categories_count ?? $this->categories()->count())
            + ($this->treatments_count ?? $this->treatments()->count())
            + ($this->team_members_count ?? $this->teamMembers()->count())
            + ($this->posts_count ?? $this->posts()->count())
            + ($this->inline_posts_count ?? $this->inlinePosts()->count());
    }

    public function isUsed(): bool
    {
        return $this->usageCount() > 0;
    }

    /** @return array<int, int> szerokości rosnąco */
    public function sortedWidths(): array
    {
        $widths = array_map('intval', $this->widths ?? []);
        sort($widths);

        return $widths;
    }

    public function variantPath(int $width): string
    {
        return "{$this->path}-{$width}.webp";
    }

    /** Ścieżka najmniejszego wariantu (miniatura w panelu), dostępna też jako `thumbnail_path`. */
    public function getThumbnailPathAttribute(): string
    {
        return $this->variantPath($this->sortedWidths()[0]);
    }

    /** Adres wariantu o danej szerokości (najbliższego niemniejszego, a w razie braku największego). */
    public function url(?int $width = null): string
    {
        return Storage::disk(self::DISK)->url($this->variantPath($this->closestWidth($width ?? self::DEFAULT_WIDTH)));
    }

    /**
     * Zdjęcie w tym samym kształcie, co wpis w manifeście `resources/js/images.json`
     * (src, srcset, wymiary, kolor tła na czas ładowania, alt), więc komponent Picture obsługuje oba źródła.
     *
     * @return array{src: string, srcset: string, width: int, height: int, color: ?string, alt: string}
     */
    public function toImage(): array
    {
        $default = $this->closestWidth(self::DEFAULT_WIDTH);

        return [
            'src' => $this->url($default),
            'srcset' => collect($this->sortedWidths())
                ->map(fn (int $width) => $this->url($width).' '.$width.'w')
                ->implode(', '),
            'width' => $default,
            'height' => (int) round($this->height * $default / $this->width),
            'color' => $this->color,
            'alt' => $this->alt,
        ];
    }

    public function deleteFiles(): void
    {
        Storage::disk(self::DISK)->delete(array_map(fn (int $width) => $this->variantPath($width), $this->sortedWidths()));
    }

    private function closestWidth(int $width): int
    {
        $widths = $this->sortedWidths();

        foreach ($widths as $candidate) {
            if ($candidate >= $width) {
                return $candidate;
            }
        }

        return end($widths);
    }
}
