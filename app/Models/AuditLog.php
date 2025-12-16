<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'entity_type',
        'entity_id',
        'action',
        'diff_json',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'diff_json' => 'array',
    ];
}
