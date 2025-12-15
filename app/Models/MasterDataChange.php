<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDataChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'requirement_id',
        'object_name',
        'field_name',
        'change_type',
        'data_type',
        'description',
        'implemented_by',
        'implemented_at',
        'version_tag',
    ];

    protected $casts = [
        'implemented_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }
}
