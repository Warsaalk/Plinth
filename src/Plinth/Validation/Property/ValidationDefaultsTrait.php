<?php

namespace Plinth\Validation\Property;


use Plinth\Common\Info;

trait ValidationDefaultsTrait
{
	/**
	 * @var boolean
	 */
	protected $required = true;

	/**
	 * @var Info
	 */
	protected $message = null;

	/**
	 * @param array $settings
	 * @return mixed
	 */
	abstract function loadFromArray (array $settings);

	/**
	 * @return bool
	 */
	public function isRequired()
	{
		return $this->required;
	}

	/**
	 * @param bool $required
	 * @return $this
	 */
	public function setRequired($required)
	{
		$this->required = $required;

		return $this;
	}

	/**
	 * @return Info
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @param Info $message
	 * @return $this
	 */
	public function setMessage($message)
	{
		$this->message = $message;

		return $this;
	}
}