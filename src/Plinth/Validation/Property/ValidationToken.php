<?php

namespace Plinth\Validation\Property;


use Plinth\Validation\Validator;

class ValidationToken extends ValidationProperty
{
	/**
	 * @var int
	 */
	protected $type = Validator::PARAM_STRING;

	/**
	 * @var bool
	 */
	protected $required = true;

	/**
	 * @param string $name
	 * @param array $settings
	 * @return ValidationProperty|ValidationToken
	 */
	public function loadFromArray($name, array $settings)
	{
		 $validationToken = new self($name);

		 if (isset($settings['rules'])) $validationToken->setRules($settings['rules']);
		 if (isset($settings['message'])) $validationToken->setMessage($settings['message']);
		 if (isset($settings['required'])) $validationToken->setRequired($settings['required']);
		 if (isset($settings['preCallback'])) $validationToken->setPreCallback($settings['preCallback']);
		 if (isset($settings['postCallback'])) $validationToken->setPostCallback($settings['postCallback']);

		 return $validationToken;
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
}