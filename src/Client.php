<?php

namespace Simple\ElasticSearch;

use Elasticsearch\ClientBuilder;

class Client
{
    /**
     * @return \Elasticsearch\Client
     */
    public static function client()
    {
        $connection = config('simple-elasticsearch.connection.host').':'.config('simple-elasticsearch.connection.port');
        return ClientBuilder::create()
            ->setHosts([$connection])
            ->setRetries(0)
            ->build();
    }

    /**
     * @param $operator
     * @return mixed
     */
/*    public static function searchOperators($operator)
    {
        return Arr::get(config('simple-elasticsearch.operators') );
    }*/

    /**
     * @param $indexName
     * @param $tableData
     * @return string
     */
    public static function createIndex($indexName, $tableData)
    {
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
        $response = self::client()->indices()->create($params);

        return ($response['acknowledged']) ? "Index ".$indexName." created successfully." : "Failed to create index ".$indexName;
    }

    /**
     * @param $index
     * @param $type
     * @param $id
     * @param $body
     * @return array
     */
    public static function store($index, $type, $id, $body)
    {
        $params = [
            'index' => $index,
            'id' => $id,
            'type' => $type,
            'body' => $body
        ];
        return self::client()->index($params);
    }

    /**
     * @param $params
     * @return array
     */
    public static function storeBulk($params)
    {
        return self::client()->bulk($params);
    }

    /**
     * @param $params
     * @return array
     */
    public static function get($params)
    {
        return self::client()->search($params);
    }

    /**
     * @param $fields
     * @param $operator
     * @param $values
     * @param null $Operator
     * @return array
     */
    public static function searchParams($fields, $operator, $values, $Operator=null)
    {
        foreach ($fields as $key => $field) {
            if ($operator == 'EX' || $operator == 'XEX') {
                $searchParams[] = [
                    'match_phrase' => [
                        $field => $values[$key],
                    ]
                ];
            } elseif ($operator == 'GT' || $operator == 'GTE' || $operator == 'LT' || $operator == 'LTE') {
                $searchParams[] = [
                    'range' => [
                        $field => [
                            $Operator => $values[$key],
                        ],
                    ],
                ];
            } elseif ($operator == 'CT' || $operator == 'XCT') {
                $search = str_replace(' ',' AND ',$values[$key]);
                $search = (preg_match('/[^A-Za-z0-9]/', $search)) ? '%'.$search.'%':'*'.$search.'*';
                $searchParams[] = [
                    'query_string' => [
                        'query' => $search,
                        'default_field' => $field,
                    ],
                ];
            }
        }
        return $searchParams;
    }

    /**
     * @param $index
     * @param $size
     * @param $fields
     * @param $operator
     * @param $values
     * @return array
     */
    public static function search($index, $size, $fields, $operator, $values)
    {
        $params = [
            'index' => $index,
            'size' => $size,
        ];

        switch ($operator) {
            case 'EX' ||  'CT':
                $params['body']['query']['bool']['must'] = self::searchParams($fields, $operator, $values);
                break;

            case 'XEX' || 'XCT':
                $params['body']['query']['bool']['must_not'] = self::searchParams($fields, $operator, $values);
                break;

            case 'GT':
            case 'GTE':
            case 'LT':
            case 'LTE':
                switch ($operator) {
                    case 'GT':
                        $Operator = 'gt';
                        break;

                    case 'GTE':
                        $Operator = 'gte';
                        break;

                    case 'LT':
                        $Operator = 'lt';
                        break;

                    case 'LTE':
                        $Operator = 'lte';
                        break;
                }
                $params['body']['query']['bool']['filter'] = self::searchParams($fields, $operator, $values, $Operator);
                break;
            case 'HAS':
                $params['body']['query']['bool']['must'] = [
                    'terms' => [
                        $fields[0] => $values,
                    ]
                ];
                break;

            case 'XHAS':
                $params['body']['query']['bool']['must_not'] = [
                    'terms' => [
                        $fields[0] => $values,
                    ]
                ];
                break;

            default:
                break;
        }

        return self::get($params);
    }

    /**
     * @param $index
     * @param $size
     * @param $field
     * @param $order
     * @return array
     */
    public static function sorting($index, $size, $field, $order)
    {
        $params = [
            'index' => $index,
            'size' => $size,
            'body' => [
                'sort' => [
                    [
                        $field => [
                            'order' => $order,
                        ],
                    ],
                ],
            ],
        ];

        return self::get($params);
    }

    /**
     * @param $index
     * @param $id
     * @param $field
     * @param $value
     * @return array
     */
    public static function update($index, $id, $field, $value)
    {
        $params = [
            'index' => $index,
            'id' => $id,
            'body' => [
                'doc' => [
                    'name' => $field,
                    'salary' => $value
                ],
            ],
        ];

        return self::client()->update($params);
    }
}
