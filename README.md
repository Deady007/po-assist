## Phase 1 (MVP Backbone)

- Auth: JWT login (`POST /api/auth/login`) + session web login (`/login`). Role-based middleware for Admin/PM/Dev/Viewer.
- Masters: Users (Admin CRUD + activate/deactivate), Customers, Contacts (single primary per customer), Sequence configs, Audit log.
- Sequence engine: transaction-safe generator with padding/prefix. Seeded for `customer` (`CL-` + 5 digits).
- Audit: logs create/update/activate/delete for users/customers/contacts/sequences. Read via `GET /api/audit`.
- UI shell: sidebar + top bar with setup dropdown, dashboards placeholders (product/tasks/workload), customers, contacts, users, sequences.

## Quick start

```bash
cp .env.example .env   # set DB + JWT_SECRET + ADMIN_EMAIL/ADMIN_PASSWORD
composer install
php artisan migrate
php artisan db:seed    # seeds roles, sequences, and the first admin user
php artisan serve      # app at http://localhost:8000
```

## API (Phase 1)

- Auth: `POST /api/auth/login` (email, password) → access_token + user info.
- Users (Admin): `GET/POST/PATCH/DELETE /api/users`, `PATCH /api/users/{id}/activate`.
- Customers: `GET /api/customers` (all roles), `POST/PATCH /api/customers` (Admin/PM), `PATCH /api/customers/{id}/activate`.
- Contacts: `GET /api/contacts`, `POST/PATCH/DELETE /api/contacts` (Admin/PM).
- Sequences (Admin): `GET/POST/PATCH /api/config/sequences`.
- Audit (Admin/PM): `GET /api/audit?entity_type=&entity_id=`.

## Web UI

- `/login` (session) → protected shell with sidebar/topbar.
- Dashboards placeholders: `/dashboard/product`, `/dashboard/tasks`, `/dashboard/workload`.
- Clients: `/clients/customers`, `/clients/customers/{id}`, `/clients/contacts`.
- Setup: `/admin/users`, `/admin/config/sequences`, `/admin/config/email-templates` (placeholder), `/admin/import-export` (placeholder).

## Tests

```bash
php artisan test
```

Includes unit tests for sequence generation and primary contact uniqueness.

## Phase 2 (Projects + Statuses)

- Project statuses: CRUD, activate/deactivate, set default (exactly one default). API under `/api/config/project-statuses`.
- Projects: code generation via sequence, status defaults, due date required, filters/sorting, activate/deactivate, status change, soft delete (Admin). API under `/api/projects`.
- Project team: list/upsert/remove members per project.
- Search: `/api/search` and `/search` UI for projects/customers.
- UI: sidebar Projects entry; setup menu adds Project Statuses; projects list/detail/create/edit with team tab and audit activity placeholder.
- Tests: project sequence, default status assignment, default uniqueness.

## Phase 3 (Workflow + Dashboards)

- Workflow engine: module templates (Admin only), project modules init/idempotent create, module updates with blockers + DONE validation, task CRUD with RBAC (Admin/PM full; Developers only own tasks), audit logging for modules/tasks/templates.
- Dashboards: `/api/dashboards/product` + `/api/dashboards/tasks`; web product + task dashboards show live KPIs, lists, and filters. Workload dashboard stays Phase 5 placeholder.
- Project detail tabs: Workflow (modules + tasks accordion, init, add/edit), Tasks (project-scoped table with filters/actions), Emails tab placeholder (keeps existing logs/templates).
- Data normalization: workflow statuses/priorities uppercased; module templates seeded (Quotation → Review).
- Tests: workflow init idempotency, module DONE guard, developer task edit permissions.
