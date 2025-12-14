<?php

namespace App\Services;

use App\Models\EmailArtifact;

class EmailGenerationService
{
    public function __construct(private GeminiClient $gemini) {}

    public function generateProductUpdateAndStore(
        int $projectId,
        string $tone,
        array $inputForStorage,
        array $promptInputs
    ): EmailArtifact {
        $prompt = EmailPromptFactory::productUpdate($promptInputs);
        $out = $this->gemini->generateJson($prompt);

        return EmailArtifact::create([
            'project_id' => $projectId,
            'type'       => 'PRODUCT_UPDATE',
            'tone'       => $tone,
            'input_json' => $inputForStorage,
            'subject'    => $out['subject'] ?? null,
            'body_text'  => $out['body_text'] ?? null,
        ]);
    }
}
