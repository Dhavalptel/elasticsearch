<?php

use Elasticsearch\ClientBuilder;

test('connection', function () {
    $client = ClientBuilder::create()
        ->setConnectionParams([
            'host' => 'http://localhost',
            'port' => '9200',
        ])
        ->setRetries(0)
        ->build();
    $response = $client->ping();
    expect($response)->toEqual(1);
});
