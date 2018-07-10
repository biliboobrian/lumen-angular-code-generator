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

class EloquentModelBuilder
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
     * @return EloquentModel
     * @throws GeneratorException
     */
    public function createModel(Config $config)
    {
        $model = new EloquentModel(
            $config->get('class_name'),
            $config->get('base_class_name'),
            $config->get('table_name')
        );

        if (!$this->manager->tablesExist($model->getTableName())) {
            throw new GeneratorException(sprintf('Table %s does not exist', $model->getTableName()));
        }

        $method = new MethodModel('getValidationRules');
        $method->addArgument(new ArgumentModel('mode', null, "'create'"));
        $method->addArgument(new ArgumentModel('primaryKeyValue', null, 'null'));
        $method->setBody('return array();');
        $method->setDocBlock(new DocBlockModel('{@inheritdoc}'));

        $model->addMethod($method);

        $this->setNamespace($model, $config)
            ->setCustomProperties($model, $config)
            ->setFields($model)
            ->setRelations($model);

        return $model;
    }

    /**
     * @param EloquentModel $model
     * @param Config $config
     * @return $this
     */
    protected function setNamespace(EloquentModel $model, Config $config)
    {
        $namespace = $config->get('namespace');
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
    protected function setFields(EloquentModel $model)
    {
        $tableDetails       = $this->manager->listTableDetails($model->getTableName());
        $primaryColumnNames = $tableDetails->getPrimaryKey()->getColumns();

        $hasTimestamps = false;
        $isAutoincrement = true;
        $columnNames = [];
        $dates = [];
        foreach ($tableDetails->getColumns() as $column) {
            $type = $this->resolveType($column->getType()->getName());
            if (strcmp($type, '\Carbon\Carbon') == 0) {
                $dates[] = $column->getName();
            }
            $model->addProperty(new VirtualPropertyModel(
                $column->getName(),
                $this->resolveType($column->getType()->getName()),
                $column->getComment()
            ));

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
        }

        return $this;
    }

    /**
     * @param EloquentModel $model
     * @return $this
     */
    protected function setRelations(EloquentModel $model)
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
                $tableForeignColumns[0]
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
                            $secondForeignKey->getLocalColumns()[0]
                        );
                        $model->addRelation($relation);

                        break;
                    } else {
                        $tableName     = $foreignKey->getLocalTableName();
                        $foreignColumn = $localColumns[0];
                        $localColumn   = $foreignKey->getForeignColumns()[0];

                        if ($this->isColumnUnique($table, $foreignColumn)) {
                            $relation = new HasOne($tableName, $foreignColumn, $localColumn);
                        } else {
                            $relation = new HasMany($tableName, $foreignColumn, $localColumn);
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
            'date'                        => '\Carbon\Carbon',
            'character varying'           => 'string',
            'boolean'                     => 'boolean',
            'name'                        => 'string',
            'double precision'            => 'float',
            'integer'                     => 'int',
            'ARRAY'                       => 'array',
            'json'                        => 'array',
            'timestamp without time zone' => 'string',
            'text'                        => 'string',
            'bigint'                      => 'int',
            'string'                      => 'string',
            'decimal'                     => 'float',
            'datetime'                    => '\Carbon\Carbon',
            'array'                       => 'mixed',   // todo test
        ];

        return array_key_exists($type, $typesMap) ? $typesMap[$type] : 'mixed';
    }
}
