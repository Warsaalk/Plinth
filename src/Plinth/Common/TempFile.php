<?php

namespace Plinth\Common;


class TempFile
{
	/**
	 * @var string
	 */
	private $fileName;

	/**
	 * @param string $prefix
	 * @param string $tempDir
	 */
	public function __construct($prefix, $tempDir = null)
	{
		$this->fileName = tempnam($tempDir?: sys_get_temp_dir(), $prefix);
	}

	/**
	 * @return string
	 */
	public function getFileName()
	{
		return $this->fileName;
	}

	public function __destruct()
	{
		unlink($this->fileName);
	}
}