<?php

namespace Plinth\Request;

use Plinth\Validation\Property\ValidationProperty;

abstract class ActionTypeLogin extends ActionType
{
	/**
	 * Return the name of the property that should be used as the login ID to find the user
	 * When omitted the UserService will try to login using only the token.
	 *
	 * @return string
	 */
	public function getLoginLabel()
	{
		return null;
	}

	/**
	 * Return the name of the property that should be used as the login token for the user
	 *
	 * @return string
	 */
	abstract public function getTokenLabel();

	/**
	 * Method called when the login has succeeded
	 *
	 * @param array $variables
	 * @param ValidationProperty[] $validations
	 * @return array
	 */
	public function onLoginSuccess(array $variables, array $validations)
	{
		return [];
	}

	/**
	 * Method called when the login has failed
	 *
	 * @param ValidationProperty[] $validations
	 * @return array
	 */
	public function onLoginFailed(array $variables, array $validations)
	{
		return [];
	}
}