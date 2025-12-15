<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'delivery_date',
        'delivered_requirements_json',
        'signoff_notes',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'delivered_requirements_json' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
