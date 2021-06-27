<?php
namespace API;

use API\Store\Response\Result;
use API\Store\Response\Error;
use API\Store\Request;

/**
 * API Store
 * @package API
 */
class Store {
    /**
     * Requests array
     * @var array
     */
    private $request = [];

    /**
     * Responses array
     * @var array
     */
    private $response = [];

    /**
     * Checking input request
     * @param object $item
     * @throws \Exception
     * @return void
     */
    public function checkRequest($item) {
        if (!isset($item->method) || !is_string($item->method)) {
            throw new \Exception('Invalid params. Variable method must contain a string', -32602);
        }
        foreach (explode('.', $item->method) as $segment) {
            if (!preg_match('/^[a-zA-Z\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $segment)) {
                throw new \Exception(
                    'Invalid params. Variable method must match regex /^[a-zA-Z\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',
                    -32602
                );
            }
        }
        if (isset($item->params) && !(is_object($item->params) || is_array($item->params))) {
            throw new \Exception(
                'Invalid params. Variable params, if included, must contain a structured value',
                -32602
            );
        }
        if (isset($item->id) && !(is_string($item->id) || is_numeric($item->id) || is_null($item->id))) {
            throw new \Exception(
                'Invalid params. Variable id, if included, must contain a String, Number, or NULL value',
                -32602
            );
        }
    }

    /**
     * Add API request item
     * @param object $item
     * @throws \Exception
     * @return void
     */
    public function addRequest($item) {
        $this->checkRequest($item);
        if (isset($item->params)) {
            if (isset($item->id)) {
                $this->request[] = new Request($item->method, $item->params, $item->id);
            } else {
                $this->request[] = new Request($item->method, $item->params);
            }
        } elseif (isset($item->id)) {
            $this->request[] = new Request($item->method, [], $item->id);
        } else {
            $this->request[] = new Request($item->method);
        }
    }

    /**
     * Fetch API request
     * @return array
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Add response result item
     * @param Result $result
     * @return void
     */
    public function addResult(Result $result) {
        $this->response[] = $result->fetch();
    }

    /**
     * Add error result item
     * @param Error $error
     * @return void
     */
    public function addError(Error $error) {
        $this->response[] = $error->fetch();
    }

    /**
     * Fetch final set of objects
     * @return array
     */
    public function getResponse() {
        return $this->response;
    }
}
