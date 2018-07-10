<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;

use biliboobrian\lumenAngularCodeGenerator\RenderableModel;

/**
 * Class UseClassModel
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
class UseClassModel extends RenderableModel
{
    /**
     * @var string
     */
    protected $name;

    /**
     * PHPClassUse constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function toLines()
    {
        return sprintf('use %s;', $this->name);
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
}
