<?php

namespace App\Models;

use App\Support\PostContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    /** Średnie tempo czytania po polsku, w słowach na minutę. */
    private const WORDS_PER_MINUTE = 200;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Zdjęcia użyte w blokach treści zapisujemy osobno, żeby biblioteka wiedziała, które są w użyciu.
        // Identyfikatory zdjęć, których już nie ma w bibliotece, pomijamy (klucz obcy).
        static::saved(fn (Post $post) => $post->photos()->sync(Photo::whereKey(PostContent::photoIds($post->body))->pluck('id')->all()));
    }

    /** Zdjęcie główne wpisu (lista, nagłówek, podgląd linku). */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    /** Zdjęcia wstawione w treść wpisu przez bloki edytora. */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class);
    }

    /** Opublikowane i już po dacie publikacji (można więc zaplanować wpis na później), najnowsze pierwsze. */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function url(): string
    {
        return '/blog/'.$this->slug;
    }

    public function html(): string
    {
        return PostContent::toHtml($this->body);
    }

    public function readingMinutes(): int
    {
        preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($this->html()), $words);

        return max(1, (int) ceil(count($words[0]) / self::WORDS_PER_MINUTE));
    }
}
