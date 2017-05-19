# Laravel-Exception-Notifier | A Laravel Excpetions Email Notification [Package](https://packagist.org/packages/jeremykenedy/laravel-exception-notifier) | v1.0.0

[![Total Downloads](https://poser.pugx.org/jeremykenedy/laravel-exception-notifier/d/total.svg)](https://packagist.org/packages/jeremykenedy/laravel-exception-notifier)
[![Latest Stable Version](https://poser.pugx.org/jeremykenedy/laravel-exception-notifier/v/stable.svg)](https://packagist.org/packages/jeremykenedy/laravel-exception-notifier)
[![License](https://poser.pugx.org/jeremykenedy/laravel-exception-notifier/license.svg)](https://packagist.org/packages/jeremykenedy/laravel-exception-notifier)

## Introduction

Laravel exception notifier will send an email of of the error along with the stack trace to the chosen recipients. [This Package](https://packagist.org/packages/jeremykenedy/laravel-exception-notifier) includes all necessary traits, views, configs, and Mailers for email notifications upon your applications exceptions. You can customize who send to, cc to, bcc to, enable/disable, and custom subject or default subject based on environment. Built for Laravel 5.2, 5.3, and 5.4+.

## Requirements

* [Laravel 5.2, 5.3, or 5.4 or newer](https://laravel.com/docs/installation)

## Installation

1. From your projects root folder in terminal run:

    ```
        composer require jeremykenedy/laravel-exception-notifier
    ```

2. Register the package with laravel in `config/app.php` under `providers` with the following:

   ```
      jeremykenedy\laravelexceptionnotifier\LaravelExceptionNotifier::class,
   ```

3. Publish the packages view, mailer, and config files by running the following from your projects root folder:

    ```
        php artisan vendor:publish --tag=laravelexceptionnotifier
    ```

4. In `App\Exceptions/Handler.php replace the `report()` method with:

    ```
        /**
         * Report or log an exception.
         *
         * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
         *
         * @param  \Exception  $exception
         * @return void
         */
        public function report(Exception $exception)
        {

            $enableEmailExceptions = config('exceptions.emailExceptionEnabled');

            if ($enableEmailExceptions === "") {
                $enableEmailExceptions = config('exceptions.emailExceptionEnabledDefault');
            }

            if ($enableEmailExceptions) {
                if ($this->shouldReport($exception)) {
                    $this->sendEmail($exception);
                }
            }

            parent::report($exception);
        }
    ```

5. In `App\Exceptions/Handler.php the method `sendEmail()`:

    ```
        /**
         * Sends an email upon exception.
         *
         * @param  \Exception  $exception
         * @return void
         */
        public function sendEmail(Exception $exception)
        {
            try {

                $e = FlattenException::create($exception);
                $handler = new SymfonyExceptionHandler();
                $html = $handler->getHtml($e);

                Mail::send(new ExceptionOccured($html));

            } catch (Exception $exception) {

                dd($exception);

            }
        }
    ```

6. Configure your email settings in the `.env` file.

7. Add the following (optional) settings to your `.env` file and enter your settings:

    * **Note:** the defaults for these are located in `config/exception.php`

    ```
        EMAIL_EXCEPTION_ENABLED=false
        EMAIL_EXCEPTION_FROM=email@email.com
        EMAIL_EXCEPTION_TO='email1@gmail.com, email2@gmail.com'
        EMAIL_EXCEPTION_CC=''
        EMAIL_EXCEPTION_BCC=''
        EMAIL_EXCEPTION_SUBJECT=''
    ```

## Screenshots

![Email Notification](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-exception-notifier/exception-error-email-min.jpeg)

## License

Laravel-Exception-Notifier | A Laravel Excpetions Email Notification Package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)