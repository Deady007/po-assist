<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriveFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'phase_key',
        'entity_type',
        'entity_id',
        'file_name',
        'mime_type',
        'drive_file_id',
        'drive_folder_id',
        'web_view_link',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
