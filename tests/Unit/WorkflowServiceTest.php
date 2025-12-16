<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\Role;
use App\Models\User;
use App\Services\WorkflowService;
use Database\Seeders\ConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WorkflowServiceTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
    }

    protected function refreshDatabase(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite driver missing');
        }

        $this->baseRefreshDatabase();
    }

    private WorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfigSeeder::class);
        $this->workflow = $this->app->make(WorkflowService::class);
    }

    public function test_module_init_is_idempotent(): void
    {
        $project = Project::create([
            'name' => 'Init Test',
            'due_date' => now()->toDateString(),
        ]);

        $first = $this->workflow->initModules($project);
        $this->assertGreaterThan(0, $first['created_count']);
        $this->assertEquals($first['created_count'], ProjectModule::where('project_id', $project->id)->count());

        $second = $this->workflow->initModules($project);
        $this->assertEquals(0, $second['created_count']);
        $this->assertEquals($first['created_count'], $second['existing_count']);
    }

    public function test_module_done_requires_tasks_done(): void
    {
        $project = Project::create([
            'name' => 'Module Validation',
            'due_date' => now()->toDateString(),
        ]);

        $module = $this->workflow->createModule($project, ['name' => 'Build', 'order_no' => 1]);
        $this->workflow->createTask($module, ['title' => 'Open task', 'status' => 'TODO']);

        $this->expectException(RuntimeException::class);
        $this->workflow->updateModule($module, ['status' => 'DONE']);
    }

    public function test_developer_permissions_on_task_update(): void
    {
        $project = Project::create([
            'name' => 'Permissions',
            'due_date' => now()->toDateString(),
        ]);
        $module = $this->workflow->createModule($project, ['name' => 'Dev', 'order_no' => 1]);

        $developerRole = Role::where('name', 'Developer')->first();
        $assignee = User::create([
            'name' => 'Dev One',
            'email' => 'dev1@example.com',
            'password' => 'secret',
            'role_id' => $developerRole?->id,
        ]);
        $otherDev = User::create([
            'name' => 'Dev Two',
            'email' => 'dev2@example.com',
            'password' => 'secret',
            'role_id' => $developerRole?->id,
        ]);

        $task = $this->workflow->createTask($module, [
            'title' => 'Assigned task',
            'assignee_user_id' => $assignee->id,
        ]);

        $this->expectException(RuntimeException::class);
        $this->workflow->updateTask($task, ['status' => 'IN_PROGRESS'], 'Developer', $otherDev->id);

        $updated = $this->workflow->updateTask($task, ['status' => 'IN_PROGRESS'], 'Developer', $assignee->id);
        $this->assertEquals('IN_PROGRESS', $updated->status);
    }
}
