<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'req_code',
        'title',
        'description',
        'source_type',
        'source_ref_type',
        'source_ref_id',
        'priority',
        'status',
        'is_change_request',
        'approved_at',
        'delivered_at',
    ];

    protected $casts = [
        'is_change_request' => 'boolean',
        'approved_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function versions()
    {
        return $this->hasMany(RequirementVersion::class);
    }

    public function assignments()
    {
        return $this->hasMany(RequirementAssignment::class);
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class);
    }

    public function testCases()
    {
        return $this->hasMany(TestCase::class);
    }

    public function masterDataChanges()
    {
        return $this->hasMany(MasterDataChange::class);
    }
}
