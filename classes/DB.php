<?php
namespace API;

use API\DB\ConnectionProxy;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;

/**
 * Database interface
 * @package API
 */
class DB {
    /**
     * Singleton instance
     * @var DB|null
     */
    private static $_instance = null;

    /**
     * DataBase Abstract Layer Connection
     * @var Connection|ConnectionProxy
     */
    private $conn;

    /**
     * DB constructor
     * @throws DBALException
     */
    public function __construct() {
        $this->conn = new ConnectionProxy(
            DriverManager::getConnection([
                'dbname' => $_ENV['DB_NAME'],
                'user' => $_ENV['DB_USER'],
                'password' => $_ENV['DB_PASS'],
                'host' => $_ENV['DB_HOST'],
                'driver' => 'mysqli',
            ])
        );
        $this->conn->connect();
        self::$_instance = $this;
    }

    /**
     * @return Connection|ConnectionProxy
     */
    public static function getInstance() {
        if (self::$_instance != null) {
            return self::$_instance->getConnection();
        }
        return (new self())->getConnection();
    }

    /**
     * Get DB connection
     * @return Connection|ConnectionProxy
     */
    public function getConnection() {
        return $this->conn;
    }
}
