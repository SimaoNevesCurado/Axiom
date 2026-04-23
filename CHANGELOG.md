# Changelog

All notable changes to `axiom` will be documented in this file.

## 0.3.1 - 2026-04-23

- add frontend-aware AI guideline profiles for `vue`, `react`, and `none`
- publish dedicated guideline stubs for React starter kits
- publish explicit fallback guideline stubs when no frontend framework is detected
- detect frontend profile from `package.json` dependencies when generating AI guideline files
- add installer test coverage for React and no-frontend guideline generation

## 0.3.0 - 2026-04-23

- add a startup banner for interactive installs
- support selecting multiple AI presets in `axiom:install`
- add `Gemini` and `Opencode` AI preset options with dedicated guideline files
- simplify AI skills flow to install all skills when enabled
- keep frontend tooling behind a single `Install Bun frontend tooling?` prompt and install the full bundle when enabled
- add `auth-routes` installer mode support and enum-backed auth route selection
- update installer prompt copy for composer commands

## 0.2.9 - 2026-04-06

- add a `Choose auth routes mode` installer prompt when `laravel/fortify` is installed
- avoid mutating `composer.json` requirements when switching auth route handling
- update Fortify provider integration to support app-managed auth routes cleanly

## 0.2.8 - 2026-04-06

- always show the `Use Server Side Rendering?` prompt in interactive installs
- add a `Use Fortify?` installer prompt that syncs `laravel/fortify` in `composer.json`

## 0.2.7 - 2026-04-06

- add a `Use Server Side Rendering?` installer prompt for frontend starter kits
- allow `axiom:install --ssr` to add or keep the SSR process in the generated `composer dev` script

## 0.1.4 - 2026-03-31

- publish Laravel model stubs so host projects generate `final` models by default
- align the published `phpstan.neon` stub with the starter kit configuration

## 0.1.3 - 2026-03-31

- avoid duplicate file entries in the installer summary
- show `composer update` instead of `composer install` when composer.json was changed by the installer
- improve frontend next-step messaging when package.json was changed

## 0.1.2 - 2026-03-31

- improve installer UX with shorter prompts
- add a clearer install summary and next steps after running the installer

## 0.1.1 - 2026-03-31

- add Laravel 13 support to package constraints

## 0.1.0 - 2026-03-31

Initial public release.

- interactive installer for adopting Axiom into host projects
- AI guideline presets and modular AI skills publishing
- architecture scaffolding for `app/Actions` and `app/Dto`
- quality preset publishing for PHPStan, Rector, Pint, and Arch tests
- strict defaults provider publishing and bootstrap registration
- optional `composer.json` and `package.json` dependency/script updates
- generators for actions, DTOs, enums, requests, and CRUD actions
