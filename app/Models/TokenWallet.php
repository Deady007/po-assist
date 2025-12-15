<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'total_tokens',
        'used_tokens',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
