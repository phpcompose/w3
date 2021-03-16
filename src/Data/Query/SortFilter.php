<?php


namespace W3\Data\Query;


use Doctrine\DBAL\Query\QueryBuilder;

class SortFilter
{
    protected
        $maps = [],
        $defaultSort,
        $defaultOrder,
        $sortKey,
        $sortField,
        $orderKey;

    /**
     * SortFilter constructor.
     * @param string $sortKey
     * @param string $orderKey
     * @param array $availableSorts
     * @param string $defaultSort
     * @param string $defaultOrder
     */
    public function __construct(string $sortKey, string $orderKey, array $availableSorts, string $defaultSort, string $defaultOrder = 'asc')
    {
        $this->sortKey = $sortKey;
        $this->orderKey= $orderKey;
        $this->maps = $availableSorts;
        $this->defaultSort = $defaultSort;
        $this->defaultOrder = $defaultOrder;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $values
     * @throws \Exception
     */
    public function __invoke(QueryBuilder $qb, array $values)
    {
        $sort = $values[$this->sortKey] ?? $this->defaultSort;
        $order = $values[$this->orderKey] ?? $this->defaultOrder ?? 'asc';

        $field = $this->maps[$sort] ?? null;
        if(!$field) {
            throw new \Exception("Unknown filter");
        }

        if($sort) {
            $qb->orderBy($field, $order);
        }
    }
}