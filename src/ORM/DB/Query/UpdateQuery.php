<?php

namespace ORM\DB\Query;

use Providers\Mysql\PDOResult;

class UpdateQuery extends AbstractQuery
{

    private $set = [];
    private $keys = [];

    /**
     * @param mixed $key Первичный ключ.
     *
     * @return $this
     */
    public function update($key)
    {
        $this->keys = $this->fillKeys($key);
        return $this;
    }

    /**
     * @param array $data Пары поле => новое значение.
     *
     * @return $this
     */
    public function set(array $data)
    {

        foreach ($data as $key => $value) {
            $field = $this->wrapper->getField($key);
            $field->setPrefix();
            if ($value instanceof Field) {
                $value->setPrefix();
            }
            $this->set[$field->getFieldKey()] = ['field' => $field, 'value' => $value];
        }

        return $this;
    }

    protected function getQuery()
    {
        if (sizeof($this->set) == 0) {
            throw new \Exception('Empty set into update');
        }

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
                $whereSql[] = $field->getFieldName().' IS NULL';
            } else {
                $whereSql[] = $field->getFieldName().' = :key_'.$field->getFieldKey();
            }
        }

        $setSql = [];

        foreach ($this->set as $set) {
            if ($set['value'] instanceof Field) {
                $setSql[] = $set['field']->getFieldName().' = '.$set['value']->getFieldName();
            } else {
                $setSql[] = $set['field']->getFieldName().' = :set_'.$set['field']->getFieldKey();
            }
        }


        return 'UPDATE `'.$this->wrapper->getTableName().'` AS `' . Field::DEFAULT_PREFIX . '` SET '.implode(', ', $setSql).' WHERE '.implode(' AND ', $whereSql);

    }

    protected function mapResult(PDOResult $result, $first)
    {
        return $result->rowCount();
    }

    protected function getParams()
    {
        $params = [];

        foreach ($this->set as $set) {
            if (!($set['value'] instanceof Field)) {
                $params['set_'.$set['field']->getFieldKey()] = $set['value'];
            }
        }

        foreach ($this->keys as $key) {
            if ($key['value'] !== null) {
                $params['key_'.$key['field']->getFieldKey()] = $key['value'];
            }
        }

        return $params;
    }

    protected function getTypes()
    {
        $types = [];
        foreach ($this->set as $set) {
            if (!($set['value'] instanceof Field)) {
                $types['set_'.$set['field']->getFieldKey()] = $this->wrapper->getFieldType($set['field']);
            }
        }

        foreach ($this->keys as $key) {
            if ($key['value'] !== null) {
                $types['key_'.$key['field']->getFieldKey()] = $this->wrapper->getFieldType($key['field']);
            }
        }

        return $types;
    }
}
