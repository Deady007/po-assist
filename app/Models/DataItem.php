<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'category',
        'expected_format',
        'owner',
        'due_date',
        'status',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'received_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function files()
    {
        return $this->hasMany(DataItemFile::class);
    }
}
