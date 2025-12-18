<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\ValidationReport;
use App\Services\AiOrchestratorService;
use App\Services\ContextBuilderService;
use App\Services\GeminiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends ApiController
{
    public function __construct(
        private AiOrchestratorService $ai,
        private ContextBuilderService $contextBuilder
    ) {
    }

    public function productUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'tone' => 'sometimes|in:formal,executive,neutral',
            'date' => 'sometimes|string|nullable',
            'highlights' => 'sometimes|string|nullable',
            'completed' => 'sometimes|string|nullable',
            'in_progress' => 'sometimes|string|nullable',
            'risks' => 'sometimes|string|nullable',
            'review_topics' => 'sometimes|string|nullable',
            'temperature' => 'sometimes|numeric|nullable',
        ]);

        $projectId = (int) $data['project_id'];
        $context = $this->contextBuilder->productUpdate($projectId, [
            'date' => $data['date'] ?? null,
            'highlights' => $data['highlights'] ?? null,
        ]);

        $context['tone'] = $data['tone'] ?? 'formal';
        $context['user_completed'] = $data['completed'] ?? null;
        $context['user_in_progress'] = $data['in_progress'] ?? null;
        $context['user_risks'] = $data['risks'] ?? null;
        $context['user_review_topics'] = $data['review_topics'] ?? null;

        $out = $this->ai->run('PRODUCT_UPDATE', $context, [
            'project_id' => $projectId,
            'entity_type' => 'project',
            'entity_id' => $projectId,
            'model' => GeminiClient::proModel(),
            'temperature' => isset($data['temperature']) ? (float) $data['temperature'] : null,
        ]);

        return $this->success(['output' => $out]);
    }

    public function meetingSchedule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'tone' => 'sometimes|in:formal,executive,neutral',
            'meeting_title' => 'sometimes|string|nullable',
            'meeting_datetime' => 'required|string',
            'duration' => 'sometimes|string|nullable',
            'attendees' => 'sometimes|string|nullable',
            'agenda_topics' => 'sometimes|string|nullable',
            'meeting_location_or_link' => 'sometimes|string|nullable',
            'temperature' => 'sometimes|numeric|nullable',
        ]);

        $projectId = (int) $data['project_id'];
        $context = $this->contextBuilder->meetingSchedule($projectId, $data);
        $context['tone'] = $data['tone'] ?? 'formal';

        $out = $this->ai->run('MEETING_SCHEDULE', $context, [
            'project_id' => $projectId,
            'entity_type' => 'project',
            'entity_id' => $projectId,
            'model' => GeminiClient::proModel(),
            'temperature' => isset($data['temperature']) ? (float) $data['temperature'] : null,
        ]);

        return $this->success(['output' => $out]);
    }

    public function momDraft(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'tone' => 'sometimes|in:formal,executive,neutral',
            'meeting_title' => 'sometimes|string|nullable',
            'meeting_datetime' => 'sometimes|string|nullable',
            'attendees' => 'sometimes|string|nullable',
            'agenda' => 'sometimes|string|nullable',
            'notes_or_transcript' => 'required|string',
            'temperature' => 'sometimes|numeric|nullable',
        ]);

        $projectId = (int) $data['project_id'];
        $context = $this->contextBuilder->momDraft($projectId, $data);
        $context['tone'] = $data['tone'] ?? 'neutral';

        $out = $this->ai->run('MOM_DRAFT', $context, [
            'project_id' => $projectId,
            'entity_type' => 'project',
            'entity_id' => $projectId,
            'model' => GeminiClient::proModel(),
            'temperature' => isset($data['temperature']) ? (float) $data['temperature'] : null,
        ]);

        return $this->success(['output' => $out]);
    }

    public function momRefine(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'sometimes|integer|exists:projects,id',
            'tone' => 'sometimes|in:formal,executive,neutral',
            'raw_mom' => 'required|string',
            'product_update_context' => 'sometimes|string|nullable',
            'temperature' => 'sometimes|numeric|nullable',
        ]);

        $projectId = isset($data['project_id']) ? (int) $data['project_id'] : null;
        $context = $this->contextBuilder->momRefine($data);
        $context['tone'] = $data['tone'] ?? 'formal';

        $out = $this->ai->run('MOM_REFINE', $context, [
            'project_id' => $projectId,
            'entity_type' => $projectId ? 'project' : null,
            'entity_id' => $projectId,
            'model' => GeminiClient::proModel(),
            'temperature' => isset($data['temperature']) ? (float) $data['temperature'] : null,
        ]);

        return $this->success(['output' => $out]);
    }

    public function momFinalEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'sometimes|integer|exists:projects,id',
            'tone' => 'sometimes|in:formal,executive,neutral',
            'meeting_title' => 'sometimes|string|nullable',
            'date' => 'sometimes|string|nullable',
            'refined_mom' => 'required|string',
            'temperature' => 'sometimes|numeric|nullable',
        ]);

        $projectId = isset($data['project_id']) ? (int) $data['project_id'] : null;
        $context = [
            'tone' => $data['tone'] ?? 'formal',
            'meeting_title' => $data['meeting_title'] ?? null,
            'date' => $data['date'] ?? null,
            'refined_mom' => $data['refined_mom'],
        ];

        $out = $this->ai->run('MOM_FINAL_EMAIL', $context, [
            'project_id' => $projectId,
            'entity_type' => $projectId ? 'project' : null,
            'entity_id' => $projectId,
            'model' => GeminiClient::proModel(),
            'temperature' => isset($data['temperature']) ? (float) $data['temperature'] : null,
        ]);

        return $this->success(['output' => $out]);
    }

    public function hrUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tone' => 'sometimes|in:formal,executive,neutral',
            'date' => 'sometimes|string|nullable',
            'temperature' => 'sometimes|numeric|nullable',
        ]);

        $context = $this->contextBuilder->hrEod($data);
        $context['tone'] = $data['tone'] ?? 'formal';

        $out = $this->ai->run('HR_UPDATE', $context, [
            'project_id' => null,
            'entity_type' => 'org',
            'entity_id' => null,
            'model' => GeminiClient::proModel(),
            'temperature' => isset($data['temperature']) ? (float) $data['temperature'] : null,
        ]);

        return $this->success(['output' => $out]);
    }

    public function validationExecSummary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'report_id' => 'required|integer|exists:validation_reports,id',
            'tone' => 'sometimes|in:formal,executive,neutral',
            'temperature' => 'sometimes|numeric|nullable',
        ]);

        $reportId = (int) $data['report_id'];
        $report = ValidationReport::findOrFail($reportId);

        $context = $this->contextBuilder->validationExecSummary($reportId);
        $context['tone'] = $data['tone'] ?? 'executive';

        $out = $this->ai->run('VALIDATION_EXEC_SUMMARY', $context, [
            'project_id' => $report->project_id,
            'entity_type' => 'validation_report',
            'entity_id' => $reportId,
            'model' => GeminiClient::proModel(),
            'temperature' => isset($data['temperature']) ? (float) $data['temperature'] : null,
        ]);

        return $this->success(['output' => $out]);
    }

    public function rfpRequirementsExtract(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'sometimes|integer|exists:projects,id',
            'source_text' => 'required|string',
            'temperature' => 'sometimes|numeric|nullable',
        ]);

        $projectId = isset($data['project_id']) ? (int) $data['project_id'] : null;
        $context = $this->contextBuilder->rfpExtraction($data['source_text'], $projectId);

        $out = $this->ai->run('RFP_REQUIREMENTS_EXTRACT', $context, [
            'project_id' => $projectId,
            'entity_type' => $projectId ? 'project' : 'rfp_text',
            'entity_id' => $projectId,
            'model' => GeminiClient::fastModel(),
            'temperature' => isset($data['temperature']) ? (float) $data['temperature'] : null,
        ]);

        return $this->success(['output' => $out]);
    }
}

