# Changelog

All notable changes to `axiom` will be documented in this file.

## 0.3.22 - 2026-04-28

- fix the Vue reset password page stub to submit through the generated `password.store` Wayfinder route instead of importing a missing `password.update` export

## 0.3.21 - 2026-04-28

- migrate legacy Fortify starter auth providers and config files when switching to app-managed auth, instead of leaving Fortify route-generating features enabled
- remove legacy Fortify auth pages from `resources/js/pages/auth` when publishing the app-managed auth pages
- avoid publishing starter-kit settings pages into apps that already have `routes/settings.php` or `resources/js/pages/settings`, preventing missing Wayfinder controller imports
- keep app-managed auth route generation compatible with existing Laravel starter settings routes

## 0.3.20 - 2026-04-28

- keep the published `FortifyServiceProvider` aligned with the starter kits by leaving `register()` empty in app-managed auth mode
- keep Fortify route features disabled through the published `config/fortify.php` feature list instead of injecting `Fortify::ignoreRoutes()`
- remove legacy `app/Actions/Fortify` auth action files when installing the new app-managed auth actions
- remove the starter Welcome route `canRegister` Fortify feature flag when app-managed auth routes are installed

## 0.3.19 - 2026-04-28

- refactor the installer from a monolithic `InstallAxiomAction` into a small orchestration action backed by focused install actions
- add typed install context, frontend stack detection, auth route definitions, and route middleware enums for clearer installer flow
- add a dedicated app-managed auth installer flow with separate actions for Fortify route disabling, auth routes, controllers, requests, pages, tests, actions, and rules
- publish React and Vue starter-kit auth page stubs plus starter-kit auth tests when app-managed auth scaffold is installed
- disable Fortify package routes from `FortifyServiceProvider::register()` in app-managed mode to avoid duplicate auth routes

## 0.3.18 - 2026-04-28

- require the Laravel 11/12/13 compatible Debugbar line when installing the Debugbar preset, avoiding old Illuminate and `psr/simple-cache` solver conflicts
- skip Pest type coverage tooling when the host project already requires `laravel/pao`, preserving the app's existing test dependency strategy

## 0.3.17 - 2026-04-28

- restore the app-managed auth scaffold to the full backend auth route set in `routes/web.php`, including register, password reset, email verification, profile, appearance, and two-factor entry points
- publish the backend auth controllers, requests, actions, rules, Fortify config, and Fortify provider used by the local React and Vue starter kits when app-managed auth routes are selected
- render React and Vue Inertia page names from the matching starter kit conventions when generating auth controllers, Fortify views, and appearance routes
- stop auto-installing frontend auth pages, shadcn-vue support files, frontend auth runtime dependencies, and Fortify scaffolding when the project only needs backend login handling
- keep Fortify-managed mode read-only so existing Fortify apps are not changed when that option is selected

## 0.3.15 - 2026-04-24

- publish starter-kit auth login/register pages together with required shadcn-vue support files (`resources/js/components`, `resources/js/layouts`, `resources/js/lib`, and required `components/ui/*` subsets)
- add required shadcn-vue runtime dependencies to `package.json` during auth scaffold installation (`reka-ui`, `@vueuse/core`, `lucide-vue-next`, `class-variance-authority`, `clsx`, `tailwind-merge`)
- keep auth controllers/actions/requests aligned with starter-kit-vue while ensuring generated pages resolve their imports in non-starter projects

## 0.3.14 - 2026-04-24

- choose auth page stubs dynamically during install: use starter-kit-vue pages when starter UI components exist, otherwise use portable Vue auth pages
- keep controllers/actions/requests aligned with starter-kit auth flow while avoiding frontend build failures in projects without shadcn-vue UI files
- add regression coverage for both auth page modes (starter UI present vs absent)

## 0.3.13 - 2026-04-24

- align auth stubs exactly with `starter-kit-vue` for `SessionController` and Vue login/register pages
- restore starter-kit UI/layout imports in generated auth pages (shadcn-vue based components)
- remove the temporary `method_exists` guard in generated `SessionController` so controller logic matches the starter kit implementation

## 0.3.12 - 2026-04-24

- make generated auth Vue login/register pages portable by removing starter-kit-specific UI component imports
- keep Wayfinder-based auth form wiring while using framework-agnostic HTML controls in generated pages
- guard two-factor login check in generated `SessionController` with `method_exists` to avoid runtime errors when the user model does not yet expose Fortify helpers
- add regression coverage for portable auth page generation without `@/components/*` dependencies

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
