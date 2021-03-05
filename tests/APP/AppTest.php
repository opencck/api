<?php
namespace APP;

use PHPUnit\Framework\TestCase;
use API\App;

/**
 * Class AppTest
 * @package APP
 */
class AppTest extends TestCase {
	/**
	 * @var App
	 */
	private $app;

	public function setUp(): void {
		$_SERVER = array_merge($_SERVER, [
			'REQUEST_METHOD' => 'GET',
		]);
		$_REQUEST = array_merge($_REQUEST, [
			'method' => 'test',
			'params' => [
				'testing' => true,
			],
		]);
		$app = new App();
		$this->app = $app->init();
	}

	/**
	 * @runInSeparateProcess
	 * @return void
	 */
	public function testInit() {
		$router = new Router();
		$this->assertSame($this->app, $this->app::getInstance(), 'App is not a singleton');
		$this->assertEquals($router->getSessionLocation(), $this->app->getRouter()->getSessionLocation(), 'Bad router location');
		$this->assertNotNull($this->app->getStore(), 'Store not initialized');
	}

	/**
	 * @runInSeparateProcess
	 * @return object
	 */
	public function testExecute() {
		ob_start();
		$this->app->execute();
		$output = json_decode(ob_get_clean());

		$this->assertIsArray($output, 'Output string is not Array');
		$this->assertEquals((object) ['testing' => true], $output[0]->result->params, 'Error test response');
		$this->assertIsString($output[0]->result->session->id, 'Error getting visitor session id');

		return $output[0]->result;
	}
}
