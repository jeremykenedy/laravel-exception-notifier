# Historical releases

These instructions apply to the older package versions. The current source and setup commands target Laravel 9 through 13.

```bash
# Laravel 7 and 8
composer require jeremykenedy/laravel-exception-notifier:2.2.0

# Laravel 5.2 through 6
composer require jeremykenedy/laravel-exception-notifier:1.2.0
```

Laravel 5.5 and later discover the provider automatically. On earlier versions, add this provider in `config/app.php`:

```php
jeremykenedy\laravelexceptionnotifier\LaravelExceptionNotifier::class,
```

Publish the assets:

```bash
php artisan vendor:publish --tag=laravelexceptionnotifier
```

The historical mailable is spelled `App\Mail\ExceptionOccured`. Use the Handler instructions from the version you installed:

- [Version 2.2.0 documentation](https://github.com/jeremykenedy/laravel-exception-notifier/tree/v2.2.0)
- [Version 1.2.0 documentation](https://github.com/jeremykenedy/laravel-exception-notifier/tree/v1.2.0)

Keep existing exception filters and reporting behavior when upgrading an application. Do not replace a customized mailer or Handler without reviewing the diff. Current email layout, theme, and setup-command options are not backported to these releases.
