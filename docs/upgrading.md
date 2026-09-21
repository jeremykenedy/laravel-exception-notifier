# Upgrading

## Existing applications

A Composer update does not publish files or modify your application. The existing `App\Mail\ExceptionOccurred` class, `exceptions` configuration keys, `emails.exception` view, provider, and publish tag remain supported. No migrations, routes, npm assets, or automatic reporting callbacks are added.

Run `php artisan exception-notifier:update --no-interaction` to restore missing files. Existing configuration, mailers, and views are kept. Published PHP files belong to the application and do not receive source changes automatically.

## Published mailer on PHP 8.4 and 8.5

PHP deprecated an implicit CSV escape parameter. The package's mailer now explicitly passes the existing backslash escape to `str_getcsv`. To apply this fix to an already published mailer, update each recipient parsing call in `app/Mail/ExceptionOccurred.php`:

```php
str_getcsv(config('exceptions.emailExceptionsTo'), ',', '"', '\\')
str_getcsv(config('exceptions.emailExceptionCCto'), ',', '"', '\\')
str_getcsv(config('exceptions.emailExceptionBCCto'), ',', '"', '\\')
```

Keep your custom mailer behavior and review this small diff before deploying. The setup commands deliberately do not replace application PHP files.

The updated mailer also includes a `build()` fallback for early Laravel 9 releases that predate the envelope/content API. Applications using those releases can compare the published mailer with `src/App/Mail/ExceptionOccurred.php` and apply the fallback while retaining their customizations.

## Copied Handler trait on PHP 8.0 through 8.4

The original trait declared a `$dontReport` property that conflicts with Laravel's Handler property on PHP versions before 8.5. The updated trait adds the same ignored exception classes using `$this->ignore()` in `register()`. This also preserves exclusions defined by the application.

If you copy the trait into your application, review and apply this change there. An existing Handler that implements the README's `sendEmail()` and callback directly does not need this change.

## Switching the email layout

```bash
php artisan exception-notifier:update --layout=modern --theme=system --force
php artisan view:clear
```

The command saves the current `resources/views/emails/exception.blade.php` to a unique adjacent `.bak` file, then installs a wrapper for the selected package template. A failed backup prevents replacement. Repeated switches create separate backups.

The modern wrapper follows the package's template updates. If you need to pin or customize its markup, copy the relevant templates to Laravel's namespaced override directory, `resources/views/vendor/laravelexceptionnotifier/emails/`.

If `exceptions.emailExceptionView` points to a custom view, it continues to do so. To use the installed wrapper, explicitly set that configuration value to `emails.exception` and rebuild your configuration cache.

An installation without a layout selection publishes the complete legacy Blade file and follows the theme configuration. Explicit layout selections, including `--layout=legacy --theme=light`, install a wrapper that fixes the chosen theme. Existing published views stay unchanged until explicitly replaced.

## Rollback

Restore the saved `.bak` file to `resources/views/emails/exception.blade.php` and run `php artisan view:clear`. Alternatively, select `--layout=legacy --theme=light --force`; this installs the current legacy source and preserves another backup.

If you changed `exceptions.emailExceptionView` separately, restore that setting and rebuild the configuration cache. No database rollback is needed.

## Moving from Laravel 9 or 10 to 11 or later

Existing applications retaining `App\Exceptions\Handler` can keep their callbacks. New Laravel skeletons configure reporting in `bootstrap/app.php`; follow the README's `withExceptions` example. Do not register the same callback in both places.

Applications on Laravel 7 or 8 should keep package version `2.2.0`. Laravel 6 and earlier use `1.2.0`. This update does not rewrite those historical releases.
