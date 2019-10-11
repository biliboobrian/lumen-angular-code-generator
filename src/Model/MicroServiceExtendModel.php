<?php
/**
 * @file
 * Contains \biliboobrian\lumenAngularCodeGenerator\Model\MicroServiceExtendModel.
 */

namespace biliboobrian\lumenAngularCodeGenerator\Model;

use biliboobrian\MicroServiceModelUtils\Models\MicroServiceBaseModel;

/**
 * A base model class that can be used in a microservice.
 *
 * @package biliboobrian\MicroServiceModelUtils\Models\MicroServiceBaseModel
 */
abstract class MicroServiceExtendModel extends MicroServiceBaseModel
{

    /**
     * Get the value of the primary key, used to identify this model.
     *
     * @return mixed
     */
    public function getPrimaryKeyValue()
    {
        return $this[$this->primaryKey];
    }

    public function getDates()
    {
        return $this->dates;
    }
}