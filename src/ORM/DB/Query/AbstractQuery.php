<?php

namespace ORM\DB\Query;

use Models\AbstractModel;
use ORM\DB\AbstractWrapper;
use Providers\Mysql\PDOResult;
use Models\Application;

abstract class AbstractQuery
{

    protected $wrapper;

    public function __construct(Application $app, AbstractWrapper $wrapper)
    {
        $this->app = $app;
        $this->wrapper = $wrapper;
    }

    protected function getMysql()
    {
        return $this->app['mysql'];
    }

    public function getField($name)
    {
        return new Field($name);
    }

    public function first()
    {
        return $this->execute(true);
    }

    public function execute($first = false)
    {
        $sql = $this->getQuery();
        $params = $this->getParams();
        $types = array();

        foreach ($params as $key => $value) {
            $types[$key] = $this->getType($key);
        }

        $result = $this->getMysql()->execute($sql, $params, $types);

        return $this->mapResult($result, $first);
    }

    protected function getType($key)
    {
        $types = $this->getTypes();

        return $types[$key];
    }

    protected function fillKeys($keysValues)
    {
        $keyFields = $this->wrapper->getPrimaryKeys();

        $keyFieldsSize = sizeof($keyFields);

        if ($keyFieldsSize == 0) {
            throw new \Exception('Primary keys is not defined for ' . get_class($this->wrapper));
        }

        if (!is_array($keysValues)) {
            $keysValues = [$keysValues];
        }

        $result = [];

        foreach ($keysValues as $key => $value) {
            if (is_int($key)) {
                if (array_key_exists($key, $keyFields)) {
                    $result[$key] = [
                        'field' => $keyFields[$key],
                        'value' => $value,
                    ];
                }
            } else {
                /**
                 * @var $keyField Field
                 */
                foreach ($keyFields as $index => $keyField) {
                    if ($keyField->checkName($key)) {
                        $result[$index] = [
                            'field' => $keyField,
                            'value' => $value
                        ];
                        break;
                    }
                }
            }
        }

        if (sizeof($result) !== $keyFieldsSize) {
            throw new \Exception('Wrong primary key');
        }

        return $result;
    }

    abstract protected function getQuery();

    abstract protected function getParams();

    abstract protected function getTypes();

    abstract protected function mapResult(PDOResult $result, $first);

    public function getWrapper() {
        return null;
    }
}
