<?php

namespace Plinth\Request;

use Plinth\Main;
use Plinth\Common\Info;
use Plinth\Connector;

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
	 * @return array
	 */
	abstract public function onFinish(array $variables, array $files);

	/**
	 * Method called when a request is invalid or has errors
	 *
	 * @return array
	 */
	abstract public function onError();

}