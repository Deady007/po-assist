<?php

namespace Tests\Unit;

use App\Models\SequenceConfig;
use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!in_array('mysql', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_mysql driver missing');
        }

        parent::setUp();

        SequenceConfig::create([
            'model_name' => 'customer',
            'prefix' => 'CL-',
            'padding' => 5,
            'start_from' => 1,
            'current_value' => 0,
            'reset_policy' => 'none',
        ]);
    }

    public function test_it_generates_incremental_sequences_safely(): void
    {
        $service = app(SequenceService::class);

        $results = collect(range(1, 10))
            ->map(fn () => $service->next('customer'))
            ->all();

        $this->assertCount(10, $results);
        $this->assertEquals(
            ['CL-00001', 'CL-00002', 'CL-00003', 'CL-00004', 'CL-00005', 'CL-00006', 'CL-00007', 'CL-00008', 'CL-00009', 'CL-00010'],
            $results
        );
    }
}
