<?php
namespace API;

use API\Store\Response\Error;

/**
 * Class Exception
 * @package API
 */
class Exception {
    /**
     * Get error object
     * @param \Exception $e
     * @param null $id
     * @return Error
     */
    private static function error($e, $id = null) {
        if (isset($_ENV['SYS_DEBUG']) && $_ENV['SYS_DEBUG'] == 'true') {
            return new Error(
                $e->getCode(),
                $e->getMessage(),
                [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                    'previous' => $e->getPrevious(),
                ],
                $id
            );
        } else {
            return new Error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Terminating error handler
     * @param \Exception $e
     * @return void
     */
    public static function global_handler($e) {
        $app = App::getInstance();
        $store = $app->getStore();

        $store->addError(self::error($e));
        $app->output();

        exit();
    }

    /**
     * Local error handler
     * @param \Exception $e
     * @param null $id
     * @return Error
     */
    public static function local_handler($e, $id = null) {
        return self::error($e, $id);
    }
}
