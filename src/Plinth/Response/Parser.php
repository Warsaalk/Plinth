<?php

namespace Plinth\Response;

use Plinth\Dictionary;

class Parser
{
    /**
     * @param Response $self
     * @param string $template
	 * @param array $templateData
     * @param string $path
     * @param string $tplExt
	 * @param Dictionary $dictionary
     * @return string
     */
	public static function parse($self, $template, $templateData = array(), $path = "", $tplExt = __EXTENSION_PHP, Dictionary $dictionary = null)
	{
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
			$templateData = array_merge($templateData, $self->getData());
		}

		foreach ($templateData as $cantoverride_key => $cantoverride_value) {
			${$cantoverride_key} = $cantoverride_value;
		}
		unset($cantoverride_key);
		unset($cantoverride_value);
		
		ob_start();
		require $fullPath;
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}