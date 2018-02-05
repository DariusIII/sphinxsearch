<?php

namespace dariusiii\SphinxSearch;

use Illuminate\Support\Facades\Facade;

class SphinxSearchFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'SphinxSearch';
    }
}