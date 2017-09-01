<?php

namespace Plinth\Database\Query;

class UnionQuery implements IQuery, OrderByQuery
{
	const UNION = " UNION ";
	
	/**
	 * @var array
	 */
	private $orderby;
	
	/**
	 * @var SelectQuery
	 */
	private $firstQuery;

	/**
	 * @var SelectQuery
	 */
	private $secondQuery;
	
	/**
	 * @param SelectQuery $firstQuery
	 * @param SelectQuery $secondQuery
	 */
	public function __construct($firstQuery, $secondQuery)
	{
		$this->orderby= array();		
		
		$this->firstQuery = $firstQuery;
		$this->secondQuery = $secondQuery;
	}

	/**
	 * @param string $column
	 * @param int $order
	 * @return $this
	 */
	public function orderBy($column, $order = self::ORDER_DEFAULT)
	{
		if ($order === self::ORDER_DESC)	$column .= " DESC";
		elseif ($order === self::ORDER_ASC)	$column .= " ASC";
		
		$this->orderby[] = $column;		
		
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	private function hasOrderBy()
	{
		return count($this->orderby) > 0;
	}

	/**
	 * @return string
	 */
	private function getOrderBy()
	{
		return " ORDER BY " . implode(',', $this->orderby);
	}

    /**
     * @param boolean $end
     * @return string
     */
	protected function getEnd($end)
	{
	    return $end ? self::END : self::NO_END;
	}

	/**
	 * @param bool $end
	 * @return string
	 */
	public function get($end = true)
	{
		$return = "(" . $this->firstQuery->get(false) . ")" . self::UNION . "(" . $this->secondQuery->get(false) . ")";
						
		if ($this->hasOrderBy()) $return .= $this->getOrderBy();
		
		return $return . $this->getEnd($end);
	}
}