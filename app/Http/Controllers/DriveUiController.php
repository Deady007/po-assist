<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\Project;
use App\Services\ProjectDriveProvisioner;

class DriveUiController extends Controller
{
    public function connect()
    {
        return view('drive.connect');
    }

    public function project(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $phaseMap = ProjectDriveProvisioner::PHASE_FOLDER_MAP;
        $folders = DriveFolder::where('project_id', $projectId)->get()->keyBy('phase_key');
        $rootFolder = $folders[ProjectDriveProvisioner::ROOT_PHASE_KEY] ?? null;
        $files = DriveFile::where('project_id', $projectId)
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('drive.project', [
            'project' => $project,
            'phaseMap' => $phaseMap,
            'folders' => $folders,
            'rootFolder' => $rootFolder,
            'files' => $files,
        ]);
    }
}
