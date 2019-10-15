<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Database\Eloquent\Relations\HasOne as EloquentHasOne;
use Illuminate\Support\Str;
use biliboobrian\lumenAngularCodeGenerator\Model\ClassModel;
use biliboobrian\lumenAngularCodeGenerator\Model\ClassNameModel;
use biliboobrian\lumenAngularCodeGenerator\Model\DocBlockModel;
use biliboobrian\lumenAngularCodeGenerator\Model\MethodModel;
use biliboobrian\lumenAngularCodeGenerator\Model\UseClassModel;
use biliboobrian\lumenAngularCodeGenerator\Model\VirtualPropertyModel;
use biliboobrian\lumenAngularCodeGenerator\Exception\GeneratorException;
use biliboobrian\lumenAngularCodeGenerator\Helper\ClassHelper;
use biliboobrian\lumenAngularCodeGenerator\Helper\TitleHelper;

/**
 * Class ControllerModel
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
class ControllerModel extends ClassModel
{
    /**
     * @var string
     */
    protected $tableName;


    /**
     * @var string
     */
    protected $className;

    /**
     * ControllerModel constructor.
     * @param string $className
     * @param string $baseClassName
     * @param string|null $tableName
     */
    public function __construct($className, $baseClassName = null, $tableName = null)
    {
        $this->className = $className;

        $cn = new ClassNameModel($className . 'Controller');

        if ($baseClassName) {
            $cn->setExtends(ClassHelper::getShortClassName($baseClassName));
            $this->addUses(new UseClassModel(ltrim($baseClassName, '\\')));
        }

        $this->setName($cn);

        $this->tableName = $tableName ?: TitleHelper::getDefaultTableName($className . 'Controller');
    }

    public function addSwaggerBlock()
    {
        $this->swaggerController[] = '   /**';
        $this->swaggerController[] = '     *';
        $this->swaggerController[] = '     * @OA\RequestBody(';
        $this->swaggerController[] = '     *     request="' . $this->className . '",';
        $this->swaggerController[] = '     *     description="' . $this->className . ' object request",';
        $this->swaggerController[] = '     *     required=true,';
        $this->swaggerController[] = '     *     @OA\JsonContent(ref="#/components/schemas/' . $this->className . '")';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = ' ';
        $this->swaggerController[] = '     * @OA\Get(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Get a collection of ' . $this->className . 's",';
        $this->swaggerController[] = '     *     operationId="get' . $this->className . 's",';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="filters",';
        $this->swaggerController[] = '     *         in="query",';
        $this->swaggerController[] = '     *         description="Filters applied to the find",';
        $this->swaggerController[] = '     *         required=false,';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/Filters",';
        $this->swaggerController[] = '     *              type="Filters", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = ' ';
        $this->swaggerController[] = '     * @OA\Get(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's/index",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Get list of all ' . $this->className . 's",';
        $this->swaggerController[] = '     *     operationId="index' . $this->className . 's",';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = '     * ';
        $this->swaggerController[] = '     * @OA\Get(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's/{id}",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Get ' . $this->className . ' by ID",';
        $this->swaggerController[] = '     *     operationId="get' . $this->className . 'sByID",';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="id",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="ID of the ' . $this->className . ' to find",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = '     * ';
        $this->swaggerController[] = '     * @OA\Get(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's/{id}/{relation}",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Get Relation of a given ' . $this->className . '",';
        $this->swaggerController[] = '     *     operationId="get' . $this->className . 'sRelation",';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="id",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="ID of the ' . $this->className . ' to find",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="relation",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="Wanted Relation name list/object",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = '     * ';
        $this->swaggerController[] = '     * @OA\Post(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Insert new ' . $this->className . '",';
        $this->swaggerController[] = '     *     operationId="post' . $this->className . '",';
        $this->swaggerController[] = '     *     requestBody={"$ref": "#/components/requestBodies/' . $this->className . '"},';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = '     * ';
        $this->swaggerController[] = '     * @OA\Post(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's/{id}/{relation}",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Insert new relation for the given ' . $this->className . '",';
        $this->swaggerController[] = '     *     operationId="post' . $this->className . 'Relation",';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="id",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="ID of the ' . $this->className . ' to find",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="relation",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="Relation where to attach new object, relation must be in hyphen-case",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     requestBody={"$ref": "#/components/requestBodies/GenericObject"},';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = '     * ';
        $this->swaggerController[] = '     * @OA\Post(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's/{id}/{relation}/{idRelation}",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Link ' . $this->className . ' with a relation",';
        $this->swaggerController[] = '     *     operationId="post' . $this->className . 'RelationID",';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="id",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="ID of the ' . $this->className . ' to find",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="relation",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="Relation name, relation must be in hyphen-case",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="idRelation",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="Relation ID",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = '     * ';
        $this->swaggerController[] = '     * @OA\Put(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's/{id}",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Update ' . $this->className . '",';
        $this->swaggerController[] = '     *     operationId="put' . $this->className . '",';
        $this->swaggerController[] = '     *     requestBody={"$ref": "#/components/requestBodies/' . $this->className . '"},';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="id",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="ID of ' . $this->className . ' to update",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = '     * ';
        $this->swaggerController[] = '     * @OA\Delete(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's/{id}",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Delete ' . $this->className . '",';
        $this->swaggerController[] = '     *     operationId="delete' . $this->className . '",';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="id",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="ID of ' . $this->className . ' to update",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = '     * ';
        $this->swaggerController[] = '     * @OA\Delete(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's/{id}/{relation}",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Delete all relation links for the given ' . $this->className . '",';
        $this->swaggerController[] = '     *     operationId="delete' . $this->className . 'Relation",';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="id",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="ID of the ' . $this->className . ' to find",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="relation",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="Relation to delete, relation must be in hyphen-case",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = '     * ';
        $this->swaggerController[] = '     * @OA\Delete(';
        $this->swaggerController[] = '     *     path="/' . strtolower($this->className) . 's/{id}/{relation}/{idRelation}",';
        $this->swaggerController[] = '     *     tags={"' . strtolower($this->className) . '"},';
        $this->swaggerController[] = '     *     summary="Delete relation by link ID for the given ' . $this->className . '",';
        $this->swaggerController[] = '     *     operationId="delete' . $this->className . 'RelationID",';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="id",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="ID of the ' . $this->className . ' to find",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="relation",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="Relation name, relation must be in hyphen-case",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Parameter(';
        $this->swaggerController[] = '     *         name="idRelation",';
        $this->swaggerController[] = '     *         in="path",';
        $this->swaggerController[] = '     *         description="Relation ID",';
        $this->swaggerController[] = '     *         required=true,';
        $this->swaggerController[] = '     *         @OA\Schema(type="string")';
        $this->swaggerController[] = '     *     ),';
        $this->swaggerController[] = '     *     @OA\Response(';
        $this->swaggerController[] = '     *         response=200,';
        $this->swaggerController[] = '     *         description="Valid result",';
        $this->swaggerController[] = '     *         @OA\JsonContent(';
        $this->swaggerController[] = '     *              ref="#/components/schemas/CrudResponse",';
        $this->swaggerController[] = '     *              type="CrudResponse", ';
        $this->swaggerController[] = '     *         ),';
        $this->swaggerController[] = '     *     )';
        $this->swaggerController[] = '     * )';
        $this->swaggerController[] = '     */ ';
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

        $arguments = [Str::studly($relation->getTableName())];
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
