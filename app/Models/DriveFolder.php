<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriveFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'phase_key',
        'drive_folder_id',
        'drive_web_view_link',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
