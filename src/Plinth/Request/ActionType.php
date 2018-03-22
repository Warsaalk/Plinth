<?php

namespace Plinth\Request;

use Plinth\Main;
use Plinth\Common\Info;
use Plinth\Connector;
use Plinth\Validation\Property\ValidationProperty;

abstract class ActionType extends Connector
{
	/**
	 * @var \Closure
	 */
	private $requestErrorCallback;

	/**
	 * ActionType constructor.
	 * @param Main $main
	 * @param $errorCallback
	 */
	public function __construct(Main $main, \Closure $errorCallback)
	{
		parent::__construct($main);

		$this->requestErrorCallback = $errorCallback;
	}

	/**
	 * @param Info $error
	 */
	protected function addError(Info $error)
	{
		$this->requestErrorCallback->__invoke($error);
	}

	/**
	 * Example:
	 * array(
	 * 		'variable' => array(
	 * 			'[name]' => array(
	 * 				'rules' => array( {optional}
	 * 					Validator::RULE_* => mixed
	 * 				)
	 * 				'type' => Validator::PARAM_* {optional}
	 * 				'required' => boolean {optional}
	 * 				'default' => mixed {optional}
	 *              'message' => Info {optional}
	 * 			)
	 *		)
	 *		'userlevel' => User::[name] {optional}
	 *		'token' => boolean {optional}
	 * )
	 *
	 * @return array
	 */
	abstract public function getSettings();

	/**
	 * Method called when a request is valid and has no errors (yet)
	 *
	 * @param array $variables
	 * @param array $files
	 * @param ValidationProperty[] $validations
	 * @return array
	 */
	abstract public function onFinish(array $variables, array $files, array $validations);

	/**
	 * Method called when a request is invalid or has errors
	 *
	 * @param ValidationProperty[] $validations
	 * @return array
	 */
	abstract public function onError(array $validations);

	/**
	 * Method called after onFinish or onError and at the end of the request handling
	 *
	 * @param ValidationProperty[] $validations
	 * @return array
	 */
	public function onFinally(array $validations)
	{
		return [];
	}
}