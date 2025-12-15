<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Services\AiSchemaValidator;

class GeminiClient
{
    private const DEFAULT_FAST_MODEL = 'gemini-1.5-flash';
    private const DEFAULT_PRO_MODEL = 'gemini-2.5-FLASH';

    /**
     * Calls Gemini generateContent and returns the raw text produced by the model.
     *
     * @throws \RuntimeException on API errors or unexpected responses
     */
    public function generateText(string $prompt): string
    {
        return $this->generateTextWithModel($prompt, config('services.gemini.model'));
    }

    /**
     * Strict JSON generation with one-shot repair.
     *
     * @param array|null $schema json-schema-like array (required keys, properties)
     * @return array
     */
    public function generateJsonStrict(string $prompt, ?array $schema = null, ?string $model = null, float $temperature = null): array
    {
        $modelToUse = $model ?: config('services.gemini.model');
        $raw = $this->generateTextWithModel($prompt, $modelToUse, $temperature);
        $parsed = $this->tryParseJson($raw);

        $errors = $schema ? app(AiSchemaValidator::class)->validate($schema, $parsed ?? []) : [];
        if ($parsed !== null && empty($errors)) {
            return $parsed;
        }

        // Attempt repair once.
        $repairPrompt = $this->buildRepairPrompt($raw, $schema);
        $repairedRaw = $this->generateTextWithModel($repairPrompt, $modelToUse, $temperature);
        $repairedParsed = $this->tryParseJson($repairedRaw);
        $repairErrors = $schema ? app(AiSchemaValidator::class)->validate($schema, $repairedParsed ?? []) : [];

        if ($repairedParsed === null || !empty($repairErrors)) {
            throw new \RuntimeException('Failed to obtain valid JSON from Gemini: ' . implode('; ', $errors ?: $repairErrors));
        }

        return $repairedParsed;
    }

    /**
     * Internal: generate text with explicit model and optional temperature.
     */
    public function generateTextWithModel(string $prompt, ?string $model = null, ?float $temperature = null): string
    {
        $key = config('services.gemini.key');
        $model = $model ?: config('services.gemini.model');
        $baseTemp = config('services.gemini.temperature');
        $maxTokens = config('services.gemini.max_tokens');
        $verify = config('services.gemini.verify', true);
        $caBundle = config('services.gemini.ca_bundle');

        if (!$key) {
            throw new \RuntimeException('GEMINI_API_KEY is not set.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $http = Http::timeout(30);

        // Allow configuring SSL verification to handle local Windows certificate issues.
        $http = $http->withOptions([
            'verify' => $caBundle ?: $verify,
        ]);

        $response = $http->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $temperature ?? $baseTemp,
                'maxOutputTokens' => $maxTokens,
            ],
        ]);

        if (!$response->ok()) {
            throw new \RuntimeException(
                "Gemini API error: HTTP {$response->status()} - {$response->body()}"
            );
        }

        $json = $response->json();

        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!is_string($text) || trim($text) === '') {
            throw new \RuntimeException(
                'Gemini API returned an unexpected response shape: ' . json_encode($json)
            );
        }

        return $text;
    }

    private function tryParseJson(string $raw): ?array
    {
        $rawTrimmed = trim($raw);

        if (preg_match('/```(?:json)?\\s*(\\{.*\\})\\s*```/s', $rawTrimmed, $matches)) {
            $rawTrimmed = $matches[1];
        }

        $decoded = json_decode($rawTrimmed, true);

        if (!is_array($decoded)) {
            $firstBrace = strpos($rawTrimmed, '{');
            $lastBrace = strrpos($rawTrimmed, '}');
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $candidate = substr($rawTrimmed, $firstBrace, $lastBrace - $firstBrace + 1);
                $decoded = json_decode($candidate, true);
            }
        }

        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function buildRepairPrompt(string $rawOutput, ?array $schema = null): string
    {
        $schemaText = $schema ? json_encode($schema, JSON_PRETTY_PRINT) : 'Same schema as requested previously.';
        return <<<PROMPT
Your previous reply was invalid JSON. Fix it now.
Rules:
- Output ONLY valid JSON. No code fences. No markdown.
- Must match this schema (required keys enforced):
{$schemaText}
Original output:
{$rawOutput}
PROMPT;
    }

    public static function fastModel(): string
    {
        return config('services.gemini.model_fast', self::DEFAULT_FAST_MODEL);
    }

    public static function proModel(): string
    {
        return config('services.gemini.model_pro', self::DEFAULT_PRO_MODEL);
    }

    /**
     * Convenience method for strict JSON responses.
     * Ensures the model returns a valid JSON object.
     *
     * @return array<string,mixed>
     */
    public function generateJson(string $prompt): array
    {
        $raw = $this->generateText($prompt);
        $rawTrimmed = trim($raw);

        // Some models wrap JSON in code fences; strip them (greedy to capture full object).
        if (preg_match('/```(?:json)?\\s*(\\{.*\\})\\s*```/s', $rawTrimmed, $matches)) {
            $rawTrimmed = $matches[1];
        }

        $decoded = json_decode($rawTrimmed, true);

        // If the model returned extra prose, try to extract the first JSON object.
        if (!is_array($decoded)) {
            $firstBrace = strpos($rawTrimmed, '{');
            $lastBrace = strrpos($rawTrimmed, '}');
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $candidate = substr($rawTrimmed, $firstBrace, $lastBrace - $firstBrace + 1);
                $decoded = json_decode($candidate, true);
            }
        }

        // If still not decoded, try a lenient pass by stripping trailing commas before } or ].
        if (!is_array($decoded)) {
            $sanitized = preg_replace('/,(\s*[}\]])/', '$1', $rawTrimmed);
            $decoded = json_decode($sanitized ?? $rawTrimmed, true);

            if (!is_array($decoded)) {
                $firstBrace = strpos($sanitized, '{');
                $lastBrace = strrpos($sanitized, '}');
                if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                    $candidate = substr($sanitized, $firstBrace, $lastBrace - $firstBrace + 1);
                    $decoded = json_decode($candidate, true);
                }
            }
        }

        // Fallback: gracefully parse common "Subject: ..." email-style responses.
        if (!is_array($decoded) && preg_match('/^Subject:\\s*(.+)$/mi', $rawTrimmed, $m, PREG_OFFSET_CAPTURE)) {
            $subjectLine = $m[0];
            $subject = trim($m[1][0]);
            $bodyStart = $subjectLine[1] + strlen($subjectLine[0]);
            $body = trim(substr($rawTrimmed, $bodyStart));

            if ($subject !== '' && $body !== '') {
                $decoded = [
                    'subject' => $subject,
                    'body_text' => $body,
                ];
            }
        }

        if (!is_array($decoded)) {
            // Common issue: model wraps JSON in extra text. We fail fast to enforce discipline.
            throw new \RuntimeException("Model did not return valid JSON. Raw output:\n{$rawTrimmed}");
        }

        return $decoded;
    }
}
