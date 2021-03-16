<?php


namespace W3\Data\Model;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class CollectionFilter
{
    protected
        /**
         * @var Connection
         */
        $conn,

        /**
         * @var QueryBuilder
         */
        $qb,
        $table,
        $criteria = [],
        $filters = [],
        $map = [],
        $sortKey,
        $sortOrderKey,
        $sort,
        $sortOrder,
        $pageKey,
        $pageSizeKey,
        $pageSizes = [500],
        $page = 1,
        $size = 500;

    /**
     * CollectionFilter constructor.
     * @param Connection $connection
     * @param mixed $table string or array of [table => alias]
     * @param array $fieldMap
     * @param array $config
     */
    public function __construct(Connection $connection, $table = null, array $fieldMap = null, array $config = null)
    {
        $this->conn = $connection;
        $this->setTable($table);
        if($fieldMap) {
            $this->setFieldsMap($fieldMap);
        }

        if($config) {
            $this->configure($config);
        }
    }

    /**
     * @param array $config
     */
    public function configure(array $config)
    {
        $this->sortKey = $config['sort_key'] ?? 's';
        $this->sortOrderKey = $config['sort_order_key'] ?? 'so';
        $this->pageKey = $config['page_key'] ?? 'p';
        $this->pageSizeKey = $config['page_size_key'] ?? 'ps';
        $this->pageSizes = $config['page_sizes'] ?? null;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder() : QueryBuilder
    {
        if(!$this->qb) {
            $this->qb = $this->conn->createQueryBuilder();
        }

        return $this->qb;
    }

    /**
     * @param $table
     * @return CollectionFilter
     */
    public function setTable($table) : self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param callable $filter
     * @param string|null $key
     * @return CollectionFilter
     */
    public function addFilter(callable $filter, string $key = null) : self
    {
        if($key) {
            $this->filters[$key][] = $filter;
        } else {
            $this->filters[0][] = $filter;
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string $op
     * @param null $default
     */
    public function addFilterCriteria(string $key, string $op = '=', $default = null)
    {
        $this->criteria[$key][] = [$op, $default];
    }

    /**
     * @param array $map
     * @return CollectionFilter
     */
    public function setFieldsMap(array $map) : self
    {
        $this->map = $map;

        return $this;
    }

    /**
     * @param string $sortKey
     * @param string|null $orderKey
     * @return CollectionFilter
     */
    public function setSortingKeys(string $sortKey, string $orderKey = null) : self
    {
        $this->sortKey = $sortKey;
        $this->sortOrderKey = $orderKey;

        return $this;
    }

    /**
     * @param string $pageKey
     * @param string|null $pageSizeKey
     * @param array|null $pageSizes
     * @return CollectionFilter
     */
    public function setPagingKeys(string $pageKey, string $pageSizeKey = null, array $pageSizes = null) : self
    {
        $this->pageKey = $pageKey;
        $this->pageSizeKey = $pageSizeKey;
        $this->pageSizes = $pageSizes;

        return $this;
    }

    /**
     * @param string $sortKey
     * @param string $sortOrder
     * @return CollectionFilter
     */
    public function setSort(string $sortKey, string $sortOrder = 'asc') : self
    {
        $this->sort = $sortKey;
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @param int $page
     * @param int $size
     * @return CollectionFilter
     */
    public function setPage(int $page, int $size) : self
    {
        $this->page = $page;
        $this->size = $size;

        return $this;
    }

    /**
     * @param array $values
     * @return array
     * @throws \Exception
     */
    public function filter(array $values) : array
    {
        $builder = $this->conn->createQueryBuilder();
        if($this->table) {
            if(is_array($this->table)) {
                $table = current($this->table);
                $alias = key($this->table);
            } else {
                $table = $this->table;
                $alias = null;
            }

            $builder->from($table, $alias);
        }

        // first run through all default all filters
        $filters = $this->filters[0] ?? null;
        if($filters) {
            foreach($filters as $filter) {
                $filter($builder, $values, $this);
            }
        }

        // run the criterion filters
        foreach($this->criteria as $key => $criteria) {
            foreach($criteria as $criterion) {
                [$op, $default] = $criterion;

                if(array_key_exists($key, $values)) {
                    $value = $values[$key];
                } else {
                    $value = $default;
                }

                $field = $this->map[$key]; // won't be able to add $key unless there is a map so this should be safe!
                $this->filterByCriterion($builder, $field, $op, $value);
            }
        }

        // sorting
        $sort = $values[$this->sortKey] ?? $this->sort;
        if($sort) {
            $field = $this->map[$sort] ?? null;
            if(!$field) {
                throw new \Exception("Unknown sorting");
            }

            $order = $values[$this->sortOrderKey] ?? $this->sortOrder ?? 'asc';
            $builder->orderBy($field, $order);
        }

        // paging
        $p = $values[$this->pageKey] ?? $this->page;
        $size = $values[$this->pageSizeKey] ?? $this->size;
        if(!in_array($size, $this->pageSizes)) {
            throw new \Exception('Invalid page size provided.');
        }

        $offset = ($p - 1) * $size;
        $builder->setFirstResult($offset)
                ->setMaxResults($size);

        return $builder->execute()->fetchAll();
    }

    /**
     * @param QueryBuilder $qb
     * @param string $field
     * @param string $op
     * @param null $value
     */
    protected function filterByCriterion(QueryBuilder $qb, string $field, string $op, $value = null)
    {
        static $counter = 0;

        $key = $field . ++$counter;
        switch (strtolower($op)) {
            case '=':
            case '<':
            case '>':
            case '>=':
            case '<=':
                $sql = "{$field} {$op} :{$key}";
                $qb->andWhere($sql)->setParameter($key, $value);
                break;

            case 'like':
                $sql = "{$field} LIKE :{$key}";
                $qb->andWhere($sql)->setParameter($key, '%' . $value . '%');
                break;

            case 'in':
                break;
        }
    }
}