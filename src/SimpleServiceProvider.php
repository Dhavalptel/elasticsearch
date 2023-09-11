<?php

namespace Simple\ElasticSearch;

use Illuminate\Support\ServiceProvider;

class SimpleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/elastic.php' => config_path('elastic.php')
        ], 'simple-elasticsearch');
    }
}