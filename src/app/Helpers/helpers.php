<?php

/**
 * Global helper functions for the application
 */

use App\Helpers\ApiLogHelper;

if (!function_exists('writeApiLog')) {
    /**
     * Write a message to the API log file
     *
     * @param string $message Message to write to log
     * @return void
     */
    function writeApiLog($message)
    {
        ApiLogHelper::writeApiLog($message);
    }
}
