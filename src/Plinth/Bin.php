<?php

namespace Plinth;
use Plinth\Exception\PlinthException;

/**
 * This will only on systems running bash & nohup support
 *
 * Class Bin
 * @package Plinth
 */
class Bin extends Connector
{
	CONST	SCRIPT_TYPE_PHP = 0;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var string
	 */
	private $executable_php;

	/**
	 * Bin constructor.
	 * @param Main $main
	 */
	public function __construct(Main $main)
	{
		parent::__construct($main);

		$this->path = __BASE_ROOT . $main->getSetting('executabledirectory') . DIRECTORY_SEPARATOR;
		$this->executable_php = $main->getSetting('executablephp');
	}

	/**
	 * @param $scriptType
	 * @param $scriptName
	 * @param array $parameters
	 * @param bool $inBackground
	 * @return mixed
	 * @throws PlinthException
	 */
	public function run($scriptType, $scriptName, $parameters = [], $inBackground = false)
	{
		$executable = false;

		switch ($scriptType) {
			case self::SCRIPT_TYPE_PHP: $executable = $this->executable_php; break;
		}

		if ($executable === false) throw new PlinthException("Please use an existing script type");

		$command = "$executable {$this->path}$scriptName " . implode(" ", $parameters);

		if ($inBackground === true) {
			exec('bash -c "exec nohup setsid ' . $command . ' > /dev/null 2>/dev/null &"');
		} else {
			exec('bash -c "exec nohup setsid ' . $command . ' 2>/dev/null &"', $output);
			return $output;
		}

		return null;
	}

	/**
	 * @param $scriptName
	 * @param array $parameters
	 * @param bool $inBackground
	 * @return mixed
	 * @throws PlinthException
	 */
	public function runPHPScript($scriptName, $parameters = [], $inBackground = false)
	{
		return $this->run(self::SCRIPT_TYPE_PHP, $scriptName, $parameters, $inBackground);
	}
}