<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_key',
        'prompt_version',
        'project_id',
        'entity_type',
        'entity_id',
        'input_context_json',
        'model_name',
        'temperature',
        'prompt_tokens',
        'output_tokens',
        'latency_ms',
        'success',
        'error_message',
        'raw_output_text',
        'parsed_output_json',
    ];

    protected $casts = [
        'input_context_json' => 'array',
        'parsed_output_json' => 'array',
        'success' => 'boolean',
        'temperature' => 'float',
    ];
}
