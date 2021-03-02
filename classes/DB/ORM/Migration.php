<?php

namespace API\DB\ORM;

use API\DB;
use Doctrine\DBAL\DBALException;

/**
 * Class Migration
 * @package API\DB\ORM
 */
class Migration {
	/**
	 * @var Table
	 */
	private $table;

	/**
	 * Label
	 * @var string
	 */
	public $label = '';

	/**
	 * Description of migration
	 * @var string
	 */
	public $description = '';

	/**
	 * Migration sql script
	 * @var string|string[]
	 */
	public $sql = '';

	/**
	 * Migration constructor
	 * @param Table|null $table
	 * @param string $label Label of migration
	 * @param string $description Description of migration
	 */
	public function __construct($table, $label = '', $description = '') {
		$this->table = $table;
		$this->label = $label;
		$this->description = $description;
	}

	/**
	 * Create table
	 * @param bool $if_not_exists
	 * @return $this
	 */
	public function create($if_not_exists = false) {
		$this->sql = $this->table->create($if_not_exists);
		return $this;
	}

	/**
	 * Drop table
	 * @return $this
	 */
	public function drop() {
		$this->sql = $this->table->drop();
		return $this;
	}

	/**
	 * Add column to table
	 * @param string $field sql
	 * @return $this
	 */
	public function addColumn($field) {
		$this->sql = $this->table->alter('ADD COLUMN ' . $field);
		return $this;
	}

	/**
	 * Drop column in table
	 * @param string $name column name
	 * @return $this
	 */
	public function dropColumn($name) {
		$db = DB::getInstance();
		$this->sql = $this->table->alter('DROP COLUMN ' . $db->quoteIdentifier($name));
		return $this;
	}

	/**
	 * Change column in table
	 * @param string $name column name
	 * @param string $field sql
	 * @return $this
	 */
	public function changeColumn($name, $field) {
		$db = DB::getInstance();
		$this->sql = $this->table->alter('CHANGE COLUMN ' . $db->quoteIdentifier($name) . ' ' . $field);
		return $this;
	}

	/**
	 * Add key to table
	 * @param string $key key name
	 * @return $this
	 */
	public function addKey($key) {
		$this->sql = $this->table->alter('ADD ' . $key);
		return $this;
	}

	/**
	 * Drop kay in table
	 * @param string $name key name
	 * @return $this
	 */
	public function dropKey($name) {
		$db = DB::getInstance();
		//$this->sql = $this->table->alter('DROP INDEX ' . $db->quoteIdentifier($name));
		$this->sql = $this->table->alter('DROP KEY ' . $db->quoteIdentifier($name));
		return $this;
	}

	/**
	 * Execute SQL query
	 * @param string|string[] $sql
	 * @return $this
	 */
	public function execute($sql) {
		$this->sql = $sql;
		return $this;
	}

	/**
	 * Implement migration
	 * @throws DBALException
	 */
	public function implement() {
		$db = DB::getInstance();

		if (is_array($this->sql)) {
			foreach ($this->sql as $sql) {
				if ($sql && !startsWith($sql, "--")) {
					$db->query($sql);
				}
			}
		} else {
			$db->query($this->sql);
		}

		return true;
	}
}
