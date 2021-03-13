<?php
namespace API\DB;

use API\DB\Cache\Handler;

/**
 * Class Cache
 * @package API\DB
 */
class Cache {
	/**
	 * Singleton instance
	 * @var Cache|null
	 */
	private static $_instance = null;

	/**
	 * Cache handlers
	 * @var Handler[]
	 */
	private $handlers = [];

	/**
	 * Cache constructor
	 * @param string[] $services
	 */
	public function __construct($services = ['redis', 'memcached']) {
		foreach ($services as $service) {
			try {
				switch ($service) {
					case 'redis':
						if ($_ENV['REDIS_HOST']) {
							$redis = new \Redis();
							$redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT'] ?? 6379);
							
							if ($_ENV['REDIS_PASSWORD']) {
								$redis->auth($_ENV['REDIS_PASSWORD']);
							}

							if ($_ENV['REDIS_DB']) {
								$redis->swapdb($redis->getDbNum(), $_ENV['REDIS_DB']);
							}

							if ($redis->ping()) {
								$this->handlers[$service] = new Handler($redis);
							}
						}
						break;
					case 'memcached':
						$memcached = new \Memcached();
						$memcached->addServer(
							$_ENV['MEMCACHED_HOST'],
							$_ENV['MEMCACHED_PORT'] ?? 11211,
							$_ENV['MEMCACHED_WEIGHT'] ?? 100
						);
						$statuses = $memcached->getStats();

						if (isset($statuses[$_ENV['MEMCACHED_HOST'] . ':' . ($_ENV['MEMCACHED_PORT'] ?? 11211)])) {
							$this->handlers[$service] = new Handler($memcached);
						}
						break;
				}
			} catch (\Exception $e) {}
		}
		self::$_instance = $this;
	}

	/**
	 * @return mixed
	 */
	public static function getInstance() {
		if (self::$_instance != null) {
			return self::$_instance;
		}
		return new self();
	}

	/**
	 * Get cache handlers
	 * @param string[]|null $cacheHandlers
	 * @return Handler[]
	 */
	public function getHandlers($cacheHandlers = null) {
		return !is_array($cacheHandlers)
			? $this->handlers
			: array_intersect_key($this->handlers, array_flip($cacheHandlers));
	}

	/**
	 * Get data from cache
	 * @param string $key
	 * @param string[]|null $cacheHandlers
	 * @return mixed|false
	 */
	public function get($key, $cacheHandlers = null) {
		foreach ($this->getHandlers($cacheHandlers) as $handler) {
			$value = $handler->get($key);
			if ($value !== false) {
				return $value;
			}
		}
		return false;
	}

	/**
	 * Set data to cache
	 * @param string $key
	 * @param mixed $value
	 * @param integer|null $timeout
	 * @param string[]|null $cacheHandlers
	 * @return bool
	 */
	public function set($key, $value, $timeout = null, $cacheHandlers = null) {
		$handlers = $this->getHandlers($cacheHandlers);
		if (count($handlers) === 0) {
			return false;
		}

		$success = true;
		foreach ($handlers as $handler) {
			$success =
				$success && (is_null($timeout) ? $handler->set($key, $value) : $handler->set($key, $value, $timeout));
		}
		return $success;
	}

	/**
	 * Drop data in cache
	 * @param string $key
	 * @param string[]|null $cacheHandlers
	 */
	public function del($key, $cacheHandlers = null) {
		$handlers = $this->getHandlers($cacheHandlers);
		if (count($handlers) === 0) {
			return false;
		}

		$success = true;
		foreach ($handlers as $handler) {
			$success = $success && $handler->del($key);
		}
		return $success;
	}

	/**
	 * Ping cache handlers
	 * @param string[]|null $cacheHandlers
	 * @return bool
	 * @throws \RedisException
	 */
	public function ping($cacheHandlers = null) {
		foreach ($this->getHandlers($cacheHandlers) as $handler) {
			if ($handler->ping()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check cache availability
	 * @return bool
	 */
	public function isActive() {
		return count($this->handlers) > 0;
	}

	/**
	 * @param string[]|null $cacheHandlers
	 * @return bool
	 */
	public function close($cacheHandlers = null) {
		$success = true;
		foreach ($this->getHandlers($cacheHandlers) as $handler) {
			$success = $success && $handler->close();
		}
		return $success;
	}
}
