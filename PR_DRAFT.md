# PR Draft: Fix Dashboard Tasks to Respect Role Filters

## Title
Fix: Dashboard tasks respecting role_filters and per-employee view_scope

## Summary
This PR aligns the dashboard "Tasks" query with the configurable role-based filters found in `src/View/settings/role_filters.json` and per-employee `view_scope`. It fixes incorrect JOIN aliases and ensures owner-scoped conditions (designer/workshop/accountant) are applied when appropriate.

## Changes
- src/Core/InitialTasksQuery.php
  - unified employee joins: `ed` (designer) and `ew` (workshop)
- src/Core/RoleBasedQuery.php
  - improved `view_scope` handling
  - added role-specific owner conditions: `o.designer_id`, `o.workshop_id`, `o.payment_settled_at` when applicable
- tests/
  - added unit tests (RoleBasedQueryTest, RoleBasedQueryExtraTest, IntegrationRoleFilterTest, SimpleTest)
- .github/workflows/ci.yml (CI for lint + tests)
- migrations/20250826_add_order_indexes.sql (recommended indexes)
- README.md, CONTRIBUTING.md, .github/PULL_REQUEST_TEMPLATE.md

## How to test locally
1. Install deps:

```powershell
composer install
```

2. Run unit tests:

```powershell
php ./vendor/bin/phpunit tests
```

3. (Optional) Apply DB migration in non-production environment:

```powershell
mysql -u root -p < migrations/20250826_add_order_indexes.sql
```

## Risks
- Low: SQL JOIN alias changes may affect code that relies on previous aliases. Verified by running tests.
- Mitigation: Tests added; migration is non-destructive; rollback is to revert this branch.

## Notes
- CI workflow runs PHPUnit and PHP lint.
- Consider adding Integration tests with a real test DB and E2E tests (Cypress/Playwright) in follow-up PR.
