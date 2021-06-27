<?php
namespace API\DB;

use Doctrine\DBAL\Connection;
use Exception;

/**
 * Class ConnectionProxy
 * @package API\DB
 */
class ConnectionProxy {
    /**
     * DataBase Abstract Layer Connection
     * @var Connection
     */
    private $conn;

    /**
     * ConnectionProxy constructor
     * @param Connection $connection
     */
    public function __construct(Connection $connection) {
        $this->conn = $connection;
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments) {
        if (is_callable([$this->conn, $method])) {
            return call_user_func_array([$this->conn, $method], $arguments);
        } else {
            return new Exception("Call to undefined method '{$method}'");
        }
    }
}
