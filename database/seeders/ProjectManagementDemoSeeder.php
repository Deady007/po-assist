<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ModuleTemplate;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectStatus;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectManagementDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $roles = $this->seedRoles();
            $users = $this->seedUsers($roles);
            $clients = $this->seedClients($users['viral']->id);
            $statusOpen = $this->seedProjectStatusOpen();

            $templates = $this->seedModuleTemplates($users['viral']->id);
            $projects = $this->seedProjects($clients, $users, $statusOpen->id, $users['viral']->id);
            $modules = $this->seedProjectModules($projects, $users, $templates);

            $this->seedTasks($projects, $modules, $users);
        });
    }

    /**
     * @return array<string,Role>
     */
    private function seedRoles(): array
    {
        $roleModels = [];

        foreach (['Admin', 'PM', 'Developer', 'Viewer'] as $roleName) {
            $roleModels[$roleName] = Role::firstOrCreate(['name' => $roleName]);
        }

        return $roleModels;
    }

    /**
     * @param array<string,Role> $roles
     * @return array<string,User>
     */
    private function seedUsers(array $roles): array
    {
        $users = [
            'viral' => [
                'name' => 'Viral Parmar',
                'email' => 'viral@fiscalox.com',
                'phone' => '9876543210',
                'role' => 'Admin',
                'password' => 'Admin@123',
                'is_active' => true,
            ],
            'aditi' => [
                'name' => 'Aditi Shah',
                'email' => 'aditi.shah@acmetech.com',
                'phone' => '9123456789',
                'role' => 'PM',
                'password' => 'Pm@123',
                'is_active' => true,
            ],
            'rohan' => [
                'name' => 'Rohan Mehta',
                'email' => 'rohan.mehta@acmetech.com',
                'phone' => '9988776655',
                'role' => 'Developer',
                'password' => 'Dev@123',
                'is_active' => true,
            ],
            'neha' => [
                'name' => 'Neha Verma',
                'email' => 'neha.verma@nova.com',
                'phone' => '9090909090',
                'role' => 'Viewer',
                'password' => 'View@123',
                'is_active' => false,
            ],
        ];

        $userModels = [];

        foreach ($users as $key => $user) {
            $roleName = $user['role'];
            $role = $roles[$roleName] ?? null;
            if (!$role) {
                throw new \RuntimeException("Role missing: {$roleName}");
            }

            $userModels[$key] = User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'role_id' => $role->id,
                    'password' => $user['password'],
                    'is_active' => $user['is_active'],
                ]
            );
        }

        return $userModels;
    }

    /**
     * @return array<string,Client>
     */
    private function seedClients(int $actorUserId): array
    {
        $clients = [
            'CUST-001' => [
                'name' => 'Acme Technologies',
                'industry' => 'Software',
                'website' => 'https://www.acmetech.com',
                'billing_address' => '401, Alpha Tower, Bengaluru, India',
                'is_active' => true,
            ],
            'CUST-002' => [
                'name' => 'GreenField Agro',
                'industry' => 'Agriculture',
                'website' => 'https://www.greenfieldagro.in',
                'billing_address' => 'Plot 22, Industrial Area, Pune, India',
                'is_active' => true,
            ],
            'CUST-003' => [
                'name' => 'Nova Healthcare',
                'industry' => 'Healthcare',
                'website' => 'https://www.novahealthcare.com',
                'billing_address' => '18, Sunrise Avenue, Mumbai, India',
                'is_active' => false,
            ],
        ];

        $clientModels = [];

        foreach ($clients as $code => $client) {
            $model = Client::withTrashed()->updateOrCreate(
                ['client_code' => $code],
                [
                    'name' => $client['name'],
                    'industry' => $client['industry'],
                    'website' => $client['website'],
                    'billing_address' => $client['billing_address'],
                    'is_active' => $client['is_active'],
                    'created_by' => $actorUserId,
                    'updated_by' => $actorUserId,
                ]
            );

            if (method_exists($model, 'trashed') && $model->trashed()) {
                $model->restore();
            }

            $clientModels[$code] = $model;
        }

        return $clientModels;
    }

    private function seedProjectStatusOpen(): ProjectStatus
    {
        $status = ProjectStatus::firstOrCreate(
            ['name' => 'Open'],
            [
                'order_no' => 1,
                'is_default' => true,
                'is_active' => true,
            ]
        );

        if (!$status->is_active || !$status->is_default) {
            $status->update([
                'is_active' => true,
                'is_default' => true,
            ]);
        }

        ProjectStatus::where('id', '!=', $status->id)->update(['is_default' => false]);

        return $status;
    }

    /**
     * @return array<string,ModuleTemplate>
     */
    private function seedModuleTemplates(int $actorUserId): array
    {
        $now = now();

        $requirements = $this->upsertModuleTemplateWithPreferredId(
            preferredId: 101,
            key: 'requirements_phase',
            name: 'Requirements',
            orderNo: 1,
            actorUserId: $actorUserId,
            now: $now
        );

        $development = $this->upsertModuleTemplateWithPreferredId(
            preferredId: 102,
            key: 'development_phase',
            name: 'Development',
            orderNo: 2,
            actorUserId: $actorUserId,
            now: $now
        );

        $testing = $this->upsertModuleTemplateWithPreferredId(
            preferredId: 103,
            key: 'qa_phase',
            name: 'QA / Testing',
            orderNo: 3,
            actorUserId: $actorUserId,
            now: $now
        );

        return [
            'requirements' => $requirements,
            'development' => $development,
            'testing' => $testing,
        ];
    }

    private function upsertModuleTemplateWithPreferredId(
        int $preferredId,
        string $key,
        string $name,
        int $orderNo,
        int $actorUserId,
        Carbon $now,
    ): ModuleTemplate {
        $byKey = ModuleTemplate::where('key', $key)->first();
        if ($byKey) {
            $byKey->update([
                'name' => $name,
                'order_no' => $orderNo,
                'is_active' => true,
                'updated_by' => $actorUserId,
            ]);

            return $byKey;
        }

        $byId = ModuleTemplate::find($preferredId);
        if ($byId) {
            return ModuleTemplate::firstOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'order_no' => $orderNo,
                    'is_active' => true,
                    'created_by' => $actorUserId,
                    'updated_by' => $actorUserId,
                ]
            );
        }

        DB::table('module_templates')->insert([
            'id' => $preferredId,
            'key' => $key,
            'name' => $name,
            'order_no' => $orderNo,
            'is_active' => true,
            'created_by' => $actorUserId,
            'updated_by' => $actorUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ModuleTemplate::findOrFail($preferredId);
    }

    /**
     * @param array<string,Client> $clients
     * @param array<string,User> $users
     * @return array<string,Project>
     */
    private function seedProjects(array $clients, array $users, int $statusId, int $actorUserId): array
    {
        $projects = [
            'PROJ-001' => [
                'name' => 'Acme ERP Revamp',
                'client_code' => 'CUST-001',
                'priority' => 'high',
                'start_date' => '2025-01-01',
                'due_date' => '2025-06-30',
                'owner' => 'aditi',
                'is_active' => true,
            ],
            'PROJ-002' => [
                'name' => 'Agro Supply Tracker',
                'client_code' => 'CUST-002',
                'priority' => 'medium',
                'start_date' => '2025-02-01',
                'due_date' => '2025-05-15',
                'owner' => 'viral',
                'is_active' => true,
            ],
            'PROJ-003' => [
                'name' => 'Hospital CRM System',
                'client_code' => 'CUST-003',
                'priority' => 'high',
                'start_date' => '2024-10-01',
                'due_date' => '2025-03-31',
                'owner' => 'viral',
                'is_active' => false,
            ],
        ];

        $projectModels = [];

        foreach ($projects as $code => $project) {
            $client = $clients[$project['client_code']] ?? null;
            if (!$client) {
                throw new \RuntimeException("Client missing: {$project['client_code']}");
            }

            $owner = $users[$project['owner']] ?? null;
            if (!$owner) {
                throw new \RuntimeException("Owner user missing: {$project['owner']}");
            }

            $model = Project::withTrashed()->updateOrCreate(
                ['project_code' => $code],
                [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'name' => $project['name'],
                    'description' => null,
                    'status_id' => $statusId,
                    'start_date' => $project['start_date'],
                    'due_date' => $project['due_date'],
                    'priority' => $project['priority'],
                    'owner_user_id' => $owner->id,
                    'is_active' => $project['is_active'],
                    'created_by' => $actorUserId,
                    'updated_by' => $actorUserId,
                ]
            );

            if (method_exists($model, 'trashed') && $model->trashed()) {
                $model->restore();
            }

            $projectModels[$code] = $model;
        }

        return $projectModels;
    }

    /**
     * @param array<string,Project> $projects
     * @param array<string,User> $users
     * @param array<string,ModuleTemplate> $templates
     * @return array<string,ProjectModule>
     */
    private function seedProjectModules(array $projects, array $users, array $templates): array
    {
        $modules = [
            'PROJ-001:1' => [
                'project' => 'PROJ-001',
                'template' => 'requirements',
                'name' => 'Requirements',
                'order_no' => 1,
                'module_name' => 'Requirement Phase',
                'status' => 'Open',
                'start_date' => '2025-01-01',
                'due_date' => '2025-01-31',
                'owner' => 'aditi',
                'blocker_reason' => null,
                'is_active' => true,
                'created_by' => 'viral',
                'updated_by' => 'viral',
                'created_at' => '2024-12-01 10:00:00',
                'updated_at' => '2024-12-05 15:30:00',
            ],
            'PROJ-001:2' => [
                'project' => 'PROJ-001',
                'template' => 'development',
                'name' => 'Development',
                'order_no' => 2,
                'module_name' => 'Development Phase',
                'status' => 'InProgress',
                'start_date' => '2025-02-01',
                'due_date' => '2025-04-30',
                'owner' => 'rohan',
                'blocker_reason' => 'Waiting for API specs',
                'is_active' => true,
                'created_by' => 'viral',
                'updated_by' => 'aditi',
                'created_at' => '2024-12-10 11:00:00',
                'updated_at' => '2025-01-15 18:00:00',
            ],
            'PROJ-002:3' => [
                'project' => 'PROJ-002',
                'template' => 'testing',
                'name' => 'Testing',
                'order_no' => 3,
                'module_name' => 'QA Phase',
                'status' => 'Pending',
                'start_date' => '2025-04-01',
                'due_date' => '2025-05-10',
                'owner' => 'rohan',
                'blocker_reason' => null,
                'is_active' => true,
                'created_by' => 'viral',
                'updated_by' => 'viral',
                'created_at' => '2025-01-01 09:00:00',
                'updated_at' => '2025-01-01 09:00:00',
            ],
        ];

        $moduleModels = [];

        foreach ($modules as $key => $module) {
            $project = $projects[$module['project']] ?? null;
            if (!$project) {
                throw new \RuntimeException("Project missing: {$module['project']}");
            }

            $template = $templates[$module['template']] ?? null;
            if (!$template) {
                throw new \RuntimeException("Module template missing: {$module['template']}");
            }

            $owner = $users[$module['owner']] ?? null;
            if (!$owner) {
                throw new \RuntimeException("Module owner missing: {$module['owner']}");
            }

            $createdBy = $users[$module['created_by']] ?? null;
            $updatedBy = $users[$module['updated_by']] ?? null;
            if (!$createdBy || !$updatedBy) {
                throw new \RuntimeException('Module created_by/updated_by user missing.');
            }

            $moduleModels[$key] = ProjectModule::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'order_no' => $module['order_no'],
                ],
                [
                    'template_id' => $template->id,
                    'name' => $module['name'],
                    'module_name' => $module['module_name'],
                    'status' => $this->normalizeModuleStatus($module['status']),
                    'start_date' => $module['start_date'],
                    'due_date' => $module['due_date'],
                    'owner_user_id' => $owner->id,
                    'blocker_reason' => $module['blocker_reason'],
                    'is_active' => $module['is_active'],
                    'created_by' => $createdBy->id,
                    'updated_by' => $updatedBy->id,
                    'created_at' => Carbon::parse($module['created_at']),
                    'updated_at' => Carbon::parse($module['updated_at']),
                ]
            );
        }

        return $moduleModels;
    }

    /**
     * @param array<string,Project> $projects
     * @param array<string,ProjectModule> $modules
     * @param array<string,User> $users
     */
    private function seedTasks(array $projects, array $modules, array $users): void
    {
        $tasks = [
            [
                'project' => 'PROJ-001',
                'module_key' => 'PROJ-001:1',
                'title' => 'Gather Business Needs',
                'description' => 'Conduct stakeholder interviews',
                'assignee' => 'aditi',
                'status' => 'Completed',
                'priority' => 'High',
                'due_date' => '2025-01-15',
                'blocker_reason' => null,
                'created_by' => 'viral',
                'updated_by' => 'aditi',
                'created_at' => '2025-01-01 10:00:00',
                'updated_at' => '2025-01-16 12:00:00',
            ],
            [
                'project' => 'PROJ-001',
                'module_key' => 'PROJ-001:2',
                'title' => 'API Integration',
                'description' => 'Integrate payment gateway APIs',
                'assignee' => 'rohan',
                'status' => 'InProgress',
                'priority' => 'High',
                'due_date' => '2025-03-15',
                'blocker_reason' => 'API access not ready',
                'created_by' => 'aditi',
                'updated_by' => 'rohan',
                'created_at' => '2025-02-01 11:30:00',
                'updated_at' => '2025-02-20 16:45:00',
            ],
            [
                'project' => 'PROJ-002',
                'module_key' => 'PROJ-002:3',
                'title' => 'Test Order Flow',
                'description' => 'Validate order creation workflow',
                'assignee' => 'rohan',
                'status' => 'Pending',
                'priority' => 'Medium',
                'due_date' => '2025-04-20',
                'blocker_reason' => null,
                'created_by' => 'viral',
                'updated_by' => 'viral',
                'created_at' => '2025-04-01 09:15:00',
                'updated_at' => '2025-04-01 09:15:00',
            ],
        ];

        foreach ($tasks as $task) {
            $project = $projects[$task['project']] ?? null;
            $module = $modules[$task['module_key']] ?? null;
            if (!$project || !$module) {
                throw new \RuntimeException('Task project/module missing.');
            }

            $assignee = $users[$task['assignee']] ?? null;
            $createdBy = $users[$task['created_by']] ?? null;
            $updatedBy = $users[$task['updated_by']] ?? null;
            if (!$assignee || !$createdBy || !$updatedBy) {
                throw new \RuntimeException('Task user references missing.');
            }

            Task::updateOrCreate(
                [
                    'project_module_id' => $module->id,
                    'title' => $task['title'],
                ],
                [
                    'project_id' => $project->id,
                    'description' => $task['description'],
                    'assignee_user_id' => $assignee->id,
                    'status' => $this->normalizeTaskStatus($task['status']),
                    'priority' => $this->normalizePriority($task['priority']),
                    'due_date' => $task['due_date'],
                    'blocker_reason' => $task['blocker_reason'],
                    'created_by' => $createdBy->id,
                    'updated_by' => $updatedBy->id,
                    'created_at' => Carbon::parse($task['created_at']),
                    'updated_at' => Carbon::parse($task['updated_at']),
                ]
            );
        }
    }

    private function normalizeModuleStatus(string $status): string
    {
        $normalized = strtoupper(trim($status));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return match ($normalized) {
            'OPEN', 'PENDING', 'NOT_STARTED', 'NOTSTARTED' => 'NOT_STARTED',
            'INPROGRESS', 'IN_PROGRESS' => 'IN_PROGRESS',
            'BLOCKED' => 'BLOCKED',
            'DONE', 'COMPLETED' => 'DONE',
            default => 'NOT_STARTED',
        };
    }

    private function normalizeTaskStatus(string $status): string
    {
        $normalized = strtoupper(trim($status));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return match ($normalized) {
            'PENDING', 'OPEN', 'TODO', 'NOT_STARTED', 'NOTSTARTED' => 'TODO',
            'INPROGRESS', 'IN_PROGRESS' => 'IN_PROGRESS',
            'BLOCKED' => 'BLOCKED',
            'DONE', 'COMPLETED' => 'DONE',
            default => 'TODO',
        };
    }

    private function normalizePriority(string $priority): string
    {
        $normalized = strtoupper(trim($priority));

        return match ($normalized) {
            'LOW' => 'LOW',
            'HIGH' => 'HIGH',
            default => 'MEDIUM',
        };
    }
}
