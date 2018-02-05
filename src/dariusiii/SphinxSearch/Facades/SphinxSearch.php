<?php

namespace dariusiii\SphinxSearch;

use Illuminate\Support\Facades\Facade;

class SphinxSearch extends Facade
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