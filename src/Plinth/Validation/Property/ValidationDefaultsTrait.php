<?php

namespace Plinth\Validation\Property;


use Plinth\Common\Message;

trait ValidationDefaultsTrait
{
	/**
	 * @var boolean
	 */
	protected $required = true;

	/**
	 * @var Message
	 */
	protected $message = null;

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
	 * @return Message
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @param Message $message
	 * @return $this
	 */
	public function setMessage(Message $message)
	{
		$this->message = $message;

		return $this;
	}
}