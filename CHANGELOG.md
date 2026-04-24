# Changelog

All notable changes to `axiom` will be documented in this file.

## 0.3.11 - 2026-04-24

- expand auth scaffold detection to cover starter-kit auth patterns (including existing login routes and session/auth page directories)
- align generated `SessionController` stub login flow with starter-kit-vue (`session/Create`, route-based password reset visibility)
- remove direct `Laravel\Fortify\Features` dependency from generated login controller to avoid runtime errors in projects without Fortify
- automatically add `laravel/fortify` and create/register `App\Providers\FortifyServiceProvider` when app-managed auth scaffold installation is requested on projects without Fortify
- publish starter-kit-vue auth building blocks during auth scaffold install (`config/fortify.php`, auth `Actions`, auth `FormRequests`, `ValidEmail` rule, and Vue `session/Create` + `user/Create` pages)
- add regression coverage for login-route-based scaffold detection and Fortify-free auth controller generation

## 0.3.9 - 2026-04-23

- simplify auth installer flow: when starter auth scaffold exists, leave auth untouched
- when no auth scaffold exists, prompt to install auth scaffold (or use `--install-auth` non-interactively)
- add base auth controller stubs for app-managed auth installs
- remove fallback/force auth-route strategy from the main installer flow

## 0.3.8 - 2026-04-23

- add `--force-app-routes` installer option to force app-managed auth route publishing in `routes/web.php`
- bypass starter-kit Fortify fallback when `--force-app-routes` is enabled
- add regression coverage ensuring forced mode writes app-managed routes and keeps `Fortify::ignoreRoutes();`

## 0.3.7 - 2026-04-23

- add automatic fallback to Fortify-managed routes in starter-kit projects that include auth pages but lack app-managed auth controllers
- when fallback is active, stop injecting app-managed auth blocks and strip stale Axiom auth blocks from `routes/web.php`
- when fallback is active, remove `Fortify::ignoreRoutes();` so Fortify package routes remain available for Wayfinder exports

## 0.3.6 - 2026-04-23

- align app-managed reset-password route naming with Fortify conventions by using `password.update`
- align compatibility confirm-password POST naming with starter-kit conventions by using `password.confirm.store`
- keep route merge behavior compatible with Wayfinder route module paths used by auth pages

## 0.3.5 - 2026-04-23

- move `Fortify::ignoreRoutes();` injection to `FortifyServiceProvider::register()` so Fortify package routes are disabled before route registration
- migrate existing providers that still have `Fortify::ignoreRoutes();` in `boot()` to `register()`
- keep fallback insertion in `boot()` only when a `register()` method is unavailable
- add regression coverage for providers with stale `boot()`-based `ignoreRoutes` wiring

## 0.3.4 - 2026-04-23

- clean previously generated Axiom auth route blocks from `routes/web.php` before reapplying route merge logic
- rebuild app-managed and Fortify compatibility blocks from missing routes only after cleanup
- keep `routes/web.php` unchanged when no Axiom route blocks are present
- add regression coverage for projects that already contain stale Axiom route blocks plus external auth routes

## 0.3.3 - 2026-04-23

- in app-managed auth mode, merge only missing auth routes instead of skipping the entire block
- detect existing auth routes across `routes/*.php` before appending new routes
- avoid adding Fortify compatibility routes when equivalent routes are already defined
- add regression coverage for mixed and fully pre-defined auth route scenarios

## 0.3.2 - 2026-04-23

- in app-managed auth mode, add web routes for the auth flow when needed
- ensure Fortify ignored routes are available in `routes/web.php` when using `Fortify::ignoreRoutes()`
- include compatibility routes for two-factor challenge and password confirmation
- keep app-managed route insertion idempotent when login/logout routes already exist
- clarify in README that Axiom works best on freshly created projects

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
