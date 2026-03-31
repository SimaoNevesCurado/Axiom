# Axiom

[![Latest Version on Packagist](https://img.shields.io/packagist/v/simaocurado/axiom.svg?style=flat-square)](https://packagist.org/packages/simaocurado/axiom)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/simaocurado/axiom/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/simaocurado/axiom/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/simaocurado/axiom/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/simaocurado/axiom/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/simaocurado/axiom.svg?style=flat-square)](https://packagist.org/packages/simaocurado/axiom)

`axiom` is an opinionated package for Laravel projects that want stronger defaults from day one.

It brings the same direction used in my starter kits into an existing Laravel app: actions, DTOs, enums, stricter quality tooling, AI guidelines, and a more disciplined baseline for code generation and project setup.

Current target support: Laravel 11, 12, and 13.

## What It Brings

- Action-oriented architecture
- DTO-first boundaries
- Final and readonly by default
- CRUD-oriented generators
- AI guidelines and modular AI skills
- Strict Laravel defaults
- Real quality presets for PHPStan, Rector, Pint, and Arch tests
- Optional project scripts and dependency presets

## Installation

Install the package:

```bash
composer require simaocurado/axiom
```

Run the installer:

```bash
php artisan axiom:install
```

You can also run it non-interactively:

```bash
php artisan axiom:install \
  --ai=boost \
  --skills \
  --actions \
  --quality \
  --strict \
  --scripts \
  --phpstan \
  --rector \
  --pint \
  --type-coverage \
  --debug-tool=debugbar \
  --oxlint \
  --prettier \
  --concurrently \
  --ncu
```

## Installer Options

The installer can:

- publish `AGENTS.md` or `CLAUDE.md`
- publish `.ai/skills/*.md`
- create `app/Actions` and `app/Dto`
- publish `phpstan.neon`, `rector.php`, `pint.json`, and `tests/Unit/ArchTest.php`
- publish a host `App\Providers\AxiomServiceProvider`
- register that provider in `bootstrap/providers.php` when available
- add opinionated `composer` scripts
- add optional PHP tooling dependencies like PHPStan, Rector, Pint, Pest type coverage, Debugbar, and Telescope
- add optional frontend tooling dependencies like Oxlint, Prettier, concurrently, and npm-check-updates

## Generated Structure

When you enable architecture-related options, the installer prepares the host project with:

- `app/Actions`
- `app/Dto`
- `.ai/architecture.md`
- `.ai/quality.md`
- `.ai/skills/actions.md`
- `.ai/skills/dto.md`
- `.ai/skills/enum.md`
- `.ai/skills/crud.md`
- `.ai/skills/quality.md`

## Commands

- `php artisan axiom:install`
- `php artisan make:action Name`
- `php artisan make:dto Name`
- `php artisan make:enum Name`
- `php artisan make:request Name`
- `php artisan make:crud-action Model --operation=create`
  Creates a CRUD-oriented action for a model workflow.

### Composer Scripts

- `composer setup`
  Prepares the project for local development.
- `composer dev`
  Starts the local development workflow.
- `composer lint`
  Runs auto-fix quality tools like Rector and Pint.
- `composer fix:rector`
  Applies Rector refactors.
- `composer test`
  Runs the full project quality and test suite.
- `composer test:type-coverage`
  Runs Pest type coverage.
- `composer test:unit`
  Runs the test suite.
- `composer test:lint`
  Runs lint checks without changing files.
- `composer test:rector`
  Runs Rector in dry-run mode.
- `composer test:types`
  Runs static analysis.
- `composer update:requirements`
  Updates project dependency constraints.
- `composer configure:app-url`
  Sets the local `APP_URL` based on the project directory name.

## Quality Presets

When enabled, Axiom can publish:

- `phpstan.neon`
- `rector.php`
- `pint.json`
- `tests/Unit/ArchTest.php`

And it can prepare the host project with optional dependencies for:

- Larastan
- PHPStan
- Rector
- Pint
- Pest type coverage
- Oxlint
- Prettier
- frontend quality scripts

Published composer scripts include dedicated Rector commands:

- `composer fix:rector`
- `composer test:rector`

## Strict Defaults

When strict defaults are enabled, the published host provider configures:

- immutable dates with `CarbonImmutable`
- `Model::shouldBeStrict(...)`
- `Model::automaticallyEagerLoadRelationships()`

## AI Guidelines And Skills

Axiom separates global guidance from task-focused workflows:

- guidelines: `AGENTS.md` / `CLAUDE.md`
- skills: `.ai/skills/*.md`

This makes it easier to keep project rules stable while still giving AI tools focused instructions for actions, DTOs, enums, CRUD, and quality work.

## Current Scope

This package is especially useful for fresh Laravel projects or projects that are still early enough to adopt stronger conventions without a painful migration.

It is not trying to replace Laravel. It is trying to make a Laravel app feel closer to my preferred defaults:

- reusable actions
- explicit DTOs
- thin controllers
- strict static analysis
- fail-fast feedback
- cleaner code generation

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Simão Curado](https://github.com/SimaoNevesCurado)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
