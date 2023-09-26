<?php

namespace Simple\ElasticSearch\Queries;

class JoiningQueries {
    /**
     * @param $fields
     * @param $values
     * @param $customOperator
     * @param $nestedData
     * @return mixed
     */
    public static function nested($fields, $values, $customOperator, $nestedData)
    {
        // normal search
        foreach ($fields as $key => $field) {
            $fieldValue = $values[$key];
            $params['bool']['should'][] = [
                'match' => [
                    $field => $fieldValue
                ]
            ];
        }

        // nested search
        if ($nestedData['path']) {
            foreach ($nestedData['nestedField'] as $key => $nfield) {
                $fieldValue = $nestedData['nestedValue'][$key];
                $params['bool']['should'][] = [
                    'nested' => [
                        'path' => $nestedData['path'],
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'match' => [
                                            $nestedData['path'].'.'.$nfield => $fieldValue
                                        ]
                                    ]
                                ]
                            ],
                        ],
                    ]
                ];
            }
        }

        return $params;
    }
}
