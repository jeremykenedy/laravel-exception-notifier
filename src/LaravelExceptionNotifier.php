<?php

namespace jeremykenedy\laravelexceptionnotifier;

use Illuminate\Support\ServiceProvider;

class LaravelExceptionNotifier extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

        // Publish Mailer
        $this->publishes([
            __DIR__.'/App/Mail/ExceptionOccured.php' => app_path('Mail/ExceptionOccured.php'),
        ], 'laravelexceptionnotifier');

        // Publish email view
        $this->publishes([
            __DIR__.'/resources/views/emails/exception.blade.php' => resource_path('views/emails/exception.blade.php'),
        ], 'laravelexceptionnotifier');

        // Publish config file
        $this->publishes([
            __DIR__.'/config/exceptions.php' => config_path('exceptions.php'),
        ], 'laravelexceptionnotifier');

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }
}