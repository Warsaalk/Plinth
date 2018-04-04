<?php

namespace Plinth\Validation;

use Plinth\Request\UploadedFile;

class FileValidator
{
    const   RULE_ALLOWED_TYPES = 'types',
            RULE_ALLOWED_SIZE = 'size'; //In Kb
    
    const   TYPE_JPG = 0,
			TYPE_PNG = 1,
			TYPE_GIF = 2,
			TYPE_BMP = 3,
			TYPE_PSD = 4,
			TYPE_MP3 = 5,
			TYPE_MP4 = 6,
			TYPE_OGV = 7,
			TYPE_WEBM = 8,
			TYPE_WAV = 9,
			TYPE_WMA = 10,
			TYPE_AVI = 11,
			TYPE_WMV = 12,
			TYPE_FLV = 13,
			TYPE_MPG = 14,
			TYPE_TIFF = 15,
			TYPE_MOV = 16,
			TYPE_M4A = 17,
			TYPE_SWF = 18,
			TYPE_HTML = 19,
			TYPE_DOC = 20,
			TYPE_DOCX = 21,
			TYPE_PPT = 22,
			TYPE_PPTX = 23,
			TYPE_XLS = 24,
			TYPE_XLSX = 25,
			TYPE_CSV = 26,
			TYPE_SAV = 27,
			TYPE_SPS = 28,
			TYPE_PDF = 29,
			TYPE_ZIP = 30,
			TYPE_RAR = 31,
			TYPE_TXT = 32,
			TYPE_JS = 33,
			TYPE_JSON = 34;

	/**
	 * @var array
	 */
	private $_mimetypes = [
		self::TYPE_JPG => ['image/jpeg', 'image/jpg', 'image/jp_', 'application/jpg', 'application/x-jpg', 'image/pjpeg', 'image/pipeg', 'image/vnd.swiftview-jpeg', 'image/x-xbitmap'],
		self::TYPE_PNG => ['image/png', 'image/x-png', 'application/png', 'application/x-png'],
		self::TYPE_GIF => ['image/gif', 'image/x-xbitmap', 'image/gi_'],
		self::TYPE_BMP => ['image/bmp', 'image/x-bmp', 'image/x-bitmap', 'image/x-xbitmap', 'image/x-win-bitmap', 'image/x-windows-bmp', 'image/ms-bmp', 'image/x-ms-bmp', 'application/bmp', 'application/x-bmp', 'application/x-win-bitmap'],
		self::TYPE_PSD => ['image/photoshop', 'image/x-photoshop', 'image/psd', 'application/photoshop', 'application/psd', 'zz-application/zz-winassoc-psd'],
		self::TYPE_MP3 => ['audio/mpeg', 'audio/x-mpeg', 'audio/mp3', 'audio/x-mp3', 'audio/mpeg3', 'audio/x-mpeg3', 'audio/mpg', 'audio/x-mpg', 'audio/x-mpegaudio'],
		self::TYPE_MP4 => ['video/mp4v-es', 'audio/mp4', 'video/mp4'],
		self::TYPE_OGV => ['video/ogg'],
		self::TYPE_WEBM =>['video/webm'],
		self::TYPE_WAV => ['audio/wav', 'audio/x-wav', 'audio/wave', 'audio/x-pn-wav'],
		self::TYPE_WMA => ['audio/x-ms-wma', 'video/x-ms-asf'],
		self::TYPE_AVI => ['video/avi', 'video/msvideo', 'video/x-msvideo', 'image/avi', 'video/xmpg2', 'application/x-troff-msvideo', 'audio/aiff', 'audio/avi'],
		self::TYPE_WMV => ['video/x-ms-wmv'],
		self::TYPE_FLV => ['video/x-flv'],
		self::TYPE_MPG => ['video/mpeg'],
		self::TYPE_TIFF =>['application/tif', 'application/tiff', 'application/x-tif', 'application/x-tiff', 'image/tif', 'image/x-tif', 'image/x-tiff', 'image/tiff'],
		self::TYPE_MOV => ['video/quicktime'],
		self::TYPE_M4A => ['audio/m4a', 'audio/x-m4a'],
		self::TYPE_SWF => ['application/x-shockwave-flash', 'application/x-shockwave-flash2-preview', 'application/futuresplash', 'image/vnd.rn-realflash'],
		self::TYPE_HTML =>['text/html'],
		self::TYPE_DOC => ['application/msword', 'application/doc', 'appl/text', 'application/vnd.msword', 'application/vnd.ms-word', 'application/winword', 'application/word', 'application/x-msw6', 'application/x-msword'],
		self::TYPE_DOCX =>['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
		self::TYPE_PPT => ['application/vnd.ms-powerpoint', 'application/mspowerpoint', 'application/ms-powerpoint', 'application/mspowerpnt', 'application/vnd-mspowerpoint', 'application/powerpoint', 'application/x-powerpoint', 'application/x-m'],
		self::TYPE_PPTX =>['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
		self::TYPE_XLS => ['application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/vnd.ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls'],
		self::TYPE_XLSX =>['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
		self::TYPE_CSV => ['text/comma-separated-values', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.ms-excel', 'application/vnd.msexcel'],
		self::TYPE_SAV => ['application/x-spss-sav', 'application/x-tads-save'],
		self::TYPE_SPS => ['application/x-spss-sps'],
		self::TYPE_PDF => ['application/pdf', 'application/x-pdf', 'application/acrobat', 'applications/vnd.pdf', 'text/pdf', 'text/x-pdf'],
		self::TYPE_ZIP => ['application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream', 'application/x-compress', 'application/x-compressed', 'multipart/x-zip'],
		self::TYPE_RAR => ['application/rar', 'application/stuffit', 'application/x-rar-compressed'],
		self::TYPE_TXT => ['text/plain', 'application/txt', 'browser/internal', 'text/anytext', 'widetext/plain', 'widetext/paragraph'],
		self::TYPE_JS =>  ['application/x-javascript', 'text/javascript', 'application/x-js', 'application/javascript'],
		self::TYPE_JSON =>['application/json']
	];
	
	/**
	 * @param array $files
	 * @param array $properties
	 * @return array
	 */
	public function filter_array(array $files, array $properties)
	{
        foreach ($properties as $label => $settings) {
            if (isset($files[$label])) {
                foreach ($files[$label] as $i => $file) {
                    /* @var $file UploadedFile */
                    if ($file->getError() === UPLOAD_ERR_NO_FILE) $files[$label][$i] = NULL;
                    else {
                        if (!$this->validateRules($file, $properties[$label])) $files[$label][$i] = false;
                    }
                }
            } else {
            	$files[$label] = array(NULL);
			}
        }

        foreach (array_diff_key($files, $properties) as $label => $uploadedFiles) {
			unset($files[$label]);
		}
        
        return $files;
	}

	/**
	 * @param UploadedFile $file
	 * @param $props
	 * @return bool
	 */
	private function validateRules(UploadedFile $file, array $props)
	{
	    foreach ($props as $rule => $ruleValue) {
	        switch ($rule) {
	            case self::RULE_ALLOWED_SIZE: if ($file->getSize() > ($ruleValue * 1000)) return false;
	                break;
	                
	            case self::RULE_ALLOWED_TYPES:
	                $found = false;
	                foreach ($ruleValue as $type) {
	                    if (isset($this->_mimetypes[$type]) && array_search($file->getType(), $this->_mimetypes[$type]) !== false) {
	                        $found = true;
	                        break;
	                    }
	                }
	                if (!$found) return false;
	                break;
	        }
	    }

	    return true;
	}
}