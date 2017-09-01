<?php

namespace Plinth\Database\Query;


interface OrderByQuery
{
    const ORDER_DEFAULT = 0;
    const ORDER_DESC = 1;
    const ORDER_ASC = 2;
    
    /**
     * @param string $column
     * @param integer $order
     */
    public function orderBy($column, $order=self::ORDER_DEFAULT);
}