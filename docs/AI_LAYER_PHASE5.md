# Phase 5 — AI Layer (Gemini-only)

This project implements a Gemini-only “AI Layer” with:
- Versioned prompt registry (`ai_prompts`)
- Run logging / observability (`ai_runs`)
- Minimal-input context building from DB
- Strict JSON outputs with schema validation + one-shot auto-repair

## Setup

1) Configure Gemini:
- Set `GEMINI_API_KEY` in `.env`
- Optional tuning:
  - `GEMINI_MODEL_FAST` (default: `gemini-2.5-pro`)
  - `GEMINI_MODEL_PRO` (default: `gemini-2.5-pro`)
  - `GEMINI_TEMPERATURE` (default: `0.3`)
  - `GEMINI_MAX_TOKENS` (default: `1200`)
  - `GEMINI_CACHE_MINUTES` (default: `10`)

2) Run migrations + seed prompts:
- `php artisan migrate`
- `php artisan db:seed --class=AiPromptSeeder`

Notes:
- The seeder inserts defaults only when missing and will not overwrite tuned prompts.
- If you edit prompts in the DB, run `php artisan cache:clear` (active prompts are cached).

## Where to edit prompts (Prompt Registry)

Prompts live in `ai_prompts`:
- `task_key` (e.g. `PRODUCT_UPDATE`)
- `version` (int)
- `system_instructions`
- `output_schema_json` (JSON schema-like guardrail)
- `few_shot_examples_json` (optional)
- `is_active`

Recommended workflow:
1) Duplicate an existing prompt row
2) Increment `version`
3) Edit `system_instructions` / `output_schema_json`
4) Set `is_active=1` for the new version (and set old versions inactive)
5) Clear cache: `php artisan cache:clear`

## Observability (AI Runs)

Every call through `AiOrchestratorService` creates an `ai_runs` row with:
- `task_key`, `prompt_version`, `project_id`, `entity_type`, `entity_id`
- `input_context_json` (includes `context_hash` for caching)
- `model_name`, `temperature`, `latency_ms`
- `prompt_tokens`, `output_tokens` (when returned by Gemini)
- `raw_output_text` (original + repair attempt if used)
- `parsed_output_json` (final validated JSON)

Quick inspection via Tinker:
- `php artisan tinker`
- `App\\Models\\AiRun::latest()->first()?->toArray()`
- `App\\Models\\AiRun::where('task_key','PRODUCT_UPDATE')->latest()->take(10)->get(['id','success','latency_ms','prompt_tokens','output_tokens','error_message']);`

## Strict JSON + Auto-Repair

Gemini calls are enforced to return JSON:
- `GeminiClient::generateJsonStrictDetailed()` attempts JSON parse + schema validation
- If invalid, it runs a single “repair” call and validates again
- If still invalid, the run fails with a clear error and logs raw outputs in `ai_runs.raw_output_text`

## API Endpoints (Minimal Inputs)

All endpoints below require JWT auth:

1) Login to get token:
- `POST /api/auth/login`
- Body:
  - `{ "email": "admin@example.com", "password": "Admin@123" }`

Use the returned `data.access_token` as:
- Header: `Authorization: Bearer <token>`

### Product Update Email
- `POST /api/ai/product-update`
- Minimal body:
  - `{ "project_id": 1, "tone": "formal" }`

### Meeting Schedule Email
- `POST /api/ai/meeting-schedule`
- Minimal body:
  - `{ "project_id": 1, "meeting_datetime": "2025-12-15 10:00 AM UTC" }`

### MoM Pipeline
- Draft: `POST /api/ai/mom/draft`
  - Minimal body:
    - `{ "project_id": 1, "notes_or_transcript": "..." }`
- Refine: `POST /api/ai/mom/refine`
  - Minimal body:
    - `{ "raw_mom": "..." }`
- Final Email: `POST /api/ai/mom/final-email`
  - Minimal body:
    - `{ "refined_mom": "..." }`

### HR End-of-Day Update (All Projects)
- `POST /api/ai/hr-update`
- Minimal body:
  - `{ "date": "2025-12-15" }`

### Validation Executive Summary
- `POST /api/ai/validation-exec-summary`
- Minimal body:
  - `{ "report_id": 12 }`

### Requirements Extraction from RFP Text
- `POST /api/ai/rfp-requirements-extract`
- Minimal body:
  - `{ "project_id": 1, "source_text": "..." }`

## RFP Extraction (Persisted on RFP Document)

If you want extraction stored on an existing `rfp_documents` row:
- `POST /api/projects/{projectId}/rfp-documents/{rfpDocumentId}/extract`
- Body:
  - `{ "source_text": "..." }`

This saves to:
- `rfp_documents.source_text`
- `rfp_documents.extracted_json`

## Tuning Guide

If outputs are inconsistent:
- Lower `GEMINI_TEMPERATURE` (e.g. `0.2` → more deterministic)
- Tighten the prompt’s “Output requirements” section (avoid ambiguity)
- Tighten `output_schema_json` (add `minLength`, stricter `items`)
- Check `ai_runs.raw_output_text` for recurring formatting issues and update the prompt accordingly

If outputs are too verbose:
- Add explicit limits (“max 6 bullets”, “3-6 sentences”)
- Reduce context size in `ContextBuilderService` to only the fields you need

