<?php

namespace App\Services;

class EmailPromptFactory
{
    private static function baseJsonRule(): string
    {
        return <<<TXT
Return ONLY a raw JSON object with exactly these keys:
- subject (string)
- body_text (string)
Do not include "Subject:" prefixes, markdown, code fences, salutations, or any extra text.
Output must be valid JSON and must start with { and end with }.
TXT;
    }

    public static function productUpdate(array $in): string
    {
        $tone = $in['tone'] ?? 'formal';

        return <<<PROMPT
You are a senior Product Owner writing a {$tone} stakeholder email.

Context:
Project: {$in['project_name']}
Date: {$in['date']}

Completed (bullets):
{$in['completed']}

In Progress (bullets):
{$in['in_progress']}

Risks/Blockers:
{$in['risks']}

Topics for review meeting:
{$in['review_topics']}

Rules:
- Use clear section headers.
- Keep it crisp and professional.
- End with a short call-to-action to review before the meeting.

{self::baseJsonRule()}
PROMPT;
    }
}
