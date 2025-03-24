<?php

namespace App\Services\Interfaces;

use Exception;

/**
 * SearchServiceInterface
 *
 * This interface defines the contract for search functionality in the SoVest application.
 */
interface SearchServiceInterface
{
    /**
     * Perform a search based on the query and type
     * 
     * @param string $query The search query
     * @param string $type The type of search (stocks, users, predictions, all)
     * @param string $prediction Optional prediction type filter
     * @param string $sort Sort order (relevance, date_desc, accuracy, votes)
     * @param int $limit The maximum number of results to return
     * @param int $offset The offset for pagination
     * @return array The search results
     * @throws Exception If an error occurs during the search
     */
    public function performSearch($query, $type = 'stocks', $prediction = '', $sort = 'relevance', $limit = 10, $offset = 0);

    /**
     * Get search suggestions for autocomplete based on the query and type
     * 
     * @param string $query The search query
     * @param string $type The type of search (stocks, users, predictions, combined)
     * @param int $limit The maximum number of suggestions to return
     * @return array The search suggestions
     * @throws Exception If an error occurs while getting suggestions
     */
    public function getSuggestions($query, $type = 'combined', $limit = 10);

    /**
     * Get stock suggestions for autocomplete
     * 
     * @param string $query The search query
     * @param int $limit The maximum number of suggestions to return
     * @return array The stock suggestions
     */
    public function getStockSuggestions($query, $limit = 10);

    /**
     * Get user suggestions for autocomplete
     * 
     * @param string $query The search query
     * @param int $limit The maximum number of suggestions to return
     * @return array The user suggestions
     */
    public function getUserSuggestions($query, $limit = 10);

    /**
     * Get prediction suggestions for autocomplete
     * 
     * @param string $query The search query
     * @param int $limit The maximum number of suggestions to return
     * @return array The prediction suggestions
     */
    public function getPredictionSuggestions($query, $limit = 10);

    /**
     * Get combined suggestions for autocomplete
     * 
     * @param string $query The search query
     * @param int $limit The maximum number of suggestions to return
     * @return array The combined suggestions
     */
    public function getCombinedSuggestions($query, $limit = 10);

    /**
     * Save a search to the user's favorites
     * 
     * @param string $query The search query
     * @param string $type The type of search
     * @return bool True if the search was saved successfully
     * @throws Exception If the user is not authenticated or if an error occurs
     */
    public function saveSearch($query, $type = 'stocks');

    /**
     * Get the user's search history
     * 
     * @param int $limit The maximum number of results to return
     * @param int $offset The offset for pagination
     * @return array The search history
     * @throws Exception If the user is not authenticated or if an error occurs
     */
    public function getSearchHistory($limit = 20, $offset = 0);

    /**
     * Clear the user's search history
     * 
     * @return bool True if the history was cleared successfully
     * @throws Exception If the user is not authenticated or if an error occurs
     */
    public function clearSearchHistory();

    /**
     * Remove a specific saved search
     * 
     * @param int $savedSearchId The ID of the saved search to remove
     * @return bool True if the search was removed successfully
     * @throws Exception If the user is not authenticated or if an error occurs
     */
    public function removeSavedSearch($savedSearchId);

    /**
     * Save a search to the user's history
     * 
     * @param string $query The search query
     * @param string $type The type of search
     * @return bool True if the search was saved to history successfully
     */
    public function saveToHistory($query, $type = 'stocks');
}