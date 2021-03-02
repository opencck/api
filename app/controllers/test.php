<?php
namespace APP;

use API\App;
use API\Input;
use API\Session;

/**
 * Class TestController
 * @package APP
 */
class TestController extends Controller {
	/**
	 * @param Input $params
	 * @return array
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
