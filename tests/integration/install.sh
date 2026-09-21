#!/usr/bin/env bash
set -euo pipefail

package_path="$(cd "$(dirname "$0")/../.." && pwd)"
fixture_path="$(mktemp -d)"
trap 'rm -rf "$fixture_path"' EXIT

composer create-project laravel/laravel "$fixture_path/app" '^13.0' --prefer-dist --no-interaction --no-scripts
cd "$fixture_path/app"
cp .env.example .env
composer config repositories.exception-notifier path "$package_path"
composer require jeremykenedy/laravel-exception-notifier:@dev --no-interaction
php artisan exception-notifier:install --no-interaction
mkdir -p app/Traits app/Exceptions
cp "$package_path/src/App/Traits/ExceptionNotificationHandlerTrait.php" app/Traits/ExceptionNotificationHandlerTrait.php
cat > app/Exceptions/Handler.php <<'PHP'
<?php

namespace App\Exceptions;

class Handler extends \Illuminate\Foundation\Exceptions\Handler
{
    use \App\Traits\ExceptionNotificationHandlerTrait;

    protected $dontReport = [\LogicException::class];

    protected $internalDontReport = [];
}
PHP

php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config()->set("mail.default", "array");
config()->set("mail.mailers.array", ["transport" => "array"]);
config()->set("exceptions.emailExceptionsTo", "developer@example.com");
foreach ([
    App\Mail\ExceptionOccurred::class => app_path("Mail/ExceptionOccurred.php"),
    App\Traits\ExceptionNotificationHandlerTrait::class => app_path("Traits/ExceptionNotificationHandlerTrait.php"),
] as $class => $path) {
    if ((new ReflectionClass($class))->getFileName() !== $path) {
        throw new RuntimeException("Application class was not loaded from ".$path);
    }
}
Illuminate\Support\Facades\Mail::send(new App\Mail\ExceptionOccurred(["message" => "Installation smoke test"]));
$message = Illuminate\Support\Facades\Mail::mailer()->getSymfonyTransport()->messages()->first()->getOriginalMessage();
if (!str_contains($message->getHtmlBody(), "Installation smoke test")) {
    exit(1);
}
$transport = Illuminate\Support\Facades\Mail::mailer()->getSymfonyTransport();
$handler = new App\Exceptions\Handler($app);
config()->set("exceptions.emailExceptionEnabled", true);
foreach ([
    new LogicException("Application exclusion"),
    new Illuminate\Auth\AuthenticationException,
    new Illuminate\Auth\Access\AuthorizationException,
    new Symfony\Component\HttpKernel\Exception\HttpException(404),
    new Illuminate\Database\Eloquent\ModelNotFoundException,
    new Illuminate\Session\TokenMismatchException,
    Illuminate\Validation\ValidationException::withMessages(["email" => "Invalid email"]),
] as $exception) {
    $handler->report($exception);
}
if ($transport->messages()->count() !== 1) {
    throw new RuntimeException("An ignored exception sent an email.");
}
$handler->report(new RuntimeException("Handler integration test"));
if ($transport->messages()->count() !== 2) {
    throw new RuntimeException("The installed handler did not send an email.");
}
config()->set("exceptions.emailExceptionEnabled", false);
$handler->report(new RuntimeException("Disabled notification"));
if ($transport->messages()->count() !== 2) {
    throw new RuntimeException("A disabled notification sent an email.");
}
'

php artisan exception-notifier:update --layout=modern --theme=dark --force --no-interaction
php artisan config:cache
php artisan view:cache

php -r '
$files = ["config/exceptions.php", "app/Mail/ExceptionOccurred.php", "resources/views/emails/exception.blade.php"];
file_put_contents("before-update.json", json_encode(array_map("hash_file", array_fill(0, count($files), "sha256"), $files)));
'
composer update jeremykenedy/laravel-exception-notifier --no-interaction
php artisan exception-notifier:update --no-interaction
php -r '
$files = ["config/exceptions.php", "app/Mail/ExceptionOccurred.php", "resources/views/emails/exception.blade.php"];
$before = json_decode(file_get_contents("before-update.json"), true);
$after = array_map("hash_file", array_fill(0, count($files), "sha256"), $files);
if ($before !== $after) {
    fwrite(STDERR, "Composer or package update changed published files.\n");
    exit(1);
}
'
echo 'Fresh application installation and cached update passed.'
