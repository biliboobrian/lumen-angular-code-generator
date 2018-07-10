<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;

use biliboobrian\lumenAngularCodeGenerator\RenderableModel;

/**
 * Class PHPClassNamespace
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
class NamespaceModel extends RenderableModel
{
    /**
     * @var string
     */
    protected $namespace;

    /**
     * PHPClassNamespace constructor.
     * @param string $namespace
     */
    public function __construct($namespace)
    {
        $this->setNamespace($namespace);
    }

    /**
     * {@inheritDoc}
     */
    public function toLines()
    {
        return sprintf('namespace %s;', $this->namespace);
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     *
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }
}
