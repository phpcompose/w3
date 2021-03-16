<?php

namespace W3\Data\Query;


use Doctrine\DBAL\Query\QueryBuilder;

class QueryFilterer
{
    /**
     * @var array
     */
    protected $filters = [];

    public function __construct()
    {
    }

    /**
     * @param callable $filter
     */
    public function add(callable $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @return array
     */
    public function getFilters() : array
    {
        return $this->filters;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $values
     * @return QueryBuilder
     */
    public function filter(QueryBuilder $qb, array $values) : QueryBuilder
    {
        foreach($this->filters as  $filter) {
            $filter($qb, $values);
        }

        return $qb;
    }
}