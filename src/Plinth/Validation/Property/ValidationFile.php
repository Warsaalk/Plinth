<?php

namespace Plinth\Validation\Property;


use Plinth\Validation\Validator;

class ValidationFile extends ValidationProperty
{
	/**
	 * @var int
	 */
	protected $type = Validator::PARAM_FILE;

	/**
	 * @param string $name
	 * @param array $settings
	 * @return ValidationProperty|ValidationFile
	 */
	public function loadFromArray($name, array $settings)
	{
		$validationFile = new self($name);

		if (isset($settings['rules'])) $validationFile->setRules($settings['rules']);
		if (isset($settings['message'])) $validationFile->setMessage($settings['message']);
		if (isset($settings['required'])) $validationFile->setRequired($settings['required']);
		if (isset($settings['multiple'])) $validationFile->setMultiple($settings['multiple']);

		return $validationFile;
	}

	/**
	 * @param array $multiple
	 * @return $this
	 */
	public function setMultiple(array $multiple = array())
	{
		$this->multiple = $multiple;

		return $this;
	}

	/**
	 * @param array $rules
	 * @return $this
	 */
	public function setRules(array $rules = array())
	{
		$this->rules = $rules;

		return $this;
	}
}