<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiClient
{
    /**
     * Calls Gemini generateContent and returns the raw text produced by the model.
     *
     * @throws \RuntimeException on API errors or unexpected responses
     */
    public function generateText(string $prompt): string
    {
        $key = config('services.gemini.key');
        $model = config('services.gemini.model');
        $temperature = config('services.gemini.temperature');
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
                'temperature' => $temperature,
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

        // Some models wrap JSON in code fences; strip them cautiously.
        if (preg_match('/```(?:json)?\\s*(\\{.*?\\})\\s*```/s', $rawTrimmed, $matches)) {
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
