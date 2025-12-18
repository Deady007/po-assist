<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\ProjectStatus;
use App\Models\SequenceConfig;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\ProjectStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectStatusServiceTest extends TestCase
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

    public function test_set_default_enforces_uniqueness(): void
    {
        $service = app(ProjectStatusService::class);

        $a = $service->create(['name' => 'A', 'order_no' => 1, 'is_default' => true]);
        $b = $service->create(['name' => 'B', 'order_no' => 2, 'is_default' => false]);

        $service->setDefault($b);

        $this->assertFalse($a->fresh()->is_default);
        $this->assertTrue($b->fresh()->is_default);
    }

    public function test_default_status_applied_on_project_create(): void
    {
        $statusDefault = ProjectStatus::create(['name' => 'Default', 'order_no' => 1, 'is_default' => true, 'is_active' => true]);
        $statusOther = ProjectStatus::create(['name' => 'Other', 'order_no' => 2, 'is_default' => false, 'is_active' => true]);

        $client = Client::create(['client_code' => 'CL-001', 'name' => 'Client']);
        $owner = User::factory()->create();

        $service = app(ProjectService::class);
        $project = $service->create([
            'client_id' => $client->id,
            'name' => 'Project X',
            'due_date' => now()->toDateString(),
            'priority' => 'medium',
            'owner_user_id' => $owner->id,
        ]);

        $this->assertEquals($statusDefault->id, $project->status_id);

        $project2 = $service->create([
            'client_id' => $client->id,
            'name' => 'Project Y',
            'status_id' => $statusOther->id,
            'due_date' => now()->toDateString(),
            'priority' => 'medium',
            'owner_user_id' => $owner->id,
        ]);

        $this->assertEquals($statusOther->id, $project2->status_id);
    }
}
