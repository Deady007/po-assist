<?php

namespace App\Services;

use App\Models\EmailArtifact;

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

        return EmailArtifact::create([
            'project_id' => $projectId,
            'type'       => 'PRODUCT_UPDATE',
            'tone'       => $tone,
            'input_json' => $inputForStorage,
            'subject'    => $this->toText($out['subject'] ?? null),
            'body_text'  => $this->toText($out['body_text'] ?? null),
        ]);
    }

    public function generateMeetingSchedule(
        int $projectId,
        string $tone,
        array $inputForStorage,
        array $promptInputs
    ): EmailArtifact {
        $context = $this->contextBuilder->meetingSchedule($projectId, $promptInputs);
        $context['tone'] = $tone;

        $out = $this->ai->run('MEETING_SCHEDULE', $context, ['project_id' => $projectId]);

        return EmailArtifact::create([
            'project_id' => $projectId,
            'type'       => 'MEETING_SCHEDULE',
            'tone'       => $tone,
            'input_json' => $inputForStorage,
            'subject'    => $this->toText($out['subject'] ?? null),
            'body_text'  => $this->toText($out['body_text'] ?? null),
        ]);
    }

    public function generateMomDraft(
        int $projectId,
        array $inputForStorage,
        array $promptInputs,
        string $tone = 'neutral'
    ): EmailArtifact {
        $context = $this->contextBuilder->momDraft($projectId, $promptInputs);
        $context['tone'] = $tone;

        $out = $this->ai->run('MOM_DRAFT', $context, ['project_id' => $projectId]);

        return EmailArtifact::create([
            'project_id' => $projectId,
            'type'       => 'MOM_DRAFT',
            'tone'       => $tone,
            'input_json' => $inputForStorage,
            'subject'    => null,
            'body_text'  => $this->toText($out['mom'] ?? null),
        ]);
    }

    public function refineMom(
        int $projectId,
        string $tone,
        array $inputForStorage,
        array $promptInputs
    ): EmailArtifact {
        $context = $this->contextBuilder->momRefine($promptInputs);
        $context['tone'] = $tone;

        $out = $this->ai->run('MOM_REFINE', $context, ['project_id' => $projectId]);

        return EmailArtifact::create([
            'project_id' => $projectId,
            'type'       => 'MOM_REFINED',
            'tone'       => $tone,
            'input_json' => $inputForStorage,
            'subject'    => null,
            'body_text'  => $this->toText($out['refined_mom'] ?? null),
        ]);
    }

    public function generateMomFinalEmail(
        int $projectId,
        string $tone,
        array $inputForStorage,
        array $promptInputs
    ): EmailArtifact {
        $context = [
            'tone' => $tone,
            'meeting_title' => $promptInputs['meeting_title'] ?? null,
            'date' => $promptInputs['date'] ?? null,
            'refined_mom' => $promptInputs['refined_mom'] ?? '',
        ];
        $out = $this->ai->run('MOM_FINAL_EMAIL', $context, ['project_id' => $projectId]);

        return EmailArtifact::create([
            'project_id' => $projectId,
            'type'       => 'MOM_FINAL',
            'tone'       => $tone,
            'input_json' => $inputForStorage,
            'subject'    => $this->toText($out['subject'] ?? null),
            'body_text'  => $this->toText($out['body_text'] ?? null),
        ]);
    }

    public function generateHrUpdate(
        int $projectId,
        string $tone,
        array $inputForStorage,
        array $promptInputs
    ): EmailArtifact {
        $context = $this->contextBuilder->hrEod($promptInputs);
        $context['tone'] = $tone;
        $out = $this->ai->run('HR_UPDATE', $context, ['project_id' => $projectId]);

        return EmailArtifact::create([
            'project_id' => $projectId,
            'type'       => 'HR_UPDATE',
            'tone'       => $tone,
            'input_json' => $inputForStorage,
            'subject'    => $this->toText($out['subject'] ?? null),
            'body_text'  => $this->toText($out['body_text'] ?? null),
        ]);
    }
}
