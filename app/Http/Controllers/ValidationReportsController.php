<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationReportGenerateRequest;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\Project;
use App\Models\ValidationReport;
use App\Exceptions\DriveException;
use App\Services\DriveClient;
use App\Services\ProjectDriveProvisioner;
use App\Services\ValidationReportService;
use Illuminate\Http\JsonResponse;

class ValidationReportsController extends ApiController
{
    public function __construct(
        private ValidationReportService $service,
        private DriveClient $drive,
        private ProjectDriveProvisioner $provisioner
    ) {
    }

    public function index(int $projectId): JsonResponse
    {
        $items = ValidationReport::where('project_id', $projectId)
            ->orderByDesc('generated_at')
            ->get();

        return $this->success(['items' => $items->toArray()]);
    }

    public function generate(ValidationReportGenerateRequest $request, int $projectId): JsonResponse
    {
        $includeSummary = (bool) $request->input('include_ai_summary', false);
        try {
            $report = $this->service->generateReport($projectId, $includeSummary);
            return $this->success(['item' => $report->toArray()]);
        } catch (\Throwable $e) {
            return $this->failure([['code' => 'REPORT_FAILED', 'message' => $e->getMessage()]], 500);
        }
    }

    public function uploadToDrive(int $projectId, int $reportId): JsonResponse
    {
        $project = Project::find($projectId);
        if (!$project) {
            return $this->failure([['code' => 'PROJECT_NOT_FOUND', 'message' => 'Project not found']], 404);
        }

        $report = ValidationReport::where('project_id', $projectId)->find($reportId);
        if (!$report) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Report not found']], 404);
        }

        $this->provisioner->provisionProjectFolders($projectId);
        $folder = DriveFolder::where('project_id', $projectId)->where('phase_key', 'DELIVERY')->first();
        if (!$folder) {
            return $this->failure([['code' => 'DRIVE_FOLDER_MISSING', 'message' => 'Delivery folder missing']], 404);
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'vrpt_');
        file_put_contents($tmpPath, $report->report_html ?? '');
        $fileName = 'validation_report_' . $report->id . '.html';

        try {
            $upload = $this->drive->uploadFile(
                $folder->drive_folder_id,
                $tmpPath,
                $fileName,
                'text/html'
            );
        } catch (DriveException $e) {
            @unlink($tmpPath);
            return $this->failure([['code' => $e->errorCode, 'message' => $e->getMessage()]], 500);
        }
        @unlink($tmpPath);

        DriveFile::create([
            'project_id' => $projectId,
            'phase_key' => 'DELIVERY',
            'entity_type' => 'validation_report',
            'entity_id' => $report->id,
            'file_name' => $upload['name'] ?? $fileName,
            'mime_type' => $upload['mime_type'] ?? 'text/html',
            'drive_file_id' => $upload['id'] ?? null,
            'drive_folder_id' => $folder->drive_folder_id,
            'web_view_link' => $upload['web_view_link'] ?? null,
            'uploaded_at' => now(),
        ]);

        return $this->success([
            'item' => $report->fresh()->toArray(),
            'drive_file' => $upload,
        ]);
    }
}
