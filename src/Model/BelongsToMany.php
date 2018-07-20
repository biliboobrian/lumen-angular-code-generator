<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;

/**
 * Class BelongsToMany
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
class BelongsToMany extends Relation
{
    /**
     * @var string
     */
    protected $joinTable;

    /**
     * BelongsToMany constructor.
     * @param string $tableName
     * @param string $joinTable
     * @param string $foreignColumnName
     * @param string $localColumnName
     */
    public function __construct($tableName, $joinTable, $foreignColumnName, $localColumnName, $config)
    {
        $this->joinTable = $joinTable;
        parent::__construct($tableName, $foreignColumnName, $localColumnName, $config);
    }

    /**
     * @return string
     */
    public function getDefaultJoinTableName()
    {
        //return
    }

    /**
     * @return string
     */
    public function getJoinTable()
    {
        return $this->joinTable;
    }

    /**
     * @param string $joinTable
     *
     * @return $this
     */
    public function setJoinTable($joinTable)
    {
        $this->joinTable = $joinTable;

        return $this;
    }
}
