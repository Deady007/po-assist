<?php

namespace App\Services;

use App\Models\AiRun;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AiOrchestratorService
{
    public function __construct(
        private AiPromptRepository $prompts,
        private GeminiClient $gemini
    ) {
    }

    /**
     * @param string $taskKey
     * @param array $context Context payload (auto-built + user inputs)
     * @param array $options model, temperature, project_id, entity_type, entity_id
     */
    public function run(string $taskKey, array $context, array $options = []): array
    {
        $promptMeta = $this->prompts->getActive($taskKey);
        if (!$promptMeta) {
            throw new \RuntimeException("Prompt not configured for {$taskKey}");
        }

        $schema = $promptMeta['output_schema_json'] ?? [];
        $model = $options['model'] ?? GeminiClient::proModel();
        $temperature = $options['temperature'] ?? (float) config('services.gemini.temperature', 0.2);
        $contextHash = hash('sha256', json_encode($context));

        // Optional caching within 10 minutes
        $cacheMinutes = (int) config('services.gemini.cache_minutes', 10);
        if ($cacheMinutes > 0) {
            $cached = AiRun::where('task_key', $taskKey)
                ->where('success', true)
                ->where('input_context_json->context_hash', $contextHash)
                ->where('created_at', '>=', now()->subMinutes($cacheMinutes))
                ->orderByDesc('id')
                ->first();
            if ($cached && $cached->parsed_output_json) {
                return $cached->parsed_output_json;
            }
        }

        $finalPrompt = $this->buildPrompt($promptMeta, $context, $schema);
        $start = microtime(true);
        $outputText = '';
        $parsed = null;
        $error = null;

        try {
            $parsed = $this->gemini->generateJsonStrict($finalPrompt, $schema, $model, $temperature);
            $outputText = json_encode($parsed);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::warning('AI orchestration failed', ['task' => $taskKey, 'error' => $error]);
        }

        $latencyMs = (int) floor((microtime(true) - $start) * 1000);

        AiRun::create([
            'task_key' => $taskKey,
            'prompt_version' => $promptMeta['version'] ?? null,
            'project_id' => $options['project_id'] ?? null,
            'entity_type' => $options['entity_type'] ?? null,
            'entity_id' => $options['entity_id'] ?? null,
            'input_context_json' => array_merge($context, ['context_hash' => $contextHash]),
            'model_name' => $model,
            'temperature' => $temperature,
            'latency_ms' => $latencyMs,
            'success' => $error === null,
            'error_message' => $error,
            'raw_output_text' => $outputText ?: null,
            'parsed_output_json' => $parsed,
        ]);

        if ($error !== null) {
            throw new \RuntimeException($error);
        }

        return $parsed ?? [];
    }

    private function buildPrompt(array $promptMeta, array $context, array $schema): string
    {
        $instructions = $promptMeta['system_instructions'] ?? '';
        $schemaText = json_encode($schema, JSON_PRETTY_PRINT);
        $ctxText = json_encode($context, JSON_PRETTY_PRINT);
        $examples = '';
        if (!empty($promptMeta['few_shot_examples_json'])) {
            $examples = "\nFew-shot examples:\n" . json_encode($promptMeta['few_shot_examples_json'], JSON_PRETTY_PRINT);
        }

        return <<<PROMPT
{$instructions}
Schema (required): {$schemaText}
Context (JSON):
{$ctxText}
{$examples}
Output rules:
- Respond with ONLY valid JSON matching schema. No markdown, no code fences.
- Fill required fields; action items must include owner and due_date (use "TBD" if not provided).
- Keep concise, corporate style.
PROMPT;
    }
}
