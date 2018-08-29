<?php

namespace ORM\DB\Query;

abstract class AbstractPrefixNamePair
{

    const DEFAULT_PREFIX = 'main';

    protected $name;
    protected $prefix;

    public function __construct($name)
    {

        $name = $this->splitName($name);
        $this->name = $name[1];
        $this->prefix = $name[0];
    }

    protected function splitName($name)
    {
        $name = explode('.', strtolower($name));
        $nameSize = sizeof($name);

        if ($nameSize > 2) {
            throw new \Exception('Wrong format of name');
        }

        if (sizeof($name) == 1) {
            $name = [self::DEFAULT_PREFIX, $name[0]];
        }

        $pattern = '/^[a-z][a-z0-9_]*$/';
        if (!preg_match($pattern, $name[0])) {
            throw new \Exception('Wrong prefix');
        }

        if (!preg_match($pattern, $name[1])) {
            throw new \Exception('Wrong name ('.$name.')');
        }

        return $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }
}
