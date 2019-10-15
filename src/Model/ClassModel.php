<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;

use biliboobrian\lumenAngularCodeGenerator\Exception\GeneratorException;
use biliboobrian\lumenAngularCodeGenerator\Model\Traits\DocBlockTrait;
use biliboobrian\lumenAngularCodeGenerator\RenderableModel;

/**
 * Class ClassModel
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
class ClassModel extends RenderableModel
{
    use DocBlockTrait;

    /**
     * @var ClassNameModel
     */
    protected $name;

    /**
     * @var NamespaceModel
     */
    protected $namespace;

    /**
     * @var UseClassModel[]
     */
    protected $uses = [];

    /**
     * @var UseTraitModel[]
     */
    protected $traits = [];

    /**
     * @var ConstantModel[]
     */
    protected $constants = [];

    /**
     * @var BasePropertyModel[]
     */
    protected $properties = [];

    /**
     * @var BaseMethodModel[]
     */
    protected $methods = [];

    /**
     * @var string[]
     */
    protected $swaggerBlock = [];

    /**
     * @var string[]
     */
    protected $swaggerController = [];

    /**
     * {@inheritDoc}
     */
    public function toLines()
    {
        $lines = [];
        $lines[] = $this->ln('<?php');
        if ($this->namespace !== null) {
            $lines[] = $this->ln($this->namespace->render());
        }
        if (count($this->uses) > 0) {
            $lines[] = $this->renderArrayLn($this->uses);
        }
        $this->prepareDocBlock();
        if ($this->docBlock !== null) {
            $lines[] = $this->docBlock->render();
        }
        $lines[] = $this->name->render();
        $lines[] = $this->processSwaggerProperties($lines);

        foreach($this->swaggerController as $line) {
            $lines[] = $line;
        } 
        
        if (count($this->traits) > 0) {
            $lines[] = $this->renderArrayLn($this->traits, 4);
        }
        if (count($this->constants) > 0) {
            $lines[] = $this->renderArrayLn($this->constants, 4);
        }
        $this->processProperties($lines);
        $this->processMethods($lines);
        $lines[] = $this->ln('}');

        return $lines;
    }

    /**
     * @return ClassNameModel
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param ClassNameModel $name
     *
     * @return $this
     */
    public function setName(ClassNameModel $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return NamespaceModel
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param NamespaceModel $namespace
     *
     * @return $this
     */
    public function setNamespace(NamespaceModel $namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @return UseClassModel[]
     */
    public function getUses()
    {
        return $this->uses;
    }

    /**
     * @param UseClassModel $use
     *
     * @return $this
     */
    public function addUses(UseClassModel $use)
    {
        $this->uses[] = $use;

        return $this;
    }

    /**
     * @return UseTraitModel[]
     */
    public function getTraits()
    {
        return $this->traits;
    }

    /**
     * @param UseTraitModel
     *
     * @return $this
     */
    public function addTrait(UseTraitModel $trait)
    {
        $this->traits[] = $trait;

        return $this;
    }

    /**
     * @return ConstantModel[]
     */
    public function getConstants()
    {
        return $this->constants;
    }

    /**
     * @param ConstantModel $constant
     *
     * @return $this
     */
    public function addConstant(ConstantModel $constant)
    {
        $this->constants[] = $constant;

        return $this;
    }

    /**
     * @return BasePropertyModel[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param BasePropertyModel $property
     *
     * @return $this
     */
    public function addProperty(BasePropertyModel $property)
    {
        $this->properties[] = $property;

        return $this;
    }

    /**
     * @return BaseMethodModel[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param BaseMethodModel
     *
     * @return $this
     */
    public function addMethod(BaseMethodModel $method, $atTheEnd = true)
    {
        if($atTheEnd) {
            $this->methods[] = $method;
        } else {
            array_unshift($this->methods, $method);
        }
        

        return $this;
    }

    /**
     * Convert virtual properties and methods to DocBlock content
     */
    protected function prepareDocBlock()
    {
        $content = [];

        foreach ($this->properties as $property) {
            if ($property instanceof VirtualPropertyModel) {
                $content[] = $property->toLines();
            }
        }

        foreach ($this->methods as $method) {
            if ($method instanceof VirtualMethodModel) {
                $content[] = $method->toLines();
            }
        }

        $content = array_merge($content, $this->swaggerBlock);


        if ($content) {
            if ($this->docBlock === null) {
                $this->docBlock = new DocBlockModel();
            }

            $this->docBlock->addContent($content);
        }
    }

    /**
     * @param array $lines
     */
    protected function processProperties(&$lines)
    {
        $properties = array_filter($this->properties, function ($property) {
            return !$property instanceof VirtualPropertyModel;
        });
        if (count($properties) > 0) {
            $lines[] = $this->renderArrayLn($properties, 4, str_repeat(PHP_EOL, 1));
        }
    }

    /**
     * @param array $lines
     */
    protected function processSwaggerProperties(&$lines)
    {
        $properties = array_filter($this->properties, function ($property) {
            return !$property instanceof VirtualPropertyModel;
        });
        if (count($properties) > 0) {
            $lines[] = "    /** "; 
            foreach ($this->properties as $property) {
                if ($property instanceof VirtualPropertyModel) {
                    $lines[] = "     * @OA\Property(";

                    if($this->getSwaggerFormat($property->type)) {
                        $lines[] = "     *    format=\"" . $this->getSwaggerFormat($property->getType()) . "\",";
                    }

                    $lines = $this->getSwaggerType($lines, $property->getType());
                    $lines[] = "     *    property=\"" . $property->getName() . "\"";
                    $lines[] = "     * )";  
                    $lines[] = "     * "; 
                }
            }
            $lines[] = "     */ "; 
        }
    }

    protected function getSwaggerFormat($type) {
        switch ($type) {
            case 'int':
                return "int64";
                break;
            
            default:
                return null;
                break;
        }
    }

    protected function getSwaggerType($lines, $type) {
        switch ($type) {
            case 'int':
                $lines[] = "     *    type=\"integer\",";
                break;
            case 'float':
                $lines[] = "     *    type=\"float\",";
                break;
            case 'string':
                $lines[] = "     *    type=\"string\",";
                break;
            case '\Carbon\Carbon':
                $lines[] = "     *    type=\"date\",";
                break;
            default:
                if(strpos($type,"[]")) {
                    $lines[] = '     *    @OA\Items(ref="#/components/schemas/' . substr($type,0, -2) . '"),';
                    $lines[] = '     *    type="array",';
                    
                } else {
                    $lines[] = '     *    ref="#/components/schemas/' . $type . '",';
                    
                }
                break;
        }
        return $lines;
    }

    /**
     * @param array $lines
     * @throws GeneratorException
     */
    protected function processMethods(&$lines)
    {
        $methods = array_filter($this->methods, function ($method) {
            return !$method instanceof VirtualMethodModel;
        });
        if (count($methods) > 0) {
            $lines[] = $this->renderArray($methods, 4, str_repeat(PHP_EOL, 2));
        }
    }
}
