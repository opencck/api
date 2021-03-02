<?php
namespace API\Session;

use API\DB;
use API\Session;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Exception;

/**
 * Class User
 * @package API\Session
 */
class User {
	/**
	 * User id
	 * @var int
	 */
	public $id = 0;

	/**
	 * User state
	 * @var int
	 */
	public $state = 0;

	/**
	 * Username
	 * @var string
	 */
	public $username = '';

	/**
	 * Password base64 sha256 hash
	 * @var string
	 */
	private $password = '';

	/**
	 * Randomly generated hash sold
	 * @var string
	 */
	private $sold = '';

	/**
	 * @var string
	 */
	public $email = '';

	/**
	 * @var string
	 */
	public $role = 'user';

	/**
	 * @var string
	 */
	public $date_create = '';

	/**
	 * User constructor.
	 * @param array $params
	 */
	public function __construct($params = []) {
		foreach ($params as $key => $value) {
			$this->{$key} = $value;
		}
	}

	/**
	 * @return User|null
	 * @throws Exception
	 */
	public function getInstance() {
		$session = Session::getInstance();
		return !is_null($session) ? $session->getUser() : null;
	}

	/**
	 * @param int $state
	 * @return User
	 */
	public function setState(int $state) {
		$this->state = $state;
		return $this;
	}

	/**
	 * @param string $username
	 * @return User
	 */
	public function setUsername(string $username) {
		$this->username = $username;
		return $this;
	}

	/**
	 * Set password base64 sha256 hash
	 * @param string $password
	 * @return User
	 * @throws Exception
	 */
	public function setPassword(string $password) {
		$this->sold = substr(base64_encode(random_bytes(32)),0, 16);
		$this->password = base64_encode(hash_hmac('sha256', $this->sold . '.' . $password, $_ENV['SYS_SECRET'], true));
		return $this;
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function checkPassword(string $password) {
		return $this->password ==
			base64_encode(hash_hmac('sha256', $this->sold . '.' . $password, $_ENV['SYS_SECRET'], true));
	}

	/**
	 * @param string $email
	 * @return User
	 */
	public function setEmail(string $email) {
		$this->email = $email;
		return $this;
	}

	/**
	 * Save user
	 * @return Statement|int
	 */
	public function save() {
		$db = DB::getInstance();
		$query = $db->createQueryBuilder();

		if ($this->id) {
			// Update user
			$query
				->update('users', 'u')
				->set('u.state', $query->createNamedParameter($this->state))
				->set('u.username', $query->createNamedParameter($this->username))
				->set('u.password', $query->createNamedParameter($this->password))
				->set('u.sold', $query->createNamedParameter($this->sold))
				->set('u.email', $query->createNamedParameter($this->email))
				->set('u.role', $query->createNamedParameter($this->role))
				->set('u.date_create', $query->createNamedParameter($this->date_create))
				->where('u.id = '.$db->quote($this->id));
			$return = $query->execute();
		} else {
			// New user
			$date_create = date('Y-m-d H:i:s');
			$query->insert('users')->values([
				'state' => $db->quote($this->state),
				'username' => $db->quote($this->username),
				'password' => $db->quote($this->password),
				'sold' => $db->quote($this->sold),
				'email' => $db->quote($this->email),
				'role' => $db->quote($this->role),
				'date_create' => $db->quote($date_create),
			]);
			if ($return = $query->execute()) {
				$this->id = $db->lastInsertId();
				$this->date_create = $date_create;
			}
		}

		return $return;
	}

	/**
	 * @param integer $users_id
	 * @return User
	 * @throws DBALException
	 */
	static function load($users_id) {
		$db = DB::getInstance();
		$query = $db->query('SELECT * FROM users WHERE state = 1 AND id = ?');
		$query->bindValue(1, $users_id);
		$query->execute();

		return new User($query->fetch());
	}
}
