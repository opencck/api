<?php
namespace API\DB\Cache;

use Memcached;
use Redis;

/**
 * Class Handler
 * @package API\DB\Cache
 */
class Handler {
	/**
	 * @var Memcached|Redis
	 */
	private $handler;

	/**
	 * Handler constructor
	 * @param Memcached|Redis $handler
	 */
	public function __construct($handler) {
		$this->handler = $handler;
	}

	public function getHandler() {
		return $this->handler;
	}

	/**
	 * Get data from cache
	 * @param string $key
	 * @return mixed|false
	 */
	public function get($key) {
		return $this->handler->get($key);
	}

	/**
	 * Set data in cache
	 * @param string $key
	 * @param mixed $value
	 * @param integer|null $timeout
	 * @return bool
	 */
	public function set($key, $value, $timeout = null) {
		return is_null($timeout)
			? $this->handler->set($key, $value)
			: $this->handler->set($key, $value, $timeout);
	}

	/**
	 * Drop data in cache
	 * @param string $key
	 * @return bool
	 * @throws \RedisException
	 */
	public function del($key) {
		if ($this->handler instanceof Redis) {
			/** @var \Redis $handler */
			$handler = $this->handler;

			return !!$handler->del($key);
		}

		if ($this->handler instanceof Memcached) {
			/** @var \Memcached $handler */
			$handler = $this->handler;

			return $handler->delete($key);
		}

		return false;
	}

	/**
	 * Check cache handler is active
	 * @return bool|string
	 * @throws \RedisException
	 */
	public function ping() {
		if ($this->handler instanceof Redis) {
			/** @var \Redis $handler */
			$handler = $this->handler;

			return $handler->ping();
		}

		if ($this->handler instanceof Memcached) {
			/** @var \Memcached $handler */
			$handler = $this->handler;

			return count($handler->getServerList()) > 0;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function close() {
		if ($this->handler instanceof Redis) {
			/** @var \Redis $handler */
			$handler = $this->handler;

			return $handler->close();
		}

		if ($this->handler instanceof Memcached) {
			/** @var \Memcached $handler */
			$handler = $this->handler;

			return $handler->quit();
		}

		return false;
	}
}
