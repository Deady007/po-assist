<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    protected $fillable = [
        'name',
        'code',
        'scope_type',
        'scope_id',
        'description',
        'created_by',
        'updated_by',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'scope_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'scope_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }
}
