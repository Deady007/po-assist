<?php

namespace App\Console\Commands;

use App\Exceptions\DriveException;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\Project;
use App\Services\DriveClient;
use Illuminate\Console\Command;

class DriveVerifyCommand extends Command
{
    protected $signature = 'drive:verify {projectId : Project ID to verify}';
    protected $description = 'Verify stored Google Drive folder and file IDs still exist';

    public function handle(DriveClient $drive): int
    {
        $projectId = $this->argument('projectId');
        $project = Project::find($projectId);

        if (!$project) {
            $this->error('Project not found.');
            return self::FAILURE;
        }

        $this->info("Verifying Drive artifacts for project #{$project->id} ({$project->name})");

        $missingFolders = [];
        $missingFiles = [];

        $folders = DriveFolder::where('project_id', $projectId)->get();
        if ($folders->isEmpty()) {
            $this->warn('No drive_folders records found for this project.');
        }

        foreach ($folders as $folder) {
            if (!$folder->drive_folder_id) {
                $missingFolders[] = $folder->phase_key;
                continue;
            }

            try {
                $drive->getFile($folder->drive_folder_id);
                $this->line("✓ Folder OK: {$folder->phase_key} ({$folder->drive_folder_id})");
            } catch (DriveException $e) {
                if ($e->errorCode === 'DRIVE_ITEM_NOT_FOUND') {
                    $missingFolders[] = $folder->phase_key;
                    $this->warn("Missing folder for {$folder->phase_key} ({$folder->drive_folder_id})");
                } else {
                    $this->warn("Folder check error {$folder->phase_key}: {$e->getMessage()}");
                }
            }
        }

        $files = DriveFile::where('project_id', $projectId)->get();
        foreach ($files as $file) {
            if (!$file->drive_file_id) {
                $missingFiles[] = "{$file->file_name} (#{$file->id})";
                continue;
            }

            try {
                $drive->getFile($file->drive_file_id);
            } catch (DriveException $e) {
                if ($e->errorCode === 'DRIVE_ITEM_NOT_FOUND') {
                    $missingFiles[] = "{$file->file_name} ({$file->drive_file_id})";
                } else {
                    $this->warn("File check error {$file->drive_file_id}: {$e->getMessage()}");
                }
            }
        }

        if (empty($missingFolders) && empty($missingFiles)) {
            $this->info('All Drive references are reachable.');
            return self::SUCCESS;
        }

        if ($missingFolders) {
            $this->error('Missing folders: ' . implode(', ', $missingFolders));
        }

        if ($missingFiles) {
            $this->error('Missing files: ' . implode(', ', $missingFiles));
        }

        return self::FAILURE;
    }
}
