<?php

namespace App\Http\Controllers;

use App\Exceptions\DriveException;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\Project;
use App\Services\DriveClient;
use App\Services\ProjectDriveProvisioner;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DriveFilesController extends Controller
{
    public function __construct(
        private DriveClient $driveClient,
        private ProjectDriveProvisioner $provisioner
    ) {
    }

    public function provision(int $projectId): JsonResponse
    {
        try {
            $data = $this->provisioner->provisionProjectFolders($projectId);
            return $this->success($data);
        } catch (ModelNotFoundException $e) {
            return $this->error('PROJECT_NOT_FOUND', 'Project not found.', 404);
        } catch (DriveException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 500);
        } catch (\Throwable $e) {
            Log::error('Provision failed', ['error' => $e->getMessage()]);
            return $this->error('DRIVE_PROVISION_FAILED', 'Failed to provision folders.', 500);
        }
    }

    public function upload(Request $request, int $projectId): JsonResponse
    {
        $request->merge(['phase_key' => strtoupper((string) $request->input('phase_key'))]);
        $phaseKeys = array_merge(
            array_keys(ProjectDriveProvisioner::PHASE_FOLDER_MAP),
            [ProjectDriveProvisioner::ROOT_PHASE_KEY]
        );

        $validator = Validator::make($request->all(), [
            'phase_key' => 'required|string|in:' . implode(',', $phaseKeys),
            'entity_type' => 'required|string',
            'entity_id' => 'nullable|integer',
            'file' => 'nullable|file',
            'file_path' => 'nullable|string',
            'file_name' => 'nullable|string',
            'mime_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('VALIDATION_FAILED', $validator->errors()->first(), 422);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return $this->error('PROJECT_NOT_FOUND', 'Project not found.', 404);
        }

        $phaseKey = strtoupper($request->input('phase_key'));

        try {
            $this->provisioner->provisionProjectFolders($projectId);
        } catch (DriveException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 500);
        }

        $folder = DriveFolder::where('project_id', $projectId)
            ->where('phase_key', $phaseKey)
            ->first();

        if (!$folder) {
            return $this->error('DRIVE_FOLDER_MISSING', 'Phase folder not found. Provision folders first.', 404);
        }

        $uploadedFile = $request->file('file');
        $filePath = $request->input('file_path');
        $fileName = $request->input('file_name');
        $mimeType = $request->input('mime_type');

        if ($uploadedFile) {
            if (!$uploadedFile->isValid()) {
                return $this->error('INVALID_FILE', 'Uploaded file is invalid.', 422);
            }
            $filePath = $uploadedFile->getRealPath();
            $fileName = $fileName ?: $uploadedFile->getClientOriginalName();
            $mimeType = $mimeType ?: $uploadedFile->getClientMimeType();
        } elseif ($filePath) {
            if (!is_readable($filePath)) {
                return $this->error('FILE_NOT_FOUND', 'Provided file_path is not readable.', 422);
            }
            $fileName = $fileName ?: basename($filePath);
            $mimeType = $mimeType ?: (mime_content_type($filePath) ?: 'application/octet-stream');
        } else {
            return $this->error('FILE_REQUIRED', 'Provide a file upload or file_path.', 422);
        }

        try {
            $driveUploaded = $this->driveClient->uploadFile(
                $folder->drive_folder_id,
                $filePath,
                $fileName,
                $mimeType
            );
        } catch (DriveException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 500);
        }

        $stored = DriveFile::create([
            'project_id' => $projectId,
            'phase_key' => $phaseKey,
            'entity_type' => $request->input('entity_type'),
            'entity_id' => $request->input('entity_id'),
            'file_name' => $driveUploaded['name'] ?? $fileName,
            'mime_type' => $driveUploaded['mime_type'] ?? $mimeType,
            'drive_file_id' => $driveUploaded['id'] ?? null,
            'drive_folder_id' => $folder->drive_folder_id,
            'web_view_link' => $driveUploaded['web_view_link'] ?? null,
            'uploaded_at' => now(),
        ]);

        return $this->success(['file' => $stored]);
    }

    public function link(Request $request, int $projectId): JsonResponse
    {
        $request->merge(['phase_key' => strtoupper((string) $request->input('phase_key'))]);
        $phaseKeys = array_merge(
            array_keys(ProjectDriveProvisioner::PHASE_FOLDER_MAP),
            [ProjectDriveProvisioner::ROOT_PHASE_KEY]
        );

        $validator = Validator::make($request->all(), [
            'phase_key' => 'required|string|in:' . implode(',', $phaseKeys),
            'entity_type' => 'required|string',
            'entity_id' => 'nullable|integer',
            'drive_file_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('VALIDATION_FAILED', $validator->errors()->first(), 422);
        }

        $phaseKey = strtoupper($request->input('phase_key'));
        $project = Project::find($projectId);
        if (!$project) {
            return $this->error('PROJECT_NOT_FOUND', 'Project not found.', 404);
        }

        try {
            $this->provisioner->provisionProjectFolders($projectId);
        } catch (DriveException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 500);
        }

        $folder = DriveFolder::where('project_id', $projectId)
            ->where('phase_key', $phaseKey)
            ->first();

        if (!$folder) {
            return $this->error('DRIVE_FOLDER_MISSING', 'Phase folder not found. Provision folders first.', 404);
        }

        try {
            $fileMeta = $this->driveClient->getFile($request->input('drive_file_id'));
        } catch (DriveException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 404);
        }

        $stored = DriveFile::create([
            'project_id' => $projectId,
            'phase_key' => $phaseKey,
            'entity_type' => $request->input('entity_type'),
            'entity_id' => $request->input('entity_id'),
            'file_name' => $fileMeta['name'] ?? null,
            'mime_type' => $fileMeta['mime_type'] ?? null,
            'drive_file_id' => $fileMeta['id'] ?? null,
            'drive_folder_id' => $folder->drive_folder_id ?? ($fileMeta['parents'][0] ?? null),
            'web_view_link' => $fileMeta['web_view_link'] ?? null,
            'uploaded_at' => now(),
        ]);

        return $this->success(['file' => $stored]);
    }

    public function list(int $projectId, Request $request): JsonResponse
    {
        if (!Project::find($projectId)) {
            return $this->error('PROJECT_NOT_FOUND', 'Project not found.', 404);
        }

        $query = DriveFile::where('project_id', $projectId);

        if ($phase = $request->query('phase_key')) {
            $query->where('phase_key', strtoupper($phase));
        }
        if ($entity = $request->query('entity_type')) {
            $query->where('entity_type', $entity);
        }

        $paginated = $query->orderByDesc('id')->paginate(20);

        return $this->success([
            'items' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    private function success(array $data = [], array $warnings = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'warnings' => $warnings,
            'errors' => [],
        ]);
    }

    private function error(string $code, string $message, int $status = 400, array $warnings = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'warnings' => $warnings,
            'errors' => [
                ['code' => $code, 'message' => $message],
            ],
        ], $status);
    }
}
