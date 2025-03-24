<?php

namespace App\Helpers;

/**
 * Helper functions for API logging
 */
class ApiLogHelper
{
    /**
     * Write a message to the API log file
     *
     * @param string $message Message to write to log
     * @return void
     */
    public static function writeApiLog($message)
    {
        $logFile = config('stock_api_config.STOCK_API_LOG_FILE', __DIR__ . '/../../logs/stock_api.log');
        
        // Create directory if it doesn't exist
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}