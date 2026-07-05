<?php

namespace App\Models;

use App\Enums\TransactionOperation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperTransactionType
 */
class TransactionType extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'operation',
    ];

    protected function casts(): array
    {
        return [
            'operation' => TransactionOperation::class,
        ];
    }
}
