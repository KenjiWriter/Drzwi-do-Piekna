<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_from' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class, 'price_group_id');
    }

    /** Cena w formie czytelnej dla człowieka, np. "od 450 zł" albo "500–700 zł". */
    public function priceLabel(): string
    {
        $zl = fn (int $n) => number_format($n, 0, ',', ' ').' zł';

        if ($this->price_min === null) {
            return $this->pack_price !== null ? $zl($this->pack_price) : 'Cena po konsultacji';
        }

        if ($this->price_max !== null) {
            return number_format($this->price_min, 0, ',', ' ').'–'.$zl($this->price_max);
        }

        return ($this->is_from ? 'od ' : '').$zl($this->price_min);
    }
}
