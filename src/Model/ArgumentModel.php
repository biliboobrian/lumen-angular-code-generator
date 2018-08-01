<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;

use biliboobrian\lumenAngularCodeGenerator\RenderableModel;

/**
 * Class Argument
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
class ArgumentModel extends RenderableModel
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $default;

    /**
     * @var string
     */
    protected $dbType;

    /**
     * ArgumentModel constructor.
     * @param string $name
     * @param string|null $type
     * @param mixed|null $default
     */
    public function __construct($name, $type = null, $default = null, $dbType = 'lumen')
    {
        $this->setName($name)
            ->setType($type)
            ->setDefault($default);
        $this->dbType = $dbType;
    }

    /**
     * {@inheritDoc}
     */
    public function toLines()
    {
        if($this->dbType == 'lumen') {
            if ($this->type !== null) {
                return $this->type . ' $' . $this->name .$this->getDefaultLine();
            } else {
                return '$' . $this->name .$this->getDefaultLine();
            }
        } else {
            if ($this->type !== null) {
                return  $this->name .': '. $this->type .$this->getDefaultLine();
            } else {
                return $this->name .$this->getDefaultLine();
            }
        }
       
    }

    public function getDefaultLine()
    {
        if($this->default)
            return " = ". $this->default;
        else 
            return '';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }
}
