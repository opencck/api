<?php
namespace API\DB;

use API\DB;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Query builder
 * @package API\DB
 */
class Query {
	/**
	 * Singleton instance
	 * @var Query|null
	 */
	private static $_instance = null;

	/**
	 * @var Connection|ConnectionProxy
	 */
	private $db;

	/**
	 * DataBase Abstract Layer Connection
	 * @var QueryBuilder
	 */
	private $query;

	/**
	 * Query constructor
	 */
	public function __construct() {
		$this->db = DB::getInstance();
		$this->query = DB::getInstance()->createQueryBuilder();

		self::$_instance = $this;
	}

	/**
	 * @param bool $inheritance
	 * @return Query
	 */
	public static function getInstance($inheritance = false) {
		if ($inheritance && !is_null(self::$_instance)) {
			return self::$_instance;
		}
		return new self();
	}

	/**
	 * @param string|array|null $select
	 * @return $this
	 */
	public function select($select = null) {
		if (is_array($select)) {
			if (count($select) > 0) {
				$items = [];
				foreach ($select as $alias => $fields) {
					if (is_string($fields)) {
						$items[] = $fields;
					} else {
						foreach ($fields as $field_name) {
							$items[] =
								$this->db->quoteIdentifier($alias) .
								'.' .
								$this->db->quoteIdentifier($field_name) .
								' AS ' .
								"`$alias.$field_name`";
						}
					}
					$this->query->addSelect(implode(', ', $items));
				}
			}
		} else {
			$this->query->addSelect($select);
		}
		return $this;
	}

	/**
	 * @param string $from
	 * @param string|null $alias
	 * @return $this
	 */
	public function from($from, $alias = null) {
		$this->query->from($from, is_null($alias) ? null : $this->db->quoteIdentifier($alias));
		return $this;
	}

	/**
	 * @param string|array $fromAlias
	 * @param string $join
	 * @param string $alias
	 * @param null $condition
	 * @return $this
	 */
	public function join($fromAlias, $join = '', $alias = '', $condition = null) {
		if (is_array($fromAlias)) {
			foreach ($fromAlias as $args) {
				$this->query->join($args[0], $args[1], $args[2], $args[3]);
			}
		} else {
			$this->query->join($fromAlias, $join, $alias, $condition);
		}
		return $this;
	}

	/**
	 * @param string|array $fromAlias
	 * @param string $join
	 * @param string $alias
	 * @param string $condition
	 * @return $this
	 */
	public function leftJoin($fromAlias, $join = '', $alias = '', $condition = null) {
		$db = DB::getInstance();
		if (is_array($fromAlias)) {
			foreach ($fromAlias as $args) {
				$this->query->leftJoin(
					$db->quoteIdentifier($args[0]),
					$args[1],
					$db->quoteIdentifier($args[2]),
					$args[3]
				);
			}
		} else {
			$this->query->leftJoin($db->quoteIdentifier($fromAlias), $join, $db->quoteIdentifier($alias), $condition);
		}
		return $this;
	}

	/**
	 * @param string|array $where
	 * @return $this
	 */
	public function where($where) {
		if (is_array($where)) {
			foreach ($where as $key => $value) {
				if (is_array($value)) {
					$array = [];
					foreach ($value as $v) {
						$array[] = $this->db->quote($v);
					}
					$this->query->andWhere($this->db->quoteIdentifier($key) . ' IN (' . implode(',', $array) . ')');
				} else {
					$this->query->andWhere($this->db->quoteIdentifier($key) . ' = ' . $this->db->quote($value));
				}
			}
		} else {
			$this->query->andWhere($where);
		}
		return $this;
	}

	/**
	 * @param array $order
	 * @return $this
	 */
	public function order(array $order) {
		if (count($order)) {
			$splice = array_splice($order, 0, 1);
			foreach ($splice as $key => $value) {
				$this->query->orderBy($key, $value ? $value : 'ASC');
			}
		}
		foreach ($order as $key => $value) {
			$this->query->addOrderBy($key, $value ? $value : 'ASC');
		}
		return $this;
	}

	/**
	 * @param array $limit
	 * @return $this
	 */
	public function limit(array $limit) {
		if (count($limit) > 1) {
			$this->query->setFirstResult($limit[0])->setMaxResults($limit[1]);
		} elseif (count($limit)) {
			$this->query->setMaxResults($limit[0]);
		}
		return $this;
	}

	/**
	 * @param string|array $group
	 * @return $this
	 */
	public function group($group) {
		if (is_array($group)) {
			foreach ($group as $value) {
				$this->query->addGroupBy($value);
			}
		} else {
			$this->query->addGroupBy($group);
		}

		return $this;
	}

	/**
	 * @param string|array $having
	 * @return $this
	 */
	public function having($having) {
		if (is_array($having)) {
			foreach ($having as $value) {
				$this->query->andHaving($value);
			}
		} else {
			$this->query->andHaving($having);
		}

		return $this;
	}

	/**
	 * @param string $table
	 * @return $this
	 */
	public function insert($table) {
		$this->query->insert($table);
		return $this;
	}

	/**
	 * @param array<string,string|null> $values
	 * @return $this
	 */
	public function values($values) {
		$escape = [];
		foreach ($values as $key => $value) {
			$escape[$this->db->quoteIdentifier($key)] = !is_null($value) ? $this->db->quote($value) : 'NULL';
		}
		$this->query->values($escape);
		return $this;
	}

	/**
	 * @param string $table
	 * @param string|null $alias
	 * @return $this
	 */
	public function update($table, $alias = null) {
		$this->query->update($table, $alias);
		return $this;
	}

	/**
	 * @param array $data
	 * @return $this
	 */
	public function set($data = []) {
		foreach ($data as $key => $val) {
			$this->query->set($this->db->quoteIdentifier($key), !is_null($val) ? $this->db->quote($val) : 'NULL');
		}
		return $this;
	}

	/**
	 * @param string $table
	 * @return $this
	 */
	public function delete($table) {
		$this->query->delete($table);
		return $this;
	}

	/**
	 * @return ResultStatement|int
	 */
	public function execute() {
		return $this->query->execute();
	}

	/**
	 * @return string
	 */
	public function lastInsertId() {
		return $this->db->lastInsertId();
	}

	/**
	 * @return ArrayCollection
	 * @throws DBALException
	 */
	public function fetch() {
		return new ArrayCollection($this->db->query($this->query)->fetchAll());
	}

	/**
	 * @return QueryBuilder
	 */
	public function getQuery() {
		return $this->query;
	}
}
