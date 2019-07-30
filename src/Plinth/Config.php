<?php 

namespace Plinth;

use Plinth\Exception\PlinthException;

class Config
{
	const ENV_FILE = "env.ini";

	const REF_REGEX = '\$(ENV|CNF)((?:\.\w+)+)';

	const
		REF_TYPE_ENV = "ENV",
		REF_TYPE_CNF = "CNF";

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
			$this->parse($this->env, parse_ini_file(__BASE_ROOT . self::ENV_FILE, true, INI_SCANNER_RAW));
		}

		if (file_exists($file)) {
			$this->parse($this->config, parse_ini_file($file, true, INI_SCANNER_RAW), true);
		} else {
			throw new PlinthException("Your config file, $file, cannot be found.");
		}

		return $this;
	}

	/**
	 * @param string $refType ENV|CONF
	 * @param mixed $value
	 * @return mixed
	 */
	private function getRefValue ($refType, $value)
	{
		$path = explode(".", $value);

		array_shift($path); // Remove the leading . (dot)

		return array_reduce($path, function ($result, $item) {return isset($result[$item]) ? $result[$item] : null;}, $refType === self::REF_TYPE_CNF ? $this->config : $this->env);
	}

	/**
	 * @param $value
	 * @param bool $checkREF
	 * @return boolean|integer|string
	 * @throws PlinthException
	 */
	private function processValue($value, $checkREF = false)
	{
		if (preg_match('/^true|false$/', $value)) {
			return $value === 'true' ? true : false;
		} elseif (preg_match('/^\d+$/', $value)) {
			if ($value > PHP_INT_MAX) return $value;
			return (int)$value;
		} elseif ($checkREF) {
			// Full value match (allows all types of ref values)
			if (preg_match('/^' . self::REF_REGEX . '$/', $value, $match) === 1) {
				return $this->getRefValue($match[1], $match[2]);
			}

			// Replacement match (allows only scalar variables)
			preg_match_all('/\{' . self::REF_REGEX . '\}/', $value, $matches, PREG_SET_ORDER);
			if (count($matches) > 0) {
				$replacements = [];
				foreach ($matches as $match) {
					// Build-in check to avoid the same reference to be replaced twice
					if (!in_array($match[0], $replacements)) {
						$replacements[] = $match[0];
						$replacement = $this->getRefValue($match[1], $match[2]);

						if (!is_scalar($replacement)) {
							if ($replacement === null) {
								throw new PlinthException("Config error: the result of {$match[0]} is null/empty.");
							} else {
								throw new PlinthException("Config error: the result of {$match[0]} is not a scalar value. You can't replace object/array values using the {\$} notation.");
							}
						}

						$value = str_replace($match[0], $replacement, $value);
					}
				}
			}
		}

		return $value;
	}

	/**
	 * @param $keys
	 * @param $value
	 * @param array $array
	 * @param bool $checkREF
	 * @throws PlinthException
	 */
	private function build($keys, $value, &$array = [], $checkREF = false)
	{
		if (count($keys) == 1) {
			if (is_array($value)) {
				$this->parse($array[$keys[0]], $value, $checkREF);
			} else {
				$array[$keys[0]] = $this->processValue($value, $checkREF);
			}
		} else {
			$key = array_shift($keys);

			if(!isset($array[$key])) $array[$key] = [];

			$this->build($keys, $value, $array[$key], $checkREF);
		}
	}

	/**
	 * @param $destination
	 * @param $config
	 * @param bool $checkREF
	 * @return array
	 * @throws PlinthException
	 */
	private function parse(&$destination, $config, $checkREF = false)
	{
		if ($config === false) throw new PlinthException('Your config/env file contains some errors');

		if ($destination === null) {
			$destination = [];
		}

		foreach ($config as $key => $value) {
			if (preg_match( '/^(?!\.).*(?<!\.)$/', $key) && preg_match('/\./', $key)) { //Contains a point but doesn't start or end with one
				$this->build(explode('.', $key), $value, $destination, $checkREF);
			} else {
				if (is_array($value)) {
					$this->parse($destination[$key], $value, $checkREF);
				} else {
					$destination[$key] = $this->processValue($value, $checkREF);
				}
			}
		}

		return $destination;
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

	/**
	 * @param string $content
	 * @return string
	 */
	public function replaceReferencesInText ($content)
	{
		preg_match_all('/(?|' . self::REF_REGEX . '|"' . self::REF_REGEX . '\|(raw)")/', $content, $matches, PREG_SET_ORDER);

		if (count($matches) > 0) {
			$replacements = [];
			foreach ($matches as $match) {
				// Build-in check to avoid the same reference to be replaced twice
				if (!isset($replacements[$match[0]])) {
					$replacements[$match[0]] = $this->getRefValue($match[1], $match[2]);
				}
			}

			$content = str_replace(array_keys($replacements), array_values($replacements), $content);
		}

		return $content;
	}
}