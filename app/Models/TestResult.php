<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_run_id',
        'test_case_id',
        'status',
        'defect_bug_id',
        'remarks',
    ];

    public function testRun()
    {
        return $this->belongsTo(TestRun::class);
    }

    public function testCase()
    {
        return $this->belongsTo(TestCase::class);
    }

    public function defectBug()
    {
        return $this->belongsTo(Bug::class, 'defect_bug_id');
    }
}
