<?php

namespace biliboobrian\lumenAngularCodeGenerator\Command;

use Illuminate\Console\Command;
use biliboobrian\lumenAngularCodeGenerator\Config;
use biliboobrian\lumenAngularCodeGenerator\Generator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class GenerateLumenModelsCommand
 * @package biliboobrian\lumenAngularCodeGenerator\Command
 */
class GenerateLumenControllersCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'bilibo:lumen:ctrls';

/**
     * @var string
     */
    protected $description = 'Generate CRUD controllers for all tables.';

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

        $tables = $this->generator->getTableList();
        foreach ($tables as $table) {
            if(strtolower($table->getName()) !== 'migrations') {
                $modelName = $this->generator->generateModelName(strtolower($table->getName()));

                $this->output->write(sprintf(
                    "%sController controller [%s] generation...", 
                    $modelName,
                    $table->getName()
                ));

                $config->set('class_name', $modelName);
                $config->set('table_name', $table->getName());
                
                $model = $this->generator->generateController($config);
                $this->output->writeln(sprintf('Done'));
            }
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
            ['lumen-ctrl-output-path', 'o', InputOption::VALUE_OPTIONAL, 'Directory to store generated controllers', null],
            ['lumen-ctrl-namespace', 's', InputOption::VALUE_OPTIONAL, 'Namespace of the controllers', null],
            ['base-class-lumen-ctrl-name', 'b', InputOption::VALUE_OPTIONAL, 'Class controllers must extend', null],
            ['config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file to use', null],
            ['no-timestamps', 't', InputOption::VALUE_NONE, 'Set timestamps property to false', null],
            ['date-format', 'd', InputOption::VALUE_OPTIONAL, 'dateFormat property', null],
            ['add-route', 'a', InputOption::VALUE_NONE, 'Add CRUD routes in web.php', null],
        ];
    }
}
