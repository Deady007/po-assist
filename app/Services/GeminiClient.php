<?php

namespace App\Services;

use App\Exceptions\AiJsonOutputException;
use Illuminate\Support\Facades\Http;
use App\Services\AiSchemaValidator;

class GeminiClient
{
    private const DEFAULT_FAST_MODEL = 'gemini-2.5-pro';
    private const DEFAULT_PRO_MODEL = 'gemini-2.5-pro';

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
    public function generateJsonStrict(string $prompt, ?array $schema = null, ?string $model = null, ?float $temperature = null): array
    {
        $result = $this->generateJsonStrictDetailed($prompt, $schema, $model, $temperature);
        return $result['parsed'];
    }

    /**
     * Strict JSON generation that also returns raw output and token usage.
     *
     * @return array{parsed: array, raw_output_text: string, prompt_tokens: ?int, output_tokens: ?int, repair_used: bool}
     */
    public function generateJsonStrictDetailed(string $prompt, ?array $schema = null, ?string $model = null, ?float $temperature = null): array
    {
        $modelToUse = $model ?: config('services.gemini.model');

        $attempt1 = $this->generateTextWithModelDetailed($prompt, $modelToUse, $temperature);
        $raw1 = $attempt1['text'];
        $parsed1 = $this->tryParseJson($raw1);

        $errors1 = $schema ? app(AiSchemaValidator::class)->validate($schema, $parsed1 ?? []) : [];
        if ($parsed1 !== null && empty($errors1)) {
            return [
                'parsed' => $parsed1,
                'raw_output_text' => $raw1,
                'prompt_tokens' => $attempt1['prompt_tokens'],
                'output_tokens' => $attempt1['output_tokens'],
                'repair_used' => false,
            ];
        }

        // Attempt repair once.
        $repairPrompt = $this->buildRepairPrompt($raw1, $schema);
        try {
            $attempt2 = $this->generateTextWithModelDetailed($repairPrompt, $modelToUse, $temperature);
        } catch (\Throwable $e) {
            throw new AiJsonOutputException(
                'Failed to obtain valid JSON from Gemini (repair call failed): ' . $e->getMessage(),
                $raw1,
                $attempt1['prompt_tokens'],
                $attempt1['output_tokens'],
                true
            );
        }

        $raw2 = $attempt2['text'];
        $parsed2 = $this->tryParseJson($raw2);
        $errors2 = $schema ? app(AiSchemaValidator::class)->validate($schema, $parsed2 ?? []) : [];

        $rawCombined = $raw1 . "\n\n--- REPAIR ATTEMPT ---\n\n" . $raw2;
        $promptTokens = $this->sumTokens($attempt1['prompt_tokens'], $attempt2['prompt_tokens']);
        $outputTokens = $this->sumTokens($attempt1['output_tokens'], $attempt2['output_tokens']);

        if ($parsed2 === null || !empty($errors2)) {
            throw new AiJsonOutputException(
                'Failed to obtain valid JSON from Gemini: ' . implode('; ', $errors2 ?: $errors1),
                $rawCombined,
                $promptTokens,
                $outputTokens,
                true
            );
        }

        return [
            'parsed' => $parsed2,
            'raw_output_text' => $rawCombined,
            'prompt_tokens' => $promptTokens,
            'output_tokens' => $outputTokens,
            'repair_used' => true,
        ];
    }

    /**
     * Internal: generate text with explicit model and optional temperature.
     */
    public function generateTextWithModel(string $prompt, ?string $model = null, ?float $temperature = null): string
    {
        $result = $this->generateTextWithModelDetailed($prompt, $model, $temperature);
        return $result['text'];
    }

    /**
     * Internal: generate text with explicit model and optional temperature, returning token usage.
     *
     * @return array{text: string, prompt_tokens: ?int, output_tokens: ?int}
     */
    public function generateTextWithModelDetailed(string $prompt, ?string $model = null, ?float $temperature = null): array
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

        $makeRequest = function (int $maxOutputTokens) use ($http, $url, $prompt, $temperature, $baseTemp) {
            $response = $http->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $temperature ?? $baseTemp,
                    'maxOutputTokens' => $maxOutputTokens,
                ],
            ]);

            if (!$response->ok()) {
                throw new \RuntimeException(
                    "Gemini API error: HTTP {$response->status()} - {$response->body()}"
                );
            }

            $json = $response->json();
            if (!is_array($json)) {
                throw new \RuntimeException('Gemini API returned a non-JSON response.');
            }

            return $json;
        };

        $json1 = $makeRequest($maxTokens);

        [$promptTokens1, $outputTokens1, $totalTokens1, $thoughtsTokens1] = $this->extractTokenCounts($json1);
        $finishReason1 = $this->extractFinishReason($json1);
        $text1 = $this->extractText($json1);

        // Retry once if Gemini returned no visible content due to MAX_TOKENS.
        if ((!is_string($text1) || trim($text1) === '') && $finishReason1 === 'MAX_TOKENS') {
            $retryMaxTokens = min(4096, max(2048, (int) ($maxTokens * 2)));

            if ($retryMaxTokens > $maxTokens) {
                $json2 = $makeRequest($retryMaxTokens);
                [$promptTokens2, $outputTokens2, $totalTokens2, $thoughtsTokens2] = $this->extractTokenCounts($json2);
                $finishReason2 = $this->extractFinishReason($json2);
                $text2 = $this->extractText($json2);

                if (is_string($text2) && trim($text2) !== '') {
                    return [
                        'text' => $text2,
                        'prompt_tokens' => $this->sumTokens($promptTokens1, $promptTokens2),
                        'output_tokens' => $this->sumTokens($outputTokens1, $outputTokens2),
                    ];
                }

                $rawCombined = $this->jsonForLog($json1)
                    . "\n\n--- RETRY maxOutputTokens={$retryMaxTokens} (finishReason={$finishReason2}) ---\n\n"
                    . $this->jsonForLog($json2);

                $meta = $this->formatGeminiMeta($finishReason2, $promptTokens2, $totalTokens2, $thoughtsTokens2);
                throw new AiJsonOutputException(
                    'Gemini returned no usable text output after retry' . ($meta ? " ({$meta})" : '') . '.',
                    $rawCombined,
                    $this->sumTokens($promptTokens1, $promptTokens2),
                    $this->sumTokens($outputTokens1, $outputTokens2),
                    false
                );
            }
        }

        if (!is_string($text1) || trim($text1) === '') {
            $meta = $this->formatGeminiMeta($finishReason1, $promptTokens1, $totalTokens1, $thoughtsTokens1);
            $hint = $finishReason1 === 'MAX_TOKENS'
                ? ' Increase GEMINI_MAX_TOKENS or reduce context size.'
                : '';

            throw new AiJsonOutputException(
                'Gemini returned no usable text output' . ($meta ? " ({$meta})" : '') . '.' . $hint,
                $this->jsonForLog($json1),
                $promptTokens1,
                $outputTokens1,
                false
            );
        }

        return [
            'text' => $text1,
            'prompt_tokens' => $promptTokens1,
            'output_tokens' => $outputTokens1,
        ];
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

    private function extractUsageInt(mixed $usage, array $keys): ?int
    {
        if (!is_array($usage)) {
            return null;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $usage) && is_numeric($usage[$key])) {
                return (int) $usage[$key];
            }
        }

        return null;
    }

    private function sumTokens(?int $a, ?int $b): ?int
    {
        if (is_null($a) && is_null($b)) {
            return null;
        }
        return ((int) ($a ?? 0)) + ((int) ($b ?? 0));
    }

    private function extractText(array $json): ?string
    {
        $candidates = $json['candidates'] ?? null;
        if (!is_array($candidates)) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $parts = $candidate['content']['parts'] ?? null;
            if (is_array($parts)) {
                $texts = [];
                foreach ($parts as $part) {
                    if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                        $texts[] = $part['text'];
                    }
                }
                $joined = implode('', $texts);
                if (trim($joined) !== '') {
                    return $joined;
                }
            }

            $alt = $candidate['content']['text'] ?? null;
            if (is_string($alt) && trim($alt) !== '') {
                return $alt;
            }
        }

        return null;
    }

    private function extractFinishReason(array $json): ?string
    {
        $candidate = $json['candidates'][0] ?? null;
        if (!is_array($candidate)) {
            return null;
        }

        $reason = $candidate['finishReason'] ?? null;
        return is_string($reason) ? $reason : null;
    }

    /**
     * @return array{0:?int,1:?int,2:?int,3:?int} promptTokens, outputTokens, totalTokens, thoughtsTokens
     */
    private function extractTokenCounts(array $json): array
    {
        $usage = $json['usageMetadata'] ?? null;
        $promptTokens = $this->extractUsageInt($usage, ['promptTokenCount']);
        $totalTokens = $this->extractUsageInt($usage, ['totalTokenCount']);
        $thoughtsTokens = $this->extractUsageInt($usage, ['thoughtsTokenCount']);

        $outputTokens = $this->extractUsageInt($usage, ['candidatesTokenCount', 'completionTokenCount', 'outputTokenCount']);
        if ($outputTokens === null && $promptTokens !== null && $totalTokens !== null) {
            $outputTokens = max(0, $totalTokens - $promptTokens);
        }

        return [$promptTokens, $outputTokens, $totalTokens, $thoughtsTokens];
    }

    private function formatGeminiMeta(?string $finishReason, ?int $promptTokens, ?int $totalTokens, ?int $thoughtsTokens): string
    {
        $parts = [];
        if ($finishReason) $parts[] = "finishReason={$finishReason}";
        if (!is_null($promptTokens)) $parts[] = "promptTokenCount={$promptTokens}";
        if (!is_null($totalTokens)) $parts[] = "totalTokenCount={$totalTokens}";
        if (!is_null($thoughtsTokens)) $parts[] = "thoughtsTokenCount={$thoughtsTokens}";
        return implode(', ', $parts);
    }

    private function jsonForLog(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return is_string($encoded) ? $encoded : var_export($value, true);
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
