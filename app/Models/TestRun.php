<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'tester_id',
        'run_date',
        'notes',
    ];

    protected $casts = [
        'run_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tester()
    {
        return $this->belongsTo(Tester::class);
    }

    public function results()
    {
        return $this->hasMany(TestResult::class);
    }
}
