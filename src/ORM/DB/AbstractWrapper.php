<?php

namespace ORM\DB;

use Models\Application;
use ORM\DB\Query\AbstractCommonQuery;
use ORM\DB\Query\CountQuery;
use ORM\DB\Query\DeleteQuery;
use ORM\DB\Query\Field;
use ORM\DB\Query\InsertQuery;
use ORM\DB\Query\ReplaceQuery;
use ORM\DB\Query\SelectForUpdateQuery;
use ORM\DB\Query\SelectQuery;
use ORM\DB\Query\UpdateQuery;
use Providers\Mysql\FieldType\AbstractFieldType;

abstract class AbstractWrapper {
    private $schema = [];

    protected $object;

    private $primaryKeys = null;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    public function getField($name) {
        return new Field($name);
    }

    public function getPrimaryKeys() {
        if (!is_array($this->primaryKeys)) {
            $schema = $this->getSchema();
            $this->primaryKeys = [];
            foreach ($schema as $field => $data) {
                if (array_key_exists('primary', $data) && $data['primary']) {
                    $this->primaryKeys[] = $this->getField($field);
                }
            }
        }

        return $this->primaryKeys;
    }

    /**
     * @param $object
     * @return AbstractWrapper
     */
    public function setObject($object) {
        $this->object = $object;

        return $this;
    }

    /**
     * @param array $schema
     * @return AbstractWrapper
     */
    public function setSchema(array $schema) {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @param            $data
     * @param bool|false $forUpdate
     * @return SelectQuery
     */
    public function select($data, $forUpdate = false) {
        if ($forUpdate) {
            $query = new SelectForUpdateQuery($this->app, $this);
        } else {
            $query = new SelectQuery($this->app, $this);
        }

        if ($data instanceof AbstractCommonQuery) {
            return $data->repeat($query);
        }

        return $query->select($data);
    }

    public function selectForUpdate($data) {
        return $this->select($data, true);
    }

    public function count($data = null) {
        if ($data instanceof AbstractCommonQuery) {
            return $data->repeat(new CountQuery($this->app, $this));
        }

        return new CountQuery($this->app, $this);
    }

    /**
     * @param $key
     *
     * @return DeleteQuery
     */
    public function delete($key) {
        $query = new DeleteQuery($this->app, $this);

        return $query->delete($key);
    }

    public function insert(array $data) {
        $query = new InsertQuery($this->app, $this);

        return $query->insert($data);
    }

    public function replace(array $data) {
        $query = new ReplaceQuery($this->app, $this);

        return $query->replace($data);
    }

    /**
     * @param $key
     *
     * @return UpdateQuery
     */
    public function update($key) {
        $query = new UpdateQuery($this->app, $this);

        return $query->update($key);
    }

    abstract public function getTableName();

    abstract protected function getSchema();

    protected function getJoinsSchema() {
        return [];
    }

    abstract protected function factoryObject($row);

    public function getJoinCondition($name) {
        $schema = $this->getJoinsSchema();
        if (array_key_exists($name, $schema)) {
            return $schema[$name];
        }

        return null;
    }

    public function factoryObjectByRow($row, $particularSchema = []) {
        $object = $this->object;
        if ($object === null) {
            $object = $this->factoryObject($row);
        } elseif (is_callable($object)) {
            $object = call_user_func($object, $row);
        }

        if (count($particularSchema)) {
            $schema = $particularSchema;
        } else {
            $schema = $this->getFullSchema();
        }

        foreach ($row as $key => $value) {
            if (array_key_exists($key, $schema) && array_key_exists('method', $schema[$key])) {
                if (is_object($object)) {
                    call_user_func([$object, $schema[$key]['method']], $value);
                } elseif (is_array($object)) {
                    $object[$schema[$key]['method']] = $value;
                }
            } elseif (is_array($object)) {
                $object[$key] = $value;
            }

            if (!is_object($object) && !is_array($object)) {
                $object = $value;
            }
        }

        return $object;
    }

    private function getFullSchema() {
        return array_replace_recursive($this->getSchema(), $this->schema);
    }

    public function getTypes($prefix = '') {
        $schema = $this->getFullSchema();
        $types = [];
        foreach ($schema as $field => $data) {
            $types[$prefix . $field] = $this->getFieldType($field);
        }

        return $types;
    }

    public function getFieldType($field) {
        if ($field instanceof Field) {
            $field = $field->getName();
        }

        $schema = $this->getFullSchema();

        if (array_key_exists($field, $schema) && array_key_exists('type', $schema[$field])) {
            return $schema[$field]['type'];
        }

        return AbstractFieldType::TYPE_TEXT;
    }

    public function getAllFields() {
        return array_keys($this->getSchema());
    }

    protected function getSelectFieldsString(array $fields) {
        foreach ($fields as $key => $field) {
            $fields[$key] = '`' . $field . '`';
        }

        return implode(', ', $fields);
    }

    protected function getSelectFieldsTypes(array $fields) {
        $selectFieldsTypes = [];

        foreach ($fields as $field) {
            $selectFieldsTypes[$field] = $this->getFieldType($field);
        }

        return $selectFieldsTypes;
    }

    public function getAll() {
        $query = $this->select($this->getAllFields());
        return $query->execute();
    }

    public function findById($id) {
        return $this->select( $this->getAllFields() )->where('id', '=', $id)->first();
    }

    public function findByField($field,$value)
    {
        return $this->select( $this->getAllFields() )->where($field, '=', $value);
    }

    public function save($data) {
        if(@$data['id']) {
            $item = $this->select( $this->getAllFields() )->where('id', '=', $data['id'])->first();
        } else {
            $item = $this->factoryObject();
        }

        if(!$item) {
            return ['success' => false, 'errors' => ['can not save']];
        }

        $item->setProperties($data);
        $errors = $item->getErrors();
        if(!$errors) {
            $item->save();
            $result = ['success' => true, 'id' => $item->get('id')];
        } else {
            $result = ['success' => false, 'errors' => $errors];
        }
        return $result;
    }

    public function remove($id) {
        $item = $this->select( $this->getAllFields() )->where('id', '=', $id)->first();
        if(!$item) {
            return ['success' => false, 'errors' => ['can not delete']];
        }
        $item->delete();
        return ['success' => true];
    }

    public function deleteByIds($ids) {
        $ids = array_unique( array_map(function($item) { return (int)$item; }, $ids) );
        if(count($ids)) {
            $sql = "DELETE FROM `" . $this->getTableName() . "` WHERE `id` IN (" . implode(',', $ids) . ")";
            $this->app->getMysql()->fetchAll($sql);
        }
    }
}
