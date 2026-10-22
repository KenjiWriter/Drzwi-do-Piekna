<?php

namespace App\Models;

use App\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
            'consent_at' => 'datetime',
            'notified_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
        ];
    }
}
