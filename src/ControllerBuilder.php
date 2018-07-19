<?php

namespace biliboobrian\lumenAngularCodeGenerator;

use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\DatabaseManager;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use biliboobrian\lumenAngularCodeGenerator\Model\HasOne;
use biliboobrian\lumenAngularCodeGenerator\Model\HasMany;
use biliboobrian\lumenAngularCodeGenerator\Model\BelongsTo;
use biliboobrian\lumenAngularCodeGenerator\Model\MethodModel;
use biliboobrian\lumenAngularCodeGenerator\Model\ArgumentModel;
use biliboobrian\lumenAngularCodeGenerator\Model\BelongsToMany;
use biliboobrian\lumenAngularCodeGenerator\Model\DocBlockModel;
use biliboobrian\lumenAngularCodeGenerator\Model\PropertyModel;
use biliboobrian\lumenAngularCodeGenerator\Model\NamespaceModel;
use biliboobrian\lumenAngularCodeGenerator\Model\ControllerModel;
use biliboobrian\lumenAngularCodeGenerator\Model\VirtualPropertyModel;
use biliboobrian\lumenAngularCodeGenerator\Exception\GeneratorException;

class ControllerBuilder
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
     * @return ControllerModel
     * @throws GeneratorException
     */
    public function createController(Config $config)
    {
        $ctrl = new ControllerModel(
            $config->get('class_name'),
            $config->get('base_class_lumen_ctrl_name'),
            $config->get('table_name')
        );

        if (!$this->manager->tablesExist($ctrl->getTableName())) {
            throw new GeneratorException(sprintf('Table %s does not exist', $ctrl->getTableName()));
        }

        $this->setNamespace($ctrl, $config)
            ->setCustomProperties($ctrl, $config)
            ->setFields($ctrl)
            ->setRelations($ctrl);

        return $ctrl;
    }

    /**
     * @param ControllerModel $ctrl
     * @param Config $config
     * @return $this
     */
    protected function setNamespace(ControllerModel $ctrl, Config $config)
    {
        $namespace = $config->get('lumen_ctrl_namespace');
        $ctrl->setNamespace(new NamespaceModel($namespace));

        return $this;
    }

    /**
     * @param ControllerModel $ctrl
     * @param Config $config
     * @return $this
     */
    protected function setCustomProperties(ControllerModel $ctrl, Config $config)
    {
        return $this;
    }

    /**
     * @param ControllerModel $ctrl
     * @return $this
     */
    protected function setFields(ControllerModel $ctrl)
    {

        return $this;
    }

    /**
     * @param ControllerModel $ctrl
     * @return $this
     */
    protected function setRelations(ControllerModel $ctrl)
    {

        return $this;
    }
}
