<?php

namespace App\Models;

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
        'name_label',
        'operation',
    ];
}
