<?php

namespace Simple\ElasticSearch;

use Elasticsearch\ClientBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Client
{
    /**
     * @return \Elasticsearch\Client
     */
    public static function client()
    {
        $connectionType = config('simple-elasticsearch.connection.type');

        $clientBuilder = ClientBuilder::create()
            ->setHosts(config('simple-elasticsearch.credentials.host') .':'.config('simple-elasticsearch.credentials.port'));

        if ($connectionType === 'basic') {
            $clientBuilder->setBasicAuthentication(
                config('simple-elasticsearch.credentials.user'),
                config('simple-elasticsearch.credentials.pass')
            );
        } elseif ($connectionType === 'api') {
            $clientBuilder->setApiKey(
                config('simple-elasticsearch.credentials.id'),
                config('simple-elasticsearch.credentials.key')
            );
        }
        return $clientBuilder
            ->setRetries(0)
            ->build();
    }


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
        $data = self::client()->search($params);
        $perPage = 10;

        $collection = new Collection($data['hits']['hits']);
        $page = request()->input('page', 1);

        $paginatedData = new LengthAwarePaginator(
            $collection->forPage($page, $perPage),
            $collection->count(),
            $perPage,
            $page,
            ['path' => url()->full()]
        );
        unset($data['hits']['hits']);
        $metadata = [
            'took' => $data['took'],
            'timed_out' => $data['timed_out'],
            '_shards' => $data['_shards'],
            'hits' => $data['hits'],
        ];
        $response = [
            'data' => $paginatedData,
            'metadata' => $metadata,
        ];

        return $response;
    }

    /**
     * @param $operator
     * @return mixed
     */
    public static function searchOperators($operatorValue, $operatorKey)
    {
        $operatorArray = config('simple-elasticsearch.operators');
        if (str_contains($operatorKey, '.')) {
            list($firstKey, $secondKey) = explode('.', $operatorKey);
            return in_array($operatorValue, $operatorArray[$firstKey][$secondKey]);
        } else {
            return in_array($operatorValue,$operatorArray[$operatorKey]);
        }
    }

    /**
     * @param $fields
     * @param $operator
     * @param $values
     * @param null $customComparison
     * @param bool $rangeComparison
     * @return array
     */
    public static function searchParams($fields, $operator, $values, $customComparison = null)
    {
        foreach ($fields as $key => $field) {
            if (self::searchOperators($operator, 'equal') || self::searchOperators($operator, 'not_equal')) {
                $searchParams[] = [
                    'match_phrase' => [
                        $field => $values[$key],
                    ]
                ];
            } elseif ($customComparison) {
                $searchParams[] = [
                    'range' => [
                        $field => [
                            $customComparison => $values[$key],
                        ],
                    ],
                ];
            } elseif (self::searchOperators($operator, 'like') || self::searchOperators($operator, 'not_like')) {
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

        if (self::searchOperators($operator, 'where') || self::searchOperators($operator, 'not_where')) {
            foreach ($values as $key => $value) {
                $searchParams[] = [
                    'match' => [
                        $fields[0] => $value,
                    ]
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
            case (self::searchOperators($operator, 'equal')) ||  (self::searchOperators($operator, 'like')):
                $params['body']['query']['bool']['must'] = self::searchParams($fields, $operator, $values);
                break;

            case (self::searchOperators($operator, 'not_equal')) ||  (self::searchOperators($operator, 'not_like') || self::searchOperators($operator, 'not_where')):
                $params['body']['query']['bool']['must_not'] = self::searchParams($fields, $operator, $values);
                break;

            case (self::searchOperators($operator, 'where')):
                $params['body']['query']['bool']['should'] = self::searchParams($fields, $operator, $values);
                break;

            case (self::searchOperators($operator, 'range_comparison.greater_than')):
                $params['body']['query']['bool']['filter'] = self::searchParams($fields, $operator, $values, 'gt');
                break;

            case (self::searchOperators($operator, 'range_comparison.greater_than_equal')):
                $params['body']['query']['bool']['filter'] = self::searchParams($fields, $operator, $values, 'gte');
                break;

            case (self::searchOperators($operator, 'range_comparison.less_than')):
                $params['body']['query']['bool']['filter'] = self::searchParams($fields, $operator, $values, 'lt');
                break;

            case (self::searchOperators($operator, 'range_comparison.less_than_equal')):
                $params['body']['query']['bool']['filter'] = self::searchParams($fields, $operator, $values, 'lte');
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
