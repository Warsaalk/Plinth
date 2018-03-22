<?php

namespace Plinth\Request;

class UploadedFile
{
    /**
     * @var string
     */
    private $_name;
    
    /**
     * @var string
     */
    private $_type;
    
    /**
     * @var string
     */
    private $_tmpName;
    
    /**
     * @var integer
     */
    private $_error;
    
    /**
     * @var integer
     */
    private $_size;
    
    /**
     * @param string $name
     * @param string $tmpName
     * @param string $type
     * @param integer $error
     * @param integer $size
     */
    public function __construct($name, $tmpName, $type, $error, $size)
	{
        $this->setName($name);
        $this->setTempName($tmpName);
        $this->setType($type);
        $this->setError($error);
        $this->setSize($size);
    }
    
    /**
     * @param string $name
	 * @return $this
     */
    public function setName($name)
	{
        $this->_name = $name;

        return $this;
    }
    
    /**
     * @return string
     */
    public function getName()
	{
        return $this->_name;
    }
    
    /**
     * @param string $type
	 * @return $this
     */
    public function setType($type)
	{
        $this->_type = $type;

        return $this;
    }
    
    /**
     * @return string
     */
    public function getType()
	{
        return $this->_type;
    }
    
    /**
     * @param string $tmpName
	 * @return $this
     */
    public function setTempName($tmpName)
	{
        $this->_tmpName = $tmpName;

        return $this;
    }
    
    /**
     * @return string
     */
    public function getTempName()
	{
        return $this->_tmpName;
    }
    
    /**
     * @param integer $error
	 * @return $this
     */
    public function setError($error)
	{
        $this->_error = $error;

        return $this;
    }
    
    /**
     * @return integer
     */
    public function getError()
	{
        return $this->_error;
    }
    
    /**
     * @param integer $size
	 * @return $this
     */
    public function setSize($size)
	{
        $this->_size = $size;

        return $this;
    }
    
    /**
     * @return integer
     */
    public function getSize()
	{
        return $this->_size;
    }
    
    /**
     * @return string
     */
    public function getData()
	{
        return file_get_contents($this->getTempName());
    }
}