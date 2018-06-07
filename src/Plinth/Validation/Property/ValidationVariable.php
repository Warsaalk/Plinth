<?php

namespace Plinth\Validation\Property;


use Plinth\Validation\Validator;

class ValidationVariable extends ValidationProperty
{
	/**
	 * @var int
	 */
	protected $type = Validator::PARAM_STRING;

	/**
	 * @var string
	 */
	protected $default = '';

	/**
	 * @param string $name
	 * @param array $settings
	 * @return ValidationProperty|ValidationVariable
	 */
	public static function loadFromArray($name, array $settings)
	{
		$validationVariable = new self($name);

		if (isset($settings['name'])) $validationVariable->setName($settings['name']); // Override the default name is defined in the settings
		if (isset($settings['type'])) $validationVariable->setType($settings['type']);
		if (isset($settings['rules'])) $validationVariable->setRules($settings['rules']);
		if (isset($settings['flags'])) $validationVariable->setFlags($settings['flags']);
		if (isset($settings['default'])) $validationVariable->setDefault($settings['default']);
		if (isset($settings['message'])) $validationVariable->setMessage($settings['message']);
		if (isset($settings['required'])) $validationVariable->setRequired($settings['required']);
		if (isset($settings['multiple'])) $validationVariable->setMultiple($settings['multiple']);
		if (isset($settings['preCallback'])) $validationVariable->setPreCallback($settings['preCallback']);
		if (isset($settings['postCallback'])) $validationVariable->setPostCallback($settings['postCallback']);

		return $validationVariable;
	}

	/**
	 * @param array $multiple
	 * @return $this
	 */
	public function setMultiple(array $multiple = [])
	{
		$this->multiple = $multiple;

		return $this;
	}

	/**
	 * @param array $rules
	 * @return $this
	 */
	public function setRules(array $rules = [])
	{
		$this->rules = $rules;

		return $this;
	}

	/**
	 * @param $flags
	 * @return $this
	 */
	public function setFlags($flags)
	{
		$this->flags = $flags;

		return $this;
	}

	/**
	 * @param callable $preCallback
	 * @return $this
	 */
	public function setPreCallback(callable $preCallback)
	{
		$this->preCallback = $preCallback;

		return $this;
	}

	/**
	 * @param callable $postCallback
	 * @return $this
	 */
	public function setPostCallback(callable $postCallback)
	{
		$this->postCallback = $postCallback;

		return $this;
	}

	/**
	 * @param int $type
	 * @return $this
	 */
	public function setType($type = Validator::PARAM_STRING)
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * @param string $default
	 * @return $this
	 */
	public function setDefault($default = '')
	{
		$this->default = $default;

		return $this;
	}
}