<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ProjectTeam;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProjectService
{
    public function __construct(
        private SequenceService $sequences,
        private AuditLogger $audit,
        private ProjectStatusService $statusService,
    ) {}

    public function create(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            $statusId = $this->resolveStatus($data['status_id'] ?? null);
            $client = Client::findOrFail($data['client_id']);

            $project = Project::create([
                'project_code' => $data['project_code'] ?? $this->sequences->next('project'),
                'client_id' => $data['client_id'],
                'client_name' => $client->name,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status_id' => $statusId,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'],
                'priority' => strtolower($data['priority'] ?? 'medium'),
                'owner_user_id' => $data['owner_user_id'],
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $data['created_by'] ?? null,
                'updated_by' => $data['created_by'] ?? null,
            ]);

            $this->syncTeam($project, $data['team_members'] ?? []);
            $this->audit->logModel($project, AuditLogger::ACTION_CREATE);

            return $project->fresh(['client', 'status', 'owner', 'team.user']);
        });
    }

    public function update(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $statusId = isset($data['status_id']) ? $this->resolveStatus($data['status_id']) : $project->status_id;
            $client = Client::findOrFail($data['client_id']);

            $project->update([
                'project_code' => $data['project_code'] ?? $project->project_code ?? $this->sequences->next('project'),
                'client_id' => $data['client_id'],
                'client_name' => $client->name,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status_id' => $statusId,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'],
                'priority' => strtolower($data['priority'] ?? $project->priority),
                'owner_user_id' => $data['owner_user_id'],
                'is_active' => $data['is_active'] ?? $project->is_active,
            ]);

            if (array_key_exists('team_members', $data)) {
                $this->syncTeam($project, $data['team_members']);
            }

            $this->audit->logModel($project, AuditLogger::ACTION_UPDATE);

            return $project->fresh(['client', 'status', 'owner', 'team.user']);
        });
    }

    public function toggle(Project $project): Project
    {
        $project->is_active = !$project->is_active;
        $project->save();

        $action = $project->is_active ? AuditLogger::ACTION_ACTIVATE : AuditLogger::ACTION_DEACTIVATE;
        $this->audit->logModel($project, $action);

        return $project;
    }

    public function changeStatus(Project $project, int $statusId): Project
    {
        $status = $this->findActiveStatus($statusId);
        $project->status_id = $status->id;
        $project->save();

        $this->audit->logModel($project, AuditLogger::ACTION_UPDATE);

        return $project->fresh('status');
    }

    /**
     * @param array<int,array<string,mixed>> $teamMembers
     */
    public function syncTeam(Project $project, array $teamMembers): void
    {
        $keep = [];
        foreach ($teamMembers as $member) {
            if (!isset($member['user_id'])) {
                continue;
            }
            $team = ProjectTeam::updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $member['user_id']],
                [
                    'role_in_project' => $member['role_in_project'] ?? null,
                    'created_by' => $member['created_by'] ?? null,
                    'updated_by' => $member['created_by'] ?? null,
                ]
            );
            $keep[] = $team->id;
        }

        if (empty($teamMembers)) {
            ProjectTeam::where('project_id', $project->id)->delete();
        } elseif ($keep) {
            ProjectTeam::where('project_id', $project->id)->whereNotIn('id', $keep)->delete();
        }

        $this->audit->log('ProjectTeam', (string) $project->id, AuditLogger::ACTION_UPDATE, ['members' => $teamMembers]);
    }

    private function resolveStatus(?int $statusId): int
    {
        if ($statusId === null) {
            return $this->statusService->defaultId();
        }

        $status = $this->findActiveStatus($statusId);

        return $status->id;
    }

    private function findActiveStatus(int $statusId): ProjectStatus
    {
        $status = ProjectStatus::find($statusId);
        if (!$status) {
            throw new ModelNotFoundException('Status not found');
        }
        if (!$status->is_active) {
            throw new RuntimeException('Status is inactive');
        }

        return $status;
    }
}
