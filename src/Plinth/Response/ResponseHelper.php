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
    const Excel     = "application/vnd.ms-excel";
    
    /**
     * @var string
     */
    const Excel2007 = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
    
    /**
     * @var string
     */
    const JSON		= "application/json";
    
    /**
     * @var string
     */
    const XML		= "application/xml";
    
    public static function getContentType($type) {
    	
    	switch ($type) {
    		
    		case Route::TYPE_JSON 	: return self::JSON;
    		case Route::TYPE_XML 	: return self::XML;
    		case Route::TYPE_PAGE 	: 
    		case Route::TYPE_HTML 	: return self::HTML;
    		default					: false;
    		
    	}
    	
    }
    
}