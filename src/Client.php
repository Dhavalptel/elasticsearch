<?php

namespace Simple\ElasticSearch;

use Elasticsearch\ClientBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Simple\ElasticSearch\Queries\MatchPhrase;

class Client
{
    /**
     * @return \Elasticsearch\Client
     */
    public static function client()
    {
        $connectionType = config('simple-elasticsearch.connection.type');

        $clientBuilder = ClientBuilder::create()
            ->setHosts([
                'host' => config('simple-elasticsearch.credentials.host'),
                'port' => config('simple-elasticsearch.credentials.port')
            ]);

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
        if (isset($tableData['fields'])) {
            foreach ($tableData['fields'] as $field) {
                if ($field['datatype'] == 'integer') {
                    $properties[$field['column_name']] = [
                        'type' => 'text',
                    ];
                }
            }

            $body = [
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
            ];
        } else {
            $relations = array_values($tableData->getQueueableRelations());
            $mappings = self::nestedMapping($relations);
            $body = [
                'mappings' => $mappings
            ];
        }

        $params = [
            'index' => $indexName,
            'body' => $body
        ];

        return self::client()->indices()->create($params);
    }

    /**
     * @param $index
     * @param $data
     * @return array
     */
    public static function store($index, $data)
    {
        $params = [
            'index' => $index,
        ];
        foreach ($data as $value) {
            $params['body'] = $value;
            $response = self::client()->index($params);
        }
        return $response;
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
        return [
            'data' => $paginatedData,
            'metadata' => $metadata,
        ];
    }

    /**
     * @param $operatorValue
     * @return int|string|null
     */
    public static function searchOperators($operatorValue)
    {
        $operatorArray = config('simple-elasticsearch.operators');
        foreach (array_merge($operatorArray, $operatorArray['range_comparison']) as $key => $item) {
            if (in_array($operatorValue, $item)) {
                return $key;
            }
        }
        return null;
    }

    /**
     * @param $index
     * @param $size
     * @param $fields
     * @param $operator
     * @param $values
     * @return array
     */
    /**
     * @param $index
     * @param $fields
     * @param $operator
     * @param $values
     * @param null $nestedData
     * @return array
     */
    public static function search($index, $fields, $operator, $values, $nestedData=null)
    {
        $params = [
            'index' => $index,
            'size' => 10000,
        ];

        $searchOperator = self::searchOperators($operator);
        $queryMethod = self::getQueryMethod($searchOperator);

        if ($queryMethod) {
            $class = key($queryMethod);
            $method = current($queryMethod);
            (is_array($method) ? list($customOperator, $method) = $method : $customOperator = $searchOperator);
            $methodParams = ($searchOperator == 'relation') ? [$fields, $values, $customOperator, $nestedData] : [$fields, $values, $customOperator];
            $methodName = (__NAMESPACE__."\Queries\\".$class)."::".$method;

            $params['body']['query'] = call_user_func_array($methodName, $methodParams);
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

    /**
     * @param $operator
     * @return mixed|null
     */
    public static function getQueryMethod($operator)
    {
        $queryMethods = [
            'equal' => ['FullTextQueries' => 'match_phrase'],
            'not_equal' => ['FullTextQueries' => 'match_phrase'],
            'like' => ['FullTextQueries' => 'query_string'],
            'not_like' => ['FullTextQueries' => 'query_string'],
            'where' => ['FullTextQueries' => 'match'],
            'not_where' => ['FullTextQueries' => 'match'],
            'greater_than' => ['TermLevelQueries' => ['gt','range']],
            'greater_than_equal' => ['TermLevelQueries' => ['gte','range']],
            'less_than' => ['TermLevelQueries' => ['lt', 'range']],
            'less_than_equal' => ['TermLevelQueries' => ['lte','range']],
            'search' => ['FullTextQueries' => 'match'],
            'multi-match' =>['FullTextQueries' =>  'multi_match'],
            'more-like-this' => ['SpecializedQueries' => 'more_like_this'],
            'exists' => ['TermLevelQueries' => 'exists'],
            'id-exists' => ['TermLevelQueries' => 'ids'],
            'prefix' => ['TermLevelQueries' =>  'prefix'],
            'between' => ['TermLevelQueries' => ['between','range']],
            'relation' => ['JoiningQueries' => 'nested'],
        ];

        return $queryMethods[$operator] ?: null;
    }

    /**
     * @param $relations
     * @return array
     */
    public static function nestedMapping($relations)
    {
        $mapping = [];
        foreach ($relations as $relation) {
            $nestedMapping = &$mapping;
            $parts = explode('.', $relation);
            foreach ($parts as $part) {
                $nestedMapping = &$nestedMapping['properties'][$part];
            }
            $nestedMapping = ['type' => 'nested'];
        }
        return $mapping;
    }

}
