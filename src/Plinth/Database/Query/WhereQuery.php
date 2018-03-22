<?php

namespace Plinth\Database\Query;

use Plinth\Exception\PlinthException;

abstract class WhereQuery extends BaseQuery
{
	/* Where */
	const WHERE_AND = " AND ";
	const WHERE_OR = " OR ";
	
	const OPERATOR_EQUAL = " = ";
	const OPERATOR_EQUAL_TO = " <=> ";
	const OPERATOR_NOT_EQUAL = " != ";
	const OPERATOR_NOT_EQUAL2 = " <> ";
	const OPERATOR_GREATER = " > ";
	const OPERATOR_GREATER_EQUAL = " >= ";
	const OPERATOR_LESS = " < ";
	const OPERATOR_LESS_EQUAL = " <= ";
	const OPERATOR_NULL = " IS NULL ";
	const OPERATOR_NOT_NULL = " IS NOT NULL ";
	const OPERATOR_LIKE = " LIKE ";
	
	/* Private operator constants */
	private static $OPERATOR_IN = " IN ";
	private static $OPERATOR_NOT_IN = " NOT IN ";
	private static $OPERATOR_BETWEEN = " BETWEEN ";	
	
	/**
	 * @var array
	 */
	private $index;
	
	/**
	 * @var string
	 */
	private $where;
	
	/**
	 * @var string|boolean
	 */
	private $openGroup = false;

	/**
	 * WhereQuery constructor.
	 * @param $table
	 * @param bool $as
	 */
	public function __construct($table, $as = false)
	{
		parent::__construct($table, $as);
		
		$this->index = [];
	}

	/**
	 * @param $index
	 * @return $this
	 */
	public function forceIndex($index)
	{
		if (is_array($index)) array_merge($this->index, $index);
		else				  $this->index[] = $index;		
		
		return $this;
	}

	/**
	 * @param bool $seperator
	 * @return $this
	 * @throws PlinthException
	 */
	private function addSeperator($seperator = false)
	{
		if ($this->hasWhere()) { //Only use seperator when there already is a where statement
			if ($this->openGroup === false) {
				if ($seperator !== false) {
					if ($seperator === self::WHERE_AND || $seperator === self::WHERE_OR)
						$this->where .= $seperator;
					else
						throw new PlinthException('Please use a valid where seperator');
				} else
					$this->where .= self::WHERE_AND; //Use Where and by default if not defined
			} else {
				$this->where .= $this->openGroup . "(";
				$this->openGroup = false;
			}
		} else {
			if ($this->openGroup !== false) {
				$this->where .= "(";
				$this->openGroup = false;
			}
		}

		return $this;
	}

	/**
	 * @param $where
	 * @param $operator
	 * @param $value
	 * @param bool $seperator
	 * @return $this
	 * @throws PlinthException
	 */
	public function where($where, $operator, $value, $seperator = false)
	{
		$this->addSeperator($seperator);

		$this->where .= $where . $operator . $value;
		
		return $this;
	}

	/**
	 * @param $where
	 * @param $left
	 * @param $right
	 * @param bool $seperator
	 * @return $this
	 * @throws PlinthException
	 */
	public function whereBetween($where, $left, $right, $seperator = false)
	{
		$this->addSeperator($seperator);
		
		$this->where .= $where . self::$OPERATOR_BETWEEN . $left . self::WHERE_AND . $right;
		
		return $this;
	}

	/**
	 * @param string $where
	 * @param bool $seperator
	 * @param array $values
	 * @param bool $not
	 * @return $this
	 * @throws PlinthException
	 */
	public function whereIn($where, $seperator = false, $values = [], $not = false)
	{
		$this->addSeperator($seperator);
		
		$this->where .= $where . ($not === true ? self::$OPERATOR_NOT_IN : self::$OPERATOR_IN) . "(";
		
		foreach ($values as $i => $value) {
			if ($i > 0) $this->where .= ",";
			$this->where .= $value;
		}
		
		$this->where .= ")";
		
		return $this;
	}

	/**
	 * @param bool $seperator
	 * @return $this
	 */
	public function openGroup($seperator = false)
	{
		if ($seperator !== false && $seperator === self::WHERE_OR) {
			$this->openGroup = $seperator;
		} else {
			$this->openGroup = self::WHERE_AND;
		}
		
		return $this;
	}

	/**
	 * @return $this
	 */
	public function closeGroup()
	{
		$this->where .= ")";

		return $this;
	}

	/**
	 * @return boolean
	 */
	protected function hasIndex()
	{
		return count($this->index) > 0;
	}
	
	/**
	 * @return boolean
	 */
	protected function hasWhere()
	{
		return !is_null($this->where);
	}
	
	/**
	 * @return string
	 */
	protected function getIndex()
	{
		return " FORCE INDEX(" . implode(',', $this->index) . ")";
	}
	
	/**
	 * @return string
	 */
	protected function getWhere()
	{
		return  " WHERE " . $this->where;
	}
}