<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfpDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'source_text',
        'extracted_json',
        'drive_file_id',
        'drive_web_view_link',
    ];

    protected $casts = [
        'extracted_json' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
