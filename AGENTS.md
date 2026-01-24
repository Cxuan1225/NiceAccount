# NiceAccount — Structure & Conventions (Humans + Codex)

## Stack

- Backend: Laravel 12
- Frontend: Inertia.js + Vue 3 + TypeScript + TailwindCSS
- Auth/RBAC: Spatie Laravel Permission
- Routing (frontend): **Laravel Wayfinder** (NO hardcoded URLs / NO hardcoded route names)

---

## Architecture Flow (non-negotiable)

### Write flow (Create/Update/Delete)

Inertia/Vue UI → Controller → FormRequest → DTO → Service → Model/Query → Database

### Read flow (Show/Index/Reports)

Open Page → Controller → Service → Resource → Inertia/Vue UI

---

## Layering Rules

- Controllers are thin: validation via FormRequest, orchestration only.
- Business logic belongs in Services.
- Resources shape output to the UI (don’t leak DB rows directly).
- Pagination is mandatory for list/index pages.
- Company-scoped queries everywhere (`company_id`).

---

## Backend Structure (Laravel)

> Accounting-related controllers live under `App\Http\Controllers\Accounting\...`

Suggested layout:
app/
DTOs/
Accounting/
Http/
Controllers/
Accounting/
BaseAccountingController.php
ChartOfAccountController.php
OpeningBalanceController.php
JournalEntryController.php
PostingPeriodController.php
FinancialYearController.php
Reports/
TrialBalanceController.php
ProfitLossController.php
BalanceSheetController.php
GeneralLedgerController.php
Requests/
Accounting/
Accounting/Reports/
Resources/
Accounting/
Services/
Accounting/
Accounting/Reports/
Models/
Accounting/

routes/
web.php (or a dedicated routes file if you split it)

---

## Frontend Structure (Inertia/Vue)

resources/js/
layouts/
AppLayout.vue
pages/
Accountings/
ChartOfAccounts/
Index.vue
Create.vue
Edit.vue
JournalEntries/
Index.vue
Show.vue
AccountingReports/
\_components/
ReportHeader.vue
ReportTabs.vue
ReportFilters.vue
TrialBalance/Index.vue
ProfitLoss/Index.vue
BalanceSheet/Index.vue
GeneralLedger/Index.vue
components/
NavMain.vue
NavUser.vue
ui/

---

## ✅ Wayfinder Routing Rules (required)

### No hardcoded routes

- ❌ Don’t use `"/accountings/..."` strings in Vue.
- ❌ Don’t use hardcoded route names in Vue.
- ✅ Always import **Wayfinder generated actions** (preferred).
- ✅ Optionally import **Wayfinder generated named routes**.

Wayfinder generates (by default):

- `resources/js/actions/**` (controller method helpers)
- `resources/js/routes/**` (named route helpers)
- `resources/js/wayfinder/**` (types/internal)

These folders can be safely regenerated and may be gitignored. :contentReference[oaicite:0]{index=0}

### Generation / Setup

- Generate files: `php artisan wayfinder:generate` :contentReference[oaicite:1]{index=1}
- Recommended: Vite plugin to auto-regenerate on route/controller changes:
    - `npm i -D @laravel/vite-plugin-wayfinder`
    - add `wayfinder()` to `vite.config.js` :contentReference[oaicite:2]{index=2}

### Prefer importing individual methods (tree-shaking)

- ✅ `import { index, store } from "@/actions/App/Http/Controllers/...Controller";`
- ⚠️ `import Controller from "@/actions/...Controller"` can prevent tree-shaking. :contentReference[oaicite:3]{index=3}
