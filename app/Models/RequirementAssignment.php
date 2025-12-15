<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequirementAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'requirement_id',
        'developer_id',
        'assigned_at',
        'status',
        'eta_date',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'eta_date' => 'date',
    ];

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }

    public function developer()
    {
        return $this->belongsTo(Developer::class);
    }
}
