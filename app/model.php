<?php
namespace APP;

use API\DB;
use API\DB\ConnectionProxy;
use API\DB\Query;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Exception;

/**
 * Class Model
 * @package APP
 */
class Model {
    /**
     * Database connection
     * @var Connection|ConnectionProxy
     */
    public $db;

    /**
     * Table name
     * @var string
     */
    public $table = '';

    /**
     * Fields of table
     * @var string[]
     */
    static $fields = [];

    /**
     * Query builder
     * @var Query
     */
    public $query;

    /**
     * Model constructor
     */
    public function __construct() {
        $this->db = DB::getInstance();
        $this->query = new Query();
    }

    /**
     * Data selection
     * @param string|array $where
     * @param array $order
     * @param array $limit
     * @param string|array $join
     * @param string|array $group
     * @param string|array $having
     * @return ArrayCollection
     * @throws DBALException
     */
    public function select($where = [], array $order = [], array $limit = [], $join = [], $group = [], $having = []) {
        return $this->query
            ::getInstance()
            ->select('a.*')
            ->from($this->table, 'a')
            ->join($join)
            ->where($where)
            ->group($group)
            ->order($order)
            ->limit($limit)
            ->having($having)
            ->fetch();
    }

    /**
     * Get first column data
     * @param string $column
     * @param string|array $where
     * @param array $order
     * @param array $limit
     * @param string|array $join
     * @param string|array $group
     * @param string|array $having
     * @return mixed|null
     * @throws DBALException
     */
    public function get(
        string $column,
        $where,
        array $order = [],
        array $limit = [1],
        $join = [],
        $group = [],
        $having = []
    ) {
        $item = $this->select($where, $order, $limit, $join, $group, $having)->first();
        return $item ? $item[$column] : null;
    }

    /**
     * Get id field data
     * @param string|array $where
     * @param string|array $join
     * @param string|array $group
     * @param string|array $having
     * @return mixed|null
     * @throws DBALException
     */
    public function getId($where, $join = [], $group = [], $having = []) {
        return $this->get('id', $where, [], [1], $join, $group, $having);
    }

    /**
     * Recursive structural grouping of elements
     * @param array $items
     * @param array $groups
     * @param string $getAlias
     * @return array
     * @throws Exception
     */
    public function group(array $items, array $groups, string $getAlias) {
        // counting items of entities in $groups->alias->name
        foreach ($groups as $alias => $group) {
            $this->groupFilterKeys($items, $groups, $group, $alias);
            if (isset($groups[$alias]['relation'])) {
                $this->groupFilter($items, $groups, $groups[$alias]['relation']);
            }
        }
        // realise entities field names
        foreach ($groups as $alias => $group) {
            if (isset($group['name'])) {
                $entries = [];
                foreach ($groups[$alias][$group['name']] as $id => $item) {
                    foreach ($item as $field => $value) {
                        $entries[$id][substr($field, 1 + strlen($alias))] = $value;
                    }
                }
                $groups[$alias][$group['name']] = $entries;
            }
        }
        // inject relation of items
        foreach ($groups as $alias => $group) {
            if (isset($group['relation'])) {
                $this->groupInject($groups, $group, $alias);
            }
        }
        //if ($getAlias == 'ui') dbg($groups);
        return isset($groups[$getAlias]['name']) && isset($groups[$getAlias][$groups[$getAlias]['name']])
            ? array_values($groups[$getAlias][$groups[$getAlias]['name']])
            : [];
    }

    /**
     * Injecting items from $group->alias->name to $group->relationAlias->relationName
     * @param array $groups
     * @param array $group
     * @param string $alias
     */
    private function groupInject(&$groups, $group, $alias) {
        if (isset($group['relation'])) {
            foreach ($group['relation'] as $relationAlias => $relationGroup) {
                // handle relation level
                foreach ($groups[$relationAlias][$relationGroup['name']] as $relationId => $relationItem) {
                    // handle item level
                    $groups[$relationAlias][$relationGroup['name']][$relationId][$group['name']] = [];
                    foreach ($groups[$alias][$group['name']] as $id => $item) {
                        // add item to relation
                        $field = isset($relationGroup['relationKey'])
                            ? $relationGroup['relationKey']
                            : $relationGroup['name'] . '_' . $group['key'];
                        if (isset($item[$field]) && $relationItem[$relationGroup['key']] === $item[$field]) {
                            $groups[$relationAlias][$relationGroup['name']][$relationId][$group['name']][] = $item;
                        }
                    }
                }
                if (isset($relationGroup['relation'])) {
                    $this->groupInject($groups, $relationGroup, $relationAlias);
                }
            }
        }
    }

    /**
     * Filter groups relation
     * @param array $items
     * @param array $groups
     * @param array $relation
     * @throws Exception
     */
    private function groupFilter(&$items, &$groups, $relation) {
        if (is_array($relation)) {
            foreach ($relation as $relationAlias => $relationGroup) {
                $this->groupFilterKeys($items, $groups, $relationGroup, $relationAlias);
            }
        }
    }

    /**
     * Filter items keys from groups relation
     * @param array $items
     * @param array $groups
     * @param array $relation
     * @param string $alias
     * @throws Exception
     */
    private function groupFilterKeys(&$items, &$groups, $relation, $alias) {
        $fields = [];
        if (count($items) > 0) {
            foreach (array_keys($items[0]) as $field) {
                if (startsWith($field, $alias . '.')) {
                    $fields[] = $field;
                }
            }
        }
        $groups[$alias][$relation['name']] = [];
        foreach ($items as $item) {
            $groups[$alias]['name'] = $relation['name'];
            $groups[$alias]['key'] = $relation['key'];
            if (!isset($groups[$alias][$relation['name']])) {
                $groups[$alias][$relation['name']] = [];
            }
            if (!array_key_exists($alias . '.' . $relation['key'], $item)) {
                throw new Exception(
                    'Undefined field: ' . $alias . '.' . $relation['key'] . ' in relation ' . $relation['name']
                );
            }
            if (!isset($groups[$alias][$relation['name']][$item[$alias . '.' . $relation['key']]])) {
                $groups[$alias][$relation['name']][$item[$alias . '.' . $relation['key']]] = [];
            }
            if (isset($item[$alias . '.' . $relation['key']])) {
                $groups[$alias][$relation['name']][$item[$alias . '.' . $relation['key']]] = array_filter(
                    $item,
                    function ($field) use ($fields, $alias) {
                        return in_array($field, $fields);
                    },
                    ARRAY_FILTER_USE_KEY
                );
            }
        }
        if (isset($relation['relation'])) {
            $this->groupFilter($items, $groups, $relation['relation']);
        }
    }

    /**
     * Replace data in table
     * @param iterable $data
     * @param array $primaryKey
     * @return bool
     * @throws DBALException
     */
    public function replace($data, array $primaryKey = []) {
        $query = 'REPLACE INTO ' . $this->table . ' ';

        $keys = [];
        $values = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, array_keys($this::$fields))) {
                continue;
            }

            $keys[] = $this->db->quoteIdentifier($key);
            if (!$value && in_array($key, array_keys($primaryKey))) {
                $values[] = 'NULL';
            } else {
                $values[] = $this->db->quote($value);
            }
        }

        $query .= '(' . implode(',', $keys) . ') ';
        $query .= 'VALUES (' . implode(',', $values) . ')';
        return $this->db->query($query)->execute();
    }

    /**
     * Data inserting
     * @param array $values [key=>value...]
     * @param array $limit [offset, count] or [count]
     * @return string|Statement|int
     */
    public function insert($values = [], array $limit = []) {
        $query = $this->query
            ::getInstance()
            ->insert($this->table)
            ->values(array_intersect_key($values, $this::$fields))
            ->limit($limit);

        $result = $query->execute();
        return $result ? $query->lastInsertId() : $result;
    }

    /**
     * Data updating
     * @param array $fields
     * @param string|array $where
     * @return Statement|int
     */
    public function update($fields = [], $where = []) {
        $query = $this->query
            ::getInstance()
            ->update($this->table, 'a')
            ->set(array_intersect_key($fields, $this::$fields))
            ->where($where);
        return $query->execute();
    }

    /**
     * Data deletion
     * @param mixed $where
     * @param array $limit
     * @return Statement|int
     */
    public function delete($where = [], array $limit = []) {
        $query = $this->query
            ::getInstance()
            ->delete($this->table)
            ->where($where)
            ->limit($limit);
        return $query->execute();
    }
}
