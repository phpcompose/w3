<?php

namespace W3\Data\Query;


use Doctrine\DBAL\Query\QueryBuilder;

class FieldFilter
{
    protected static $counter = 0;
    protected
        $value,
        $key,
        $op,
        $field;

    /**
     * FieldFilterer constructor.
     * @param string $key
     * @param string $field
     * @param string $op
     * @param null $defaultValue
     */
    public function __construct(string $key, string $field, string $op = '=', $defaultValue = null)
    {
        $this->key = $key;
        $this->field = $field;
        $this->op = $op;
        $this->value = $defaultValue;

        self::$counter++;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $values
     */
    public function __invoke(QueryBuilder $qb, array $values)
    {
        $value = $values[$this->key] ?? $this->value;
        $key = $this->key . self::$counter;

        switch (strtolower($this->op)) {
            case '=':
            case '<':
            case '>':
            case '>=':
            case '<=':
                $sql = "{$this->field} {$this->op} :{$key}";
                $qb->andWhere($sql)->setParameter($key, $value);
                break;

            case 'like':
                $sql = "{$this->field} LIKE :{$key}";
                $qb->andWhere($sql)->setParameter($key, '%' . $value . '%');
                break;

            case 'in':
                break;
        }
    }
}