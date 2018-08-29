<?php

namespace ORM\DB\Query;

use Models\AbstractModel;
use ORM\DB\AbstractWrapper;
use ORM\DB\DbQueryException;
use System\Application;

/**
 * @method AbstractCommonQuery where($field, $operator, $value, $logicalOperator = 'AND')
 * @method AbstractCommonQuery order($field, $direction = 'ASC')
 * @method AbstractCommonQuery first()
 * @method LeftJoinQuery leftJoin(AbstractWrapper $wrapper, $prefix)
 * @method AbstractCommonQuery offset($value)
 */
abstract class AbstractJoinQuery extends AbstractModel
{

    const TYPE_MANY_TO_ONE = 'many2one';
    const TYPE_ONE_TO_ONE = 'one2one';

    private $wrapper;
    private $prefix;
    private $parentQuery;
    /**
     * @var WhereOptions
     */
    private $on;

    public function __construct(Application $app, AbstractWrapper $wrapper, $prefix, AbstractCommonQuery $parentQuery)
    {
        parent::__construct($app);
        $this->wrapper = $wrapper;
        $this->prefix = $prefix;
        $this->parentQuery = $parentQuery;
        $this->on = new WhereOptions($app);
    }

    public function setParentQuery(AbstractCommonQuery $query)
    {
        $this->parentQuery = $query;
    }

    public function __clone()
    {
        $this->on = clone $this->on;
    }

    /**
     * @param $field
     * @param null $operator
     * @param null $value
     * @param string $logicalOperator
     * @param bool $withoutClearCache
     *
     * @throws \ORM\DB\DbQueryException
     * @return AbstractJoinQuery
     */
    public function on($field, $operator = null, $value = null, $logicalOperator = 'AND', $withoutClearCache = false)
    {

        if (!$withoutClearCache) {
            $this->parentQuery->clearCachedParams();
        }

        if (($operator === null && !($field instanceof WhereOptions)) || $field instanceof JoinRules) {
            $rules = $field;
            if (!($rules instanceof JoinRules)) {
                $rules = new JoinRules($rules);
            }

            $wrapper = $this->parentQuery->getWrapperByPrefix($rules->getPrefix());
            $joinSchema = $wrapper->getJoinCondition($rules->getName());
            if ($joinSchema === null) {
                throw new DbQueryException('Unknown join schema "'.$rules->getPrefix().'.'.$rules->getName().'"');
            }

            $targetWrapper = $this->getWrapper();
            if (! ($targetWrapper instanceof $joinSchema['class'])) {
                throw new DbQueryException('Join error. Expected '.$joinSchema['class']);
            }

            $this->parentQuery->addJoinRules($joinSchema, $rules->getPrefix(), $this->prefix);
            foreach ($joinSchema['on'] as $joinOn) {
                $field = $this->parentQuery->getField($rules->getPrefix().'.'.$joinOn[0]);
                $value = $joinOn[2];

                if ($value instanceof Field) {
                    $value->setPrefix($this->prefix);
                }
                $this->on(
                    $field,
                    $joinOn[1],
                    $value,
                    array_key_exists(3, $joinOn) ? $joinOn[3] : 'AND',
                    true
                );
            }

            return $this;
        }

        $this->on->where($field, $operator, $value, $logicalOperator);

        return $this;
    }

    public function andOn($field, $operator = null, $value = null)
    {
        return $this->on($field, $operator, $value, 'AND');
    }

    public function andOr($field, $operator = null, $value = null)
    {
        return $this->on($field, $operator, $value, 'OR');
    }

    public function __call($name, $arguments)
    {

        if (method_exists($this->parentQuery, $name)) {
            return call_user_func_array([$this->parentQuery, $name], $arguments);
        }

        throw new \Exception('Unknown method '.$name.' for '.get_class($this));
    }

    public function getOn()
    {
        return $this->on;
    }

    public function reset()
    {
        $this->on = new WhereOptions($this->app);
    }

    public function getWrapper()
    {
        return $this->wrapper;
    }

    abstract public function getJoinCommand();
}
