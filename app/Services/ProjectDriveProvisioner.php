<?php

namespace App\Services;

use App\Exceptions\DriveException;
use App\Models\DriveFolder;
use App\Models\Project;

class ProjectDriveProvisioner
{
    public const ROOT_PHASE_KEY = 'PROJECT_ROOT';

    public const PHASE_FOLDER_MAP = [
        'REQUIREMENTS' => '01_Requirements',
        'DATA_COLLECTION' => '02_DataCollection',
        'MASTER_DATA_SETUP' => '03_MasterDataSetup',
        'DEVELOPMENT' => '04_Development',
        'TESTING' => '05_Testing',
        'DELIVERY' => '06_Delivery',
    ];

    public function __construct(private DriveClient $driveClient)
    {
    }

    public function provisionProjectFolders(int $projectId): array
    {
        $project = Project::findOrFail($projectId);
        $parentId = config('services.google_drive.root_folder_id') ?: null;
        $projectRootName = "Project - {$project->name}";

        $root = $this->ensureFolderRecord($projectId, self::ROOT_PHASE_KEY, $projectRootName, $parentId);

        $phaseResults = [];
        foreach (self::PHASE_FOLDER_MAP as $phaseKey => $folderName) {
            $phaseResults[$phaseKey] = $this->ensureFolderRecord(
                $projectId,
                $phaseKey,
                $folderName,
                $root['drive_folder_id']
            );
        }

        return [
            'project_root' => $root,
            'phases' => $phaseResults,
        ];
    }

    private function ensureFolderRecord(int $projectId, string $phaseKey, string $folderName, ?string $parentId): array
    {
        $existingRecord = DriveFolder::where('project_id', $projectId)
            ->where('phase_key', $phaseKey)
            ->first();

        $existingId = $existingRecord?->drive_folder_id;
        $existingMeta = null;

        if ($existingId) {
            try {
                $existingMeta = $this->driveClient->getFile($existingId);
            } catch (DriveException $e) {
                if ($e->errorCode !== 'DRIVE_ITEM_NOT_FOUND') {
                    throw $e;
                }
                $existingMeta = null;
            }
        }

        if (!$existingMeta) {
            $found = $this->driveClient->findFolderByName($folderName, $parentId);
            if ($found) {
                $existingMeta = $found;
            }
        }

        if (!$existingMeta) {
            $existingMeta = $this->driveClient->createFolder($folderName, $parentId);
        }

        $record = DriveFolder::updateOrCreate(
            [
                'project_id' => $projectId,
                'phase_key' => $phaseKey,
            ],
            [
                'drive_folder_id' => $existingMeta['id'],
                'drive_web_view_link' => $existingMeta['web_view_link'] ?? null,
            ]
        );

        return [
            'project_id' => $record->project_id,
            'phase_key' => $record->phase_key,
            'drive_folder_id' => $record->drive_folder_id,
            'drive_web_view_link' => $record->drive_web_view_link,
        ];
    }
}
