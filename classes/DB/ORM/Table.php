<?php
/** @noinspection PhpMissingBreakStatementInspection */

namespace API\DB\ORM;

use API\DB;

/**
 * Class Table
 * @package API\DB\ORM
 */
class Table {
	/**
	 * Name of table
	 * @var string
	 */
	public $name = '';

	/**
	 * Table name prefix
	 * @var string
	 */
	public $prefix = '';

	/**
	 * Fields of table
	 * @var array
	 */
	public $fields = [];

	/**
	 * Keys of table
	 * @var array
	 */
	public $keys = [];

	/**
	 * Initialize table fields and keys
	 * @param string $name Table name
	 * @param string $prefix Table name prefix
	 * @param array $options Table fields
	 * @param array $keys Table keys
	 */
	public function __construct($name, $prefix = '', $options = [], $keys = []) {
		$this->name = $name;
		$this->prefix = $prefix;

		foreach ($options as $field => $params) {
			$this->addField(
				$field,
				isset($params->type) ? $params->type : '',
				isset($params->null) ? $params->null : '',
				isset($params->default) ? $params->default : null,
				isset($params->auto_increment) ? $params->auto_increment : false
			);
		}

		foreach ($keys as $key => $item) {
			$this->addKey(
				$key,
				isset($item->type) ? $item->type : '',
				isset($item->fields) ? $item->fields : [],
				isset($item->references) ? $item->references : (object) []
			);
		}
	}

	/**
	 * Get SQL Create table query
	 * @param bool $if_not_exists
	 * @return string
	 */
	public function create($if_not_exists = false) {
		$db = DB::getInstance();

		$query = [];
		$query[] = 'CREATE TABLE';
		if ($if_not_exists) {
			$query[] = 'IF NOT EXISTS';
		}
		$query[] = $db->quoteIdentifier($this->prefix . $this->name);

		$fields = [];
		foreach ($this->fields as $item) {
			$fields[] = '    ' . $item;
		}
		foreach ($this->keys as $item) {
			$fields[] = '    ' . $item;
		}

		$query[] = "(\n" . implode(",\n", $fields) . "\n)";

		return implode(' ', $query);
	}

	/**
	 * Add table field
	 * @param string $column
	 * @param string $type
	 * @param string|null $null
	 * @param string|null $default
	 * @param bool $auto_increment
	 * @return void
	 */
	private function addField($column, $type, $null = '', $default = null, $auto_increment = false) {
		$db = DB::getInstance();

		$query = [$db->quoteIdentifier($column)];
		$query[] = strtoupper($type);
		if (!is_null($null)) {
			$query[] = strtoupper($null);
		}
		if (!is_null($default) && !$auto_increment) {
			$query[] = in_array($default, [
				'NULL',
				'CURRENT_TIMESTAMP',
				'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
				'\'\'',
			])
				? 'DEFAULT ' . $default
				: 'DEFAULT ' . $db->quote($default);
		}
		if ($auto_increment) {
			$query[] = 'AUTO_INCREMENT';
		}

		if ($type) {
			$this->fields[$column] = implode(' ', $query);
		}
	}

	/**
	 * Add table key
	 * @param string $name
	 * @param string $type
	 * @param array $fields
	 * @param object $references
	 * @return void
	 */
	private function addKey($name, $type, $fields, $references) {
		$db = DB::getInstance();

		$query = [];
		switch (strtoupper($type)) {
			case 'PRIMARY':
				$query[] = strtoupper($type) . ' KEY';
				if ($fields) {
					foreach ($fields as &$key) {
						$key = $db->quoteIdentifier($key);
					}
					$query[] = '(' . implode(',', $fields) . ')';
				} else {
					$query[] = '(' . $db->quoteIdentifier($name) . ')';
				}
				break;
			case 'FOREIGN':
				$keys = array_map(
					function ($item) use ($db) {
						return $db->quoteIdentifier($item);
					},
					$fields ? $fields : []
				);
				$query[] =
					'CONSTRAINT ' . $db->quoteIdentifier($name) . ' ' . $type . ' KEY (' . implode(',', $keys) . ')';
				$query[] = 'REFERENCES ' . $db->quoteIdentifier($this->prefix . $references->entity);
				$keys = array_map(
					function ($item) use ($db) {
						return $db->quoteIdentifier($item);
					},
					$references->fields ? $references->fields : []
				);
				$query[] = '(' . implode(',', $keys) . ')';
				$query[] = 'ON DELETE CASCADE';
				break;
			case 'UNIQUE':
				$query[] = 'UNIQUE';
			case 'STATIC':
			default:
				$query[] = 'KEY ' . $db->quoteIdentifier($name);

				$keys = [];
				foreach ($fields as $field) {
					$keys[] = $db->quoteIdentifier($field);
				}
				$query[] = '(' . implode(',', $keys) . ')';
				break;
		}

		$this->keys[$name] = implode(' ', $query);
	}

	/**
	 * Get alter table SQL query
	 * @param string $action [add|drop|change] column...
	 * @return string sql
	 */
	public function alter($action) {
		$db = DB::getInstance();
		return implode(' ', ['ALTER TABLE', $db->quoteIdentifier($this->prefix . $this->name), $action]);
	}

	/**
	 * Get drop table SQL query
	 * @return string sql
	 */
	public function drop() {
		$db = DB::getInstance();

		$query = [];
		$query[] = 'DROP TABLE';
		$query[] = $db->quoteIdentifier($this->prefix . $this->name);

		return implode(' ', $query);
	}
}
