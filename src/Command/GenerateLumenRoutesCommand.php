<?php

namespace biliboobrian\lumenAngularCodeGenerator\Command;

use Illuminate\Console\Command;
use biliboobrian\lumenAngularCodeGenerator\Config;
use biliboobrian\lumenAngularCodeGenerator\Generator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class GenerateLumenControllerCommand
 * @package biliboobrian\lumenAngularCodeGenerator\Command
 */
class GenerateLumenRoutesCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'bilibo:lumen:routes';

/**
     * @var string
     */
    protected $description = 'Generate missing routes from db model list.';

    /**
     * @var Generator
     */
    protected $generator;

    /** 
     * @param Generator $generator
     */
    public function __construct(Generator $generator)
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
        $ctrl = 
        $tables = $this->generator->getTableList();
        
        foreach ($tables as $table) {
            $modelName = $this->generator->generateModelName(strtolower($table->getName()));

            $this->output->write(sprintf(
                "%s routes generation...", 
                $modelName,
                $table->getName()
            ));

            $config->set('class_name', $modelName);
            $config->set('table_name', strtolower($table->getName()));
            
            $this->generator->generateroutes($config);
            $this->output->writeln(sprintf('Done'));
        } 

        $this->output->writeln('Missing routes generated');
    }

    /**
     * @return Config
     */
    protected function createConfig()
    {
        $config = [];

        foreach ($this->getArguments() as $argument) {
            if (!empty($this->argument($argument[0]))) {
                $config[$argument[0]] = $this->argument($argument[0]);
            }
        }
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
            ['config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file to use', null],
            ['connection', 'cn', InputOption::VALUE_OPTIONAL, 'Connection property', null],
        ];
    }
}
