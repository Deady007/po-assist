<?php

namespace App\Services;

use App\Models\AiPrompt;
use Illuminate\Support\Facades\Cache;

class AiPromptRepository
{
    public const BRAND_GUIDE = <<<TXT
Brand & Tone:
- Professional corporate voice, crisp headings, no emojis.
- Keep concise; avoid fluff and jargon (especially HR outputs).
- Action items must include owner and due_date (use "TBD" if missing).
- Never include markdown code fences or extra formatting noise.
TXT;

    private array $defaults = [];

    public function __construct()
    {
        $this->defaults = $this->defaultPrompts();
    }

    public function getActive(string $taskKey): ?array
    {
        $cacheKey = "ai_prompt_active_{$taskKey}";
        return Cache::remember($cacheKey, 60, function () use ($taskKey) {
            $record = AiPrompt::where('task_key', $taskKey)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();

            if ($record) {
                return $record->toArray();
            }

            return $this->defaults[$taskKey] ?? null;
        });
    }

    private function defaultPrompts(): array
    {
        $emailSchema = [
            'type' => 'object',
            'required' => ['subject', 'body_text'],
            'properties' => [
                'subject' => ['type' => 'string', 'maxLength' => 180],
                'body_text' => ['type' => 'string'],
            ],
        ];

        $momSchema = [
            'type' => 'object',
            'required' => ['mom', 'action_items'],
            'properties' => [
                'mom' => ['type' => 'string'],
                'action_items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['action', 'owner', 'due_date'],
                        'properties' => [
                            'action' => ['type' => 'string'],
                            'owner' => ['type' => 'string'],
                            'due_date' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];

        $refineSchema = [
            'type' => 'object',
            'required' => ['refined_mom'],
            'properties' => [
                'refined_mom' => ['type' => 'string'],
            ],
        ];

        $reqExtractSchema = [
            'type' => 'object',
            'required' => ['requirements'],
            'properties' => [
                'requirements' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['req_code', 'title', 'description', 'priority'],
                        'properties' => [
                            'req_code' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'priority' => ['type' => 'string'],
                            'source' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];

        $execSummarySchema = [
            'type' => 'object',
            'required' => ['summary', 'risks', 'next_steps'],
            'properties' => [
                'summary' => ['type' => 'string'],
                'risks' => ['type' => 'array'],
                'next_steps' => ['type' => 'array'],
            ],
        ];

        return [
            'PRODUCT_UPDATE' => [
                'task_key' => 'PRODUCT_UPDATE',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . "\nGenerate a product update email from context. Output JSON only.",
                'output_schema_json' => $emailSchema,
                'few_shot_examples_json' => null,
            ],
            'MEETING_SCHEDULE' => [
                'task_key' => 'MEETING_SCHEDULE',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . "\nDraft a meeting scheduling email using the provided agenda/logistics. Output JSON only.",
                'output_schema_json' => $emailSchema,
                'few_shot_examples_json' => null,
            ],
            'MOM_DRAFT' => [
                'task_key' => 'MOM_DRAFT',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . "\nCreate draft MoM with action items (owner + due_date). No code fences. JSON only.",
                'output_schema_json' => $momSchema,
                'few_shot_examples_json' => null,
            ],
            'MOM_REFINE' => [
                'task_key' => 'MOM_REFINE',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . "\nRefine MoM for clarity. Normalize action items. Output refined_mom JSON.",
                'output_schema_json' => $refineSchema,
                'few_shot_examples_json' => null,
            ],
            'MOM_FINAL_EMAIL' => [
                'task_key' => 'MOM_FINAL_EMAIL',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . "\nTurn refined MoM into final email with subject/body. JSON only.",
                'output_schema_json' => $emailSchema,
                'few_shot_examples_json' => null,
            ],
            'HR_UPDATE' => [
                'task_key' => 'HR_UPDATE',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . "\nWrite HR end-of-day update in plain language. JSON only.",
                'output_schema_json' => $emailSchema,
                'few_shot_examples_json' => null,
            ],
            'VALIDATION_EXEC_SUMMARY' => [
                'task_key' => 'VALIDATION_EXEC_SUMMARY',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . "\nSummarize validation report for executives. Output summary/risks/next_steps JSON.",
                'output_schema_json' => $execSummarySchema,
                'few_shot_examples_json' => null,
            ],
            'RFP_REQUIREMENTS_EXTRACT' => [
                'task_key' => 'RFP_REQUIREMENTS_EXTRACT',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . "\nExtract requirements from RFP text. Output array of requirement objects. JSON only.",
                'output_schema_json' => $reqExtractSchema,
                'few_shot_examples_json' => null,
            ],
        ];
    }
}
