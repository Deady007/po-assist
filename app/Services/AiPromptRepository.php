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
- Output must be a single JSON object matching the provided schema. No extra keys.
TXT;

    private array $defaults = [];

    public function __construct()
    {
        $this->defaults = $this->defaultPrompts();
    }

    public function getDefaultPrompts(): array
    {
        return $this->defaults;
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
        $actionItemSchema = [
            'type' => 'object',
            'required' => ['action', 'owner', 'due_date'],
            'properties' => [
                'action' => ['type' => 'string', 'minLength' => 1],
                'owner' => ['type' => 'string', 'minLength' => 1],
                'due_date' => ['type' => 'string', 'minLength' => 1],
            ],
        ];

        $actionItemsSchema = [
            'type' => 'array',
            'items' => $actionItemSchema,
        ];

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
                'action_items' => $actionItemsSchema,
            ],
        ];

        $refineSchema = [
            'type' => 'object',
            'required' => ['refined_mom', 'action_items'],
            'properties' => [
                'refined_mom' => ['type' => 'string'],
                'action_items' => $actionItemsSchema,
            ],
        ];

        $momFinalEmailSchema = [
            'type' => 'object',
            'required' => ['subject', 'body_text', 'action_items'],
            'properties' => [
                'subject' => ['type' => 'string', 'maxLength' => 180],
                'body_text' => ['type' => 'string'],
                'action_items' => $actionItemsSchema,
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
                'risks' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'next_steps' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];

        return [
            'PRODUCT_UPDATE' => [
                'task_key' => 'PRODUCT_UPDATE',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . <<<TXT
Task: Generate a product update email from the provided context.

Email body requirements (plain text):
- Start with a short summary (1-2 lines).
- Sections in this exact order:
  1) Highlights (if provided)
  2) Completed (bullets)
  3) In Progress (bullets)
  4) Risks / Blockers (bullets; write "None" if none)
  5) Upcoming / Asks (bullets; can be TBD)
- Prefer database context over user notes when both exist, but incorporate user notes when present.

Tone handling:
- formal: complete sentences, professional.
- executive: shorter, outcomes + risks, minimal detail.
- neutral: direct, simple.
TXT,
                'output_schema_json' => $emailSchema,
                'few_shot_examples_json' => null,
            ],
            'MEETING_SCHEDULE' => [
                'task_key' => 'MEETING_SCHEDULE',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . <<<TXT
Task: Draft a meeting scheduling email using the provided agenda/logistics.

Email body requirements (plain text):
- Include a clear meeting details block:
  Title, Date/Time, Duration, Location/Link (use "TBD" if missing).
- Include an Agenda section with 3-6 bullets (use agenda seeds/warnings if topics are not provided).
- End with a confirmation request and thank you.
TXT,
                'output_schema_json' => $emailSchema,
                'few_shot_examples_json' => null,
            ],
            'MOM_DRAFT' => [
                'task_key' => 'MOM_DRAFT',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . <<<TXT
Task: Create a draft Minutes of Meeting (MoM) from notes/transcript and project context.

Output requirements:
- Produce both:
  - mom: refined plain-text MoM with crisp headings (no markdown).
  - action_items: structured list extracted from the notes (each item must include action, owner, due_date).
- If owner/due_date is unknown, set the value to "TBD".
- Ensure action_items is not empty if the notes contain any actions.
TXT,
                'output_schema_json' => $momSchema,
                'few_shot_examples_json' => null,
            ],
            'MOM_REFINE' => [
                'task_key' => 'MOM_REFINE',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . <<<TXT
Task: Refine the provided raw MoM for clarity and consistency.

Output requirements:
- refined_mom: rewrite to be clearer, remove duplication, and standardize headings.
- action_items: extract/normalize action items with required fields (action, owner, due_date).
- If owner/due_date is missing, use "TBD".
TXT,
                'output_schema_json' => $refineSchema,
                'few_shot_examples_json' => null,
            ],
            'MOM_FINAL_EMAIL' => [
                'task_key' => 'MOM_FINAL_EMAIL',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . <<<TXT
Task: Turn refined MoM into a client-ready final email.

Output requirements:
- subject: concise and specific.
- body_text: plain-text email with sections: Summary, Decisions (if any), Action Items, Next Steps.
- action_items: structured list with owner and due_date; use "TBD" when unknown.
TXT,
                'output_schema_json' => $momFinalEmailSchema,
                'few_shot_examples_json' => null,
            ],
            'HR_UPDATE' => [
                'task_key' => 'HR_UPDATE',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . <<<TXT
Task: Write an HR end-of-day update in plain language for leadership/people ops.

Email body requirements (plain text):
- Sections: Overall status, Project snapshots (short), Risks, Tomorrow plan.
- Avoid technical jargon; explain risks in business terms.
TXT,
                'output_schema_json' => $emailSchema,
                'few_shot_examples_json' => null,
            ],
            'VALIDATION_EXEC_SUMMARY' => [
                'task_key' => 'VALIDATION_EXEC_SUMMARY',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . <<<TXT
Task: Summarize the validation report for executives.

Output requirements:
- summary: 1 short paragraph (3-6 sentences max).
- risks: 3-7 bullets as strings (each a single sentence).
- next_steps: 3-7 bullets as strings (each a single sentence, with owner/due date if available; otherwise "TBD").
TXT,
                'output_schema_json' => $execSummarySchema,
                'few_shot_examples_json' => null,
            ],
            'RFP_REQUIREMENTS_EXTRACT' => [
                'task_key' => 'RFP_REQUIREMENTS_EXTRACT',
                'version' => 1,
                'system_instructions' => self::BRAND_GUIDE . <<<TXT
Task: Extract clear, implementation-ready requirements from the provided RFP text.

Output requirements:
- requirements: array of objects with req_code/title/description/priority.
- req_code: stable format like "RFP-001", "RFP-002", ...
- priority: choose one of HIGH, MEDIUM, LOW (infer from wording; default MEDIUM).
- Keep descriptions crisp and testable (avoid vague phrasing).
TXT,
                'output_schema_json' => $reqExtractSchema,
                'few_shot_examples_json' => null,
            ],
        ];
    }
}
