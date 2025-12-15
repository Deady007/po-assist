<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiPrompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_key',
        'version',
        'system_instructions',
        'output_schema_json',
        'few_shot_examples_json',
        'is_active',
    ];

    protected $casts = [
        'output_schema_json' => 'array',
        'few_shot_examples_json' => 'array',
        'is_active' => 'boolean',
    ];
}
