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
        ]
    ]
];