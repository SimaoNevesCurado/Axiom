# Changelog

All notable changes to `axiom` will be documented in this file.

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
