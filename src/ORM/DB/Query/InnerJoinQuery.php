<?php

namespace ORM\DB\Query;

class InnerJoinQuery extends AbstractJoinQuery
{

    public function getJoinCommand()
    {
        return 'INNER JOIN';
    }
}
