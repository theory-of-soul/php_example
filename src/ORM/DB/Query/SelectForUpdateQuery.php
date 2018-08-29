<?php

namespace ORM\DB\Query;

class SelectForUpdateQuery extends SelectQuery
{
    protected function getQuery()
    {
        $sql = parent::getQuery();
        $sql .= ' FOR UPDATE';
        return $sql;
    }
}
