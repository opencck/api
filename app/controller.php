<?php
namespace APP;

use API\App;
use API\Input;
use API\Session;
use API\Store\Response\Result;
use Exception;

/**
 * Class Controller
 * @package APP
 */
class Controller {
	/**
	 * Visitor Session
	 * @var Session
	 */
	public $session;

	/**
	 * @var array
	 */
	private $mutations = [];

	/**
	 * @var array
	 */
	private $actions = [];

	/**
	 * Controller constructor.
	 * @throws Exception
	 */
	public function __construct() {
		$this->session = App::getInstance()->getSession();
	}

	/**
	 * Calling unknown method
	 * @param string $name
	 * @param array<array-key, Input> $arguments
	 * @throws Exception
	 */
	public function __call($name, $arguments) {
		throw new Exception("Unknown method '{$name}' of " . get_class($this), -32601);
	}

	/**
	 * @param string $method
	 * @param object|array $params
	 * @param string|integer|null $id
	 * @return Result
	 */
	public function execute($method, $params = [], $id = null) {
		if (!is_null($result = $this->{$method}(new Input($params), $id))) {
			return new Result($result, $id);
		} else {
			return new Result(
				array_merge(
					count($this->mutations) ? ['mutations' => $this->getMutations()] : [],
					count($this->actions) ? ['actions' => $this->getActions()] : []
				),
				$id
			);
		}
	}

	/**
	 * @param string $name mutation name
	 * @param mixed $data payload
	 * @return void
	 */
	public function addMutation($name, $data = []) {
		$this->mutations[] = ['name' => $name, 'data' => $data];
	}

	/**
	 * @param string $name action name
	 * @param mixed $data payload
	 * @return void
	 */
	public function addAction($name, $data = []) {
		$this->actions[] = ['name' => $name, 'data' => $data];
	}

	/**
	 * @return array
	 */
	public function getMutations() {
		return $this->mutations;
	}

	/**
	 * @return array
	 */
	public function getActions() {
		return $this->actions;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getModel($name) {
		/** @psalm-var class-string */
		$name = '\\APP\\' . $name . 'Model';
		return new $name();
	}
}
