<?php

namespace ORM\DB;

use Models\Sport;
use Providers\Mysql\FieldType\AbstractFieldType;


class SportWrapper extends AbstractWrapper {
	public function getTableName() {
        return 'sports';
    }

    protected function getSchema() {
        return [
            'id' => ['method' => 'setId', 'type' => AbstractFieldType::TYPE_INT, 'primary' => true],
            'title' => ['method' => 'setTitle'],
            'slug' => ['method' => 'setSlug'],
            'keep' => ['method' => 'setKeep'],
        ];
    }

    protected function factoryObject($row = null) {
        return new Sport($this->app);
    }

    public function findByTitle($title) {
        return $this->select( $this->getAllFields() )->where('title', '=', $title)->first();
    }
}