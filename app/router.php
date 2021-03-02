<?php
/** @noinspection PhpUndefinedClassInspection */

namespace APP;

/**
 * Class Router
 * @package APP
 */
class Router {
	/**
	 * Application visitor session location
	 * @var string
	 */
	private $location = 'site';

	/**
	 * Router constructor.
	 */
	public function __construct() {
		if (isset($_SERVER['REQUEST_URI'])) {
			$segments = explode('/', $_SERVER['REQUEST_URI']);
			foreach ($segments as $segment) {
				switch (true) {
					case $segment === 'api':
						$this->location = 'site';
						break;
					case $segment === 'admin':
						$this->location = 'admin';
						break;
				}
			}
		}
	}

	/**
	 * Parse request method
	 * @param string $path
	 * @param array $params
	 * @return array
	 */
	public function parse($path, &$params = []) {
		$method = 'default';
		$parts = explode('.', $path);
		if (count($parts) > 1) {
			$method = array_pop($parts);
		}
		// prettier-ignore
		return [
			'class' => implode('', [
				'\\APP\\',
				implode('\\', $parts),
				'Controller'
			]),
			'method' => $method
		];
	}

	/**
	 * Get application visitor session location
	 * @return string
	 */
	public function getSessionLocation() {
		return $this->location;
	}
}
