<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;

use biliboobrian\lumenAngularCodeGenerator\Config;

/**
 * Class Relation
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
abstract class Relation
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $foreignColumnName;

    /**
     * @var string
     */
    protected $localColumnName;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Relation constructor.
     * @param string $tableName
     * @param string $joinColumnName
     * @param string $localColumnName
     */
    public function __construct($tableName, $joinColumnName, $localColumnName, $config)
    {
        $this->setTableName($tableName);
        $this->setForeignColumnName($joinColumnName);
        $this->setLocalColumnName($localColumnName);
        $this->setConfig($config);
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     *
     * @return $this
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $config
     *
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return string
     */
    public function getForeignColumnName()
    {
        return $this->foreignColumnName;
    }

    /**
     * @param string $foreignColumnName
     *
     * @return $this
     */
    public function setForeignColumnName($foreignColumnName)
    {
        $this->foreignColumnName = $foreignColumnName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocalColumnName()
    {
        return $this->localColumnName;
    }

    /**
     * @param string $localColumnName
     *
     * @return $this
     */
    public function setLocalColumnName($localColumnName)
    {
        $this->localColumnName = $localColumnName;

        return $this;
    }
}
