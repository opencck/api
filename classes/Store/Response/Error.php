<?php
namespace API\Store\Response;

use API\Store\Response;

/**
 * List of default JSON RPC Error
 */
const JSONRPC_Errors = [
	'-32700' => [
		'message' => 'Parse error',
		'meaning' =>
			'Invalid JSON was received by the server. An error occurred on the server while parsing the JSON text.',
	],
	'-32600' => [
		'message' => 'Invalid Request',
		'meaning' => 'The JSON sent is not a valid Request object.',
	],
	'-32601' => [
		'message' => 'Method not found',
		'meaning' => 'The method does not exist / is not available.',
	],
	'-32602' => [
		'message' => 'Invalid params',
		'meaning' => 'Invalid method parameter(s).',
	],
	'-32603' => [
		'message' => 'Internal error',
		'meaning' => 'Internal JSON-RPC error.',
	],
	'-32000' => [
		'message' => 'Server error',
		'meaning' => 'Reserved for implementation-defined server-errors.',
	],
];

/**
 * Error item of API Store
 * @package API\Store\Response
 */
class Error extends Response {
	/**
	 * A Number that indicates the error type that occurred
	 * @var
	 */
	private $code;

	/**
	 * A String providing a short description of the error
	 * @var
	 */
	private $message;

	/**
	 * A Primitive or Structured value that contains additional information about the error
	 * @var string
	 */
	private $data;

	/**
	 * Error response constructor
	 * @param $code
	 * @param string $message
	 * @param array|string $data
	 * @param null|integer $id
	 */
	public function __construct($code, $message = '', $data = '', $id = null) {
		$this->code = $code;
		if (!$message) {
			if ($description = JSONRPC_Errors[$code]) {
				$message = $description['message'];
				if (!$data) {
					$data = $description['meaning'];
				}
			}
		}
		$this->message = $message;
		$this->data = $data;
		$this->id = $id;
	}

	/**
	 * Fetch final object
	 * @return array
	 */
	public function fetch() {
		return [
			'jsonrpc' => $this->jsonrpc,
			'error' => array_merge(
				[
					'code' => (int) $this->code,
					'message' => (string) $this->message,
				],
				$this->data ? ['data' => $this->data] : []
			),
			'id' => !is_null($this->id) ? (int) $this->id : null,
		];
	}
}
