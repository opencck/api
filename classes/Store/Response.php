<?php
namespace API\Store;

/**
 * Response item of API
 * @package API\Store
 */
class Response {
    /**
     * A String specifying the version of the JSON-RPC protocol
     * @var string
     */
    public $jsonrpc = '2.0';

    /**
     * Value of the request id member
     * @var null|integer
     */
    public $id = null;

    /**
     * Set value of the request id member
     * @param null|integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
    }
}
