<?php

namespace ORM\DB\Query;

use ORM\DB\AbstractWrapper;
use ORM\DB\DbQueryException;

class MapObject
{

    private $prefix;
    private $isNeedUnique;

    public function __construct($prefix = Field::DEFAULT_PREFIX, $isNeedUnique = false)
    {
        $this->prefix = $prefix;
        $this->isNeedUnique = $isNeedUnique;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getObject(AbstractCommonQuery $query, &$objectsByRow)
    {

        if (!array_key_exists($this->prefix, $objectsByRow)) {
            $wrapper = $query->getWrapperByPrefix($this->prefix);
            if (!($wrapper instanceof AbstractWrapper)) {
                throw new DbQueryException('Unknown prefix "'.$this->prefix.'"');
            }
            $objectsByRow[$this->prefix] = [$wrapper->factoryObjectByRow([])];
        }

        $result = $objectsByRow[$this->prefix][0];

        if ($this->isNeedUnique && array_key_exists(1, $objectsByRow[$this->prefix])) {
            $result = $objectsByRow[$this->prefix][1];
        }

        return $result;

    }
}
