<?php
namespace APP;

use API\App;
use API\Input;
use API\Session;
use Exception;

/**
 * Class TestController
 * @package APP
 */
class TestController extends Controller {
    /**
     * @param Input $params
     * @return array
     * @throws Exception
     */
    public function default($params) {
        $session = Session::getInstance();
        $store = App::getInstance()->getStore();
        return [
            'params' => $params,
            'session' => $session,
            'store' => $store,
        ];
    }
}
