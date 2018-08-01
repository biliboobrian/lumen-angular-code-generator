<?php

namespace biliboobrian\lumenAngularCodeGenerator\Command;

use Illuminate\Console\Command;
use biliboobrian\lumenAngularCodeGenerator\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use biliboobrian\lumenAngularCodeGenerator\AngularGenerator;

/**
 * Class GenerateAngularModelCommand
 * @package biliboobrian\lumenAngularCodeGenerator\Command
 */
class GenerateAngularModelCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'bilibo:angular:model';

/**
     * @var string
     */
    protected $description = 'Generate a Angular model according to Table passed in argument.';

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

        $model = $this->generator->generateModel($config);

        $this->output->writeln(sprintf('Model %s generated', $model->getName()->getName()));
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
        return [
            ['class-name', InputArgument::REQUIRED, 'Name of the table'],
        ];
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['table-name', 't', InputOption::VALUE_OPTIONAL, 'Name of the table to use', null],
            ['output-path', 'o', InputOption::VALUE_OPTIONAL, 'Directory to store generated model', null],
            ['namespace', 's', InputOption::VALUE_OPTIONAL, 'Namespace of the model', null],
            ['base-class-name', 'b', InputOption::VALUE_OPTIONAL, 'Class that model must extend', null],
            ['config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file to use', null],
            ['no-timestamps', 'm', InputOption::VALUE_NONE, 'Set timestamps property to false', null],
            ['date-format', 'f', InputOption::VALUE_OPTIONAL, 'dateFormat property', null],
            ['connection', 'e', InputOption::VALUE_OPTIONAL, 'Connection property', null],
        ];
    }
}
