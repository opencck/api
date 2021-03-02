<?php
namespace API\DB;

use API\DB;
use API\DB\ORM\Entity;
use API\DB\ORM\Migration;
use Doctrine\DBAL\DBALException;
use Exception;

/**
 * Object-relational Model
 * @package API\DB
 */
class ORM {
	/**
	 * Application name
	 * @var string
	 */
	public $name = '';

	/**
	 * Application config
	 * @var object|null
	 */
	private $config;

	/**
	 * Entities of application
	 * @var Entity[]
	 */
	public $entities = [];

	/**
	 * Initialize application entities
	 * @param string $name App name
	 * @param object|null $config App config
	 * @throws Exception
	 */
	public function __construct(string $name, $config) {
		$this->name = $name;
		$this->config = $config;
		if (isset($config->orm)) {
			$this->entities = array_map(
				/** @param object $item */
				function ($item) {
					return new Entity($item, $this->name);
				},
				$config->orm
			);
		}
	}

	/**
	 * Install root entities if not exist in DB
	 * @return bool
	 * @throws DBALException
	 */
	public function install() {
		$db = DB::getInstance();

		foreach ($this->entities as $entity) {
			if ($entity->root) {
				// Existing of root tables checking
				$query = $db->query('SHOW TABLES LIKE ' . $db->quote($entity->name));
				$tables = array_filter(
					array_map(
						/**
						 * @param object $item
						 * @return string table name
						 */
						function ($item) {
							return array_values((array) $item)[0];
						},
						$query->fetchAll()
					),
					function ($table) use ($entity) {
						return $table === $entity->name;
					}
				);

				if (!in_array($entity->name, $tables)) {
					// Crete root entity tables
					foreach ($entity->tables as $table) {
						$db->query($table->create());
					}
				}
			}
		}
		return true;
	}

	/**
	 * Find all migrations with old and current application config
	 * @param object $config Actual config
	 * @return array
	 * @throws Exception
	 */
	public function check($config) {
		$migrations = [];
		// create ORM based on actual config
		$orm = new self($this->name, $config);
		// if this is new app installation (old config not exist)
		if (is_null($this->config)) {
			foreach ($orm->entities as $entity) {
				if (!$entity->root) {
					foreach ($entity->tables as $table) {
						// create tables of entities of new application
						$migrations[] = (new Migration(
							$table,
							'Create table',
							"Create table '{$table->name}' for application '{$this->name}'"
						))->create(true);
					}
				}
			}
		} else {
			// Actual tables of ORM entities
			$tables = [];
			foreach ($orm->entities as $entity) {
				foreach ($entity->tables as $table) {
					$tables[$table->name] = $table;
				}
			}
			$old = [];
			foreach ($this->entities as $entity) {
				foreach ($entity->tables as $table) {
					$old[$table->name] = $table;
				}
			}

			// table create
			foreach (array_diff_key($tables, $old) as $table) {
				$migrations[] = (new Migration(
					$table,
					'Create table',
					"Create table '{$table->name}' for application '{$this->name}'"
				))->create();
			}

			// table drop
			foreach (array_diff_key($old, $tables) as $table) {
				$migrations[] = (new Migration(
					$table,
					'Drop table',
					"Drop table '{$table->name}' for application '{$this->name}'"
				))->drop();
			}
			foreach (array_intersect_key($tables, $old) as $tableName => $table) {
				// key drop
				foreach (array_diff_key($old[$tableName]->keys, $table->keys) as $keyName => $key) {
					$migrations[] = (new Migration(
						$table,
						'Alter table',
						"Alter '{$table->name}' drop key for application '{$this->name}'"
					))->dropKey($keyName);
				}
				// column drop
				foreach (array_diff_key($old[$tableName]->fields, $table->fields) as $column => $field) {
					$migrations[] = (new Migration(
						$table,
						'Alter table',
						"Alter '{$table->name}' drop column for application '{$this->name}'"
					))->dropColumn($column);
				}
				// column add
				foreach (array_diff_key($table->fields, $old[$tableName]->fields) as $field) {
					$migrations[] = (new Migration(
						$table,
						'Alter table',
						"Alter '{$table->name}' add column for application '{$this->name}'"
					))->addColumn($field);
				}
				foreach (array_intersect_key($table->fields, $old[$tableName]->fields) as $column => $field) {
					// column change
					if ($field != $old[$tableName]->fields[$column]) {
						$migrations[] = (new Migration(
							$table,
							'Alter table',
							"Alter '{$table->name}' change column for application '{$this->name}'"
						))->changeColumn($column, $field);
					}
				}
				// key add
				foreach (array_diff_key($table->keys, $old[$tableName]->keys) as $keyName => $key) {
					$migrations[] = (new Migration(
						$table,
						'Alter table',
						"Alter '{$table->name}' add key for application '{$this->name}'"
					))->addKey($key);
				}
				foreach (array_intersect_key($table->keys, $old[$tableName]->keys) as $keyName => $key) {
					// key change
					if ($key != $old[$tableName]->keys[$keyName]) {
						$migrations[] = (new Migration(
							$table,
							'Alter table',
							"Alter '{$table->name}' drop key for application '{$this->name}'"
						))->dropKey($keyName);
						$migrations[] = (new Migration(
							$table,
							'Alter table',
							"Alter '{$table->name}' add key for application '{$this->name}'"
						))->addKey($key);
					}
				}
			}
		}
		return $migrations;
	}

	/**
	 * Get application config
	 * @return object|null
	 */
	public function getConfig() {
		return $this->config;
	}
}
