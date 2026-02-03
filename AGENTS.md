# NiceAccount - Agent Instructions

## Stack (Mandatory)

| Technology   | Version                   |
| ------------ | ------------------------- |
| Laravel      | 12                        |
| PHP          | 8.4+                      |
| Inertia      | v2                        |
| Vue          | 3                         |
| TypeScript   | Required                  |
| Tailwind CSS | v4                        |
| Permissions  | Spatie Laravel Permission |

### Frontend Routing: Wayfinder Only

- ✅ Navigation (`<Link>`, redirects), form actions/requests
- ❌ No hardcoded URLs
- ❌ No hardcoded route names

---

## Architecture Flow (Non-Negotiable)

```
Write: UI → Controller → FormRequest → DTO → Service → Model/Query → DB
Read:  Page → Controller → Service → Resource → UI
```

---

## Layer Rules (Mandatory)

| Layer           | Responsibility                                                                      |
| --------------- | ----------------------------------------------------------------------------------- |
| **Controller**  | I/O only; inject FormRequest + Service; return Inertia render or redirect+flash     |
| **FormRequest** | Validate + authorize + normalize (`prepareForValidation()`); no DB work             |
| **DTO**         | Validated fields only; typed; no DB calls; no business logic                        |
| **Service**     | Business rules + orchestration; transactions; enforce company scope + authorization |
| **Model**       | Lean Eloquent (relations/casts/scopes only)                                         |
| **Resource**    | Shape only needed props; never return raw models/rows directly                      |

### Global Rules

- **Company scope everywhere** (`company_id`)
- **Pagination mandatory** for all list/index pages

---

## Folder Conventions

### Backend

```
app/Http/Controllers/Accounting/...
app/Http/Requests/Accounting/<Feature>/...
app/DTO/Accounting/<Feature>/...
app/Services/Accounting/<Feature>/...
app/Http/Resources/Accounting/...
app/Models/Accounting/...
```

### Frontend

```
resources/js/Pages/Accountings/...
resources/js/Components/...
resources/js/ui/...
```

### Routes

```
routes/web.php
routes/accounting.php
```

---

## PHP 8.0–8.4 Rules (Mandatory)

### Baseline Typing & Safety

All new/edited files under `app/` must include:

- `declare(strict_types=1);`
- Explicit parameter + return types
- Explicit property types
- Nullable must be explicit (`?Type`)
- Avoid weak typing (`mixed` only when justified)

### Modern PHP Expectations

| Version | Requirements                                                                                       |
| ------- | -------------------------------------------------------------------------------------------------- |
| **8.0** | Prefer `match`, nullsafe `?->`, named args, union types, constructor promotion (DTO/value objects) |
| **8.1** | Use `enum` for status/type fields; prefer `readonly` DTOs/value objects                            |
| **8.2** | Avoid dynamic properties (declare everything)                                                      |
| **8.3** | JSON: decode once with throw-on-error; prefer validation without decoding when possible            |
| **8.4** | Property hooks allowed only for DTO/value objects when it improves clarity (avoid on Eloquent)     |

### JSON Rule

- Decode JSON only if needed, and only once (throw on error)
- If only validating format/shape, validate without decoding when practical

### PHPStan (Mandatory)

- **Level 10** is the enforced standard for this project
- Run `./vendor/bin/phpstan analyse --memory-limit=2G` before finalizing changes
- All new/edited code must pass PHPStan level 10 with zero errors
- Use proper PHPDoc annotations for generics, array shapes, and complex types
- Never use `@phpstan-ignore` without explicit justification and approval

---

## Wayfinder Rules

- Vue **must** import Wayfinder-generated helpers
- No `"/..."` URLs or `"route.name"` strings in Vue
- Regenerate when routes/controllers change: `php artisan wayfinder:generate`

---

## Delivery Workflow

### Build Order

```
Migration → Model → FormRequest → DTO → Service → Resource → Controller → Routes → Inertia Page → Pest tests
```

### Requirements

- **Tests required**: Pest (feature preferred)
- **Format before PR**: `vendor/bin/pint --dirty`

### Commit Prefixes

| Prefix      | Usage                      |
| ----------- | -------------------------- |
| `db:`       | Database/migration changes |
| `feat:`     | New features               |
| `fix:`      | Bug fixes                  |
| `refactor:` | Code refactoring           |
| `docs:`     | Documentation              |
| `style:`    | Formatting/style changes   |
| `chore:`    | Maintenance tasks          |
