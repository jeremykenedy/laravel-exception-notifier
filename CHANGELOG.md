# Changelog

## Unreleased

- Add install/update commands that preserve application files and back up explicit view replacements.
- Keep the standalone Blade email as the default; add optional Bootstrap 5 and Tailwind layouts.
- Add light, dark, and system themes and optional UI Kit configuration selection.
- Fix long file paths overflowing the legacy email on mobile screens.
- Preserve CSV recipient parsing while avoiding PHP 8.4/8.5 deprecations.
- Register publish paths during provider boot to honor configured application paths.
- Add Laravel 9 through 13 compatibility tests, browser/accessibility tests, lint, audits, and coverage reporting in GitHub Actions.
- Update installation, upgrade, testing, and historical-release documentation, README images, and the 2026 license notice.
