<?php
namespace API\Store\Response;

use API\Store\Response;

/**
 * Result item of API Store
 * @package API\Store\Response
 */
class Result extends Response {
	/**
	 * Result data
	 * @var
	 */
	public $item;

	/**
	 * Result constructor
	 * @param $item
	 * @param string|integer|null $id
	 */
	public function __construct($item, $id = null) {
		$this->item = $item;
		$this->id = $id;
	}

	/**
	 * Fetch final object
	 * @return array
	 */
	public function fetch() {
		return array_merge(
			[
				'jsonrpc' => $this->jsonrpc,
				'result' => $this->item,
			],
			!is_null($this->id) ? ['id' => $this->id] : []
		);
	}
}
