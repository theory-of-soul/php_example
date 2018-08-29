<?php

namespace ORM\DB\Query;

use Providers\Mysql\PDOResult;

class DeleteQuery extends AbstractQuery
{

    private $keys = [];

    /**
     * @param mixed $key Первичный ключ.
     *
     * @return DeleteQuery
     */
    public function delete($key)
    {
        $this->keys = $this->fillKeys($key);
        return $this;
    }

    protected function getQuery()
    {
        if (sizeof($this->keys) == 0) {
            throw new \Exception('Primary key is not set');
        }

        $whereSql = [];

        foreach ($this->keys as $key) {
            /**
             * @var $field Field
             */
            $field = $key['field'];
            $value = $key['value'];

            if ($value === null) {
                $whereSql[] = $field->getFieldNameWithoutPrefix().' IS NULL';
            } else {
                $whereSql[] = $field->getFieldNameWithoutPrefix().' = :'.$field->getFieldKey();
            }
        }

        $sql = 'DELETE FROM `'.$this->wrapper->getTableName().'` WHERE '.implode(' AND ', $whereSql);
        return $sql;
    }

    protected function mapResult(PDOResult $result, $first)
    {
        return $result->rowCount();
    }

    protected function getParams()
    {
        $params = [];
        foreach ($this->keys as $key) {
            if ($key['value'] !== null) {
                $params[$key['field']->getFieldKey()] = $key['value'];
            }
        }

        return $params;
    }

    protected function getTypes()
    {
        $types = [];
        foreach ($this->keys as $key) {
            if ($key['value'] !== null) {
                $types[$key['field']->getFieldKey()] = $this->wrapper->getFieldType($key['field']);
            }
        }

        return $types;
    }
}
