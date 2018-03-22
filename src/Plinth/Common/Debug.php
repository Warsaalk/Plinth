<?php

namespace Plinth\Common;

class Debug
{
	/**
	 * Print arrays in readable format
	 *
	 * @param array $arr
	 * @param bool $breakLine
	 */
	public static function arr($arr, $breakLine = false)
	{
		if ($breakLine === false) print '<pre>';
		print_r ($arr);
		print $breakLine === false ? '</pre>' : "\n";
	} 
	
	/**
	 * Print string on new line
	 *
	 * @param string $str
	 * @param bool $breakLine
	 */
	public static function str($str, $breakLine = false)
	{
		if ($breakLine === false) print '<div>';
		print ($str);
		print $breakLine === false ? '</div>' : "\n";
	}
	
	/**
	 * Print string on new line
	 *
	 * @param string $str
	 */
	public static function dump($str)
	{
		print '<pre>';
		var_dump($str);
		print '</pre>';
	}
}
