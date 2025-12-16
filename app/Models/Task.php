<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use Blameable;

    protected $fillable = [
        'project_module_id',
        'project_id',
        'title',
        'description',
        'assignee_user_id',
        'status',
        'priority',
        'due_date',
        'blocker_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(ProjectModule::class, 'project_module_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }
}
