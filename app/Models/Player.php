<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPlayer
 */
class Player extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'overall',
        'position',
        'nationality',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'overall' => 'integer',
        ];
    }
}
