<?php

namespace Plinth\Validation\Property;

abstract class ValidationProperty implements ValidationPropertyLoader
{
	use ValidationDefaultsTrait;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var int
	 */
	protected $type;

	/**
	 * @var array
	 */
	protected $multiple = [];

	/**
	 * @var array
	 */
	protected $rules = [];

	/**
	 * PHP Filter flags
	 * http://php.net/manual/en/filter.filters.flags.php
	 *
	 * @var int
	 */
	protected $flags;

	/**
	 * @var mixed
	 */
	protected $default;

	/**
	 * @var callable
	 */
	protected $preCallback;

	/**
	 * @var callable
	 */
	protected $postCallback;

	/**
	 * @var mixed
	 */
	private $value;

	/**
	 * @var bool
	 */
	private $valid = false;

	/**
	 * @param string $name
	 * @return $this
	 */
	public function __construct($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return array
	 */
	public function getMultiple()
	{
		return $this->multiple;
	}

	/**
	 * @return bool
	 */
	public function hasMultiple()
	{
		return !empty($this->multiple);
	}

	/**
	 * @return array
	 */
	public function getRules()
	{
		return $this->rules;
	}

	/**
	 * @return bool
	 */
	public function hasRules()
	{
		return !empty($this->rules);
	}

	/**
	 * @return int
	 */
	public function getFlags()
	{
		return $this->flags;
	}

	/**
	 * @return bool
	 */
	public function hasFlags()
	{
		return $this->flags !== null;
	}

	/**
	 * @return mixed
	 */
	public function getDefault()
	{
		return $this->default;
	}

	/**
	 * @return callable
	 */
	public function getPreCallback()
	{
		return $this->preCallback;
	}

	/**
	 * @return bool
	 */
	public function hasPreCallback()
	{
		return is_callable($this->preCallback);
	}

	/**
	 * @return callable
	 */
	public function getPostCallback()
	{
		return $this->postCallback;
	}

	/**
	 * @return bool
	 */
	public function hasPostCallback()
	{
		return is_callable($this->postCallback);
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 * @return $this
	 */
	public function setValue($value)
	{
		$this->value = $value;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isValid()
	{
		return $this->valid;
	}

	/**
	 * @return $this
	 */
	public function setValid()
	{
		$this->valid = true;

		return $this;
	}
}