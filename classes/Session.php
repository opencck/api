<?php
namespace API;

use API\DB\Cache;
use API\DB\ConnectionProxy;
use API\Session\Handler;
use API\Session\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * Visitor Session
 * @package API
 */
class Session {
    /**
     * Session handler
     * @var Handler|null
     */
    private $hander = null;

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
        if (php_sapi_name() === 'cli') {
            $this->id = session_id();
            $this->data = new Input();
            return;
        }

        // Check cache session handler
        $cache = Cache::getInstance();
        // If it was not possible to receive cache go to the session in the database
        if (count($cache->getHandlers($_ENV['SESSIONS_HANDLER'] ? [$_ENV['SESSIONS_HANDLER']] : null))) {
            // Set Redis Session Handler
            $this->hander = new Handler\CacheHandler($this->location);
        } else {
            // Set DB Session Handler
            $this->hander = new Handler\DBHandler($this->location);
        }

        session_set_save_handler(
            [$this, '_open'],
            [$this, '_close'],
            [$this, '_read'],
            [$this, '_write'],
            [$this, '_destroy'],
            [$this, '_gc']
        );

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

        // Set session name
        session_name($location);
        // Start the session
        session_start();

        // Set session id
        $this->id = session_id();
        // Put data into input
        $this->data = new Input($_SESSION);

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
    }

    /**
     * @return Session
     * @throws \Exception
     */
    public static function getInstance() {
        return App::getInstance()->getSession();
    }

    /**
     * Set handler to override SESSION
     */
    private function sessionSetDBSaveHandler() {
        // Instantiate new Database object
        $this->db = DB::getInstance();

        session_set_save_handler(
            [$this, '_open'],
            [$this, '_close'],
            [$this, '_read'],
            [$this, '_write'],
            [$this, '_destroy'],
            [$this, '_gc']
        );
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
     * @param string $path
     * @param string $name
     * @return bool
     */
    public function _open($path, $name) {
        return $this->hander->open($path, $name);
    }

    /**
     * Session @_close callback
     * @return bool
     */
    public function _close() {
        return $this->hander->close();
    }

    /**
     * Get Session data @_read
     * @param string $id
     * @return string
     * @throws DBALException
     */
    public function _read($id) {
        $session = $this->hander->read($id);

        // Load session user
        if ($session['users_id']) {
            $this->user = User::load($session['users_id']);
            $this->user_id = $session['users_id'];
        }

        return $session['data'];
    }

    /**
     * Update Session data @_write
     * @param string $id
     * @param string $data
     * @return bool
     * @throws DBALException
     */
    public function _write($id, $data) {
        return $this->hander->write($id, $data, $this->user_id);
    }

    /**
     * Delete Session data @_destroy
     * @param string $id
     * @return bool
     * @throws DBALException
     */
    public function _destroy($id) {
        return $this->hander->destroy($id);
    }

    /**
     * The garbage collector callback @_gc
     * @param string $lifetime
     * @return bool
     * @throws DBALException
     */
    public function _gc($lifetime) {
        return $this->hander->gc(intval($lifetime));
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
        $this->data->delete($key);
    }

    /**
     * Set session user
     * @param User|null $user
     * @return bool
     * @throws DBALException
     */
    public function setUser($user) {
        $this->user = $user;
        $this->user_id = isset($user->id) ? $user->id : null;
        return $this->hander->setUserId($this->id, $this->user_id);
    }

    /**
     * Get session user
     * @return User|null
     */
    public function getUser() {
        return $this->user;
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
