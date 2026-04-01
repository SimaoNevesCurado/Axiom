## Axiom

This package brings opinionated Laravel defaults for action-oriented architecture, DTO-first boundaries, strict typing, and stronger code quality defaults.

### Conventions

- Prefer reusable Action classes in `app/Actions` for business logic.
- Name actions by what they do and keep one focused public `handle()` method.
- Create new actions with `php artisan make:action "{name}" --no-interaction`.
- Prefer DTOs over loose arrays at application boundaries.
- Keep controllers thin and move business logic into actions.
- Prefer Form Requests for validation instead of inline controller validation.
- Prefer `final` classes, `readonly` objects, and explicit property, parameter, and return types.
- Use `declare(strict_types=1);` in PHP files.
- Keep new code free from `dd`, `dump`, and `ray`.

### Generators

- Use `php artisan make:action Name` to generate actions in `app/Actions`.
- Use `php artisan make:dto Name` to generate readonly DTOs in `app/Dto`.
- Use `php artisan make:enum Name` to generate enums in `app/Enums`.
- Use `php artisan make:request Name` to generate form requests in `app/Http/Requests`.
- Use `php artisan make:crud-action Model --operation=create` for CRUD-oriented action scaffolding.

### Quality

- Prefer static-analysis-friendly code over clever shortcuts.
- When behavior changes, add or update tests.
- If this project uses Laravel Boost tooling, search the Laravel docs before changing framework-specific behavior.

