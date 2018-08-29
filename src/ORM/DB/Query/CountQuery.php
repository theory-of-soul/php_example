<?php

namespace ORM\DB\Query;

use Providers\Mysql\FieldType\AbstractFieldType;
use Providers\Mysql\PDOResult;

class CountQuery extends AbstractCommonQuery
{


    protected function getQuery()
    {
        return 'SELECT COUNT(1) countValue FROM `'.$this->wrapper->getTableName().'` '.Field::DEFAULT_PREFIX.' '.$this->getJoinsSql().' '.$this->getWhereSql().' '.$this->getOrderSql().' '.$this->getLimitSql();
    }

    protected function mapResult(PDOResult $result, $first)
    {
        $row = $result->fetch(array('countValue', AbstractFieldType::TYPE_INT));
        return $row['countValue'];
    }
}
