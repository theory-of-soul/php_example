<?php

namespace ORM\DB;

use Models\Banner;
use Providers\Mysql\FieldType\AbstractFieldType;


class BannerWrapper extends AbstractWrapper {
	public function getTableName() {
        return 'banner';
    }

    protected function getSchema() {
        return [
            'id' => ['method' => 'setId', 'type' => AbstractFieldType::TYPE_INT, 'primary' => true],
            'url' => ['method' => 'setUrl'],
            'img' => ['method' => 'setImg'],
            'weight' => ['method' => 'setWeight', 'type' => AbstractFieldType::TYPE_INT],
            'num' => ['method' => 'setNum', 'type' => AbstractFieldType::TYPE_INT],
            'hit' => ['method' => 'setHit', 'type' => AbstractFieldType::TYPE_INT],
            'click' => ['method' => 'setClick', 'type' => AbstractFieldType::TYPE_INT],
            'locale' => ['method' => 'setLocale']
        ];
    }

    protected function factoryObject($row=[]) {
        return new Banner($this->app);
    }
}