# NiceAccount — Structure & Conventions (Humans + Codex)

## Stack

- Backend: Laravel 12
- Frontend: Inertia.js + Vue 3 + TypeScript + TailwindCSS
- Auth/RBAC: Spatie Laravel Permission
- Routing (frontend): **Laravel Wayfinder** (NO hardcoded URLs / NO hardcoded route names)

---

## Architecture Flow (non-negotiable)

### Write flow (Create / Update / Delete)

**Inertia/Vue UI → Controller → FormRequest → DTO → Service → Model/Query → Database**

### Read flow (Show / Index / Reports)

**Open Page → Controller → Service → Resource → Inertia/Vue UI**

---

## Layering Rules

- **Controllers are thin**: validation via FormRequest, orchestration only.
- **Business logic belongs in Services**.
- **Resources shape output** to the UI (don't leak DB rows directly).
- **Pagination is mandatory** for list/index pages.
- **Company-scoped queries everywhere** (`company_id`).

---

## Backend Structure (Laravel)

Accounting features should live under `app/Http/Controllers/Accounting` and follow the project's layering (Controller → Request → DTO → Service → Resource → Model).

Recommended folders (concise):

- `app/Http/Controllers/Accounting` — controllers
- `app/Requests/Accounting` — FormRequests
- `app/DTOs/Accounting` — DTOs
- `app/Services/Accounting` — domain services
- `app/Resources/Accounting` — API/Inertia resources
- `app/Models/Accounting` — models

- Routes: keep in `routes/web.php` or split into `routes/accounting.php` when the file grows.

---

## Frontend Structure (Inertia/Vue)

Keep pages under `resources/js/Pages/Accountings/*` with subfolders for `ChartOfAccounts`, `JournalEntries`, and `AccountingReports`.

Recommended folders (concise):

- `resources/js/Pages/Accountings` — page entry points
- `resources/js/Pages/Accountings/ChartOfAccounts` — index/create/edit
- `resources/js/Pages/Accountings/JournalEntries` — index/show
- `resources/js/Pages/Accountings/AccountingReports` — TrialBalance, ProfitLoss, BalanceSheet, GeneralLedger
- `resources/js/Components` — shared UI (NavMain, NavUser, etc.)
- `resources/js/ui` — small UI primitives

---

## Wayfinder Routing Rules (required)

### No hardcoded routes

- ❌ Don't use `"/accountings/..."` strings in Vue.
- ❌ Don't use hardcoded route names in Vue.
- ✅ Always import **Wayfinder generated actions** (preferred).
- ✅ Optionally import **Wayfinder generated named routes**.

Wayfinder generates (by default):

- `resources/js/actions/**` (controller method helpers)
- `resources/js/routes/**` (named route helpers)
- `resources/js/wayfinder/**` (types/internal)

These folders can be safely regenerated and may be gitignored.

### Generation / Setup

- Generate files: `php artisan wayfinder:generate`
- Recommended: Vite plugin to auto-regenerate on route/controller changes:
    - `npm i -D @laravel/vite-plugin-wayfinder`
    - add `wayfinder()` to `vite.config.js`

### Prefer importing individual methods (tree-shaking)

- ✅ `import { index, store } from "@/actions/App/Http/Controllers/...Controller";`
- ⚠️ `import Controller from "@/actions/...Controller"` can prevent tree-shaking.

---

## Developer workflow notes

- Tests: every change must include a Pest test (feature tests preferred). Create with `php artisan make:test --pest Name` and run targeted tests via `php artisan test --compact`.
- Formatting: run `vendor/bin/pint --dirty` before finalizing changes.
- Frontend rebuild: if UI changes do not appear, run `npm run dev` or `npm run build` (or `composer run dev` if configured).

---

## Commit Messages

For every large change, use concise commit messages with a conventional prefix. Examples:

- `db:` — database changes/migrations
- `refactor:` — refactors that don't add features
- `feat:` — new feature
- `fix:` — bug fix
- `docs:` — documentation only
- `style:` — formatting/linters (no logic changes)
- `chore:` — build or tooling changes

Format: `<prefix>: Short summary` (e.g. `feat: add journal entry service`).
