<?php

namespace ORM\DB;

use Models\Aliases;
use Providers\Mysql\FieldType\AbstractFieldType;


class AliasesWrapper extends AbstractWrapper {
    
    public function getTableName() {
        return 'tournaments_aliases';
    }

    protected function getSchema() {
        return [
            'id' => ['method' => 'setId', 'type' => AbstractFieldType::TYPE_INT, 'primary' => true],
            'title' => ['method' => 'setTitle'],
            'lang' => ['method' => 'setLang'],
            'tournament_id' => ['method' => 'setTournamentId', 'type' => AbstractFieldType::TYPE_INT]
        ];
    }

    protected function factoryObject($row = null) {
        return new Aliases($this->app);
    }

         
}