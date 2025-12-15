<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidationReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'generated_at',
        'report_json',
        'report_html',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'report_json' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
