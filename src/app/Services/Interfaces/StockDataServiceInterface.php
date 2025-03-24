<?php

namespace App\Services\Interfaces;

/**
 * StockDataServiceInterface
 *
 * This interface defines the contract for stock data management in the SoVest application.
 */
interface StockDataServiceInterface
{
    /**
     * Add a stock to track
     * 
     * @param string $symbol Stock symbol
     * @param string $name Stock name
     * @param string $description Stock description
     * @param string $sector Stock sector
     * @return bool Success status
     */
    public function addStock($symbol, $name, $description = '', $sector = '');

    /**
     * Remove a stock from tracking
     * 
     * @param string $symbol Stock symbol
     * @return bool Success status
     */
    public function removeStock($symbol);

    /**
     * Get all tracked stocks
     * 
     * @param bool $activeOnly Only return active stocks
     * @return array Array of stocks
     */
    public function getStocks($activeOnly = true);

    /**
     * Fetch stock data from API
     * 
     * @param string $symbol Stock symbol
     * @return array|bool Stock data or false on failure
     */
    public function fetchStockData($symbol);

    /**
     * Fetch and store stock data
     * 
     * @param string $symbol Stock symbol
     * @return bool Success status
     */
    public function fetchAndStoreStockData($symbol);

    /**
     * Store stock price in database
     * 
     * @param string $symbol Stock symbol
     * @param float $price Stock price
     * @param string $timestamp Timestamp (defaults to now)
     * @return bool Success status
     */
    public function storeStockPrice($symbol, $price, $timestamp = null);

    /**
     * Get latest price for a stock
     * 
     * @param string $symbol Stock symbol
     * @return float|bool Price or false if not found
     */
    public function getLatestPrice($symbol);

    /**
     * Get price history for a stock
     * 
     * @param string $symbol Stock symbol
     * @param int $days Number of days to look back
     * @return array Price history data
     */
    public function getPriceHistory($symbol, $days = 30);

    /**
     * Update all active stocks
     * 
     * @return array Results for each stock update
     */
    public function updateAllStocks();

    /**
     * Initialize default stocks
     * 
     * @return array Results of each stock addition
     */
    public function initializeDefaultStocks();
}