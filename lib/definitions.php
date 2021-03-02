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
						'router' => [],
					]
					as $pattern => $paths
				) {
					if ($filename == $pattern) {
						$paths = array_merge(['/app/', '/includes/app/'], $paths);
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
 * Global error handler
 */
set_exception_handler('API\Exception::global_handler');

/**
 * Load configuration
 */
$dotenv = Dotenv\Dotenv::createImmutable(PATH_ROOT);
$dotenv->load();
if (isset($_ENV['APP_ENV'])) {
	switch ($_ENV['APP_ENV']) {
		case 'APITesting':
			$dotenv = Dotenv\Dotenv::createImmutable(PATH_INCLUDES . '/tests');
			$_ENV = array_merge($_ENV, $dotenv->load());
			break;
		case 'APPTesting':
			$dotenv = Dotenv\Dotenv::createImmutable(PATH_ROOT . '/tests');
			$_ENV = array_merge($_ENV, $dotenv->load());
			break;
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
