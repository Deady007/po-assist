<?php

namespace App\Http\Controllers;

use App\Http\Requests\RfpDocumentLinkRequest;
use App\Http\Requests\RfpDocumentUploadRequest;
use App\Exceptions\DriveException;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\Project;
use App\Models\RfpDocument;
use App\Services\AiOrchestratorService;
use App\Services\ContextBuilderService;
use App\Services\DriveClient;
use App\Services\GeminiClient;
use App\Services\ProjectDriveProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfpDocumentsController extends ApiController
{
    public function __construct(
        private DriveClient $drive,
        private ProjectDriveProvisioner $provisioner,
        private AiOrchestratorService $ai,
        private ContextBuilderService $contextBuilder
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

    public function extractRequirements(Request $request, int $projectId, int $rfpDocumentId): JsonResponse
    {
        $data = $request->validate([
            'source_text' => 'sometimes|string|nullable',
            'model' => 'sometimes|string|nullable',
            'temperature' => 'sometimes|numeric|nullable',
        ]);

        $project = Project::find($projectId);
        if (!$project) {
            return $this->failure([['code' => 'PROJECT_NOT_FOUND', 'message' => 'Project not found']], 404);
        }

        $doc = RfpDocument::where('project_id', $projectId)->find($rfpDocumentId);
        if (!$doc) {
            return $this->failure([['code' => 'RFP_NOT_FOUND', 'message' => 'RFP document not found']], 404);
        }

        // Allow supplying/persisting raw text (e.g., pasted content from a PDF).
        if (array_key_exists('source_text', $data)) {
            $doc->source_text = $data['source_text'];
            $doc->save();
        }

        $text = $data['source_text'] ?? $doc->source_text;
        if (!is_string($text) || trim($text) === '') {
            return $this->failure([
                ['code' => 'SOURCE_TEXT_REQUIRED', 'message' => 'Provide source_text or store it on the RFP document before extracting.'],
            ], 422);
        }

        $context = $this->contextBuilder->rfpExtraction($text, $projectId);
        $model = $data['model'] ?? GeminiClient::fastModel();
        $temperature = isset($data['temperature']) ? (float) $data['temperature'] : null;

        $out = $this->ai->run('RFP_REQUIREMENTS_EXTRACT', $context, [
            'project_id' => $projectId,
            'entity_type' => 'rfp_document',
            'entity_id' => $doc->id,
            'model' => $model,
            'temperature' => $temperature,
        ]);

        $doc->extracted_json = $out;
        $doc->save();

        return $this->success([
            'item' => $doc->toArray(),
            'extracted' => $out,
        ]);
    }
}
