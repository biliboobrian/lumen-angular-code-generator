<?php

namespace biliboobrian\lumenAngularCodeGenerator\Provider;

use Illuminate\Support\ServiceProvider;
use biliboobrian\lumenAngularCodeGenerator\Command\GenerateLumenModelCommand;
use biliboobrian\lumenAngularCodeGenerator\Command\GenerateLumenModelsCommand;
use biliboobrian\lumenAngularCodeGenerator\Command\GenerateLumenControllerCommand;
use biliboobrian\lumenAngularCodeGenerator\Command\GenerateLumenControllersCommand;

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
            GenerateLumenModelCommand::class,
            GenerateLumenModelsCommand::class,
            GenerateLumenControllerCommand::class,
            GenerateLumenControllersCommand::class
        ]);

    }
}