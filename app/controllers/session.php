<?php
namespace APP;

use API\Input;
use API\Session;

/**
 * Class SessionController
 * @package APP
 */
class SessionController extends Controller {
    /**
     * @param Input $params
     * @param string|integer|null $id
     * @return Session|null
     */
    public function default($params, $id = null) {
        return $this->session;
    }

    /**
     * @return array
     */
    public function test() {
        $this->session->set('test', ['hello']);
        return ['test' => $this->session->get('test')];
    }
}
