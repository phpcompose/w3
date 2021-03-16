<?php

namespace W3\Data\Query;


use Doctrine\DBAL\Query\QueryBuilder;

class LimitFilter
{
    protected
        $page,
        $key,
        $size;

    /**
     * LimitFilter constructor.
     * @param string $key
     * @param int $page
     * @param int $size
     */
    public function __construct(string $key, int $page = 1, int $size = 100)
    {
        $this->key = $key;
        $this->size = $size;
        $this->page = $page;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $values
     */
    public function __invoke(QueryBuilder $qb, array $values)
    {
        $size = $this->size;
        $p = $values[$this->key] ?? $this->page;
        $offset = ($p - 1) * $size;

        $qb->setFirstResult($offset)
            ->setMaxResults($size);
    }
}