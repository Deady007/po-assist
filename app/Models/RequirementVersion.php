<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequirementVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'requirement_id',
        'version_no',
        'payload_json',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }
}
