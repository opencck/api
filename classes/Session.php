<?php
namespace API;

use API\DB\ConnectionProxy;
use API\Session\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * Visitor Session
 * @package API
 */
class Session {
	/**
	 * DataBase Abstract Layer Connection
	 * @var Connection|ConnectionProxy
	 */
	private $db;

	/**
	 * Session ID
	 * @var string
	 */
	public $id;

	/**
	 * Session location
	 * @var string
	 */
	private $location;

	/**
	 * Session user id
	 * @var integer|null
	 */
	private $user_id = null;

	/**
	 * Session user
	 * @var User|null
	 */
	public $user = null;

	/**
	 * Session data
	 * @var Input
	 */
	public $data;

	/**
	 * Session constructor
	 * @param string $location
	 */
	public function __construct($location = 'session') {
		$this->location = $location;

		// Check that this is cli
		if (php_sapi_name() === "cli") {
			$this->id = session_id();
			$this->data = new Input();
			return;
		}

		// Instantiate new Database object
		$this->db = DB::getInstance();

		// Set session name
		session_name($location);
		$this->location = $location;

		// Set session duration
		ini_set('session.gc_maxlifetime', $_ENV['SYS_COOKIE_LIFETIME']);
		ini_set('session.cookie_lifetime', $_ENV['SYS_COOKIE_LIFETIME']);
		session_set_cookie_params(
			$_ENV['SYS_COOKIE_LIFETIME'],
			$_ENV['SYS_COOKIE_PATH'],
			$_ENV['SYS_COOKIE_DOMAIN'],
			$_ENV['SYS_COOKIE_SECURE'] === 'true',
			$_ENV['SYS_COOKIE_HTTP_ONLY'] === 'true'
		);

		// Set handler to override SESSION
		session_set_save_handler(
			[$this, '_open'],
			[$this, '_close'],
			[$this, '_read'],
			[$this, '_write'],
			[$this, '_destroy'],
			[$this, '_gc']
		);

		// Start the session
		session_start();

		// Set session id
		$this->id = session_id();

		// Refresh visitor cookie
		setcookie(
			$location,
			$this->id,
			time() + $_ENV['SYS_COOKIE_LIFETIME'],
			$_ENV['SYS_COOKIE_PATH'],
			$_ENV['SYS_COOKIE_DOMAIN'],
			$_ENV['SYS_COOKIE_SECURE'] === 'true',
			$_ENV['SYS_COOKIE_HTTP_ONLY'] === 'true'
		);

		// Put data into input
		$this->data = new Input($_SESSION);
	}

	/**
	 * @return Session
	 * @throws \Exception
	 */
	public static function getInstance() {
		return App::getInstance()->getSession();
	}

	/**
	 * Get id of session
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get location of session
	 * @return mixed
	 */
	public function getLocation() {
		return $this->location;
	}

	/**
	 * Session @_open callback
	 * @return bool
	 */
	public function _open() {
		return $this->db->isConnected();
	}

	/**
	 * Session @_close callback
	 * @return bool
	 */
	public function _close() {
		// Close the database connection
		$this->db->close();
		return !$this->db->isConnected();
	}

	/**
	 * Get Session data @_read
	 * @param string $id
	 * @return string
	 * @throws DBALException
	 */
	public function _read($id) {
		$query = $this->db->query('SELECT data, users_id FROM sessions WHERE id = ? AND location = ?');
		$query->bindValue(1, $id);
		$query->bindValue(2, $this->location);
		$query->execute();

		if ($row = $query->fetch()) {
			if ($row['users_id']) {
				$this->user = User::load($row['users_id']);
			}
			return $row['data'];
		} else {
			return '';
		}
	}

	/**
	 * Update Session data @_write
	 * @param string $id
	 * @param string $data
	 * @return bool
	 * @throws DBALException
	 */
	public function _write($id, $data) {
		// Check that this is testing
		/*if (isset($_ENV['APP_ENV']) && endsWith($_ENV['APP_ENV'], 'Testing')) {
			return true;
		}*/
		$query = $this->db->prepare('REPLACE INTO sessions VALUES (?, ?, ?, ?, ?)');
		$query->bindValue(1, $id);
		$query->bindValue(2, $this->location);
		$query->bindValue(3, time());
		$query->bindValue(4, isset($this->user->id) ? $this->user->id : 0);
		$query->bindValue(5, $data);

		return $query->execute();
	}

	/**
	 * Delete Session data @_destroy
	 * @param string $id
	 * @return bool
	 * @throws DBALException
	 */
	public function _destroy($id) {
		// Check that this is testing
		if (isset($_ENV['APP_ENV']) && endsWith($_ENV['APP_ENV'], 'Testing')) {
			return true;
		}
		$query = $this->db->prepare('DELETE FROM sessions WHERE id = ? AND location = ?');
		$query->bindValue(1, $id);
		$query->bindValue(2, $this->location);

		return $query->execute();
	}

	/**
	 * The garbage collector callback @_gc
	 * @param string $lifetime
	 * @return bool
	 * @throws DBALException
	 */
	public function _gc($lifetime) {
		$old = time() - intval($lifetime); // Calculate what is to be deemed old
		$query = $this->db->prepare('DELETE FROM sessions WHERE access < ?');
		$query->bindValue(1, $old);

		return $query->execute();
	}

	/**
	 * Get value from session
	 * @param string $key
	 * @param mixed $default
	 * @param string|null $filter
	 * @return mixed
	 */
	public function get($key, $default = null, $filter = null) {
		return $this->data->get($key, $default, $filter);
	}

	/**
	 * Set value into session
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set($key, $value) {
		$_SESSION[$key] = $value;
		$this->data->set($key, $value);
	}

	/**
	 * Delete value from session
	 * @param string $key
	 * @return void
	 */
	public function delete($key) {
		unset($_SESSION[$key]);
	}

	/**
	 * Set session user
	 * @param User|null $user
	 * @return bool
	 * @throws DBALException
	 */
	public function setUser($user) {
		$this->user = $user;
		return $this->setUserId(isset($user->id) ? $user->id : null);
	}

	/**
	 * Get session user
	 * @return User|null
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * User session auth
	 * @param integer|null $user_id
	 * @return bool
	 * @throws DBALException
	 */
	private function setUserId($user_id) {
		$this->user_id = $user_id;
		$query = $this->db->prepare('UPDATE sessions SET users_id = ? WHERE id = ? AND location = ?');
		$query->bindValue(1, !is_null($user_id) ? $user_id : 0);
		$query->bindValue(2, $this->id);
		$query->bindValue(3, $this->location);

		return $query->execute();
	}

	/**
	 * Get session user id
	 * @return mixed
	 */
	public function getUserId() {
		return $this->user_id;
	}

	/**
	 * Get session data
	 * @return Input
	 */
	public function getData() {
		return $this->data;
	}
}
