<?php
/**
 * Class autoload principle
 *
 * @noinspection PhpIncludeInspection
 */
spl_autoload_register(function ($class) {
	$parts = explode('\\', $class);
	$namespace = array_shift($parts);
	switch ($namespace) {
		case 'API':
			if ($filename = array_pop($parts)) {
				include implode('', [
					PATH_INCLUDES . '/classes/',
					implode('/', $parts) . (count($parts) ? '/' : ''),
					$filename . '.php',
				]);
			}
			break;
		case 'APP':
			if ($filename = strtolower(array_pop($parts))) {
				foreach (
					[
						'controller' => ['/app/', '/app/controllers/'],
						'model' => ['/app/', '/app/models/'],
						'helper' => ['/app/', '/app/helpers/'],
						'router' => ['/app/'],
					]
					as $pattern => $paths
				) {
					if ($filename == $pattern) {
						$paths = array_merge($paths, ['/vendor/opencck/api/app/']);
					} elseif (endsWith($filename, $pattern)) {
						$filename = str_replace($pattern, '', $filename);
					}
					foreach ($paths as $path) {
						$file = strtolower(
							implode('', [
								PATH_ROOT . $path,
								implode('/', $parts) . (count($parts) ? '/' : ''),
								$filename . '.php',
							])
						);
						if (is_file($file)) {
							require_once $file;
							break;
						}
					}
				}
			}
			break;
		default:
			throw new \Exception('Unknown namespace ' . $class);
	}
});

/**
 * Global exception handler
 */
set_exception_handler('API\Exception::global_handler');
set_error_handler(function(){}); // todo set_error_handler

/**
 * Load configuration
 */
$dotenv = Dotenv\Dotenv::createMutable(PATH_ROOT);
$dotenv->load();
if (isset($_ENV['APP_ENV'])) {
	$paths = [
		'APITesting' => PATH_INCLUDES,
		'APPTesting' => PATH_ROOT
	];
	foreach (Dotenv\Dotenv::createArrayBacked($paths[$_ENV['APP_ENV']] . '/tests')->load() as $key => $value) {
		$_ENV[$key] = $value;
	}
}

/**
 * Enable system errors
 */
if (isset($_ENV['SYS_DEBUG']) && $_ENV['SYS_DEBUG'] == 'true') {
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);
}
