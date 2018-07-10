<?php

namespace biliboobrian\lumenAngularCodeGenerator;

/**
 * Interface LineableInterface
 * @package biliboobrian\lumenAngularCodeGenerator
 */
interface LineableInterface
{
    /**
     * @return string|string[]
     */
    public function toLines();
}