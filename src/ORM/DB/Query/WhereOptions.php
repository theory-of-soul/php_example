<?php

namespace ORM\DB\Query;

use Helpers\CommonHelper;
use Models\AbstractModel;
use ORM\DB\DbQueryException;
use Providers\Mysql\FieldType\AbstractFieldType;

class WhereOptions extends AbstractModel
{

    private $where = [];
    private $params = [];
    private $types = [];

    /**
     * @param $field
     * @param $operator
     * @param $value
     * @param string $logicalOperator
     *
     * @return WhereOptions
     */ /**
     * @param $field
     * @param $operator
     * @param $value
     * @param string $logicalOperator
     *
     * @return WhereOptions
     */
    public function where($field, $operator = null, $value = null, $logicalOperator = 'AND')
    {

        $this->where[] = $this->checkWhere($field, $operator, $value, $logicalOperator);

        return $this;
    }

    /**
     * @param $field
     * @param $operator
     * @param $value
     *
     * @return WhereOptions
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
     * @return WhereOptions
     */
    public function orWhere($field, $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'OR');
    }

    private function checkLogicalOperator($logicalOperator)
    {
        $logicalOperator = strtoupper($logicalOperator);
        $availableLogicalOperators = ['OR', 'AND'];

        if (array_search($logicalOperator, $availableLogicalOperators) === false) {
            throw new DbQueryException('Wrong where logical operator');
        }
        return $logicalOperator;
    }

    private function checkWhere($field, $operator, $value, $logicalOperator)
    {

        $logicalOperator = $this->checkLogicalOperator($logicalOperator);

        if ($field instanceof WhereOptions) {
            return [
                'field' => $field,
                'logicalOperator' => $logicalOperator
            ];
        }

        $operator = strtoupper($operator);
        if (!($field instanceof Field)) {
            $field = new Field($field);
        }

        $availableOperators = ['=', '!=', '>', '<', '>=', '<=', 'BETWEEN', 'IN', 'LIKE'];

        if (array_search($operator, $availableOperators) === false) {
            throw new DbQueryException('Wrong where operator');
        }

        if ($operator == 'IN' && !is_array($value)) {
            throw new DbQueryException('Wrong in operator value');
        }

        if ($operator == 'BETWEEN' && !is_array($value) && sizeof($value) != 2) {
            throw new DbQueryException('Wrong between operator value');
        }

        return [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'logicalOperator' => $logicalOperator
        ];
    }

    public function __clone()
    {
        $this->where = CommonHelper::fullClone($this->where);
    }

    public function getSql()
    {

        if (sizeof($this->where) == 0) {
            return '';
        }

        $sql = '';

        $isFirst = true;
        foreach ($this->where as $item) {
            $field = $item['field'];

            if ($field instanceof WhereOptions) {
                $subSql = $field->getSql();
                if (strlen($subSql) > 0) {
                    if (!$isFirst) {
                        $sql .= ' '.$item['logicalOperator'].' ';
                    } else {
                        $isFirst = false;
                    }
                    $sql .= '('.$subSql.')';
                }
            } else {
                /**
                 * @var $field Field
                 */
                if (!$isFirst) {
                    $sql .= ' '.$item['logicalOperator'].' ';
                } else {
                    $isFirst = false;
                }

                switch ($item['operator']) {
                    case 'IN':
                        $inSql = [];
                        foreach ($item['value'] as $value) {
                            if ($value instanceof Field) {
                                $inSql[] = $value->getFieldName();
                            } else {
                                $inSql[] = '?';
                            }
                        }
                        $sql .= $field->getFieldName().' IN ('.implode(', ', $inSql).')';
                        break;
                    case 'BETWEEN':
                        $first = '?';
                        $last = '?';

                        $firstValue = $item['value'][0];
                        if ($firstValue instanceof Field) {
                            $first = $firstValue->getFieldName();
                        }
                        $lastValue = $item['value'][1];
                        if ($lastValue instanceof Field) {
                            $last = $lastValue->getFieldName();
                        }
                        $sql .= $field->getFieldName().' BETWEEN '.$first.' AND '.$last;
                        break;
                    default:
                        if ($item['value'] === null) {
                            if ($item['operator'] == '=') {
                                $sql .= $field->getFieldName().' IS NULL';
                                break;
                            }
                            if ($item['operator'] == '!=') {
                                $sql .= $field->getFieldName().' IS NOT NULL';
                                break;
                            }
                        }
                        if ($item['value'] instanceof Field) {
                            $sql .= $field->getFieldName().' '.$item['operator'].' '.$item['value']->getFieldName();
                            break;
                        }
                        $sql .= $field->getFieldName().' '.$item['operator'].' ?';
                        break;
                }
            }
        }

        return $sql;
    }

    public function getTypesAndParams(AbstractCommonQuery $query)
    {

        $this->params = [];
        $this->types = [];

        foreach ($this->where as $item) {
            $field = $item['field'];
            if ($field instanceof WhereOptions) {
                $field->getTypesAndParams($query);
                $this->params = array_merge($this->params, $field->params);
                $this->types = array_merge($this->types, $field->types);
            } else {
                $value = $item['value'];
                /**
                 * @var $field Field
                 */
                $field = $item['field'];
                $wrapper = $query->getWrapperByPrefix($field->getPrefix());

                switch ($item['operator']) {
                    case 'IN':
                    case 'BETWEEN':
                        foreach ($value as $valueItem) {
                            if (!($valueItem instanceof Field)) {
                                $this->params[] = $valueItem;
                                $this->types[] = $wrapper->getFieldType($item['field']);
                            }
                        }
                        break;
                    case 'LIKE':
                        if (!($value instanceof Field)) {
                            $this->params[] = $value;
                            $this->types[] = AbstractFieldType::TYPE_TEXT;
                        }
                        break;
                    default:
                        if (!($value instanceof Field)) {
                            if ($value !== null || ($item['operator'] != '=' && $item['operator'] != '!=')) {
                                $this->params[] = $value;
                                $this->types[] = $wrapper->getFieldType($item['field']);
                            }
                        }
                        break;
                }
            }

        }
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function getWrapper() {
        return null;
    }
}
