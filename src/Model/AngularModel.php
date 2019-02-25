<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;

use Illuminate\Support\Str;
use biliboobrian\lumenAngularCodeGenerator\Helper\ClassHelper;
use biliboobrian\lumenAngularCodeGenerator\Helper\TitleHelper;
use biliboobrian\lumenAngularCodeGenerator\Model\ClassModel;
use biliboobrian\lumenAngularCodeGenerator\Model\MethodModel;
use biliboobrian\lumenAngularCodeGenerator\Model\DocBlockModel;
use biliboobrian\lumenAngularCodeGenerator\Model\PropertyModel;
use biliboobrian\lumenAngularCodeGenerator\Model\ClassNameModel;
use Illuminate\Database\Eloquent\Relations\HasOne as EloquentHasOne;
use biliboobrian\lumenAngularCodeGenerator\Model\ImportClassModel;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use biliboobrian\lumenAngularCodeGenerator\Exception\GeneratorException;
use biliboobrian\lumenAngularCodeGenerator\Model\VirtualPropertyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;

/**
 * Class AngularModel
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
class AngularModel extends ClassModel
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var ImportClassModel[]
     */
    protected $imports = [];


    /**
     * @return ImportClassModel[]
     */
    public function getImports()
    {
        return $this->uses;
    }

    /**
     * @param ImportClassModel $import
     *
     * @return $this
     */
    public function addImport(ImportClassModel $import)
    {
        $this->imports[] = $import;

        return $this;
    }

    /**
     * AngularModel constructor.
     * @param string $className
     * @param string $baseClassName
     * @param string|null $tableName
     */
    public function __construct($className, $baseClassName, $from , $tableName = null)
    {
        $cn = new ClassNameModel($className, $baseClassName, 'angular');
        $this->setName($cn);

        $this->addImport(new ImportClassModel($baseClassName, $from));
        $this->addImport(new ImportClassModel('CrudService', '../../services/crud.service'));
        
        $this->tableName = $tableName ?: TitleHelper::getDefaultTableName($className);

        $property = new PropertyModel('TABLE', 'public',  str_replace('_', '-', strtolower($this->tableName)) .'s', 'angular');
        $property->setStatic(true);

        $this->addProperty($property);
        
    }

    /**
     * {@inheritDoc}
     */
    public function toLines()
    {
        $lines = [];
        if (count($this->imports) > 0) {
            $lines[] = $this->renderArrayLn($this->imports);
        }

        $lines[] = $this->name->render();

        $this->processProperties($lines);
        $this->processMethods($lines);
        $lines[] = $this->ln('}');

        return $lines;
    }

    /**
     * @param Relation $relation
     * @return $this
     * @throws GeneratorException
     */
    public function addRelation(Relation $relation)
    {
        $relationClass = Str::studly($relation->getTableName());
        if ($relation instanceof HasOne) {
            $name     = Str::camel($relation->getTableName());
            $docBlock = sprintf('@return \%s', EloquentHasOne::class);

            $virtualPropertyType = $relationClass;
        } elseif ($relation instanceof HasMany) {
            $name     = Str::plural(Str::camel($relation->getTableName()));
            $docBlock = sprintf('@return \%s', EloquentHasMany::class);

            $virtualPropertyType = sprintf('%s[]', $relationClass);
        } elseif ($relation instanceof BelongsTo) {
            $name     = Str::camel($relation->getTableName());
            $docBlock = sprintf('@return \%s', EloquentBelongsTo::class);

            $virtualPropertyType = $relationClass;
        } elseif ($relation instanceof BelongsToMany) {
            $name     = Str::plural(Str::camel($relation->getTableName()));
            $docBlock = sprintf('@return \%s', EloquentBelongsToMany::class);

            $virtualPropertyType = sprintf('%s[]', $relationClass);
        } else {
            throw new GeneratorException('Relation not supported');
        }

        $method = new MethodModel($name);
        $method->setBody($this->createMethodBody($relation));
        $method->setDocBlock(new DocBlockModel($docBlock));

        $this->addMethod($method);
        $this->addProperty((new VirtualPropertyModel($name, $virtualPropertyType))->setWritable(false));

        return $this;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param Relation $relation
     * @return string
     * @throws GeneratorException
     */
    protected function createMethodBody(Relation $relation)
    {
        $reflectionObject = new \ReflectionObject($relation);
        $name             = Str::camel($reflectionObject->getShortName());

        $arguments = [$relation->getConfig()->get('lumen_model_namespace') .'\\'. Str::studly($relation->getTableName())];
        if ($relation instanceof BelongsToMany) {
            $defaultJoinTableName = TitleHelper::getDefaultJoinTableName($this->tableName, $relation->getTableName());
            $joinTableName        = $relation->getJoinTable() === $defaultJoinTableName
                ? null
                : $relation->getJoinTable();
            $arguments[]          = $joinTableName;

            $arguments[] = $this->resolveArgument(
                $relation->getForeignColumnName(),
                TitleHelper::getDefaultForeignColumnName($this->getTableName())
            );
            $arguments[] = $this->resolveArgument(
                $relation->getLocalColumnName(),
                TitleHelper::getDefaultForeignColumnName($relation->getTableName())
            );
        } elseif ($relation instanceof HasMany) {
            $arguments[] = $this->resolveArgument(
                $relation->getForeignColumnName(),
                TitleHelper::getDefaultForeignColumnName($this->getTableName())
            );
            $arguments[] = $this->resolveArgument(
                $relation->getLocalColumnName(),
                TitleHelper::$defaultPrimaryKey
            );
        } else {
            $arguments[] = $this->resolveArgument(
                $relation->getForeignColumnName(),
                TitleHelper::getDefaultForeignColumnName($relation->getTableName())
            );
            $arguments[] = $this->resolveArgument(
                $relation->getLocalColumnName(),
                TitleHelper::$defaultPrimaryKey
            );
        }

        return sprintf('return $this->%s(%s);', $name, $this->prepareArguments($arguments));
    }

    /**
     * @param array $array
     * @return array
     */
    protected function prepareArguments(array $array)
    {
        $array     = array_reverse($array);
        $milestone = false;
        foreach ($array as $key => &$item) {
            if (!$milestone) {
                if (!is_string($item)) {
                    unset($array[$key]);
                } else {
                    $milestone = true;
                }
            } else {
                if ($item === null) {
                    $item = 'null';

                    continue;
                }
            }
            $item = sprintf("'%s'", $item);
        }

        return implode(', ', array_reverse($array));
    }

    /**
     * @param string $actual
     * @param string $default
     * @return string|null
     */
    protected function resolveArgument($actual, $default)
    {
        return $actual === $default ? null : $actual;
    }
}
