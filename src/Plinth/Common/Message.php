<?php
/**
 * Created by PhpStorm.
 * User: klaas
 * Date: 5/04/2018
 * Time: 10:43
 */

namespace Plinth\Common;


class Message implements \JsonSerializable
{
	const	TYPE_INFO		= 1,
			TYPE_SUCCESS	= 2,
			TYPE_WARNING	= 4,
			TYPE_ERROR		= 8,
			TYPE_DEBUG		= 16;

	/**
	 * @var string
	 */
	private $_message;

	/**
	 * @var string
	 */
	private $_actionLabel;

	/**
	 * @var string|integer
	 */
	protected $_type;

	/**
	 * Message constructor.
	 *
	 * @param string $message
	 * @param integer|string $type
	 * @param string $actionLabel
	 */
	public function __construct ($message, $type = self::TYPE_INFO, $actionLabel = null)
	{
		$this->_message		= $message;
		$this->_type		= $type;
		$this->_actionLabel	= $actionLabel;
	}

	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->_message;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @return string
	 */
	public function getActionLabel()
	{
		return $this->_actionLabel;
	}

	/**
	 * @param string $actionLabel
	 * @return $this
	 */
	public function setActionLabel($actionLabel)
	{
		$this->_actionLabel = $actionLabel;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function hasActionLabel()
	{
		return $this->_actionLabel !== null;
	}

	/**
	 * @return array
	 */
	public function getArray()
	{
		return [
			'message' => $this->getMessage(),
			'label' => $this->getActionLabel(),
			'type' => $this->getType()
		];
	}

	/**
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->getArray();
	}
}