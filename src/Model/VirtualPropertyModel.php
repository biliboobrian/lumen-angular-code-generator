<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;


/**
 * Class VirtualPropertyModel
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
class VirtualPropertyModel extends BasePropertyModel
{
	/**
	 * @var string
	 */
	protected $comment;

	/**
     * @var boolean
     */
    protected $readable = true;

    /**
     * @var
     */
	protected $writable = true;
	
	/**
	 * VirtualPropertyModel constructor.
	 * @param string $name
	 * @param string $type
	 * @param string $comment
	 * @param string $required
	 */
	public function __construct($name, $type = null, $comment = null, $required = null)
	{
		$this->setName($name)
			->setType($type)
            ->setComment($comment)
			->setRequired($required);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toLines()
	{
		$property = '@property';
		if (!$this->readable) {
			$property .= '-write';
		} elseif (!$this->writable) {
			$property .= '-read';
		}

		if ($this->type !== null) {
			$property .= ' ' . $this->type;
		}

		return $property . ' $' . $this->name . (empty($this->comment) ? '' : ' ' . $this->comment);
	}

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @param string $required
     *
     * @return $this
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

	/**
     * @return boolean
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * @param boolean $readable
     *
     * @return $this
     */
    public function setReadable($readable = true)
    {
        $this->readable = $readable;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWritable()
    {
        return $this->writable;
    }

    /**
     * @param mixed $writable
     *
     * @return $this
     */
    public function setWritable($writable = true)
    {
        $this->writable = $writable;

        return $this;
    }

	/**
	 * @return string
	 */
	public function getComment()
	{
		return $this->comment;
	}

	/**
	 * @param string $comment
	 *
	 * @return $this
	 */
	public function setComment($comment)
	{
		$this->comment = $comment;

		return $this;
	}
}
