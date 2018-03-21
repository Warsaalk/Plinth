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
	protected $multiple = array();

	/**
	 * @var array
	 */
	protected $rules = array();

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
	 * @return array
	 */
	public function getRules()
	{
		return $this->rules;
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
	 * @return callable
	 */
	public function getPostCallback()
	{
		return $this->postCallback;
	}
}