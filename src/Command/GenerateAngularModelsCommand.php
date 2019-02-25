<?php

namespace biliboobrian\lumenAngularCodeGenerator\Command;

use Illuminate\Console\Command;
use biliboobrian\lumenAngularCodeGenerator\Config;
use biliboobrian\lumenAngularCodeGenerator\AngularGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class GenerateAngularModelsCommand
 * @package biliboobrian\lumenAngularCodeGenerator\Command
 */
class GenerateAngularModelsCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'bilibo:angular:models';

/**
     * @var string
     */
    protected $description = 'Generate Angular models for all database schema.';

    /**
     * @var AngularGenerator
     */
    protected $generator;

    /** 
     * @param AngularGenerator $generator
     */
    public function __construct(AngularGenerator $generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }

    /**
     * Handler for lumen command
     */
    public function handle()
    {
        return $this->fire();
    }

    /**
     * Executes the command
     */
    public function fire()
    {
        $config = $this->createConfig();

        $tables = $this->generator->getTableList();
        foreach ($tables as $table) {
            $modelName = $this->generator->generateModelName(strtolower($table->getName()));

            $this->output->write(sprintf(
                "%s model [%s] generation...", 
                $modelName,
                $table->getName()
            ));

            $config->set('class_name', $modelName);
            $config->set('table_name', $table->getName());
            
            $model = $this->generator->generateModel($config);
            
            
            $this->output->writeln(sprintf('Done'));
        }

        
        
    }

    /**
     * @return Config
     */
    protected function createConfig()
    {
        $config = [];

        foreach ($this->getOptions() as $option) {
            if (!empty($this->option($option[0]))) {
                $config[$option[0]] = $this->option($option[0]);
            }
        }

        return new Config($config);
    }

    /**
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['lumen-model-output-path',     'o', InputOption::VALUE_OPTIONAL, 'Directory to store generated model', null],
            ['lumen-model-namespace',       's', InputOption::VALUE_OPTIONAL, 'Namespace of the model', null],
            ['base-class-lumen-model-name', 'b', InputOption::VALUE_OPTIONAL, 'Class that model must extend', null],
            ['config',                      'c', InputOption::VALUE_OPTIONAL, 'Path to config file to use', null],
            ['no-timestamps',               't', InputOption::VALUE_NONE, 'Set timestamps property to false', null],
            ['date-format',                 'd', InputOption::VALUE_OPTIONAL, 'dateFormat property', null],
            ['add-cache', 'a', InputOption::VALUE_OPTIONAL, 'Add Models in caches observer system', null],
        ];
    }
}
