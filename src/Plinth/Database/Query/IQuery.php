<?php

namespace Plinth\Database\Query;

/**
 * IQuery interface
 */
interface IQuery
{
    const END = " ;";
    const NO_END = "";
        
	/**
	 * Returns the build query
	 * 
	 * @return string 
	 */
	public function get();
}