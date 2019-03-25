<?php

namespace jeremykenedy\laravelexceptionnotifier\Test;

use jeremykenedy\laravelexceptionnotifier\LaravelExceptionNotifier;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * Load package service provider.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return jeremykenedy\laravelexceptionnotifier\LaravelExceptionNotifier
     */
    protected function getPackageProviders($app)
    {
        return [LaravelExceptionNotifier::class];
    }

    /**
     * Load package alias.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'laravelexceptionnotifier',
        ];
    }
}
