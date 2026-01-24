# NiceAccount AI Guidelines

## Architecture

- UI (Inertia/Vue) → Controller → FormRequest → DTO → Service → Model/Query → Database
- Controllers handle input/output only
- Validation must be in FormRequest
- Services handle business rules and transactions
- Models must not contain business logic

## Accounting Rules

- All accounting data must be scoped by company_id
- Journal entries must always balance (debit = credit)
- Never hardcode account codes
- Use enums for statuses (draft, posted, reversed)

## Laravel Rules

- Laravel 12 only
- Use Inertia + Vue
- Prefer Events/Listeners for posting logic
- Use Policies for authorization
