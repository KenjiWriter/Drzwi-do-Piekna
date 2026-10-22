<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'consent_confirmed' => 'boolean',
            'is_demo' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Opinie widoczne na stronie: opublikowane i albo ze zgodą pacjentki na publikację, albo przykładowe (demo).
     * Zgoda jest sprawdzana tu, nie tylko w formularzu panelu, żeby żaden zapis nie opublikował opinii bez niej.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(fn (Builder $q) => $q->where('consent_confirmed', true)->orWhere('is_demo', true))
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
