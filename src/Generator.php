<?php

namespace biliboobrian\lumenAngularCodeGenerator;

use biliboobrian\lumenAngularCodeGenerator\Model\ClassModel;
use biliboobrian\lumenAngularCodeGenerator\ControllerBuilder;
use biliboobrian\lumenAngularCodeGenerator\Exception\GeneratorException;

/**
 * Class Generator
 * @package Krlove\Generator
 */
class Generator
{
    /**
     * @var EloquentModelBuilder
     */
    protected $modelBuilder;

    /**
     * @var ControllerBuilder
     */
    protected $ctrlBuilder;


    private $routes = [
        ['get',     '/%s',                                  '%sController@get'],
        ['get',     '/%s/index',                            '%sController@index'],
        ['get',     '/%s/{id}',                             '%sController@getById'],
        ['get',     '/%s/{id}/{relation}',                  '%sController@getRelation'],
        ['post',    '/%s',                                  '%sController@store'],
        ['post',    '/%s/{id}/{relation}',                  '%sController@createRelationWithObject'],
        ['post',    '/%s/{id}/{relation}/{idRelation}',     '%sController@createRelation'],
        ['put',     '/%s/{id}',                             '%sController@update'],
        ['delete',  '/%s/{id}',                             '%sController@destroy'],
        ['delete',  '/%s/{id}/{relation}',                  '%sController@emptyRelation'],
        ['delete',  '/%s/{id}/{relation}/{idRelation}',     '%sController@deleteRelation']
    ];

    /**
     * Generator constructor.
     * @param EloquentModelBuilder $ModelBuilder
     */
    public function __construct(EloquentModelBuilder $modelBuilder, ControllerBuilder $ctrlBuilder)
    {
        $this->modelBuilder = $modelBuilder;
        $this->ctrlBuilder = $ctrlBuilder;
    }
    

    public function getTableList(){
        return $this->modelBuilder->getTableList();
    }

    public function generateModelName($table) {
        return str_replace('_', '', ucwords(ucfirst($table), "_"));
    }

    /**
     * @param Config $config
     * @return ClassModel
     * @throws GeneratorException
     */
    public function generateModel(Config $config)
    {
        $model   = $this->modelBuilder->createModel($config);
        $content = $model->render();

        $outputPath = $this->resolveModelOutputPath($config);
        file_put_contents($outputPath, $content);

        return $model;
    }

    /**
     * @param Config $config
     * @return ClassModel
     * @throws GeneratorException
     */
    public function generateController(Config $config)
    {
        $ctrl   = $this->ctrlBuilder->createController($config);
        $content = $ctrl->render();

        $outputPath = $this->resolveControllerOutputPath($config);
        file_put_contents($outputPath, $content);

        if($config->get('add_route')) {
            $routesFile = file_get_contents(app_path('../routes/').'web.php');
            $routesLines = explode(PHP_EOL, $routesFile);

            foreach($this->routes as $route) {
                $find = false;
                $currentLine = $this->createLine($route, $config->get('table_name'));
                    
                foreach($routesLines as $line) {
                    if(strpos($line, $currentLine) !== false) {
                        $find = true;
                    }
                }
                if(!$find) {
                    $routesFile .= $currentLine .PHP_EOL;
                }
            }
            $routesFile .= PHP_EOL;
            
            file_put_contents(app_path('../routes/').'web.php', $routesFile);
        }
        return $ctrl;
    }

    /**
     * @param Config $config
     * @return ClassModel
     * @throws GeneratorException
     */
    public function generateBulkController(Config $config)
    {
        $ctrl   = $this->ctrlBuilder->createBulkController($config);
        $content = $ctrl->render();

        $outputPath = $this->resolveControllerOutputPath($config);
        file_put_contents($outputPath, $content);

        if($config->get('add_route')) {
            $routesFile = file_get_contents(app_path('../routes/').'web.php');
            $routesLines = explode(PHP_EOL, $routesFile);

            foreach($this->routes as $route) {
                $find = false;
                $currentLine = $this->createLine($route, $config->get('table_name'));
                    
                foreach($routesLines as $line) {
                    if(strpos($line, $currentLine) !== false) {
                        $find = true;
                    }
                }
                if(!$find) {
                    $routesFile .= $currentLine .PHP_EOL;
                }
            }
            $routesFile .= PHP_EOL;
            
            file_put_contents(app_path('../routes/').'web.php', $routesFile);
        }
        return $ctrl;
    }

     /**
     * @param Config $config
     * @return ClassModel
     * @throws GeneratorException
     */
    public function generateRoutes(Config $config)
    { 
        $routesFile = file_get_contents(app_path('../routes/').'web.php');
        $routesLines = explode(PHP_EOL, $routesFile);

        foreach($this->routes as $route) {
            $find = false;
            $currentLine = $this->createLine($route, $config->get('table_name'));
                
            foreach($routesLines as $line) {
                if(strpos($line, $currentLine) !== false) {
                    $find = true;
                }
            }
            if(!$find) {
                $routesFile .= $currentLine .PHP_EOL;
            }
        }
        $routesFile .= PHP_EOL;
        
        file_put_contents(app_path('../routes/').'web.php', $routesFile);
    }

    protected function createLine($route, $tableName) {
        return '$router->'. $route[0] .'(\''. sprintf(
            $route[1], 
            strtolower(str_replace('_', '-', $tableName)). 's'
        ). '\', \''. sprintf(
            $route[2], 
            $this->generateModelName($tableName)
        ).'\');';
    }

    /**
     * @param Config $config
     * @return string
     */
    protected function resolveModelOutputPath(Config $config)
    {
        $path = $config->get('lumen_model_output_path', app_path());
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        return $path . '/' . $config->get('class_name') . '.php';
    }

    /**
     * @param Config $config
     * @return string
     */
    protected function resolveControllerOutputPath(Config $config)
    {
        $path = $config->get('lumen_ctrl_output_path', app_path());
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        return $path . '/' . $config->get('class_name') . 'Controller.php';
    }
}
