<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;

class SequenceConfig extends Model
{
    use Blameable;

    protected $fillable = [
        'model_name',
        'prefix',
        'padding',
        'start_from',
        'current_value',
        'reset_policy',
        'format_template',
        'last_reset_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'padding' => 'integer',
        'start_from' => 'integer',
        'current_value' => 'integer',
        'last_reset_at' => 'datetime',
    ];
}
