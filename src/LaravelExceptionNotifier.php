<?php

namespace jeremykenedy\laravelexceptionnotifier;

use Illuminate\Support\ServiceProvider;

class LaravelExceptionNotifier extends ServiceProvider
{
    private $_packageTag = 'laravelexceptionnotifier';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views/', $this->_packageTag);
        $this->mergeConfigFrom(__DIR__.'/config/exceptions.php', $this->_packageTag);
        $this->publishFiles();
    }

    /**
     * Publish files for package.
     *
     * @return void
     */
    private function publishFiles()
    {
        $publishTag = $this->_packageTag;

        // Publish Mailer
        $this->publishes([
            __DIR__.'/App/Mail/ExceptionOccured.php' => app_path('Mail/ExceptionOccured.php'),
        ], $publishTag);

        // Publish email view
        $this->publishes([
            __DIR__.'/resources/views/emails/exception.blade.php' => resource_path('views/emails/exception.blade.php'),
        ], $publishTag);

        // Publish config file
        $this->publishes([
            __DIR__.'/config/exceptions.php' => config_path('exceptions.php'),
        ], $publishTag);
    }
}
