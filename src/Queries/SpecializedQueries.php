<?php

namespace Simple\ElasticSearch\Queries;

class SpecializedQueries {

    /**
     * @param $fields
     * @param $values
     * @return mixed
     */
    public static function more_like_this($fields, $values)
    {
        $params['more_like_this'] = [
            'fields' => $fields,
            'like' => $values[0],
            'min_term_freq' => 1,
            'max_query_terms' => 12
        ];

        return $params;
    }
}