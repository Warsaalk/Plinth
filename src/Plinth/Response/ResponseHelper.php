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
    		case Route::TYPE_HTML 	: 
    		case Route::TYPE_ERROR	: return self::HTML;
    		default					: false;
    		
    	}
    	
    }
    
    public static function getTemplatePath($type) {
    	
    	switch ($type) {
    	
    		case Route::TYPE_FILE 	: return __TEMPLATE_FILE;
    		case Route::TYPE_JSON 	: return __TEMPLATE_JSON;
    		case Route::TYPE_PAGE 	: return __TEMPLATE_PAGE;
    		case Route::TYPE_HTML 	: return __TEMPLATE_HTML;
    		case Route::TYPE_XML 	: return __TEMPLATE_HTML;
    		case Route::TYPE_ERROR	: return __TEMPLATE_ERROR;
    		default					: return false;
    	
    	}
    	
    }
    
}