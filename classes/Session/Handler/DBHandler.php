<?php
namespace API\Session\Handler;

use API\DB;
use API\DB\ConnectionProxy;
use API\Session;
use Doctrine\DBAL\Connection;

/**
 * DB Session Handler
 * @package API\Session\Handler
 */
class DBHandler {
    /**
     * DataBase Abstract Layer Connection
     * @var Connection|ConnectionProxy $db
     */
    protected $db;

    /**
     * Session location
     * @var string
     */
    protected $location;

    /**
     * DB Session Handler constructor
     * @param string $location
     */
    public function __construct($location) {
        $this->db = DB::getInstance();
        $this->location = $location;
    }

    /**
     * @param string $path
     * @param string $name
     * @return bool
     */
    public function open(string $path, string $name): bool {
        return $this->db->isConnected();
    }

    /**
     * @return bool
     */
    public function close(): bool {
        // Close the database connection
        $this->db->close();
        return !$this->db->isConnected();
    }

    /**
     * @param string $id
     * @return array
     */
    public function read(string $id): array {
        $query = $this->db->query('SELECT data, users_id FROM sessions WHERE id = ? AND location = ?');
        $query->bindValue(1, $id);
        $query->bindValue(2, $this->location);
        $query->execute();

        if ($row = $query->fetch()) {
            return [
                'data' => $row['data'],
                'users_id' => $row['users_id'],
            ];
        } else {
            return [
                'data' => '',
                'users_id' => 0,
            ];
        }
    }

    /**
     * @param string $id
     * @param string $data
     * @param integer|null $user_id
     * @return bool
     */
    public function write(string $id, string $data = '', $user_id = null): bool {
        $query = $this->db->prepare('REPLACE INTO sessions VALUES (?, ?, ?, ?, ?)');
        $query->bindValue(1, $id);
        $query->bindValue(2, $this->location);
        $query->bindValue(3, time());
        $query->bindValue(4, $user_id);
        $query->bindValue(5, $data);

        return $query->execute();
    }

    /**
     * @param string $id
     * @return bool
     */
    public function destroy(string $id): bool {
        $query = $this->db->prepare('DELETE FROM sessions WHERE id = ? AND location = ?');
        $query->bindValue(1, $id);
        $query->bindValue(2, $this->location);

        return $query->execute();
    }

    /**
     * @param int $max_lifetime
     * @return int|bool
     */
    public function gc(int $max_lifetime) {
        $old = time() - $lifetime; // Calculate what is to be deemed old
        $query = $this->db->prepare('DELETE FROM sessions WHERE access < ?');
        $query->bindValue(1, $old);

        return $query->execute();
    }

    /**
     * @param string $id
     * @param integer|null $user_id
     * @return bool
     */
    public function setUserId(string $id, $user_id) {
        $query = $this->db->prepare('UPDATE sessions SET users_id = ? WHERE id = ? AND location = ?');
        $query->bindValue(1, !is_null($user_id) ? $user_id : 0);
        $query->bindValue(2, $id);
        $query->bindValue(3, $this->location);

        return $query->execute();
    }

    // public function create_sid(): string {}
}
