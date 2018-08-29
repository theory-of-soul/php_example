<?php

namespace ORM\DB\Query;

use Helpers\CommonHelper;
use ORM\DB\DbQueryException;
use Providers\Mysql\PDOResult;

class SelectQuery extends AbstractCommonQuery
{
    private $select = [];
    private $mapCallbacks = [];
    private $group = [];

    public function addMapCallback($object, $method, $arguments = [])
    {
        if (!is_array($arguments)) {
            $arguments = [$arguments];
        }

        $this->mapCallbacks[] = ['object' => $object, 'method' => $method, 'arguments' => $arguments];
    }

    /**
     * @param $fields
     * @return SelectQuery
     */
    public function select($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $key => $field) {
            if (!($field instanceof Field)) {
                $fields[$key] = $this->getField($field);
            }
        }

        $this->select = array_merge($this->select, $fields);

        return $this;
    }

    public function group($field)
    {
        if (!($field instanceof Field)) {
            $field = $this->getField($field);
        }

        $this->group[] = $field;

        return $this;
    }

    protected function getQuery()
    {
        array_map(
            function ($a) { },
            $this->select
        );

        $selectSql = [];

        /**
         * @var $field Field
         */
        foreach ($this->select as $field) {
            if (!$this->hasPrefix($field->getPrefix())) {
                throw new DbQueryException('Unknown prefix "' . $field->getPrefix() . '"');
            }
            $selectSql[] = $field->getFieldName() . ' AS `' . $field->getFieldKey() . '`';
        }

        $sql = 'SELECT ' . implode(', ', $selectSql);
        $sql .= ' FROM `' . $this->wrapper->getTableName() . '` ' . Field::DEFAULT_PREFIX;
        $sql .= ' ' . $this->getJoinsSql();
        $sql .= ' ' . $this->getWhereSql();
        $sql .= ' ' . $this->getOrderSql();
        $sql .= ' ' . $this->getGroupSql();
        $sql .= ' ' . $this->getLimitSql();

        return $sql;
    }

    protected function getGroupSql()
    {
        if (sizeof($this->group) == 0) {
            return '';
        }

        $groupSql = [];

        /**
         * @var $field Field
         */
        foreach ($this->group as $field) {

            if (!$this->hasPrefix($field->getPrefix())) {
                throw new DbQueryException('Unknown prefix "' . $field->getPrefix() . '"');
            }

            $groupSql[] = $field->getFieldName();
        }

        return 'GROUP BY ' . implode(', ', $groupSql);
    }

    public function getSql()
    {
        return $this->getQuery();
    }

    public function addJoinRules(array $joinCondition, $prefix, $targetPrefix)
    {
        switch ($joinCondition['type']) {
            case AbstractJoinQuery::TYPE_MANY_TO_ONE:
                $this->addMapCallback(
                    new MapObject($prefix),
                    $joinCondition['method'],
                    [new MapObject($targetPrefix, true)]
                );
                break;
            case AbstractJoinQuery::TYPE_ONE_TO_ONE:
                $this->addMapCallback(new MapObject($prefix), $joinCondition['method'], [new MapObject($targetPrefix)]);
                break;
        }
    }

    protected function mapResult(PDOResult $result, $first)
    {
        $types = [];
        /**
         * @var $field Field
         */
        foreach ($this->select as $field) {
            if (!isset($types[$field->getPrefix()])) {
                $types[$field->getPrefix()] = [];
            }
            $types[$field->getPrefix()][$field->getName()] = $this->getWrapperByPrefix($field->getPrefix())
                ->getFieldType($field);
        }

        $objects = [];

        $mainMapObject = new MapObject();

        $cache = [];

        while ($rawRow = $result->rawFetch()) {
            $rawRowsByPrefix = [];

            foreach ($this->select as $field) {
                if (!isset($rawRowsByPrefix[$field->getPrefix()])) {
                    $rawRowsByPrefix[$field->getPrefix()] = [];
                }
                $rawRowsByPrefix[$field->getPrefix()][$field->getName()] = $rawRow[$field->getFieldKey()];
            }

            $objectsByPrefix = [];

            foreach ($rawRowsByPrefix as $prefix => $rawPrefixRow) {
                $prefixRow = $result->convertRawRow($rawPrefixRow, $types[$prefix]);

                $wrapper = $this->getWrapperByPrefix($prefix);

                $objectsByPrefix[$prefix] = [$wrapper->factoryObjectByRow($prefixRow)];

                $keys = $wrapper->getPrimaryKeys();

                $cacheKey = null;
                /**
                 * @var $key Field
                 */
                foreach ($keys as $key) {
                    if (!array_key_exists($key->getName(), $rawPrefixRow)) {
                        $cacheKey = null;
                        break;
                    }
                    $cacheKey .= '.' . $rawPrefixRow[$key->getName()];
                }

                if ($cacheKey !== null) {
                    $cacheKey = $prefix . '.' . $cacheKey;
                    if (array_key_exists($cacheKey, $cache)) {
                        $objectsByPrefix[$prefix][] = $cache[$cacheKey];
                    } else {
                        $cache[$cacheKey] = $objectsByPrefix[$prefix][0];
                    }
                }
            };

            $mainObject = $mainMapObject->getObject($this, $objectsByPrefix);

            foreach ($this->mapCallbacks as $callback) {
                $callbackObject = $callback['object'];
                $callbackMethod = $callback['method'];
                $callbackArguments = $callback['arguments'];
                if ($callbackObject instanceof MapObject) {
                    $callbackObject = $callbackObject->getObject($this, $objectsByPrefix);
                }

                /**
                 * @var $key string
                 */
                foreach ($callbackArguments as $key => $argument) {
                    if ($argument instanceof MapObject) {
                        $callbackArguments[$key] = $argument->getObject($this, $objectsByPrefix);
                    }
                }

                if (is_array($callbackObject)) {
                    $callbackObject[$callbackMethod] = $callbackArguments;
                } else {
                    call_user_func_array([$callbackObject, $callbackMethod], $callbackArguments);
                }
            }
            $objects[] = $mainObject;

            if ($first) {
                break;
            }
        }

        if ($first) {
            if (sizeof($objects) == 0) {
                return null;
            }

            return $objects[0];
        }

        return $objects;
    }

    public function repeat(AbstractCommonQuery $query)
    {

        if ($query instanceof SelectQuery) {
            $query->select = CommonHelper::fullClone($this->select);
            $query->mapCallbacks = CommonHelper::fullClone($this->mapCallbacks);
        }

        return parent::repeat($query);
    }
}
