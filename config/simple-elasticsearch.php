<?php

declare(strict_types=1);

return [

    /**
     * Elasticsearch connection
     */
    'connection' => [
        'host' => env('ELASTIC_HOST'),
        'port' => env('ELASTIC_PORT'),
    ],
];