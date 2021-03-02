<?php
namespace API\DB\ORM;

use Exception;

/**
 * Class Entity
 * @package API\DB\ORM
 */
class Entity {
	/**
	 * Name of entity
	 * @var string
	 */
	public $name = '';

	/**
	 * Root entity (without prefix)
	 * @var bool
	 */
	public $root = false;

	/**
	 * Description of entity
	 * @var string
	 */
	public $description = '';

	/**
	 * Entity options (fields and field-view params)
	 * @var array
	 */
	public $options = [];

	/**
	 * Entity tables
	 * @var Table[]
	 */
	public $tables = [];

	/**
	 * Entity relations
	 * @var array
	 */
	public $relations = [];

	/**
	 * Entity constructor
	 * @param object $item {name, description, options}
	 * @param string $prefix
	 * @throws Exception
	 */
	public function __construct($item, $prefix = '') {
		if (isset($item->root) && $item->root) {
			$prefix = '';
			$this->root = true;
		} elseif ($prefix) {
			$prefix = $prefix . '_';
		}

		if (isset($item->name)) {
			$this->name = $item->name;
		} else {
			throw new Exception('Name of ORM entity is invalid');
		}

		if (isset($item->description)) {
			$this->description = $item->description;
		}

		if (isset($item->options)) {
			$this->options = $item->options;

			$this->tables[$prefix . $this->name] = new Table(
				$this->name,
				$prefix,
				isset($item->options) ? $item->options : [],
				isset($item->keys) ? $item->keys : []
			);
		} else {
			throw new Exception('ORM entity "' . $item->name . '" haves invalid option "options"');
		}

		if (isset($item->relations)) {
			foreach ($item->relations as $relation) {
				$this->relations[] = $relation;

				if (isset($relation->multiple) && $relation->multiple) {
					$this->tables[$prefix . $relation->multiple->name] = new Table(
						$relation->multiple->name,
						$prefix,
						isset($relation->multiple->options) ? $relation->multiple->options : [],
						isset($relation->multiple->keys) ? $relation->multiple->keys : []
					);
				}
			}
		}
	}
}
