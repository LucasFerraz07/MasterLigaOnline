<?php

namespace App\Models;

use App\Enums\ClubRegion;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'crest',
        'region',
    ];

    protected function casts(): array
    {
        return [
            'region' => ClubRegion::class,
        ];
    }
}
