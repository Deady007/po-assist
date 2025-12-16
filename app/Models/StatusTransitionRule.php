<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusTransitionRule extends Model
{
    protected $fillable = [
        'from_status_id',
        'to_status_id',
        'allowed_role_ids',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'allowed_role_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'to_status_id');
    }
}
