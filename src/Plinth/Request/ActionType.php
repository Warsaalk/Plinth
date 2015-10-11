<?php 

namespace Plinth\Request;

use Plinth\Main;
use Plinth\Common\Info;
use Plinth\Connector;

abstract class ActionType extends Connector {
	
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
	 * @param Main $main
	 * @param array $variables
	 * @param array $files
	 */
	abstract public function onFinish(array $variables, array $files);
	
	/**
	 * @param Main $main
	 * @param Info $error
	 */
	abstract public function onError();
	
}