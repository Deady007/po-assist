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

    /**
     * Helper to require a strict JSON object with custom keys.
     */
    private static function baseJsonRuleForKeys(array $keys): string
    {
        $list = implode("\n- ", $keys);

        return <<<TXT
Return ONLY a raw JSON object with exactly these keys:
- {$list}
Do not include markdown, code fences, salutations, or any extra text.
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

    public static function meetingSchedule(array $in): string
    {
        $tone = $in['tone'] ?? 'formal';
        $project = $in['project_name'] ?? 'N/A';

        return <<<PROMPT
You are drafting a {$tone} meeting scheduling email for stakeholders.

Project: {$project}
Meeting title: {$in['meeting_title']}
Date/time: {$in['meeting_datetime']} (duration: {$in['duration']})
Attendees: {$in['attendees']}
Agenda topics: {$in['agenda_topics']}
Location / Link: {$in['meeting_location_or_link']}

Guidelines:
- Provide a concise subject.
- Summarize the meeting purpose, agenda, and logistics.
- Offer a clear confirmation / RSVP call-to-action.
- Keep the tone professional and easy to skim.

{self::baseJsonRule()}
PROMPT;
    }

    public static function momDraft(array $in): string
    {
        $tone = $in['tone'] ?? 'neutral';
        $project = $in['project_name'] ?? 'N/A';
        $agenda = trim($in['agenda'] ?? '');

        return <<<PROMPT
You are a {$tone} note taker creating draft minutes of meeting (MoM).

Project: {$project}
Meeting title: {$in['meeting_title']}
Date/time: {$in['meeting_datetime']}
Attendees: {$in['attendees']}
Agenda:
{$agenda}

Raw notes / transcript:
{$in['notes_or_transcript']}

Produce structured minutes that separate decisions, discussion notes, and action items (owner + due date if present).
Keep bullets concise and factual.

{self::baseJsonRuleForKeys(['mom'])}
PROMPT;
    }

    public static function momRefine(array $in): string
    {
        $tone = $in['tone'] ?? 'formal';
        $context = trim($in['product_update_context'] ?? '');
        $contextBlock = $context !== '' ? "\nAdditional product context:\n{$context}\n" : '';

        return <<<PROMPT
You are refining meeting minutes to be clear, unambiguous, and action-oriented.
Tone: {$tone}

Raw MoM to refine:
{$in['raw_mom']}
{$contextBlock}
Tasks:
- Remove ambiguity and vague references.
- Normalize action items with owner + due date (use "TBD" if missing).
- Keep structure: Summary, Decisions, Action Items, Next Steps.
- Ensure clarity and brevity for stakeholders.

{self::baseJsonRuleForKeys(['refined_mom'])}
PROMPT;
    }

    public static function momFinalEmail(array $in): string
    {
        $tone = $in['tone'] ?? 'formal';

        return <<<PROMPT
You are preparing the final MoM email in a {$tone} stakeholder-ready voice.

Meeting: {$in['meeting_title']}
Date: {$in['date']}

Refined MoM content:
{$in['refined_mom']}

Rules:
- Subject should start with "MoM - {Meeting Title}".
- Body must be ready to paste into an email with clear sections.
- Include a short closing inviting clarifications.

{self::baseJsonRule()}
PROMPT;
    }

    public static function hrUpdate(array $in): string
    {
        $tone = $in['tone'] ?? 'neutral';

        return <<<PROMPT
You are an HR/People Ops lead writing a {$tone} end-of-day project health update for non-technical leadership.

Date: {$in['date']}
Projects summary (plain language): {$in['projects_summary']}
Status per project (Green/Amber/Red): {$in['status_per_project']}
People or timeline risks: {$in['people_or_timeline_risks']}
Tomorrow's plan: {$in['tomorrow_plan']}

Rules:
- Avoid engineering jargon; focus on delivery health and people impact.
- Keep it concise, clear, and actionable.
- Highlight risks plainly with suggested mitigations.

{self::baseJsonRule()}
PROMPT;
    }
}
