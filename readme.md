# Laravel Exception Notifier | A Laravel 5, 6, 7, 8, 9 and 10 Exceptions Email Notification [Package](https://packagist.org/packages/jeremykenedy/laravel-exception-notifier)

[![Total Downloads](https://poser.pugx.org/jeremykenedy/laravel-exception-notifier/d/total.svg)](https://packagist.org/packages/jeremykenedy/laravel-exception-notifier)
[![Latest Stable Version](https://poser.pugx.org/jeremykenedy/laravel-exception-notifier/v/stable.svg)](https://packagist.org/packages/jeremykenedy/laravel-exception-notifier)
[![Build Status](https://travis-ci.org/jeremykenedy/laravel-exception-notifier.svg?branch=master)](https://travis-ci.org/jeremykenedy/laravel-exception-notifier)
[![StyleCI](https://github.styleci.io/repos/91833181/shield?branch=master)](https://github.styleci.io/repos/91833181)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jeremykenedy/laravel-exception-notifier/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jeremykenedy/laravel-exception-notifier/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/jeremykenedy/laravel-exception-notifier/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)
[![MadeWithLaravel.com shield](https://madewithlaravel.com/storage/repo-shields/1350-shield.svg)](https://madewithlaravel.com/p/laravel-exception-notifier/shield-link)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Table of contents:
- [About](#about)
- [Requirements](#requirements)
- [Installation Instructions](#installation-instructions)
- [Screenshots](#screenshots)
- [File Tree](#file-tree)
- [License](#license)

## About
Laravel exception notifier will send an email of the error along with the stack trace to the chosen recipients.
[This Package](https://packagist.org/packages/jeremykenedy/laravel-exception-notifier) includes all necessary traits, views, configs, and Mailers for email notifications upon your applications exceptions.
You can customize who send to, cc to, bcc to, enable/disable, and custom subject or default subject based on environment.
Built for Laravel 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 6, 7, 8, 9, and 10.

Get the errors and fix them before the client even reports them, that's why this exists!

## Requirements
* [Laravel 5.2+, 6, 7, 8, 9, or 10](https://laravel.com/docs/installation)

## Installation Instructions
1. From your projects root folder in terminal run:

    Laravel 9-10 use:

    ```bash
        composer require jeremykenedy/laravel-exception-notifier
    ```

    Laravel 7-8 use:

    ```bash
        composer require jeremykenedy/laravel-exception-notifier:2.2.0
    ```

    Laravel 6 and below use:

    ```bash
        composer require jeremykenedy/laravel-exception-notifier:1.2.0
    ```

2. Register the package
* Laravel 5.5 and up
Uses package auto discovery feature, no need to edit the `config/app.php` file.

* Laravel 5.4 and below
Register the package with laravel in `config/app.php` under `providers` with the following:

```php
    jeremykenedy\laravelexceptionnotifier\LaravelExceptionNotifier::class,
```

3. Publish the packages view, mailer, and config files by running the following from your projects root folder:

```bash
    php artisan vendor:publish --tag=laravelexceptionnotifier
```

#### NOTE: If upgrading to Laravel 9 or 10 from an older version of this package you will need to republish the assets with:

```bash
    php artisan vendor:publish --force --tag=laravelexceptionnotifier
```

4. In `App\Exceptions\Handler.php` include the additional following classes in the head:

#### Laravel 9 and Above use:

```php
    use App\Mail\ExceptionOccured;
    use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
    use Illuminate\Support\Facades\Log;
    use Mail;
    use Throwable;
```

#### Laravel 8 and Below use:

```php
    use App\Mail\ExceptionOccured;
    use Illuminate\Support\Facades\Log;
    use Mail;
    use Symfony\Component\Debug\Exception\FlattenException;
    use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
```

5. Update `App\Exceptions\Handler.php`

#### Laravel 9 and Above:
##### In `App\Exceptions\Handler.php` replace the `register()` method with:

```php
    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            $this->sendEmail($e);
        });
    }
```

#### Laravel 8 and Below:
##### In `App\Exceptions\Handler.php` replace the `report()` method with:

```php
    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    public function report(Throwable $exception)
    {
        $enableEmailExceptions = config('exceptions.emailExceptionEnabled');

        if ($enableEmailExceptions === '') {
            $enableEmailExceptions = config('exceptions.emailExceptionEnabledDefault');
        }

        if ($enableEmailExceptions && $this->shouldReport($exception)) {
            $this->sendEmail($exception);
        }

        parent::report($exception);
    }
```

6. In `App\Exceptions\Handler.php` add the method `sendEmail()`:

#### Laravel 9 and Above:

```php
    /**
     * Sends an email upon exception.
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    public function sendEmail(Throwable $exception)
    {
       try {
            $content['message'] = $exception->getMessage();
            $content['file'] = $exception->getFile();
            $content['line'] = $exception->getLine();
            $content['trace'] = $exception->getTrace();
            $content['url'] = request()->url();
            $content['body'] = request()->all();
            $content['ip'] = request()->ip();
            Mail::send(new ExceptionOccured($content));
        } catch (Throwable $exception) {
            Log::error($exception);
        }
    }
```

#### Laravel 8 and Below:

```php
    /**
     * Sends an email upon exception.
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    public function sendEmail(Throwable $exception)
    {
        try {
            $e = FlattenException::create($exception);
            $handler = new SymfonyExceptionHandler();
            $html = $handler->getHtml($e);

            Mail::send(new ExceptionOccured($html));
        } catch (Throwable $exception) {
            Log::error($exception);
        }
    }
```

7. Configure your email settings in the `.env` file.

8. Add the following (optional) settings to your `.env` file and enter your settings:

    * **Note:** the defaults for these are located in `config/exception.php`

```bash
        EMAIL_EXCEPTION_ENABLED=false
        EMAIL_EXCEPTION_FROM="${MAIL_FROM_ADDRESS}"
        EMAIL_EXCEPTION_TO='email1@gmail.com, email2@gmail.com'
        EMAIL_EXCEPTION_CC=''
        EMAIL_EXCEPTION_BCC=''
        EMAIL_EXCEPTION_SUBJECT=''
```

## Screenshots
![Email Notification](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-exception-notifier/exception-error-email-min.jpeg)

## File Tree
```
└── laravel-exception-notifier
    ├── .gitignore
    ├── LICENSE
    ├── composer.json
    ├── readme.md
    └── src
        ├── .env.example
        ├── App
        │   ├── Mail
        │   │   └── ExceptionOccured.php
        │   └── Traits
        │       └── ExceptionNotificationHandlerTrait.php
        ├── LaravelExceptionNotifier.php
        ├── config
        │   └── exceptions.php
        └── resources
            └── views
                └── emails
                    └── exception.blade.php
```

* Tree command can be installed using brew: `brew install tree`
* File tree generated using command `tree -a -I '.git|node_modules|vendor|storage|tests'`

## License
Laravel-Exception-Notifier | A Laravel Exceptions Email Notification Package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
