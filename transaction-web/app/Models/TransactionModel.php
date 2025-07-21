<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TransactionModel extends Model
{
    // Define fillable attributes to match your transaction data
    protected $fillable = [
        'id',
        'created_at',
        'type',
        'description',
        'amount',
        'status',
        'reference',
        'fee',
        'currency',
        'metadata',
    ];

    // Disable timestamps since this model doesn't use a database table
    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'metadata' => 'array',
    ];
}
