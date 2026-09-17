# Testing

## PHP suite

Run `composer test` after `composer install`. Orchestra Testbench provides an isolated Laravel application. Every test uses a temporary application directory for published files. The suite covers:

- Original publish tag, destinations, defaults, and overwrite protection.
- Custom views, recipient lists, sender, CC/BCC, subject, and rendered mail through the array transport.
- The existing Handler trait's enabled switch and failure logging.
- All layouts and themes, missing fields, internal stack frames, and untrusted HTML.
- Installation/update options, interactive choices, invalid inputs, repeated backups, and rollback to legacy.
- Optional UI Kit configuration and preservation of application mailers/configuration.

Run `composer lint` to check the Laravel Pint rules and `composer format` to apply them.

## Browser suite

```bash
npm ci --ignore-scripts
npx playwright install chromium
npm run test:browser
```

Playwright starts a loopback-only PHP server on port 8765. `scripts/render-previews.php` renders synthetic data through Blade into `build/previews`. All layouts are tested at 375px and 1200px in light, dark, and system themes. Further cases check live system-theme changes and long exception data. The modern layouts are checked with axe against WCAG A/AA rules.

Screenshots and the HTML report are written under `build/`. CI uploads them along with the PHP coverage report. For manual inspection, run:

```bash
php scripts/render-previews.php
php -S 127.0.0.1:8765 -t build/previews
```

Open `http://127.0.0.1:8765/bootstrap5-light.html` or `tailwind-dark.html`. The sample URL and IP address are reserved examples.

## Fresh application integration

`bash tests/integration/install.sh` creates a temporary Laravel 13 application, installs this checkout through a Composer path repository, verifies package discovery and mail delivery, compiles views/configuration, and confirms that a Composer update preserves published file hashes. It removes its temporary application on exit and runs in the current-stack CI job.

## CI matrix

| Laravel | Testbench | PHP |
| --- | --- | --- |
| 9 | 7 | 8.0, 8.1, 8.2 |
| 10 | 8 | 8.1, 8.2, 8.3 |
| 11 | 9 | 8.2, 8.3, 8.4 |
| 12 | 10 | 8.2, 8.3, 8.4, 8.5 |
| 13 | 11 | 8.3, 8.4, 8.5 |

A separate lowest-dependency run covers Laravel 9 on PHP 8.0. Historical jobs allow advisory-affected dependency versions only in their temporary CI checkouts so backward compatibility can be tested. The latest-stack quality job retains Composer's security blocking, runs Composer/npm audits, and fails on PHP deprecations.

Actions have read-only repository permissions, pinned revisions, concurrency cancellation, and timeouts. Dependabot checks development dependencies and action revisions monthly.
