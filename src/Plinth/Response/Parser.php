<?php

namespace Plinth\Response;

use Plinth\Dictionary;
class Parser {

    /**
     * @param Response $self
     * @param string $template
     * @param string $path
     * @param string $tplExt
     * @return string
     */
	public static function parse($self, $template, $path, $tplExt, Dictionary $dictionary = null) {
		
		$fullPath = $path . $template . $tplExt;
		
		if (!file_exists($fullPath)) return false;
		
		/*
		 * Create shorthand for translating string via the dictionary
		 */
		if ($dictionary !== null) {
			$__ = function () use ($dictionary) {
				return call_user_func_array(array($dictionary, 'get'), func_get_args());
			};
		}
		
		/*
		 * Push data into variables
		 */
		if (method_exists($self, 'hasData') && $self->hasData()) {
			foreach ($self->getData() as $cantoverride_key => $cantoverride_value) {
				${$cantoverride_key} = $cantoverride_value;
			}
			unset($cantoverride_key);
			unset($cantoverride_value);
		}
		
		ob_start();
		require $fullPath;
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	
	}

}