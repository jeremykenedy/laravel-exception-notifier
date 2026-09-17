<?php

require dirname(__DIR__).'/vendor/autoload.php';

use jeremykenedy\laravelexceptionnotifier\LaravelExceptionNotifier;
use Orchestra\Testbench\Foundation\Application;

$app = Application::create();
$app->register(LaravelExceptionNotifier::class);
$app['config']->set('app.name', 'Acme Store');
$app['files']->ensureDirectoryExists(dirname(__DIR__).'/build/previews');
$content = [
    'message' => 'Unable to complete the checkout request',
    'file'    => '/var/www/app/Services/Checkout.php',
    'line'    => 42,
    'url'     => 'https://example.com/checkout',
    'ip'      => '192.0.2.24',
    'trace'   => [
        ['class' => 'App\\Services\\Checkout', 'type' => '->', 'function' => 'submit', 'file' => '/var/www/app/Http/Controllers/CheckoutController.php', 'line' => 28],
        ['class' => 'App\\Http\\Controllers\\CheckoutController', 'type' => '->', 'function' => 'store', 'file' => '/var/www/vendor/laravel/framework/src/Illuminate/Routing/Controller.php', 'line' => 54],
        ['function' => 'call_user_func'],
    ],
];

foreach (['exception', 'bootstrap5', 'tailwind'] as $layout) {
    foreach (['light', 'dark', 'system'] as $theme) {
        $html = $app['view']->make('laravelexceptionnotifier::emails.'.$layout, compact('content', 'theme'))->render();
        file_put_contents(dirname(__DIR__).'/build/previews/'.$layout.'-'.$theme.'.html', $html);
    }
}

$content['message'] = str_repeat('LongExceptionMessage', 30);
$content['file'] = '/'.str_repeat('long-path/', 45).'file.php';
$content['url'] = 'https://example.com/'.str_repeat('long-url', 60);
$content['trace'][0]['class'] = str_repeat('Namespace\\', 50).'Checkout';
foreach (['exception', 'bootstrap5', 'tailwind'] as $layout) {
    file_put_contents(
        dirname(__DIR__).'/build/previews/'.$layout.'-long.html',
        $app['view']->make('laravelexceptionnotifier::emails.'.$layout, ['content' => $content])->render()
    );
}

echo "Email previews written to build/previews.\n";
