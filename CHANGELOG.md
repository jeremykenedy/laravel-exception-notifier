# Changelog

## Unreleased

- Add install/update commands that preserve application files and back up explicit view replacements.
- Keep the standalone Blade email as the default; add optional Bootstrap 5 and Tailwind layouts.
- Add light, dark, and system themes and optional UI Kit configuration selection.
- Fix long file paths overflowing the legacy email on mobile screens.
- Support the original Laravel 9 mail builder API alongside the current envelope/content API.
- Preserve CSV recipient parsing while avoiding PHP 8.4/8.5 deprecations.
- Fix the copied Handler trait's inherited property conflict on PHP versions before 8.5 while preserving exception exclusions.
- Register publish paths during provider boot to honor configured application paths.
- Add Laravel 9 through 13 compatibility tests, browser/accessibility tests, lint, audits, and coverage reporting in GitHub Actions.
- Update installation, upgrade, testing, and historical-release documentation, README images, and the 2026 license notice.
