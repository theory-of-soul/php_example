<?php

namespace ORM\DB\Query;

class LeftJoinQuery extends AbstractJoinQuery
{

    public function getJoinCommand()
    {
        return 'LEFT JOIN';
    }
}
