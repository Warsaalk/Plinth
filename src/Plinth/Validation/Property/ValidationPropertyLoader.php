<?php
/**
 * Created by PhpStorm.
 * User: klaas
 * Date: 21/03/2018
 * Time: 10:19
 */

namespace Plinth\Validation\Property;


interface ValidationPropertyLoader
{
	/**
	 * @param string $name
	 * @param array $settings
	 * @return ValidationProperty
	 */
	public static function loadFromArray($name, array $settings);
}