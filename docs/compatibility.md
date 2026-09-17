# Compatibility decisions

## Baseline

The repository at `d18fb5d` contained a service provider, facade, published mailable, Handler trait, configuration, and one standalone Blade email template. Its development dependency allowed Laravel 9 through 13 and its PHP requirement was `^8.0`. Historical Laravel releases had separate package versions documented in the README.

The email used embedded CSS. There was no Bootstrap/Tailwind dependency, frontend build, database, HTTP route, or dashboard. The existing Travis job installed the released package into another application rather than testing this checkout. PHPUnit configuration referenced an absent feature suite, and Testbench was not declared as a dependency.

Repository conventions are the existing namespace, four-space PHP indentation, Laravel formatting, aligned array arrows, and the MIT license.

## Preserved contracts

- Composer runtime requirements and package auto-discovery remain unchanged.
- The public provider and facade class names, published `App` classes, trait methods, configuration keys, content array, and publish tag remain available.
- The original publish tag still maps exactly three files to their original paths.
- `exceptions.emailExceptionView` remains `emails.exception` by default.
- Composer installation/update does not publish, overwrite, scaffold, or register reporting callbacks.
- Existing customized application PHP and Blade files are preserved by default.
- Reporting remains synchronous, with the existing enabled switch and transport-failure logging behavior.

Publish mappings are now registered during provider boot, after application paths have been configured. View loading and the original namespaced default configuration remain registered as before.

## Added behavior

The setup commands create missing files and allow explicit view replacement with backups. The update command shares that implementation to keep overwrite behavior consistent. They do not edit `.env`, rewrite PHP configuration, install dependencies, alter application exception handlers, or invalidate deployment caches automatically.

Bootstrap 5 and Tailwind views share markup and inline email styles. Framework classes are available for host customization, while mail clients do not depend on those stylesheets. Dark mode is CSS based. UI Kit integration reads its configured framework/theme only when requested; no global frontend switch occurs.

The legacy template keeps its existing appearance with mobile wrapping and document metadata fixes. Dark mode defaults to light. Exception values continue to be escaped with Blade. Request bodies and stack arguments are not included in the HTML.

No database, queue policy, retry policy, exception filtering change, or automatic request-data capture is introduced. Additional toast, theme-toggle, IP-persistence, and seeding packages would add unrelated behavior, so they remain unnecessary.

## Verification boundaries

Compatibility tests protect the contracts above across the Laravel/PHP matrix. Browser checks cover Chromium rendering, responsive widths, dark modes, and WCAG checks on the new layouts. Mail is rendered and delivered through Laravel's array transport; no external recipient is contacted.

Individual email clients may strip CSS or alter colors. Browser tests cannot certify Outlook, Gmail, or every customized host application. Existing published files cannot be patched safely without reviewing the application's changes, so the upgrade guide documents the recipient-parsing fix for maintainers to apply.
