<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataItemStoreRequest;
use App\Http\Requests\DataItemUpdateRequest;
use App\Http\Requests\DataItemUploadRequest;
use App\Exceptions\DriveException;
use App\Models\DataItem;
use App\Models\DataItemFile;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\Project;
use App\Services\DriveClient;
use App\Services\ProjectDriveProvisioner;
use Illuminate\Http\JsonResponse;

class DataItemsController extends ApiController
{
    public function __construct(
        private DriveClient $drive,
        private ProjectDriveProvisioner $provisioner
    ) {
    }

    public function index(int $projectId): JsonResponse
    {
        if (!Project::find($projectId)) {
            return $this->failure([['code' => 'PROJECT_NOT_FOUND', 'message' => 'Project not found']], 404);
        }

        $items = DataItem::with('files')
            ->where('project_id', $projectId)
            ->orderBy('due_date')
            ->get();

        return $this->success(['items' => $items->toArray()]);
    }

    public function store(DataItemStoreRequest $request, int $projectId): JsonResponse
    {
        if (!Project::find($projectId)) {
            return $this->failure([['code' => 'PROJECT_NOT_FOUND', 'message' => 'Project not found']], 404);
        }

        $data = $request->validated();
        $data['project_id'] = $projectId;
        $item = DataItem::create($data);

        return $this->success(['item' => $item->toArray()]);
    }

    public function update(DataItemUpdateRequest $request, int $projectId, int $id): JsonResponse
    {
        $item = DataItem::where('project_id', $projectId)->find($id);
        if (!$item) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Data item not found']], 404);
        }

        $data = $request->validated();
        if (isset($data['status']) && $data['status'] === 'RECEIVED' && !$item->received_at) {
            $data['received_at'] = now();
        }

        $item->update($data);

        return $this->success(['item' => $item->fresh()->toArray()]);
    }

    public function destroy(int $projectId, int $id): JsonResponse
    {
        $item = DataItem::where('project_id', $projectId)->find($id);
        if (!$item) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Data item not found']], 404);
        }
        $item->delete();
        return $this->success(['deleted' => true]);
    }

    public function upload(DataItemUploadRequest $request, int $projectId, int $id): JsonResponse
    {
        $item = DataItem::where('project_id', $projectId)->find($id);
        if (!$item) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Data item not found']], 404);
        }

        $this->provisioner->provisionProjectFolders($projectId);
        $folder = DriveFolder::where('project_id', $projectId)->where('phase_key', 'DATA_COLLECTION')->first();
        if (!$folder) {
            return $this->failure([['code' => 'DRIVE_FOLDER_MISSING', 'message' => 'Data collection folder missing']], 404);
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

        $fileRecord = DataItemFile::create([
            'data_item_id' => $item->id,
            'file_name' => $upload['name'] ?? $file->getClientOriginalName(),
            'drive_file_id' => $upload['id'] ?? null,
            'drive_web_view_link' => $upload['web_view_link'] ?? null,
            'uploaded_at' => now(),
        ]);

        DriveFile::create([
            'project_id' => $projectId,
            'phase_key' => 'DATA_COLLECTION',
            'entity_type' => 'data_item',
            'entity_id' => $item->id,
            'file_name' => $fileRecord->file_name,
            'mime_type' => $upload['mime_type'] ?? $file->getClientMimeType(),
            'drive_file_id' => $upload['id'] ?? null,
            'drive_folder_id' => $folder->drive_folder_id,
            'web_view_link' => $upload['web_view_link'] ?? null,
            'uploaded_at' => now(),
        ]);

        return $this->success(['item' => $fileRecord->toArray()]);
    }
}
