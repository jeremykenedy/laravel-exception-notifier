<?php

namespace jeremykenedy\laravelexceptionnotifier\Test;

use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelexceptionnotifier\LaravelExceptionNotifier;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected string $sandbox;

    protected function getPackageProviders($app)
    {
        return [LaravelExceptionNotifier::class];
    }

    protected function defineEnvironment($app)
    {
        $this->sandbox = sys_get_temp_dir().'/exception-notifier-'.bin2hex(random_bytes(8));
        $files = new Filesystem;
        foreach (['app', 'config', 'resources/views', 'bootstrap/cache'] as $path) {
            $files->makeDirectory($this->sandbox.'/'.$path, 0755, true);
        }
        $app->useAppPath($this->sandbox.'/app');
        $bootstrap = $app->bootstrapPath();
        $storage = $app->storagePath();
        $app->setBasePath($this->sandbox);
        if (method_exists($app, 'useBootstrapPath')) {
            $app->useBootstrapPath($bootstrap);
        }
        $app->useStoragePath($storage);
        $app['config']->set('view.paths', [$this->sandbox.'/resources/views']);
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', ['transport' => 'array']);
        $app['config']->set('mail.from', ['address' => 'app@example.com', 'name' => 'Example']);
    }

    protected function tearDown(): void
    {
        if (isset($this->sandbox)) {
            (new Filesystem)->deleteDirectory($this->sandbox);
        }
        parent::tearDown();
    }

    protected function configureMail(): void
    {
        config()->set('exceptions', array_merge(
            require dirname(__DIR__).'/src/config/exceptions.php',
            [
                'emailExceptionFrom'    => 'errors@example.com',
                'emailExceptionsTo'     => 'first@example.com, second@example.com',
                'emailExceptionCCto'    => 'cc@example.com',
                'emailExceptionBCCto'   => 'bcc@example.com',
                'emailExceptionSubject' => 'Production exception',
            ]
        ));
    }

    protected function content(): array
    {
        return [
            'message' => 'Unable to complete the request',
            'file'    => '/var/www/app/Services/Checkout.php',
            'line'    => 42,
            'url'     => 'https://example.com/checkout',
            'ip'      => '192.0.2.1',
            'body'    => ['password' => 'must-not-appear'],
            'trace'   => [
                ['class' => 'App\\Services\\Checkout', 'function' => 'submit', 'type' => '->', 'file' => '/var/www/app/Http/Controllers/CheckoutController.php', 'line' => 28],
                ['function' => 'call_user_func'],
            ],
        ];
    }
}
