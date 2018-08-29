<?php

namespace ORM\DB\Query;

use Helpers\CommonHelper;
use ORM\DB\AbstractWrapper;
use ORM\DB\DbQueryException;
use Models\Application;

abstract class AbstractCommonQuery extends AbstractQuery
{

    /**
     * @var WhereOptions
     */
    private $where;

    private $order = [];

    private $offset = null;

    private $limit = null;

    protected $types;

    protected $params;

    protected $joins = [];

    protected $isNeedCache = false;

    public function __construct(Application $app, AbstractWrapper $wrapper)
    {
        $this->where = new WhereOptions($app);
        parent::__construct($app, $wrapper);
    }

    /**
     * @return WhereOptions
     */
    public function getWhereOptions()
    {
        return $this->where;
    }

    public function repeat(AbstractCommonQuery $query)
    {
        $query->where = CommonHelper::fullClone($this->where);
        $query->order = CommonHelper::fullClone($this->order);
        $query->offset = CommonHelper::fullClone($this->offset);
        $query->limit = CommonHelper::fullClone($this->limit);
        $query->joins = [];
        /**
         * @var $join AbstractJoinQuery
         */
        foreach ($this->joins as $key => $join) {
            $newJoin = clone $join;
            $newJoin->setParentQuery($query);
            $query->joins[$key] = $newJoin;
        }

        return $query;
    }

    public function cache($value = true)
    {
        $this->isNeedCache = $value;
        return $this;
    }

    public function clearCachedParams()
    {
        if (isset($this->params)) {
            unset($this->params);
        }

        if (isset($this->types)) {
            unset($this->types);
        }
    }

    public function getWrapperByPrefix($prefix)
    {
        $prefix = strtolower($prefix);
        if ($prefix == Field::DEFAULT_PREFIX || $prefix == '') {
            return $this->wrapper;
        }

        if (array_key_exists($prefix, $this->joins)) {
            /**
             * @var $join AbstractJoinQuery
             */
            $join = $this->joins[$prefix];
            return $join->getWrapper();
        }

        return null;
    }

    protected function hasPrefix($prefix)
    {
        $prefix = strtolower($prefix);
        if ($prefix == Field::DEFAULT_PREFIX) {
            return true;
        }
        if ($prefix == '') {
            return true;
        }
        if (array_key_exists($prefix, $this->joins)) {
            return true;
        }
        return false;
    }

    private function checkJoinPrefix($prefix)
    {
        $prefix = strtolower($prefix);

        if ($prefix == Field::DEFAULT_PREFIX) {
            throw new \Exception('Prefix "'.Field::DEFAULT_PREFIX.'" is reserved');
        }
        if (array_key_exists($prefix, $this->joins)) {
            throw new \Exception('Prefix "'.$prefix.'" already in use');
        }

        return $prefix;
    }

    /**
     * @param AbstractWrapper   $wrapper    Wrapper.
     * @param string            $prefix     Prefix.
     *
     * @return InnerJoinQuery
     */
    public function innerJoin(AbstractWrapper $wrapper, $prefix)
    {

        $prefix = $this->checkJoinPrefix($prefix);
        $joinQuery = new InnerJoinQuery($this->app, $wrapper, $prefix, $this);
        $this->joins[$prefix] = $joinQuery;
        return $joinQuery;
    }

    /**
     * @param AbstractWrapper   $wrapper    Wrapper.
     * @param string            $prefix     Prefix.
     *
     * @return LeftJoinQuery
     */
    public function leftJoin(AbstractWrapper $wrapper, $prefix)
    {

        $prefix = $this->checkJoinPrefix($prefix);
        $joinQuery = new LeftJoinQuery($this->app, $wrapper, $prefix, $this);
        $this->joins[$prefix] = $joinQuery;
        return $joinQuery;
    }

    /**
     * @param $field
     * @param $operator
     * @param $value
     *
     * @return $this
     *
     * @throws \ORM\DB\DbQueryException
     */
    public function andWhere($field, $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'AND');
    }

    /**
     * @param $field
     * @param $operator
     * @param $value
     *
     * @return $this
     *
     * @throws \ORM\DB\DbQueryException
     */
    public function orWhere($field, $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'OR');
    }


    /**
     * @param $field
     * @param $operator
     * @param $value
     * @param string $logicalOperator
     *
     * @return AbstractCommonQuery
     *
     * @throws \ORM\DB\DbQueryException
     */
    public function where($field, $operator = null, $value = null, $logicalOperator = 'AND')
    {
        $this->clearCachedParams();

        $this->where->where($field, $operator, $value, $logicalOperator);

        return $this;
    }

    /**
     * @param $field
     * @param string $direction
     * @return AbstractCommonQuery
     * @throws \ORM\DB\DbQueryException
     */
    public function order($field, $direction = 'ASC')
    {
        if (!($field instanceof Field)) {
            $field = $this->getField($field);
        }

        $direction = strtoupper($direction);

        $availableDirection = ['ASC', 'DESC'];
        if (array_search($direction, $availableDirection) === false) {
            throw new DbQueryException('Wrong order direction');
        }

        $this->order[] = array(
            'field' => $field,
            'direction' => $direction
        );

        return $this;
    }

    /**
     * @param $value
     *
     * @return AbstractCommonQuery
     */
    public function offset($value)
    {
        $value = (int)$value;
        $this->offset = $value;
        return $this;
    }

    /**
     * @param $value
     *
     * @return AbstractCommonQuery
     */
    public function limit($value)
    {
        $value = (int)$value;
        $this->limit = $value;
        return $this;
    }

    protected function getJoinsSql()
    {
        $joinsSql = [];
        /**
         * @var $join AbstractJoinQuery
         */
        foreach ($this->joins as $prefix => $join) {
            $sql = $join->getJoinCommand().' `'.$join->getWrapper()->getTableName().'` AS `'.$prefix.'`';
            $on = $join->getOn();
            $onSql = $on->getSql();

            if (strlen($onSql) > 0) {
                $sql .= ' ON '.$onSql;
            }

            $joinsSql[] = $sql;
        }

        return implode(' ', $joinsSql);

    }

    protected function getWhereSql()
    {
        $whereSql = $this->where->getSql();

        if (strlen($whereSql) == 0) {
            return '';
        }

        return 'WHERE '.$whereSql.' ';
    }

    protected function getOrderSql()
    {
        if (sizeof($this->order) == 0) {
            return '';
        }
        $orderSql = [];
        foreach ($this->order as $order) {
            /**
             * @var $field Field
             */
            $field = $order['field'];
            if (!$this->hasPrefix($field->getPrefix())) {
                throw new DbQueryException('Unknown prefix "' . $field->getPrefix() . '"');
            }
            $orderSql [] = $field->getFieldName().' '.$order['direction'];
        }

        return 'ORDER BY '.implode(', ', $orderSql);
    }

    protected function getLimitSql()
    {
        if ($this->limit === null) {
            return '';
        }

        $sql = 'LIMIT ';

        if ($this->offset !== null) {
            $sql .= $this->offset.', ';
        }

        $sql .= $this->limit;

        return $sql;

    }

    protected function getTypes()
    {
        if (!isset($this->types)) {
            $this->getTypesAndParams();
        }

        return $this->types;
    }

    public function addJoinRules(array $joinCondition, $prefix, $targetPrefix)
    {

    }

    protected function getTypesAndParams()
    {

        $this->types = [];
        $this->params = [];


        /**
         * @var $join AbstractJoinQuery
         */
        foreach ($this->joins as $join) {
            $join->getOn()->getTypesAndParams($this);
            $this->params = array_merge($this->params, $join->getOn()->getParams());
            $this->types = array_merge($this->types, $join->getOn()->getTypes());
        }

        $this->where->getTypesAndParams($this);
        $this->params = array_merge($this->params, $this->where->getParams());
        $this->types = array_merge($this->types, $this->where->getTypes());

    }

    protected function getParams()
    {

        if (!isset($this->params)) {
            $this->getTypesAndParams();
        }

        return $this->params;
    }

    public function getCacheKey($first)
    {
        return 'QUERY.'.($first ? 'FIRST.' : '').$this->getMysql()->getHash($this->getQuery(), $this->getParams(), $this->getTypes());
    }

    /**
     * @param bool|false $first
     * @return mixed
     */
    public function execute($first = false)
    {

        $key = $this->getCacheKey($first);
        if ($this->isNeedCache) {
            try {
                return $this->getObjectCache()->get($key, true);
            } catch (\Exception $ex) {
            }
        }

        $result = parent::execute($first);

        if ($this->isNeedCache) {
            $this->getObjectCache()->setQueryResult($key, $result);
        }

        return $result;
    }
}
