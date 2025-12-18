<?php

namespace Tests\Unit;

use App\Models\SequenceConfig;
use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSequenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!in_array('mysql', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_mysql driver missing');
        }

        parent::setUp();

        SequenceConfig::create([
            'model_name' => 'project',
            'prefix' => 'PRJ-',
            'padding' => 5,
            'start_from' => 1,
            'current_value' => 0,
            'reset_policy' => 'none',
        ]);
    }

    public function test_project_codes_increment(): void
    {
        $seq = app(SequenceService::class);

        $a = $seq->next('project');
        $b = $seq->next('project');

        $this->assertEquals('PRJ-00001', $a);
        $this->assertEquals('PRJ-00002', $b);
    }
}
