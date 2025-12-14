<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailArtifact extends Model
{
    protected $fillable = [
    'project_id',
    'type',
    'tone',
    'input_json',
    'subject',
    'body_text'
];


protected $casts = [
    'input_json' => 'array',
];
public function project()
{
    return $this->belongsTo(\App\Models\Project::class);
}

}
