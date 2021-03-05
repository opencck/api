<?php
/** @noinspection PhpUndefinedClassInspection */

namespace API;

use APP\Router;
use Exception;
use API\Exception as APIException;

/**
 * Class App
 * @package API
 */
class App {
	/**
	 * Singleton instance
	 * @var App|null
	 */
	private static $_instance = null;

	/**
	 * Visitor session
	 * @var Session
	 */
	private $session;

	/**
	 * Application store
	 * @var Store
	 */
	private $store;

	/**
	 * Application router
	 * @var Router
	 */
	private $router;

	/**
	 * Application constructor
	 */
	public function __construct() {
		$this->store = new Store();
		$this->router = new Router();
		$this->session = new Session($this->router->getSessionLocation());

		self::$_instance = $this;
	}

	/**
	 * @param bool $inheritance
	 * @return App
	 */
	public static function getInstance($inheritance = true) {
		if ($inheritance && self::$_instance != null) {
			return self::$_instance;
		}
		return new self();
	}

	/**
	 * Get visitor session
	 * @return Session
	 * @throws Exception
	 */
	public function getSession() {
		/*if (is_null($this->session) && php_sapi_name() != "cli") {
			throw new Exception('Session is not initialized', 403);
		}*/
		return $this->session;
	}

	/**
	 * Get application store
	 * @return Store
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * Get application router
	 * @return Router
	 */
	public function getRouter() {
		return $this->router;
	}

	/**
	 * Application initialization
	 * @return $this
	 */
	public function init() {
		try {
			// Session initialization
			//$this->session = new Session($this->router->getSessionLocation());

			switch ($_SERVER['REQUEST_METHOD']) {
				case 'POST':
					switch (true) {
						case startsWith($_SERVER['CONTENT_TYPE'], 'application/json'):
							// Takes raw data from the request
							$json = file_get_contents('php://input');
							$data = json_decode($json);
							if (is_array($data)) {
								foreach ($data as $item) {
									$this->store->addRequest($item);
								}
							} elseif (is_object($data)) {
								$this->store->addRequest($data);
							} elseif (!$data) {
								throw new Exception('Parse error', -32700);
							} else {
								throw new Exception('Invalid Request', -32600);
							}
							break;
						default:
							$this->store->addRequest((object) $_REQUEST);
							break;
					}
					break;
				case 'GET':
				default:
					$this->store->addRequest((object) $_REQUEST);
					break;
			}
		} catch (Exception $e) {
			APIException::global_handler($e);
		}
		return $this;
	}

	/**
	 * Application executing
	 * @param bool $return
	 * @param bool $silent
	 * @return void|array
	 */
	public function execute($return = false, $silent = false) {
		foreach ($this->store->getRequest() as $item) {
			$router = $this->router->parse($item->method, $item->params);
			try {
				if (!class_exists($router['class'], true)) {
					throw new Exception("Class {$router['class']} not found", -32601);
				}
				$controller = new $router['class']();
				// Executing of controller method (prettier-ignore)
				$this->store->addResult(
					$controller->execute(
						$router['method'],
						$item->params,
						$item->id
					)
				);
			} catch (Exception $e) {
				$this->store->addError(APIException::local_handler($e, $item->id));
			}
		}
		if (!$silent) {
			return $this->output($return);
		}
	}

	/**
	 * Application output
	 * @param bool $return
	 * @return void|array
	 */
	public function output($return = false) {
		if ($return) {
			return $this->store->getResponse();
		} else {
			if (php_sapi_name() != 'cli') {
				header('Content-Type: application/json');
			}
			echo json_encode($this->store->getResponse());
		}
	}
}
