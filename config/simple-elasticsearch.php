<?php

declare(strict_types=1);

return [

    /**
     * Elasticsearch connection
     */
    'connection' => [
        'type' => 'basic' //api
    ],

    /**
     * Elasticsearch credentials
     */
    'credentials' => [
        'host' => env('ELASTIC_HOST',null),
        'port' => env('ELASTIC_PORT',null),
        'user' => env('ELASTIC_USER',null),
        'pass' => env('ELASTIC_PASS',null),
        'id' => env('ELASTIC_ID',null),
        'key' => env('ELASTIC_KEY',null)
    ],

    /**
     * Operators for elastic search conditions
     */
    'operators' => [
        'equal' => [
            '=',
            'EX',
            'equal',
        ],
        'not_equal' => [
            '!=',
            '<>',
            'XEX',
            'not equal'
        ],
        'range_comparison' => [
            'greater_than' => [
                '>',
                'gt',
                'greater than',
                'GT',
            ],
            'greater_than_equal' => [
                '>=',
                'gte',
                'greater than equal',
                'GTE',
            ],
            'less_than' =>[
                '<',
                'lt',
                'less than',
                'LT',
            ],
            'less_than_equal' =>[
                '<=',
                'lte',
                'less than equal',
                'LTE',
            ],
        ],
        'like' => [
            'like',
            'CT'
        ],
        'not_like' => [
            'not like',
            'XCT',
        ],
        'where' => [
            'where in',
            'HAS'
        ],
        'not_where' => [
            'not where in',
            'XHAS',
        ],
        'search' => [
            'find',
            'search',
        ],
        'multi-match' => [
            'multi-match',
        ],
        'more-like-this' => [
            'more-like-this',
        ],
        'exists' => [
            'exists',
        ],
        'id-exists' => [
            'id-exists',
        ],
        'prefix' => [
            'prefix',
        ],
        'between' => [
            'between',
        ],
    ]
];