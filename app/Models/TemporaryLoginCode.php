<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TemporaryLoginCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'state',
        'temp_code',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime'
    ];
}
