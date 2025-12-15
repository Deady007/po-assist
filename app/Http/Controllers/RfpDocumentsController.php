<?php

namespace App\Http\Controllers;

use App\Http\Requests\RfpDocumentLinkRequest;
use App\Http\Requests\RfpDocumentUploadRequest;
use App\Exceptions\DriveException;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\Project;
use App\Models\RfpDocument;
use App\Services\DriveClient;
use App\Services\ProjectDriveProvisioner;
use Illuminate\Http\JsonResponse;

class RfpDocumentsController extends ApiController
{
    public function __construct(
        private DriveClient $drive,
        private ProjectDriveProvisioner $provisioner
    ) {
    }

    public function upload(RfpDocumentUploadRequest $request, int $projectId): JsonResponse
    {
        $project = Project::find($projectId);
        if (!$project) {
            return $this->failure([['code' => 'PROJECT_NOT_FOUND', 'message' => 'Project not found']], 404);
        }

        $this->provisioner->provisionProjectFolders($projectId);
        $folder = DriveFolder::where('project_id', $projectId)
            ->where('phase_key', 'REQUIREMENTS')
            ->first();

        if (!$folder) {
            return $this->failure([['code' => 'DRIVE_FOLDER_MISSING', 'message' => 'Requirements folder missing']], 404);
        }

        $file = $request->file('file');
        try {
            $upload = $this->drive->uploadFile(
                $folder->drive_folder_id,
                $file->getRealPath(),
                $file->getClientOriginalName(),
                $file->getClientMimeType()
            );
        } catch (DriveException $e) {
            return $this->failure([['code' => $e->errorCode, 'message' => $e->getMessage()]], 500);
        }

        $doc = RfpDocument::create([
            'project_id' => $projectId,
            'title' => $request->input('title'),
            'drive_file_id' => $upload['id'] ?? null,
            'drive_web_view_link' => $upload['web_view_link'] ?? null,
        ]);

        DriveFile::create([
            'project_id' => $projectId,
            'phase_key' => 'REQUIREMENTS',
            'entity_type' => 'rfp_document',
            'entity_id' => $doc->id,
            'file_name' => $upload['name'] ?? $file->getClientOriginalName(),
            'mime_type' => $upload['mime_type'] ?? $file->getClientMimeType(),
            'drive_file_id' => $upload['id'] ?? null,
            'drive_folder_id' => $folder->drive_folder_id,
            'web_view_link' => $upload['web_view_link'] ?? null,
            'uploaded_at' => now(),
        ]);

        return $this->success(['item' => $doc->toArray()]);
    }

    public function link(RfpDocumentLinkRequest $request, int $projectId): JsonResponse
    {
        $project = Project::find($projectId);
        if (!$project) {
            return $this->failure([['code' => 'PROJECT_NOT_FOUND', 'message' => 'Project not found']], 404);
        }

        $this->provisioner->provisionProjectFolders($projectId);
        $folder = DriveFolder::where('project_id', $projectId)
            ->where('phase_key', 'REQUIREMENTS')
            ->first();

        try {
            $meta = $this->drive->getFile($request->input('drive_file_id'));
        } catch (DriveException $e) {
            return $this->failure([['code' => $e->errorCode, 'message' => $e->getMessage()]], 404);
        }

        $doc = RfpDocument::create([
            'project_id' => $projectId,
            'title' => $request->input('title'),
            'drive_file_id' => $meta['id'] ?? null,
            'drive_web_view_link' => $meta['web_view_link'] ?? null,
        ]);

        DriveFile::create([
            'project_id' => $projectId,
            'phase_key' => 'REQUIREMENTS',
            'entity_type' => 'rfp_document',
            'entity_id' => $doc->id,
            'file_name' => $meta['name'] ?? $request->input('title'),
            'mime_type' => $meta['mime_type'] ?? null,
            'drive_file_id' => $meta['id'] ?? null,
            'drive_folder_id' => $folder?->drive_folder_id ?? ($meta['parents'][0] ?? null),
            'web_view_link' => $meta['web_view_link'] ?? null,
            'uploaded_at' => now(),
        ]);

        return $this->success(['item' => $doc->toArray()]);
    }
}
