# Laravel Extra

[![Latest Version on Packagist](https://img.shields.io/packagist/v/simaocurado/laravel-extra.svg?style=flat-square)](https://packagist.org/packages/simaocurado/laravel-extra)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/simaocurado/laravel-extra/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/simaocurado/laravel-extra/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/simaocurado/laravel-extra/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/simaocurado/laravel-extra/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/simaocurado/laravel-extra.svg?style=flat-square)](https://packagist.org/packages/simaocurado/laravel-extra)

`laravel-extra` is an opinionated package for Laravel projects that want stronger defaults from day one.

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
composer require simaocurado/laravel-extra
```

Run the installer:

```bash
php artisan laravel-extra:install
```

You can also run it non-interactively:

```bash
php artisan laravel-extra:install \
  --ai=boost \
  --skills \
  --actions \
  --quality \
  --strict \
  --scripts \
  --php-deps \
  --frontend-deps
```

## Installer Options

The installer can:

- publish `AGENTS.md` or `CLAUDE.md`
- publish `.ai/skills/*.md`
- create `app/Actions` and `app/Dto`
- publish Laravel model stubs so `make:model` generates `final` classes
- publish `phpstan.neon`, `rector.php`, `pint.json`, and `tests/Unit/ArchTest.php`
- publish a host `App\Providers\LaravelExtraServiceProvider`
- register that provider in `bootstrap/providers.php` when available
- add opinionated `composer` scripts
- add optional PHP quality `require-dev` dependencies
- add optional frontend quality `devDependencies` to `package.json`

## Generated Structure

When you enable architecture-related options, the installer prepares the host project with:

- `app/Actions`
- `app/Dto`
- `stubs/model.stub`
- `stubs/model.pivot.stub`
- `stubs/model.morph-pivot.stub`
- `.ai/architecture.md`
- `.ai/quality.md`
- `.ai/skills/actions.md`
- `.ai/skills/dto.md`
- `.ai/skills/enum.md`
- `.ai/skills/crud.md`
- `.ai/skills/quality.md`

## Commands

### Installer Command

```bash
php artisan laravel-extra:install

php artisan laravel-extra:install --ai=boost
php artisan laravel-extra:install --ai=codex
php artisan laravel-extra:install --ai=claude
php artisan laravel-extra:install --ai=none

php artisan laravel-extra:install --skills
php artisan laravel-extra:install --actions
php artisan laravel-extra:install --quality
php artisan laravel-extra:install --strict
php artisan laravel-extra:install --scripts
php artisan laravel-extra:install --php-deps
php artisan laravel-extra:install --frontend-deps
php artisan laravel-extra:install --force
```

### Generator Commands

```bash
php artisan make:action CreateInvoice
php artisan make:action Billing/CreateInvoice
php artisan make:action CreateInvoice --dto=Billing/InvoiceDto
php artisan make:action CreateInvoice --force

php artisan make:dto CreateInvoiceDto
php artisan make:dto Billing/InvoiceDto
php artisan make:dto CreateInvoiceDto --property=total:int
php artisan make:dto CreateInvoiceDto --property=total:int --property=reference:string
php artisan make:dto CreateInvoiceDto --force

php artisan make:enum InvoiceStatus
php artisan make:enum Billing/InvoiceStatus
php artisan make:enum Priority --int
php artisan make:enum InvoiceStatus --force

php artisan make:request CreateInvoiceRequest
php artisan make:request Billing/CreateInvoiceRequest
php artisan make:request CreateInvoiceRequest --force

php artisan make:crud-action Invoice --operation=create
php artisan make:crud-action Invoice --operation=create --dto=Invoices/CreateInvoiceDto
php artisan make:crud-action Invoice --operation=list
php artisan make:crud-action Invoice --operation=show
php artisan make:crud-action Invoice --operation=update
php artisan make:crud-action Invoice --operation=delete
php artisan make:crud-action Invoice --operation=create --force
```

When architecture stubs are installed, Laravel's own model generator also follows the package convention:

```bash
php artisan make:model Invoice
php artisan make:model Invoice --pivot
php artisan make:model Taggable --morph-pivot
```

Those commands are still Laravel commands, but the published stubs make the generated models `final`.

### Published Composer Scripts

When you enable scripts in the installer, the host project gets:

```bash
composer setup
composer dev
composer lint
composer fix:rector
composer test
composer test:type-coverage
composer test:unit
composer test:lint
composer test:rector
composer test:types
composer update:requirements
composer configure:app-url
```

## Quality Presets

When enabled, Laravel Extra can publish:

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

Laravel Extra separates global guidance from task-focused workflows:

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
