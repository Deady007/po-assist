<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailArtifact extends Model
{
    protected $fillable = [
        'project_id',
        'client_id',
        'type',
        'email_template_id',
        'scope_type',
        'tone',
        'generated_by',
        'input_json',
        'subject',
        'body_text',
        'created_by',
        'updated_by',
    ];


protected $casts = [
    'input_json' => 'array',
];
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

}
