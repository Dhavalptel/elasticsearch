<?php

use Elasticsearch\ClientBuilder;
use Illuminate\Pagination\LengthAwarePaginator;

//Connection before running any test
beforeEach(function () {
    $this->connection = ClientBuilder::create()
        ->setConnectionParams([
            'http://localhost:9200'
        ])
        ->setRetries(0)
        ->build();
});

//Get index
test('get', function () {
    $params = [
        'index' => 'test_table1',
        'body' => [
            'query' => [
                'match_all' => (object)[],
            ],
        ],
    ];

    $data = $this->connection->search($params);
    $count = $data['hits']['total']['value'];
    expect($count)->toBeInt()->toBeGreaterThan(0);

})->skip();

//Create index
test('create', function () {
    $indexName = 'create_index_test';
    $tableData = [
        'fields' => [
            [
                'column_name' => 'name',
                'datatype' => 'varchar',
                'length' => 255,
            ],
            [
                'column_name' => 'salary',
                'datatype' => 'integer',
                'length' => 11,
            ]
        ]
    ];

    foreach ($tableData['fields'] as $field) {
        if ($field['datatype'] == 'integer') {
            $properties[$field['column_name']] = [
                'type' => 'text',
            ];
        }
    }

    $params = [
        'index' => $indexName,
        'body' => [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ],
            'mappings' => [
                '_source' => [
                    'enabled' => true,
                ],
                'properties' => $properties
            ],
        ],
    ];

    // Create the index
    $response = $this->connection->indices()->create($params);
    expect(true)->toBeTrue($response['acknowledged']);
});

//Store index
test('store', function () {
    $id=1;
    $index = 'store_index_test';
    $type = 'index';
    $params = [
        'index' => $index,
        'id' => $id,
        'body' => [
            "name" => "Voluptas accusantium.",
            "salary" => 11116
        ]
    ];

    $response = $this->connection->index($params);
    expect('created')->toEqual($response['result']);
});

//Update index
test('update', function () {
    $index= 'store_index_test';
    $id = 1;
    $field1 = 'updated name';
    $field2 = 1200;

    $params = [
        'index' => $index,
        'id' => $id,
        'body' => [
            'doc' => [
                'name' => $field1,
                'salary' => $field2
            ],
        ],
    ];

    $response = $this->connection->update($params);
    expect('updated')->toEqual($response['result']);
});

//Cleaning elasticsearch after any test
afterAll(function () {
    $createIndex = 'create_index_test';
    $storeIndex = 'store_index_test';
    $indexes = [$createIndex, $storeIndex];
    $connection = ClientBuilder::create()
        ->setConnectionParams([
            'http://localhost:9200'
        ])
        ->setRetries(0)
        ->build();
    foreach ($indexes as $index) {
        $params = ['index' => $index];
        $connection->indices()->delete($params);
    }
});
