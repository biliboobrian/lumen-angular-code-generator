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
class GenerateLumenBulkControllerCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'bilibo:lumen:bulkCtrl';

/**
     * @var string
     */
    protected $description = 'Generate a bulk controller for your api.';

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
        $config->set('class_name', 'Bulk');

        $ctrl = $this->generator->generateBulkController($config);

        $this->output->writeln('Controller bulk generated');
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
            ['lumen-ctrl-output-path', 'op', InputOption::VALUE_OPTIONAL, 'Directory to store generated controller', null],
            ['config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file to use', null],
            ['connection', 'cn', InputOption::VALUE_OPTIONAL, 'Connection property', null],
        ];
    }
}
