<?php

namespace biliboobrian\lumenAngularCodeGenerator;

/**
 * Interface RenderableInterface
 * @package biliboobrian\lumenAngularCodeGenerator
 */
interface RenderableInterface
{
    /**
     * @param int $indent
     * @param string $delimiter
     * @return string
     */
    public function render($indent = 0, $delimiter = PHP_EOL);
}
