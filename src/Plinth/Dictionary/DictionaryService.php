<?php

namespace Plinth\Dictionary;


use Plinth\Connector;

abstract class DictionaryService extends Connector
{
	/**
	 * Return an array of translations, array["label"] = "Text";
	 *
	 * @param string $locale
	 * @return array
	 */
	abstract public function loadTranslations($locale);
}