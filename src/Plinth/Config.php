<?php 

namespace Plinth;

use Plinth\Exception\PlinthException;

class Config
{
	const ENV_FILE = "env.ini";

	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var array
	 */
	private $env;

	/**
	 * Config constructor.
	 * @param $file
	 * @throws PlinthException
	 */
	public function __construct($file)
	{
		if (file_exists(__BASE_ROOT . self::ENV_FILE)) {
			$this->env = $this->parse(parse_ini_file(__BASE_ROOT . self::ENV_FILE, true, INI_SCANNER_RAW));
		}

		if (file_exists($file)) {
			$this->config = $this->parse(parse_ini_file($file, true, INI_SCANNER_RAW), true);
		} else {
			throw new PlinthException("Your config file, $file, cannot be found.");
		}

		return $this;
	}

	/**
	 * @param $value
	 * @return mixed
	 */
	private function getEnvValue ($value)
	{
		$path = explode(".", $value);

		array_shift($path); // Remove the $ENV

		return array_reduce($path, function ($result, $item) {return isset($result[$item]) ? $result[$item] : null;}, $this->env);
	}

	/**
	 * @param $value
	 * @param bool $checkEnv
	 * @return boolean|integer|string
	 */
	private function processValue($value, $checkEnv = false)
	{
		if (preg_match('/^true|false$/', $value)) {
			return $value === 'true' ? true : false;
		} elseif (preg_match('/^\d+$/', $value)) {
			if ($value > PHP_INT_MAX) return $value;
			return (int)$value;
		} elseif ($checkEnv && preg_match('/^\$ENV(\.\w+)+$/', $value)) {
			return $this->getEnvValue($value);
		}

		return $value;
	}

	/**
	 * @param array $keys
	 * @param array $array
	 * @param mixed $value
	 * @param bool $checkEnv
	 */
	private function build($keys, $value, &$array = [], $checkEnv = false)
	{
		if (count($keys) == 1) {
			$array[$keys[0]] = $this->processValue($value, $checkEnv);
		} else {
			$key = array_shift($keys);

			if(!isset($array[$key])) $array[$key] = [];

			$this->build($keys, $value, $array[$key], $checkEnv);
		}
	}

	/**
	 * @param $config
	 * @param bool $checkEnv
	 * @return array
	 * @throws PlinthException
	 */
	private function parse($config, $checkEnv = false)
	{
		if ($config === false) throw new PlinthException('Your config/env file contains some errors');

		$parsed = [];

		foreach ($config as $section => $keys) {
			$parsed[$section] = [];

			foreach ($keys as $key => $value) {
				if (preg_match( '/^(?!\.).*(?<!\.)$/', $key) && preg_match('/\./', $key)) { //Contains a point but doesn't start or end with one
					$this->build(explode('.', $key), $value, $parsed[$section], $checkEnv);
				} else {
					if (is_array($value)) {
						$parsed[$section][$key] = [];

						foreach ($value as $i => $arrayvalue) {
							$parsed[$section][$key][$i] = $this->processValue($arrayvalue, $checkEnv);
						}
					} else {
						$parsed[$section][$key] = $this->processValue($value, $checkEnv);
					}
				}
			}
		}

		return $parsed;
	}

	/**
	 * @param array $keys
	 * @param mixed $current
	 * @return boolean|string|integer
	 */
	private function walk($keys, $current)
	{
		if (count($keys) > 1) {
			$key = array_shift($keys);

			return isset($current[$key]) ? $this->walk($keys, $current[$key]) : false;
		} else {
			return isset($current[$keys[0]]) ? $current[$keys[0]] : false;
		}
	}

	/**
	 * @return array
	 */
	public function getAll()
	{
		return $this->config;
	}

	/**
	 * @param string $key
	 * @return boolean|string|integer|array
	 */
	public function get($key)
	{
		return $this->walk(explode(':', $key), $this->config);
	}

	/**
	 * @param string $firstKey
	 * @return $this
	 */
	public function destroy($firstKey)
	{
		if (isset($this->config[$firstKey]))
			unset($this->config[$firstKey]);

		return $this;
	}

	/**
	 * @param Config $config
	 * @return $this
	 */
	public function merge(Config $config)
	{
		$this->config = array_replace_recursive($this->config, $config->getAll());

		return $this;
	}
}