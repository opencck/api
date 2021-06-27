<?php
namespace API\Session\Handler;

use API\DB\Cache;
use API\Session;

/**
 * Redis Session Handler
 * @package API\Session\Handler
 */
class CacheHandler {
    /**
     * @var Cache $cache
     */
    protected $cache;

    /**
     * Session location
     * @var string
     */
    protected $location;

    /**
     * Cache handlers (cache services)
     * @var string
     */
    protected $handers;

    /**
     * Cache Session Handler constructor
     * @param string $location
     */
    public function __construct($location) {
        $this->cache = Cache::getInstance();
        $this->location = $location;
        $this->handers = array_slice(
            array_keys($this->cache->getHandlers($_ENV['SESSIONS_HANDLER'] ? [$_ENV['SESSIONS_HANDLER']] : null)),
            0,
            1
        );
    }

    /**
     * @param string $path
     * @param string $name
     * @return bool
     */
    public function open(string $path, string $name): bool {
        return !!$this->cache->ping($this->handers);
    }

    /**
     * @return bool
     */
    public function close(): bool {
        return $this->cache->close($this->handers);
    }

    /**
     * @param string $id
     * @return array
     */
    public function read(string $id): array {
        if ($row = $this->cache->get($this->location . ':' . $id, $this->handers)) {
            $session = json_decode($row);

            return [
                'data' => isset($session->data) ? $session->data : '',
                'users_id' => isset($session->users_id) ? $session->users_id : 0,
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
        return $this->cache->set(
            $this->location . ':' . $id,
            json_encode([
                'data' => $data,
                'users_id' => $user_id,
            ]),
            null,
            $this->handers
        );
    }

    /**
     * @param string $id
     * @return bool
     */
    public function destroy(string $id): bool {
        return $this->cache->del($this->location . ':' . $id, $this->handers);
    }

    /**
     * @param int $max_lifetime
     * @return int|bool
     */
    public function gc(int $max_lifetime) {
        return true;
    }

    /**
     * @param string $id
     * @param integer|null $user_id
     * @return bool
     */
    public function setUserId(string $id, $user_id) {
        if ($row = $this->cache->get($this->location . ':' . $id, $this->handers)) {
            $session = json_decode($row);
            $session->users_id = !is_null($user_id) ? $user_id : 0;
            return $this->cache->set($this->location . ':' . $id, json_encode($session), null, $this->handers);
        }
        return false;
    }

    // public function create_sid(): string {}
}
