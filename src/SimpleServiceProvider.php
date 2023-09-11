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
            __DIR__.'/../config/simple-elasticsearch.php' => config_path('simple-elasticsearch.php')
        ], 'simple-elasticsearch');
    }
}