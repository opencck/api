<?php
namespace API\Store;

/**
 * Request item of API
 * @package API\Store
 */
class Request {
	/**
	 * Method name
	 * @var string
	 */
	public $method;

	/**
	 * Method params
	 * @var object|array
	 */
	public $params;

	/**
	 * Value of the request id member
	 * @var integer|null
	 */
	public $id;

	/**
	 * Request constructor.
	 * @param string $method
	 * @param object|array $params
	 * @param integer|null $id
	 */
	public function __construct($method, $params = [], $id = null) {
		$this->method = $method;
		$this->params = $params;
		$this->id = $id;
	}
}
