<?php

namespace ORM\DB\Query;

class ReplaceQuery extends InsertQuery
{

    protected function getMethod()
    {
        return 'REPLACE';
    }

    public function replace(array $data)
    {
        return $this->insert($data);
    }
}
