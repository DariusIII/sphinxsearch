<?php
namespace dariusiii\SphinxSearch;

use Illuminate\Support\ServiceProvider;

class SphinxSearchServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('sphinxsearch', function ($app) {
            return new SphinxSearch;
        });
        
        $this->app->alias('SphinxSearch', SphinxSearch::class);
    }


    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/sphinxsearch.php' => config_path('sphinxsearch.php')
        ]);
    }

}
