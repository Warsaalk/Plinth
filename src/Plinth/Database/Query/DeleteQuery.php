<?php

namespace Plinth\Database\Query;

class DeleteQuery extends WhereQuery {

	/**
	 * @return string
	 */
	private function getDelete()	{ return "DELETE";						}
	
	/**
	 * @return string
	 */
	private function getFrom()		{ return " FROM " . $this->getTable();	}

	/**
	 * (non-PHPdoc)
	 * @see IQuery::get()
	 */
	public function get($end=true) {

		$return = $this->getDelete() . $this->getFrom();
			
		if ($this->hasAs()) 	$return .= $this->getAs();
		if ($this->hasWhere()) 	$return .= $this->getWhere();
		if ($this->hasLimit()) 	$return .= $this->getLimit();
			
		return  $return . $this->getEnd($end);

	}

}