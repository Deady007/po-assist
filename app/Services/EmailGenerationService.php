<?php

namespace App\Services;

use App\Models\EmailArtifact;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Project;

class EmailGenerationService
{
    public function __construct(
        private AiOrchestratorService $ai,
        private ContextBuilderService $contextBuilder
    ) {}

    /**
     * Defensive conversion so DB columns get strings even if the model returns nested data.
     */
    private function toText(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        // Fallback: stringify arrays/objects to JSON to avoid "array to string" DB errors.
        return json_encode($value);
    }

    public function generateProductUpdateAndStore(
        int $projectId,
        string $tone,
        array $inputForStorage,
        array $promptInputs
    ): EmailArtifact {
        $project = Project::findOrFail($projectId);
        [$template, $scopeType, $clientId] = $this->resolveTemplate('PRODUCT_UPDATE', $project);

        $context = $this->contextBuilder->productUpdate($projectId, [
            'date' => $promptInputs['date'] ?? null,
            'highlights' => $promptInputs['highlights'] ?? null,
        ]);
        $context['user_completed'] = $promptInputs['completed'] ?? null;
        $context['user_in_progress'] = $promptInputs['in_progress'] ?? null;
        $context['user_risks'] = $promptInputs['risks'] ?? null;
        $context['user_review_topics'] = $promptInputs['review_topics'] ?? null;
        $context['tone'] = $tone;

        $out = $this->ai->run('PRODUCT_UPDATE', $context, ['project_id' => $projectId]);

        $artifact = EmailArtifact::create([
            'project_id' => $projectId,
            'client_id' => $clientId,
            'type'       => 'PRODUCT_UPDATE',
            'email_template_id' => $template?->id,
            'scope_type' => $scopeType,
            'tone'       => $tone,
            'generated_by' => auth()->id(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'input_json' => $inputForStorage,
            'subject'    => $this->toText($out['subject'] ?? null),
            'body_text'  => $this->toText($out['body_text'] ?? null),
        ]);

        $this->logEmail($template, $projectId, $clientId, $inputForStorage, $out['subject'] ?? null, $out['body_text'] ?? null);

        return $artifact;
    }

    public function generateMeetingSchedule(
        int $projectId,
        string $tone,
        array $inputForStorage,
        array $promptInputs
    ): EmailArtifact {
        $project = Project::findOrFail($projectId);
        [$template, $scopeType, $clientId] = $this->resolveTemplate('MEETING_SCHEDULE', $project);

        $context = $this->contextBuilder->meetingSchedule($projectId, $promptInputs);
        $context['tone'] = $tone;

        $out = $this->ai->run('MEETING_SCHEDULE', $context, ['project_id' => $projectId]);

        $artifact = EmailArtifact::create([
            'project_id' => $projectId,
            'client_id' => $clientId,
            'type'       => 'MEETING_SCHEDULE',
            'email_template_id' => $template?->id,
            'scope_type' => $scopeType,
            'tone'       => $tone,
            'generated_by' => auth()->id(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'input_json' => $inputForStorage,
            'subject'    => $this->toText($out['subject'] ?? null),
            'body_text'  => $this->toText($out['body_text'] ?? null),
        ]);

        $this->logEmail($template, $projectId, $clientId, $inputForStorage, $out['subject'] ?? null, $out['body_text'] ?? null);

        return $artifact;
    }

    public function generateMomDraft(
        int $projectId,
        array $inputForStorage,
        array $promptInputs,
        string $tone = 'neutral'
    ): EmailArtifact {
        $project = Project::findOrFail($projectId);
        [$template, $scopeType, $clientId] = $this->resolveTemplate('MOM_DRAFT', $project);

        $context = $this->contextBuilder->momDraft($projectId, $promptInputs);
        $context['tone'] = $tone;

        $out = $this->ai->run('MOM_DRAFT', $context, ['project_id' => $projectId]);

        $artifact = EmailArtifact::create([
            'project_id' => $projectId,
            'client_id' => $clientId,
            'type'       => 'MOM_DRAFT',
            'email_template_id' => $template?->id,
            'scope_type' => $scopeType,
            'tone'       => $tone,
            'generated_by' => auth()->id(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'input_json' => $inputForStorage,
            'subject'    => null,
            'body_text'  => $this->toText($out['mom'] ?? null),
        ]);

        $this->logEmail($template, $projectId, $clientId, $inputForStorage, null, $out['mom'] ?? null);

        return $artifact;
    }

    public function refineMom(
        int $projectId,
        string $tone,
        array $inputForStorage,
        array $promptInputs
    ): EmailArtifact {
        $project = Project::findOrFail($projectId);
        [$template, $scopeType, $clientId] = $this->resolveTemplate('MOM_REFINED', $project);

        $context = $this->contextBuilder->momRefine($promptInputs);
        $context['tone'] = $tone;

        $out = $this->ai->run('MOM_REFINE', $context, ['project_id' => $projectId]);

        $artifact = EmailArtifact::create([
            'project_id' => $projectId,
            'client_id' => $clientId,
            'type'       => 'MOM_REFINED',
            'email_template_id' => $template?->id,
            'scope_type' => $scopeType,
            'tone'       => $tone,
            'generated_by' => auth()->id(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'input_json' => $inputForStorage,
            'subject'    => null,
            'body_text'  => $this->toText($out['refined_mom'] ?? null),
        ]);

        $this->logEmail($template, $projectId, $clientId, $inputForStorage, null, $out['refined_mom'] ?? null);

        return $artifact;
    }

    public function generateMomFinalEmail(
        int $projectId,
        string $tone,
        array $inputForStorage,
        array $promptInputs
    ): EmailArtifact {
        $project = Project::findOrFail($projectId);
        [$template, $scopeType, $clientId] = $this->resolveTemplate('MOM_FINAL', $project);

        $context = [
            'tone' => $tone,
            'meeting_title' => $promptInputs['meeting_title'] ?? null,
            'date' => $promptInputs['date'] ?? null,
            'refined_mom' => $promptInputs['refined_mom'] ?? '',
        ];
        $out = $this->ai->run('MOM_FINAL_EMAIL', $context, ['project_id' => $projectId]);

        $artifact = EmailArtifact::create([
            'project_id' => $projectId,
            'client_id' => $clientId,
            'type'       => 'MOM_FINAL',
            'email_template_id' => $template?->id,
            'scope_type' => $scopeType,
            'tone'       => $tone,
            'generated_by' => auth()->id(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'input_json' => $inputForStorage,
            'subject'    => $this->toText($out['subject'] ?? null),
            'body_text'  => $this->toText($out['body_text'] ?? null),
        ]);

        $this->logEmail($template, $projectId, $clientId, $inputForStorage, $out['subject'] ?? null, $out['body_text'] ?? null);

        return $artifact;
    }

    public function generateHrUpdate(
        int $projectId,
        string $tone,
        array $inputForStorage,
        array $promptInputs
    ): EmailArtifact {
        $project = Project::findOrFail($projectId);
        [$template, $scopeType, $clientId] = $this->resolveTemplate('HR_UPDATE', $project);

        $context = $this->contextBuilder->hrEod($promptInputs);
        $context['tone'] = $tone;
        $out = $this->ai->run('HR_UPDATE', $context, ['project_id' => $projectId]);

        $artifact = EmailArtifact::create([
            'project_id' => $projectId,
            'client_id' => $clientId,
            'type'       => 'HR_UPDATE',
            'email_template_id' => $template?->id,
            'scope_type' => $scopeType,
            'tone'       => $tone,
            'generated_by' => auth()->id(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'input_json' => $inputForStorage,
            'subject'    => $this->toText($out['subject'] ?? null),
            'body_text'  => $this->toText($out['body_text'] ?? null),
        ]);

        $this->logEmail($template, $projectId, $clientId, $inputForStorage, $out['subject'] ?? null, $out['body_text'] ?? null);

        return $artifact;
    }

    private function resolveTemplate(string $code, Project $project): array
    {
        $template = EmailTemplate::where('code', $code)
            ->where('scope_type', 'project')
            ->where('scope_id', $project->id)
            ->first();

        if (!$template && $project->client_id) {
            $template = EmailTemplate::where('code', $code)
                ->where('scope_type', 'client')
                ->where('scope_id', $project->client_id)
                ->first();
        }

        if (!$template) {
            $template = EmailTemplate::where('code', $code)
                ->where('scope_type', 'global')
                ->first();
        }

        if (!$template) {
            $template = EmailTemplate::create([
                'code' => $code,
                'name' => $code,
                'scope_type' => 'global',
                'scope_id' => null,
            ]);
        }

        $scopeType = $template->scope_type;
        $clientId = $scopeType === 'client' ? $template->scope_id : $project->client_id;

        return [$template, $scopeType, $clientId];
    }

    private function logEmail(
        EmailTemplate $template,
        int $projectId,
        ?int $clientId,
        array $inputForStorage,
        ?string $subject,
        ?string $body
    ): void {
        EmailLog::create([
            'email_template_id' => $template->id,
            'project_id' => $projectId,
            'client_id' => $clientId,
            'subject' => $this->toText($subject),
            'body' => $this->toText($body),
            'variables_json' => $inputForStorage,
            'generated_at' => now(),
            'generated_by' => auth()->id(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }
}
