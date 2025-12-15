<?php

namespace App\Console\Commands;

use App\Exceptions\DriveException;
use App\Models\Project;
use App\Services\ProjectDriveProvisioner;
use Illuminate\Console\Command;

class DriveProvisionCommand extends Command
{
    protected $signature = 'drive:provision {projectId? : Provision a single project (omit for all projects)}';
    protected $description = 'Provision Google Drive folder structure for one or all projects';

    public function handle(ProjectDriveProvisioner $provisioner): int
    {
        $projectId = $this->argument('projectId');
        $projects = $projectId
            ? Project::whereKey($projectId)->get()
            : Project::orderBy('id')->get();

        if ($projects->isEmpty()) {
            $this->error('No matching projects found.');
            return self::FAILURE;
        }

        $status = self::SUCCESS;

        foreach ($projects as $project) {
            $this->info("Provisioning Drive for project #{$project->id} ({$project->name})");
            try {
                $result = $provisioner->provisionProjectFolders($project->id);
                $rootId = $result['project_root']['drive_folder_id'] ?? 'n/a';
                $this->line(" - Root folder: {$rootId}");
            } catch (DriveException $e) {
                $this->error("Failed: {$e->errorCode} - {$e->getMessage()}");
                $status = self::FAILURE;
            } catch (\Throwable $e) {
                $this->error("Unexpected failure: {$e->getMessage()}");
                $status = self::FAILURE;
            }
        }

        return $status;
    }
}
