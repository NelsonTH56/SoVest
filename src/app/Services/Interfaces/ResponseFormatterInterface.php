<?php
/**
 * SoVest - Response Formatter Interface
 *
 * This interface defines the contract for response formatting services
 * in the SoVest application. It provides methods for standardizing responses
 * across different formats (JSON, HTML, XML) and types (success, error).
 */

namespace App\Services\Interfaces;

interface ResponseFormatterInterface
{
    /**
     * Format a JSON response
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function json(array $data, int $statusCode = 200);
    
    /**
     * Format a JSON success response
     * 
     * @param string $message Success message
     * @param array $data Additional data
     * @param string|null $redirect Redirect URL
     * @return void
     */
    public function jsonSuccess(string $message, array $data = [], ?string $redirect = null);
    
    /**
     * Format a JSON error response
     * 
     * @param string $message Error message
     * @param array $errors Additional errors
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function jsonError(string $message, array $errors = [], int $statusCode = 400);
    
    /**
     * Format an HTML response
     * 
     * @param string $content HTML content
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function html(string $content, int $statusCode = 200);
    
    /**
     * Format an XML response
     * 
     * @param string $xml XML content
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function xml(string $xml, int $statusCode = 200);
    
    /**
     * Format a response for validation errors
     * 
     * @param array $errors Validation errors
     * @param string $format Response format (json, html, xml)
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function validationErrors(array $errors, string $format = 'json', int $statusCode = 422);
    
    /**
     * Format a redirect response
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code
     * @param array $flash Flash data to store in session
     * @return void
     */
    public function redirect(string $url, int $statusCode = 302, array $flash = []);
    
    /**
     * Detect if the current request is an API request
     * 
     * @return bool
     */
    public function isApiRequest(): bool;
    
    /**
     * Create a unified response that works for both API and web requests
     * 
     * @param string $view View to render for web requests
     * @param array $data Data for both API and web responses
     * @param string $successMessage Success message
     * @param string|null $redirectUrl URL to redirect to after web request
     * @return void
     */
    public function respond(string $view, array $data = [], string $successMessage = '', ?string $redirectUrl = null);
    
    /**
     * Create a unified error response that works for both API and web requests
     * 
     * @param string $view View to render for web requests
     * @param array $data Data for both API and web responses
     * @param string $errorMessage Error message
     * @param string|null $redirectUrl URL to redirect to after web request
     * @param int $statusCode HTTP status code for API requests
     * @return void
     */
    public function respondWithError(string $view, array $data = [], string $errorMessage = '', ?string $redirectUrl = null, int $statusCode = 400);
}