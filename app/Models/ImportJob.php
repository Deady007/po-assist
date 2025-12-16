<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    protected $fillable = [
        'model_name',
        'file_name',
        'uploaded_by',
        'status',
        'total_rows',
        'error_count',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function errors(): HasMany
    {
        return $this->hasMany(ImportJobRowError::class);
    }
}
