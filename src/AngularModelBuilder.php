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
use biliboobrian\lumenAngularCodeGenerator\Model\AngularModel;
use biliboobrian\lumenAngularCodeGenerator\Model\VirtualPropertyModel;
use biliboobrian\lumenAngularCodeGenerator\Exception\GeneratorException;

class AngularModelBuilder
{
    /**
     * @var AbstractSchemaManager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $exportProperties = [];
      

    /**
     * Builder constructor.
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        try {
            $this->manager = $databaseManager->connection()->getDoctrineSchemaManager();
        } catch (\Exception $e) {   // connection error
            echo $e->getMessage();
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
     * @return AngularModel
     * @throws GeneratorException
     */
    public function createModel(Config $config)
    {
        $model = new AngularModel(
            $config->get('class_name'),
            $config->get('base_class_angular_model_name'),
            $config->get('base_class_angular_model_from'),
            $config->get('table_name')
        );

        if (!$this->manager->tablesExist($model->getTableName())) {
            throw new GeneratorException(sprintf('Table %s does not exist', $model->getTableName()));
        }

        $this->setNamespace($model, $config)
            ->setCustomProperties($model, $config)
            ->setFields($model)
            ->setConstructor($model, $config);

            //  ->setRelations($model, $config);
        
        
        
        return $model;
    }

    /**
     * @param AngularModel $model
     * @param Config $config
     * @return $this
     */
    protected function setConstructor(AngularModel $model, Config $config)
    {
        $tableDetails       = $this->manager->listTableDetails($model->getTableName());
        $primaryColumnNames = $tableDetails->getPrimaryKey()->getColumns();

        if (count($primaryColumnNames) > 1) {
            $primaryColumnNames = [$primaryColumnNames[0]];
        }
    
        $constructBody = 'super(obj, crudService);' .PHP_EOL;
        $constructBody .= '        this.table = \''. $config->get('table_name') .'s\';' .PHP_EOL;
        $constructBody .= '        this.primaryKey = \''. $primaryColumnNames[0] .'\';' .PHP_EOL;
        $constructBody .= '        this.exportProperties = [\''. implode('\', \'', $this->exportProperties) .'\'];' .PHP_EOL;


        $constructMethod = new MethodModel('constructor', '', 'angular');
        $constructMethod->addArgument(new ArgumentModel('obj?', 'Object', null, 'angular'));
        $constructMethod->addArgument(new ArgumentModel('crudService?', 'CrudService', null, 'angular'));
        $constructMethod->setBody($constructBody);
        $model->addMethod($constructMethod, false);

        return $this;
    }

    /**
     * @param AngularModel $model
     * @param Config $config
     * @return $this
     */
    protected function setNamespace(AngularModel $model, Config $config)
    {
        $namespace = $config->get('lumen_model_namespace');
        $model->setNamespace(new NamespaceModel($namespace));

        return $this;
    }

    /**
     * @param AngularModel $model
     * @param Config $config
     * @return $this
     */
    protected function setCustomProperties(AngularModel $model, Config $config)
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
     * @param AngularModel $model
     * @return $this
     */
    protected function setFields(AngularModel $model)
    {
        $tableDetails       = $this->manager->listTableDetails($model->getTableName());
        $primaryColumnNames = $tableDetails->getPrimaryKey()->getColumns();

        $hasTimestamps = false;
        $isAutoincrement = true;
        $columnNames = [];
        $dates = [];
        $this->exportProperties = [];
        
        foreach ($tableDetails->getColumns() as $column) {

            $model->addProperty(new PropertyModel(
                '_'. $column->getName(),
                'private',
                $column->getComment(),
                'angular',
                $this->resolveType($column->getType()->getName())
                
            ));

            $this->exportProperties[] = $column->getName();

            if (in_array($column->getName(), $primaryColumnNames)) {
                $isAutoincrement = $column->getAutoincrement();
            }
            if (in_array($column->getName(), ['created_at', 'updated_at'])) {
                $hasTimestamps = true;
                continue;   // remove timestamps
            }
            //if (!in_array($column->getName(), $primaryColumnNames)) {
            $columnNames[] = $column->getName();
            //}

            $getMethod = new MethodModel('get '. $column->getName(), 'public', 'angular');
            $getMethod->setBody('return this.'. $column->getName() .';');

            $model->addMethod($getMethod);

            $setMethod = new MethodModel('set '. $column->getName(), 'public', 'angular');
            $setMethod->addArgument(new ArgumentModel('val', $this->resolveType($column->getType()->getName()), null, 'angular'));
            $setMethod->setBody('this.sync = false;'. PHP_EOL . '        this._'. $column->getName() .' = val;');

            $model->addMethod($setMethod);
        }

        /* if (!empty($dates)) {
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
                $primaryColumnNames = [$primaryColumnNames[0]];
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
        } */

        return $this;
    }

    /**
     * @param AngularModel $model
     * @return $this
     */
    protected function setRelations(AngularModel $model, $config)
    {
        $foreignKeys = $this->manager->listTableForeignKeys($model->getTableName());
        foreach ($foreignKeys as $tableForeignKey) {
            $tableForeignColumns = $tableForeignKey->getForeignColumns();
            if (count($tableForeignColumns) !== 1) {
                continue;
            }

            $relation = new BelongsTo(
                $tableForeignKey->getForeignTableName(),
                $tableForeignKey->getLocalColumns()[0],
                $tableForeignColumns[0],
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
                if ($foreignKey->getForeignTableName() === $model->getTableName()) {
                    $localColumns = $foreignKey->getLocalColumns();
                    if (count($localColumns) !== 1) {
                        continue;
                    }

                    if (count($foreignKeys) === 2 && count($table->getColumns()) === 2) {
                        $keys               = array_keys($foreignKeys);
                        $key                = array_search($name, $keys) === 0 ? 1 : 0;
                        $secondForeignKey   = $foreignKeys[$keys[$key]];
                        $secondForeignTable = $secondForeignKey->getForeignTableName();

                        $relation = new BelongsToMany(
                            $secondForeignTable,
                            $table->getName(),
                            $localColumns[0],
                            $secondForeignKey->getLocalColumns()[0],
                            $config
                        );
                        $model->addRelation($relation);

                        break;
                    } else {
                        $tableName     = $foreignKey->getLocalTableName();
                        $foreignColumn = $localColumns[0];
                        $localColumn   = $foreignKey->getForeignColumns()[0];

                        if ($this->isColumnUnique($table, $foreignColumn)) {
                            $relation = new HasOne($tableName, $foreignColumn, $localColumn, $config);
                        } else {
                            $relation = new HasMany($tableName, $foreignColumn, $localColumn, $config);
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
            'date'                        => 'Date',
            'character varying'           => 'string',
            'boolean'                     => 'boolean',
            'name'                        => 'string',
            'double precision'            => 'number',
            'float'                       => 'number',
            'integer'                     => 'number',
            'ARRAY'                       => 'Array<any>',
            'json'                        => 'Object',
            'timestamp without time zone' => 'string',
            'text'                        => 'string',
            'bigint'                      => 'number',
            'string'                      => 'string',
            'decimal'                     => 'number',
            'datetime'                    => 'Date',
            'array'                       => 'Array<any>',   // todo test
        ];

        return array_key_exists($type, $typesMap) ? $typesMap[$type] : '__'. $type;
    }
}
