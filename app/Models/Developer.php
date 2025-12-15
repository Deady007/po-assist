<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Developer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
    ];

    public function assignments()
    {
        return $this->hasMany(RequirementAssignment::class);
    }

    public function assignedBugs()
    {
        return $this->hasMany(Bug::class, 'assigned_to_developer_id');
    }

    public function resolvedBugs()
    {
        return $this->hasMany(Bug::class, 'resolved_by_developer_id');
    }
}
