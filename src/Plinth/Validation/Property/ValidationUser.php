<?php

namespace Plinth\Validation\Property;

class ValidationUser
{
	use ValidationDefaultsTrait;

	/**
	 * @var callable
	 */
	private $callback;

	/**
	 * @param array $settings
	 * @return ValidationUser
	 */
	public static function loadFromArray(array $settings)
	{
		 $validationUser = new self();

		 if (isset($settings['message'])) $validationUser->setMessage($settings['message']);
		 if (isset($settings['required'])) $validationUser->setRequired($settings['required']);
		 if (isset($settings['callback'])) $validationUser->setCallback($settings['callback']);

		 return $validationUser;
	}

	/**
	 * @param callable $callback
	 * @return $this
	 */
	public function setCallback(callable $callback)
	{
		$this->callback = $callback;

		return $this;
	}

	/**
	 * @return callable
	 */
	public function getCallback()
	{
		return $this->callback;
	}
}