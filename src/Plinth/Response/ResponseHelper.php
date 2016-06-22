<?php 

namespace Plinth\Response;

use Plinth\Routing\Route;

class ResponseHelper {
    
    /**
     * @var string
     */
    const HTML      = "text/html";
    
    /**
     * @var string
     */
    const JSON		= "application/json";
    
    /**
     * @var string
     */
    const XML		= "application/xml";

	/**
	 * @var string
	 */
	const DEF		= "application/octet-stream";
    
    public static function getContentType(Route $route) {
    	
		if ($route->hasContentType()) return $route->getContentType();
		
    	switch ($route->getType()) {
    		
    		case Route::TYPE_JSON 	: return self::JSON;
    		case Route::TYPE_XML 	: return self::XML;
    		case Route::TYPE_PAGE 	: 
    		case Route::TYPE_HTML 	: return self::HTML;
    		default					: return self::DEF;
    		
    	}
    	
    }
    
}