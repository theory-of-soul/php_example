<?php

namespace ORM\DB\Query;

class Field extends AbstractPrefixNamePair
{

    public function getFieldName()
    {
        return "`".$this->prefix."`.`".$this->name."`";
    }

    public function getFieldNameWithoutPrefix()
    {
        return "`".$this->name."`";
    }

    public function getFieldKey()
    {
        return $this->prefix.'__'.$this->name;
    }

    public function setPrefix($value = null)
    {
        if ($value === null) {
            $value = self::DEFAULT_PREFIX;
        }
        $this->prefix = $value;
    }

    public function checkName($name)
    {
        $name = $this->splitName($name);
        if ($name[0] == $this->prefix && $name[1] == $this->name) {
            return true;
        }

        return false;
    }
}
