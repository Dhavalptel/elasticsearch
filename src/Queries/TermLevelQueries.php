<?php

namespace Simple\ElasticSearch\Queries;

class TermLevelQueries {

    /**
     * @param $fields
     * @param $values
     * @param $customOperator
     * @return mixed
     */
    public static function range($fields, $values, $customOperator)
    {
        foreach ($fields as $key => $field) {
            if ($customOperator == 'between') {
                $params['range'][$fields[0]] = [
                    'gte' => $values[0],
                    'lte' => $values[1],
                ];
            } else {
                $params['bool']['filter'][] = [
                    'range' => [
                        $field => [
                            $customOperator => $values[$key],
                        ],
                    ],
                ];
            }
        }
        return $params;
    }

    /**
     * @param $fields
     * @return mixed
     */
    public static function exists($fields)
    {
        $params['exists'] = [
            'field' => $fields[0],
        ];
        return $params;
    }

    /**
     * @param $values
     * @return mixed
     */
    public static function ids($fields, $values)
    {
        $params['ids'] = [
            'values' => $values,
        ];
        return $params;
    }

    /**
     * @param $fields
     * @param $values
     * @return mixed
     */
    public static function prefix($fields, $values)
    {
        $params['prefix'][$fields[0]] = [
            'value' => $values[0],
        ];
        return $params;
    }
}
