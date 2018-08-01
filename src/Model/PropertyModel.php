<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;

use biliboobrian\lumenAngularCodeGenerator\Model\Traits\AccessModifierTrait;
use biliboobrian\lumenAngularCodeGenerator\Model\Traits\DocBlockTrait;
use biliboobrian\lumenAngularCodeGenerator\Model\Traits\StaticModifierTrait;
use biliboobrian\lumenAngularCodeGenerator\Model\Traits\ValueTrait;

/**

 * Class PHPClassProperty
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
class PropertyModel extends BasePropertyModel
{
    use AccessModifierTrait;
    use DocBlockTrait;
    use StaticModifierTrait;
    use ValueTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $dbType;

    /**
     * PropertyModel constructor.
     * @param string $name
     * @param string $access
     * @param mixed|null $value
     * @param string $type
     */
    public function __construct($name, $access = 'public', $value = null, $type = 'lumen', $dbType = 'string')
    {
        $this->setName($name)
            ->setAccess($access)
            ->setValue($value);
        
        $this->type = $type;
        $this->dbType = $dbType;
        
    }

    /**
     * {@inheritDoc}
     */
    public function toLines()
    {
        $lines = [];
        if ($this->docBlock !== null) {
            $lines = array_merge($lines, $this->docBlock->toLines());
        }

        $property = $this->access . ' ';
        if ($this->static) {
            $property .= 'static ';
        }

        if($this->type == 'lumen') {
            $property .= '$' . $this->name;
        } else {
            $property .= (!$this->value && $this->dbType) ? $this->name . ': ' .$this->dbType : $this->name;
        }
        

        if ($this->value !== null) {
            $value = $this->renderValue();
            if ($value !== null) {
                $property .= sprintf(' = %s', $this->renderValue());
            }
        }
        $property .= ';';
        $lines[] = $property;

        return $lines;
    }
}
