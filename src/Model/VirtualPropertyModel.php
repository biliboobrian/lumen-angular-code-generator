<?php

namespace biliboobrian\lumenAngularCodeGenerator\Model;

use biliboobrian\lumenAngularCodeGenerator\Model\VirtualPropertyModel as BaseVirtualPropertyModel;

/**
 * Class VirtualPropertyModel
 * @package biliboobrian\lumenAngularCodeGenerator\Model
 */
class VirtualPropertyModel extends BaseVirtualPropertyModel
{
	/**
	 * @var string
	 */
	protected $comment;

	/**
	 * VirtualPropertyModel constructor.
	 * @param string $name
	 * @param string $type
	 * @param string $comment
	 */
	public function __construct($name, $type = null, $comment = null)
	{
		parent::__construct($name, $type);
		
		$this->setComment($comment);
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
