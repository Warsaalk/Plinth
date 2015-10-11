<?php

namespace Plinth\Common;

class Language {

	/**
	 * Default language 
	 * @var string
	 */
	private static $default;
	
	/**
	 * @var string[]
	 */
	private static $languages;
	
	/**
	 * @param string[] $languages
	 * @param string $default
	 */
	public static function init( array $languages = array(), $default=false ){
	
			self::$languages = $languages;
	
			if( $default !== false ) 				self::$default = $default;
			elseif( $default === false && 
					!empty( self::$languages ) )	self::$default = self::$languages[0];
			else									self::$default = false;
	
	}
	
	/**
	 * If language doesn't exist return default language
	 * 
	 * @param string $lang
	 * @return string
	 */
	public static function validate( $lang ){
				
			if( !in_array( $lang, self::$languages ) )
					return self::$default;
			return $lang;
	
	}
	
	/**
	 * @return string[]
	 */
	public static function getLanguages() {
		
		return self::$languages;
		
	}
	
	/**
	 * @return string
	 */
	public static function getDefault(){
				
			return self::$default;
	
	}
	
}