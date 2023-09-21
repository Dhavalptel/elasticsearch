<?php

namespace Simple\ElasticSearch\Queries;

class FullTextQueries {

    /**
     * @param $fields
     * @param $values
     * @param $customOperator
     * @return mixed
     */
    public static function match_phrase($fields, $values, $customOperator)
    {
        $arg = ($customOperator == 'equal') ? 'must' : 'must_not';
        foreach ($fields as $key => $field) {
            $params['bool'][$arg][] = [
                'match_phrase' => [
                    $field => $values[$key],
                ]
            ];
        }
        return $params;
    }

    /**
     * @param $fields
     * @param $values
     * @param $customOperator
     * @return mixed
     */
    public static function match($fields, $values, $customOperator)
    {
        $arg = ($customOperator == 'where') ? 'should' : 'must_not';

        foreach ($values as $value) {
            if ($customOperator == 'search') {
                $params['match'][$fields[0]] = [
                    'query' => $values[0],
                    'fuzziness' => 'AUTO'
                ];
            } else {
                $params['bool'][$arg][] = [
                    'match' => [
                        $fields[0] => $value,
                    ]
                ];
            }
        }
        return $params;
    }

    /**
     * @param $fields
     * @param $values
     * @param $customOperator
     * @return mixed
     */
    public static function query_string($fields, $values, $customOperator)
    {
        $arg = ($customOperator == 'like') ? 'must' : 'must_not';

        foreach ($fields as $key => $field) {
            $search = str_replace(' ',' AND ',$values[$key]);
            $search = (preg_match('/[^A-Za-z0-9]/', $search)) ? '%'.$search.'%':'*'.$search.'*';
            $params['bool'][$arg][] = [
                'query_string' => [
                    'query' => $search,
                    'default_field' => $field,
                ],
            ];
        }
        return $params;
    }

    /**
     * @param $fields
     * @param $values
     * @return mixed
     */
    public static function multi_match($fields, $values)
    {
        $params['multi_match'] = [
            'query' => $values[0],
            'type' => 'best_fields',
            'fields' => $fields
        ];
        return $params;
    }
}
