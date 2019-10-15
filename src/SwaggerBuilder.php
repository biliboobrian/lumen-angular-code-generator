<?php

namespace biliboobrian\lumenAngularCodeGenerator;

use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\DatabaseManager;
use biliboobrian\lumenAngularCodeGenerator\Model\MethodModel;
use biliboobrian\lumenAngularCodeGenerator\Model\ArgumentModel;
use biliboobrian\lumenAngularCodeGenerator\Model\DocBlockModel;
use biliboobrian\lumenAngularCodeGenerator\Model\PropertyModel;
use biliboobrian\lumenAngularCodeGenerator\Model\NamespaceModel;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use biliboobrian\lumenAngularCodeGenerator\Model\HasOne;
use biliboobrian\lumenAngularCodeGenerator\Model\HasMany;
use biliboobrian\lumenAngularCodeGenerator\Model\BelongsTo;
use biliboobrian\lumenAngularCodeGenerator\Model\BelongsToMany;
use biliboobrian\lumenAngularCodeGenerator\Model\EloquentModel;
use biliboobrian\lumenAngularCodeGenerator\Model\VirtualPropertyModel;
use biliboobrian\lumenAngularCodeGenerator\Exception\GeneratorException;

class SwaggerBuilder
{
    /**
     * @var AbstractSchemaManager
     */
    protected $manager;

    /**
     * Builder constructor.
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        try {
            $this->manager = $databaseManager->connection()->getDoctrineSchemaManager();
        } catch (\Exception $e) {   // connection error
            echo env('DB_USERNAME');
            return;
        }
        $dp = $this->manager->getDatabasePlatform();
        $dp->registerDoctrineTypeMapping('enum', 'array');
        $dp->registerDoctrineTypeMapping('set', 'array');
    }

    public function getTableList()
    {
        return $this->manager->listTables();
    }

    /**
     * @param Config $config
     * @return EloquentModel
     * @throws GeneratorException
     */
    public function createInfoFile(Config $config)
    {
        $swagger = array();
        $swagger[] = "<?php";
        $swagger[] = "";
        $swagger[] = "/**";

        $swagger = $this->createInfo(
            $swagger,
            "API swagger description.",
            "1.0.0",
            "API"
        );

        $swagger = $this->createSchema(
            $swagger, 
            'Filters',
            'Filters object used by all get collection methods',
            ['code'],
            [
                [
                    'andLink',
                    "Make all the 'members' Filter apply in query with AND or OR",
                    'true',
                    'boolean',
                    null
                ], 
                [
                    'members',
                    "Array of filter to apply in query as following: (member[0] andLink member[1])",
                    null,
                    'array',
                    '#/components/schemas/Filter'
                ], 
                [
                    'childrens',
                    "Filters: Children of this Filters for complex query , will apply them as the following: Filters.andLink ( child.member[0] child.andLink child.member[1])",
                    null,
                    'array',
                    '#/components/schemas/Filters'
                ], 
                [
                    'relationName',
                    "Joining name you want to use for the query",
                    null,
                    'string',
                    null
                ], 
            ]
        );

        $swagger = $this->createSchema(
            $swagger, 
            'Filter',
            'Filter object describing the the fild and value you want to filter',
            ['column', "value", "operation"],
            [
                [
                    'column',
                    "Column used for the filter",
                    null,
                    'string',
                    null
                ], 
                [
                    'value',
                    "Value to match for the selected column",
                    null,
                    'string',
                    null
                ], 
                [
                    'operation',
                    "Operation between column and value as following: column operation column > ex: username = 'John'",
                    null,
                    'string',
                    null
                ], 
                [
                    'field',
                    "Specify if the value is a column, ex: username = firstname",
                    'false',
                    'boolean',
                    null
                ], 
                [
                    'type',
                    "ype of the value, ex: string, boolean, date",
                    'string',
                    'string',
                    null
                ], 
            ]
        );

        $swagger = $this->createSchema(
            $swagger, 
            'CrudResponse',
            'Response object that wrap all data',
            ['code', "data"],
            [
                [
                    'code',
                    "HTTP code for this response",
                    null,
                    'string',
                    null
                ], 
                [
                    'message',
                    "In case of status 'error', contain the error message from server",
                    null,
                    'string',
                    null
                ], 
                [
                    'status',
                    "Status of the call, could be 'ok' or 'error'",
                    null,
                    'string',
                    null
                ], 
                [
                    'data',
                    "Data send for the current call, could be an object or an array depending of call",
                    null,
                    'object',
                    null
                ]
            ]
        );
        
        

        $swagger = $this->createBody(
            $swagger,
            "GenericObject",
            "Generic object request",
            "true",
            "object"
        );


        $swagger[] = " */";
        
        return implode(PHP_EOL, $swagger);
    }

    public function createInfo($swagger, $description, $version, $title) {

        $swagger[] = " * @OA\Info(";
        $swagger[] = " *     description=\"" . $description . "\",";
        $swagger[] = " *     version=\"" . $version . "\",";
        $swagger[] = " *     title=\"" . $title . "\"";
        $swagger[] = " * )";     
        $swagger[] = " *"; 

        return $swagger;
    }

    public function createSchema($swagger, $title, $description, $required, $properties) {

        $swagger[] = " * @OA\Schema(";
        $swagger[] = " *     schema=\"" . $title . "\",";
        $swagger[] = " *     description=\"" . $description . "\",";
        $swagger[] = " *     title=\"" . $title . "\",";
        $swagger[] = " *     required={\"" . implode("\",\"", $required) . "\"},";
        $swagger[] = " * "; 
        $swagger[] = " *     @OA\Xml(";
        $swagger[] = " *         name=\"" . $title . "\"";
        $swagger[] = " *     ),";
        
        $swagger[] = " * ";  

        foreach($properties as $property) {
            $swagger = $this->createProperty($swagger, $property[0], $property[1], $property[2], $property[3], $property[4]);
        }
        
        $swagger[sizeof($swagger) - 2] = " *     )";
        $swagger[] = " * )";     
        $swagger[] = " * "; 
            
        return $swagger;
    }

    public function createBody($swagger, $request, $description, $required, $type) {

        $swagger[] = " * @OA\RequestBody(";
        $swagger[] = " *     request=\"" . $request . "\",";
        $swagger[] = " *     description=\"" . $description . "\",";
        $swagger[] = " *     required=" . $required . ",";
        $swagger[] = " * "; 
        $swagger[] = " *     @OA\JsonContent(";
        $swagger[] = " *         type=\"" . $type . "\"";
        $swagger[] = " *     )"; 
        $swagger[] = " * )";     
        $swagger[] = " * "; 
            
        return $swagger;
    }

    public function createProperty($swagger, $property, $description, $default, $type, $item) {

        $swagger[] = " *     @OA\Property(";
        $swagger[] = " *         description=\"" . $description . "\",";
        
        if($default) {
            $swagger[] = " *         default=\"" . $default . "\",";
        }
        
        if($item) {
            $swagger[] = " *         @OA\Items(ref=\"" . $item . "\"),";
        }

        $swagger[] = " *         property=\"" . $property . "\",";
        $swagger[] = " *         type=\"" . $type . "\"";
        
        
            
        $swagger[] = " *     ),";  
        $swagger[] = " * "; 

            
        return $swagger;
    }

    /**
     * @param EloquentModel $model
     * @param Config $config
     * @return $this
     */
    protected function setNamespace(EloquentModel $model, Config $config)
    {
        $namespace = $config->get('lumen_model_namespace');
        $model->setNamespace(new NamespaceModel($namespace));

        return $this;
    }

    /**
     * @param EloquentModel $model
     * @param Config $config
     * @return $this
     */
    protected function setCustomProperties(EloquentModel $model, Config $config)
    {
        if ($config->get('no_timestamps') == true) {
            $pNoTimestamps = new PropertyModel('timestamps', 'public', false);
            $pNoTimestamps->setDocBlock(
                new DocBlockModel('Indicates if the model should be timestamped.', '', '@var bool')
            );
            $model->addProperty($pNoTimestamps);
        }

        if ($config->has('date_format')) {
            $pDateFormat = new PropertyModel('dateFormat', 'protected', $config->get('date_format'));
            $pDateFormat->setDocBlock(
                new DocBlockModel('The storage format of the model\'s date columns.', '', '@var string')
            );
            $model->addProperty($pDateFormat);
        }

        if ($config->has('connection')) {
            $pConnection = new PropertyModel('connection', 'protected', $config->get('connection'));
            $pConnection->setDocBlock(
                new DocBlockModel('The connection name for the model.', '', '@var string')
            );
            $model->addProperty($pConnection);
        }

        return $this;
    }

    /**
     * @param EloquentModel $model
     * @return $this
     */
    protected function setCasts(EloquentModel $model)
    {
        $tableDetails = $this->manager->listTableDetails($model->getTableName());
        $casts = new \stdClass;

        foreach ($tableDetails->getColumns() as $column) {
            $colName = strtolower($column->getName());
            $type = $this->resolveType($column->getType()->getName());

            switch ($type) {
                case 'int':
                    $casts->$colName = 'integer';
                    break;
                case 'float':
                    $casts->$colName = 'float';
                    break;
            }
        }


        $fillableProperty = new PropertyModel('casts');
        $fillableProperty->setAccess('protected')
            ->setValue($casts)
            ->setDocBlock(new DocBlockModel('@var array'));
        $model->addProperty($fillableProperty);

        return $this;
    }

    /**
     * @param EloquentModel $model
     * @return $this
     */
    protected function setFields(EloquentModel $model)
    {
        $tableDetails = $this->manager->listTableDetails($model->getTableName());
        $primaryColumnNames = array_map('strtolower', $tableDetails->getPrimaryKey()->getColumns());

        $hasTimestamps = false;
        $isAutoincrement = true;
        $columnNames = [];
        $dates = [];
        foreach ($tableDetails->getColumns() as $column) {
            $colName = strtolower($column->getName());
            $type = $this->resolveType($column->getType()->getName());
            if (strcmp($type, '\Carbon\Carbon') == 0) {
                $dates[] = $colName;

                $n = str_replace('_', '', ucwords($colName, '_'));
                $method = new MethodModel('set' . $n . 'Atttribute');
                $method->addArgument(new ArgumentModel('value'));
                $method->setBody('return intval(strtotime($value) . \'000\');');
                $method->setDocBlock(new DocBlockModel('{@inheritdoc}'));

                $model->addMethod($method);

            }
            $model->addProperty(new VirtualPropertyModel(
                $colName,
                $this->resolveType($column->getType()->getName()),
                $column->getComment()
            ));

            if (in_array($colName, $primaryColumnNames)) {
                $isAutoincrement = $column->getAutoincrement();
            }
            if (in_array($colName, ['created_at', 'updated_at'])) {
                $hasTimestamps = true;
                continue;   // remove timestamps
            }
            //if (!in_array($colName, $primaryColumnNames)) {
            $columnNames[] = $colName;
            //}
        }

        $fillableProperty = new PropertyModel('fillable');
        $fillableProperty->setAccess('protected')
            ->setValue($columnNames)
            ->setDocBlock(new DocBlockModel('@var array'));
        $model->addProperty($fillableProperty);

        if (!empty($dates)) {
            $datesProperty = new PropertyModel('dates');
            $datesProperty->setAccess('protected')
                ->setValue($dates)
                ->setDocBlock(new DocBlockModel('@var array'));
            $model->addProperty($datesProperty);
        }

        if (!empty($primaryColumnNames)) {
            $comments = [];
            if (count($primaryColumnNames) > 1) {
                $comments[] = 'Eloquent doesn\'t support composite primary keys : ' . implode(', ', $primaryColumnNames);
                $comments[] = '';
                $primaryColumnNames = [strtolower($primaryColumnNames[0])];
            }
            if ($primaryColumnNames[0] != 'id') {
                $comments[] = '@var string';
                $primatyProperty = new PropertyModel('primaryKey');
                $primatyProperty->setAccess('protected')
                    ->setValue($primaryColumnNames[0])
                    ->setDocBlock((new DocBlockModel())->addContent($comments));
                $model->addProperty($primatyProperty);
            }

            $comments = [];
            if (!$isAutoincrement) {
                $comments[] = ['Indicates if the IDs are auto-incrementing.', '', '@var bool'];
                $autoincrementProperty = new PropertyModel('incrementing');
                $autoincrementProperty->setAccess('public')
                    ->setValue(false)
                    ->setDocBlock((new DocBlockModel())->addContent($comments));
                $model->addProperty($autoincrementProperty);
            }

            $comments = [];
            if (!$hasTimestamps) {
                $comments[] = ['Indicates if the model should be timestamped.', '', '@var bool'];
                $timestampsProperty = new PropertyModel('timestamps');
                $timestampsProperty->setAccess('public')
                    ->setValue(false)
                    ->setDocBlock((new DocBlockModel())->addContent($comments));
                $model->addProperty($timestampsProperty);
            }
        }

        return $this;
    }

    /**
     * @param EloquentModel $model
     * @return $this
     */
    protected function setRelations(EloquentModel $model, $config)
    {
        $foreignKeys = $this->manager->listTableForeignKeys($model->getTableName());

        foreach ($foreignKeys as $tableForeignKey) {
            $tableForeignColumns = $tableForeignKey->getForeignColumns();
            if (count($tableForeignColumns) !== 1) {
                continue;
            }

            $relation = new BelongsTo(
                strtolower($tableForeignKey->getForeignTableName()),
                strtolower($tableForeignKey->getLocalColumns()[0]),
                strtolower( $tableForeignColumns[0]),
                $config
            );
            $model->addRelation($relation);
        }

        $tables = $this->manager->listTables();
        foreach ($tables as $table) {
            if ($table->getName() === $model->getTableName()) {
                continue;
            }
            
            $foreignKeys = $table->getForeignKeys();
            foreach ($foreignKeys as $name => $foreignKey) {
                if (strtolower($foreignKey->getForeignTableName()) === strtolower($model->getTableName())) {
                    $localColumns = $foreignKey->getLocalColumns();
                    if (count($localColumns) !== 1) {
                        continue;
                    }

                    if (count($foreignKeys) === 2 && count($table->getColumns()) === 2) {
                        $keys = array_keys($foreignKeys);
                        $key = array_search($name, $keys) === 0 ? 1 : 0;
                        $secondForeignKey = $foreignKeys[$keys[$key]];
                        $secondForeignTable = $secondForeignKey->getForeignTableName();

                        $relation = new BelongsToMany(
                            strtolower($secondForeignTable),
                            strtolower($table->getName()),
                            strtolower($localColumns[0]),
                            strtolower($secondForeignKey->getLocalColumns()[0]),
                            $config
                        );
                        $model->addRelation($relation);

                        break;
                    } else {
                        $tableName = $foreignKey->getLocalTableName();
                        $foreignColumn = $localColumns[0];
                        $localColumn = $foreignKey->getForeignColumns()[0];
                        
                        if ($this->isColumnUnique($table, $foreignColumn)) {
                            $relation = new HasOne(
                                strtolower($tableName), 
                                strtolower($foreignColumn), 
                                strtolower($localColumn), 
                                $config);
                        } else {
                            $relation = new HasMany(
                                strtolower($tableName), 
                                strtolower($foreignColumn), 
                                strtolower($localColumn), 
                                $config);
                        }

                        $model->addRelation($relation);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param Table $table
     * @param string $column
     * @return bool
     */
    protected function isColumnUnique(Table $table, $column)
    {
        foreach ($table->getIndexes() as $index) {
            $indexColumns = $index->getColumns();
            if (count($indexColumns) !== 1) {
                continue;
            }
            $indexColumn = $indexColumns[0];
            if ($indexColumn === $column && $index->isUnique()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function resolveType($type)
    {
        static $typesMap = [
            'date' => '\Carbon\Carbon',
            'character varying' => 'string',
            'boolean' => 'boolean',
            'name' => 'string',
            'double precision' => 'float',
            'float' => 'float',
            'integer' => 'int',
            'ARRAY' => 'array',
            'json' => 'array',
            'timestamp without time zone' => 'string',
            'text' => 'string',
            'bigint' => 'int',
            'string' => 'string',
            'decimal' => 'float',
            'datetime' => '\Carbon\Carbon',
            'array' => 'mixed',   // todo test
        ];

        return array_key_exists($type, $typesMap) ? $typesMap[$type] : 'mixed';
    }
}
