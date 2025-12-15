<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Windows (WSL) usage

If you're on Windows and using WSL, run Sail via the included PowerShell wrapper:

```powershell
.\sail.ps1 up -d
```

Or run directly in WSL:

```bash
cd /mnt/c/Users/Parmar\ Viral\ V/po-assist
./vendor/bin/sail up -d
```

## Google Drive Integration (Phase 3)

Single-admin Google Drive connectivity with per-project folders and uploads.

1) Create OAuth client  
- In Google Cloud Console, create OAuth client (type: Web).  
- Add redirect URI: `http://localhost/drive/oauth/callback`.  
- Enable the Drive API.  
- Keep the client ID/secret handy.

2) Configure `.env`  
Set the following keys:  
`GOOGLE_DRIVE_CLIENT_ID`, `GOOGLE_DRIVE_CLIENT_SECRET`, `GOOGLE_DRIVE_REDIRECT_URI`, `GOOGLE_DRIVE_REFRESH_TOKEN` (blank until obtained), `GOOGLE_DRIVE_ROOT_FOLDER_ID` (optional parent), `GOOGLE_DRIVE_APP_NAME`, `GOOGLE_DRIVE_SCOPE` (`https://www.googleapis.com/auth/drive.file` by default).

3) Obtain refresh token  
- Visit `/drive/connect` and click **Connect Google Drive**.  
- Approve consent (prompt forces offline access).  
- Copy the refresh token shown on the callback page and paste it into `GOOGLE_DRIVE_REFRESH_TOKEN` in `.env`, then reload the app.

4) Provision project folders  
- UI: `/projects/{id}/drive` -> **Provision Drive Folders**.  
- CLI: `php artisan drive:provision {projectId?}` (omit ID to provision all).
- Verify: `php artisan drive:verify {projectId}` to check stored IDs still exist.

5) Upload or link files  
- UI: `/projects/{id}/drive` upload form (phase, entity_type, optional entity_id + file).  
- API: `POST /api/projects/{id}/drive/upload` (multipart) or `POST /api/projects/{id}/drive/link` with a Drive file ID.  
- List files: `GET /api/projects/{id}/drive/files?phase_key=...&entity_type=...`.

## Phase 4 (Workflow Modules & Validation Reports)

- API envelope helper: `App\Support\ApiResponse::success|failure` used across controllers.
- Warnings: `GET /api/projects/{id}/warnings` (phase overdue, missing folders, blocking bugs, etc.).
- Requirements & RFP: `GET/POST /api/projects/{id}/requirements`, `PUT/DELETE /requirements/{rid}`, RFP upload/link via `/rfp-documents/upload|link`. UI: `/projects/{id}/requirements`.
- Data collection: `GET/POST /api/projects/{id}/data-items`, `PUT/DELETE /data-items/{id}`, upload files `/data-items/{id}/upload`. UI: `/projects/{id}/data-items`.
- Master data changes: `GET/POST /api/projects/{id}/master-data-changes`, `PUT/DELETE /master-data-changes/{id}`. UI: `/projects/{id}/master-data`.
- Developers/Assignments/Bugs: `GET/POST /api/developers`, assignments `/api/projects/{id}/assignments`, bugs `/api/projects/{id}/bugs`. UI: `/developers`, `/projects/{id}/assignments`, `/projects/{id}/bugs`.
- Testing: testers `/api/testers`, test cases `/api/projects/{id}/test-cases`, test runs `/api/projects/{id}/test-runs`, results `/test-runs/{run}/results`, coverage `/testing/coverage`. UI: `/projects/{id}/testing`.
- Delivery & tokens: delivery `/api/projects/{id}/delivery`, wallet `/api/projects/{id}/token-wallet`, requests `/api/projects/{id}/token-requests` + `/transition`. UI: `/projects/{id}/tokens`.
- Validation reports: `POST /api/projects/{id}/validation-reports/generate` (optional `include_ai_summary`), list `/validation-reports`, upload to Drive `/validation-reports/{report}/upload-to-drive`. UI: `/projects/{id}/validation-reports`.

Quick local testing flow:
1. Set a project_id via dashboard selector (persists in nav), then visit module pages above.
2. Add developers/testers, create requirements and assignments.
3. Add data items and upload a file (goes to Drive DATA_COLLECTION folder).
4. Log bugs/test cases/test runs; add results (FAIL can auto-create bugs).
5. Record delivery + token wallet/requests.
6. Generate validation report and upload to Drive (phase DELIVERY). Check warnings endpoint to confirm blockers.

## Phase 5 (AI Layer)

- New tables: `ai_prompts` (versioned prompt registry) and `ai_runs` (observability).
- Models/Services: `AiPrompt`, `AiRun`, `AiPromptRepository`, `AiOrchestratorService`, `ContextBuilderService`, `AiSchemaValidator`.
- Strict JSON generation with one-shot repair via updated `GeminiClient::generateJsonStrict`.
- Config: `.env` supports `GEMINI_MODEL_FAST`, `GEMINI_MODEL_PRO`, `GEMINI_CACHE_MINUTES` plus existing Gemini keys.
- Seed prompts: `php artisan migrate` then `php artisan db:seed --class=AiPromptSeeder`.
- Email/MoM/HR generators now pull DB context automatically and log ai_runs; minimal inputs accepted (project + key timing fields).
- Validation report executive summary now uses orchestrator with schema enforcement.

Testing examples:
- Product update (minimal): `POST /emails/product-update` with `project_id` and optional `highlights`; context auto-enriched.
- HR EOD: `POST /emails/hr-update` with `project_id` only (date defaults today).
- Meeting schedule: `POST /emails/meeting-schedule` with `project_id`, `meeting_datetime`, `duration`; agenda inferred from warnings/open blockers.
- Validation exec summary: `POST /api/projects/{id}/validation-reports/generate` with `include_ai_summary=true`.
