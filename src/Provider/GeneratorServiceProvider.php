<?php

namespace biliboobrian\lumenAngularCodeGenerator\Provider;

use Illuminate\Support\ServiceProvider;
use biliboobrian\lumenAngularCodeGenerator\Command\GenerateModelCommand;

/**
 * Class GeneratorServiceProvider
 * @package biliboobrian\lumenAngularCodeGenerator\Provider
 */
class GeneratorServiceProvider extends ServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function register()
    {
        $this->commands([
            GenerateModelCommand::class,
        ]);
    }
}