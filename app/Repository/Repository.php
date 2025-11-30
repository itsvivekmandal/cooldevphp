<?php

namespace App\Repository;

class Repository
{
    /**
     * The model class used by the repository
     *
     * @var \Illuminate\Database\Eloquent\Model|\Jenssegers\Mongodb\Eloquent\Model
     */
    protected static $model;

    /**
     * Find the first record based on filter and project fields
     *
     * @param array $project Fields to select
     * @param array $filter Conditions for querying
     * @param array $sort Sorting order
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function find($project = [], $filter = [], $sort = []): ?array
    {
        if (!static::$model) {
            throw new \Exception("Model not defined for this repository.");
        }

        // Start query
        $query = static::$model::query();

        // Apply filters
        if (!empty($filter)) {
            foreach ($filter as $field => $value) {
                $query->where($field, $value);
            }
        }

        // Apply projection (select specific fields)
        if (!empty($project)) {
            $query->select($project);
        }

        // Apply sorting
        if (!empty($sort)) {
            foreach ($sort as $field => $direction) {
                $query->orderBy($field, $direction);
            }
        }

        // Return first matching record
        return $query->first()->toArray();
    }
}
