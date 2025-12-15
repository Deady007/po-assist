<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bug extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'requirement_id',
        'title',
        'description',
        'severity',
        'status',
        'reported_by',
        'assigned_to_developer_id',
        'resolved_by_developer_id',
        'opened_at',
        'fixed_at',
        'closed_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'fixed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(Developer::class, 'assigned_to_developer_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(Developer::class, 'resolved_by_developer_id');
    }
}
