<?php

namespace jeremykenedy\laravelexceptionnotifier;

use Illuminate\Support\Facades\Facade;

class LaravelExceptionNotifierFacade extends Facade
{
    /**
     * Gets the facade accessor.
     *
     * @return string The facade accessor.
     */
    protected static function getFacadeAccessor()
    {
        return 'laravelexceptionnotifier';
    }
}
