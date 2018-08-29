<?php

namespace ORM\DB\Query;

use Providers\Mysql\FieldType\AbstractFieldType;
use Providers\Mysql\PDOResult;

class InsertQuery extends AbstractQuery
{

    private $insert = [];

    private $isDelayed = false;

    private $fields = [];

    private $types = null;

    /**
     * @param array $data
     *
     * @return InsertQuery
     */
    public function insert(array $data)
    {
        $clearData = array();

        foreach ($data as $key => $value) {
            $field = $this->wrapper->getField($key);
            $fieldName = $field->getName();
            $clearData[$fieldName] = $value;
            if (array_search($fieldName, $this->fields) === false) {
                $this->fields[] = $fieldName;
            }
        }

        $this->insert[] = $clearData;
        $this->types = null;

        return $this;
    }

    protected function getMethod()
    {
        return 'INSERT';
    }

    private function getAllFields()
    {
        return $this->fields;
    }

    protected function getQuery()
    {
        $fields = $this->getAllFields();

        $sql = $this->getMethod() . ($this->isDelayed ? ' DELAYED' : '');
        $sql .= ' INTO `' . $this->wrapper->getTableName() . '` ';
        $sql .= '('
            . implode(
                ', ',
                array_map(
                    function ($a) {
                        return '`' . $a . '`';
                    },
                    $fields
                )
            )
            . ') VALUES ';

        $values = array();
        $count = sizeof($this->insert);
        for ($i = 0; $i < $count; $i++) {
            $values[] = '(' . implode(', ', array_fill(0, sizeof($fields), '?')) . ')';
        }

        return $sql . implode(', ', $values);
    }

    protected function getParams()
    {
        $fields = $this->getAllFields();
        $count = sizeof($this->insert);

        $params = array();

        for ($i = 0; $i < $count; $i++) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $this->insert[$i])) {
                    $params[] = $this->insert[$i][$field];
                } else {
                    $params[] = AbstractFieldType::DEFAULT_VALUE;
                }
            }
        }

        return $params;
    }

    protected function mapResult(PDOResult $result, $first)
    {
        return $result->rowCount();
    }

    protected function getTypes()
    {
        if ($this->types === null) {
            $fields = $this->getAllFields();

            $count = sizeof($this->insert);

            $this->types = array();

            for ($i = 0; $i < $count; $i++) {
                foreach ($fields as $field) {
                    $this->types[] = $this->wrapper->getFieldType($field);
                }
            }
        }

        return $this->types;
    }

    /**
     * @param bool $isDelayed
     * @return InsertQuery
     */
    public function delayed($isDelayed = true)
    {
        $this->isDelayed = $isDelayed;

        return $this;
    }
}
