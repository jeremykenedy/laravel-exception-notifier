# Laravel-Exception-Notifier | A Laravel Excpetions Email Notification [Package](https://packagist.org/packages/jeremykenedy/laravel-exception-notifier) | v0.0.1

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

3. Publish the packages language files by running the following from your projects root folder:

    ```
        php artisan vendor:publish --tag=laravelexceptionnotifier
    ```

5. Configure your email settings in the `.env` file.

5. Add the following (optional) settings to your `.env` file and enter your settings:

    * Note: the defaults for these are located in `config/exception.php`

    ```
        EMAIL_EXCEPTION_ENABLED=false
        EMAIL_EXCEPTION_FROM=email@email.com
        EMAIL_EXCEPTION_TO='email1@gmail.com, email2@gmail.com'
        EMAIL_EXCEPTION_CC=''
        EMAIL_EXCEPTION_BCC=''
        EMAIL_EXCEPTION_SUBJECT=''
    ```

## Screenshots

![Email Notification]( {IMAGE_URL} )

## License

Laravel-Exception-Notifier | A Laravel Excpetions Email Notification Package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)