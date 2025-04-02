<?php
/**
 * SoVest - Response Formatter Service
 *
 * This service standardizes response structures across the application
 * for different formats (JSON, HTML, XML) and response types (success, error).
 */

namespace App\Services;

use App\Services\Interfaces\ResponseFormatterInterface;

class ResponseFormatter implements ResponseFormatterInterface
{
    /**
     * @var array Content type headers for different formats
     */
    protected $contentTypes = [
        'json' => 'application/json',
        'html' => 'text/html; charset=UTF-8',
        'xml' => 'application/xml',
        'text' => 'text/plain',
    ];
    
    /**
     * @var object|null View renderer for HTML responses (optional)
     */
    protected $viewRenderer = null;
    
    /**
     * Constructor
     *
     * @param object|null $viewRenderer Optional view renderer
     */
    public function __construct($viewRenderer = null)
    {
        $this->viewRenderer = $viewRenderer;
    }
    
    /**
     * Format a JSON response
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function json(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: ' . $this->contentTypes['json']);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Format a JSON success response
     * 
     * @param string $message Success message
     * @param array $data Additional data
     * @param string|null $redirect Redirect URL
     * @return void
     */
    public function jsonSuccess(string $message, array $data = [], ?string $redirect = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
        
        if ($redirect) {
            $response['redirect'] = $redirect;
        }
        
        $this->json($response);
    }
    
    /**
     * Format a JSON error response
     * 
     * @param string $message Error message
     * @param array $errors Additional errors
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function jsonError(string $message, array $errors = [], int $statusCode = 400)
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
    
    /**
     * Format an HTML response
     * 
     * @param string $content HTML content
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function html(string $content, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: ' . $this->contentTypes['html']);
        echo $content;
        exit;
    }
    
    /**
     * Format an XML response
     * 
     * @param string $xml XML content
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function xml(string $xml, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: ' . $this->contentTypes['xml']);
        echo $xml;
        exit;
    }
    
    /**
     * Format a response for validation errors
     * 
     * @param array $errors Validation errors
     * @param string $format Response format (json, html, xml)
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function validationErrors(array $errors, string $format = 'json', int $statusCode = 422)
    {
        switch (strtolower($format)) {
            case 'json':
                $this->jsonError('Validation failed', $errors, $statusCode);
                break;
                
            case 'xml':
                $xml = $this->errorsToXml($errors);
                $this->xml($xml, $statusCode);
                break;
                
            case 'html':
            default:
                // Basic HTML representation of errors
                $html = $this->errorsToHtml($errors);
                $this->html($html, $statusCode);
                break;
        }
    }
    
    /**
     * Format a redirect response
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code
     * @param array $flash Flash data to store in session
     * @return void
     */
    public function redirect(string $url, int $statusCode = 302, array $flash = [])
    {
        // Store flash data in session if needed
        if (!empty($flash) && isset($_SESSION)) {
            foreach ($flash as $key => $value) {
                $_SESSION['flash_' . $key] = $value;
            }
        }
        
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Detect if the current request is an API request
     * 
     * @return bool
     */
    public function isApiRequest(): bool
    {
        // Check if the request is an AJAX request
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Check if the request accepts JSON
        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && 
                       (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        // Check if the request is to the API endpoint
        $isApiUrl = isset($_SERVER['REQUEST_URI']) && 
                    (strpos($_SERVER['REQUEST_URI'], '/api/') === 0);
                    
        // Check for format parameter
        $hasFormatParam = (isset($_GET['format']) && $_GET['format'] === 'json') || 
                          (isset($_POST['format']) && $_POST['format'] === 'json');
        
        return $isAjax || $acceptsJson || $isApiUrl || $hasFormatParam;
    }
    
    /**
     * Create a unified response that works for both API and web requests
     * 
     * @param string $view View to render for web requests
     * @param array $data Data for both API and web responses
     * @param string $successMessage Success message
     * @param string|null $redirectUrl URL to redirect to after web request
     * @return void
     */
    public function respond(string $view, array $data = [], string $successMessage = '', ?string $redirectUrl = null)
    {
        if ($this->isApiRequest()) {
            $this->jsonSuccess($successMessage, $data, $redirectUrl);
        } else {
            // For non-API requests, handle redirection first
            if ($redirectUrl) {
                $flash = [];
                if (!empty($successMessage)) {
                    $flash['success'] = $successMessage;
                }
                $this->redirect($redirectUrl, 302, $flash);
            }
            
            // Otherwise, render the view
            // Note: In actual implementation, we need access to view rendering
            // Here we use a simple approach with viewRenderer if available
            if ($this->viewRenderer !== null && method_exists($this->viewRenderer, 'render')) {
                $viewData = $data;
                
                // Add success message to view data if provided
                if (!empty($successMessage)) {
                    $viewData['success'] = true;
                    $viewData['message'] = $successMessage;
                }
                
                $content = $this->viewRenderer->render($view, $viewData);
                $this->html($content);
            } else {
                // Fallback for when no renderer is available
                // This is a simplified implementation for backward compatibility
                $viewPath = $this->getViewPath($view);
                
                if (file_exists($viewPath)) {
                    // Extract data to make it available in the included file
                    extract($data);
                    
                    // Capture output from the included file
                    ob_start();
                    include $viewPath;
                    $content = ob_get_clean();
                    
                    $this->html($content);
                } else {
                    // If view file doesn't exist, return a simple HTML response
                    $html = $this->generateSimpleHtml($view, $data, $successMessage);
                    $this->html($html);
                }
            }
        }
    }
    
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
    public function respondWithError(string $view, array $data = [], string $errorMessage = '', ?string $redirectUrl = null, int $statusCode = 400)
    {
        if ($this->isApiRequest()) {
            $this->jsonError($errorMessage, $data, $statusCode);
        } else {
            // For non-API requests, handle redirection first
            if ($redirectUrl) {
                $flash = [];
                if (!empty($errorMessage)) {
                    $flash['error'] = $errorMessage;
                }
                $this->redirect($redirectUrl, 302, $flash);
            }
            
            // Otherwise, render the view
            if ($this->viewRenderer !== null && method_exists($this->viewRenderer, 'render')) {
                $viewData = $data;
                
                // Add error message to view data if provided
                if (!empty($errorMessage)) {
                    $viewData['errors'] = [$errorMessage];
                }
                
                $content = $this->viewRenderer->render($view, $viewData);
                $this->html($content, $statusCode);
            } else {
                // Fallback for when no renderer is available
                $viewPath = $this->getViewPath($view);
                
                if (file_exists($viewPath)) {
                    // Extract data to make it available in the included file
                    extract($data);
                    
                    // Add error message to view data if provided
                    $errors = [];
                    if (!empty($errorMessage)) {
                        $errors[] = $errorMessage;
                    }
                    
                    // Capture output from the included file
                    ob_start();
                    include $viewPath;
                    $content = ob_get_clean();
                    
                    $this->html($content, $statusCode);
                } else {
                    // If view file doesn't exist, return a simple HTML response
                    $html = $this->generateSimpleHtml($view, $data, '', $errorMessage);
                    $this->html($html, $statusCode);
                }
            }
        }
    }
    
    /**
     * Convert errors array to XML
     * 
     * @param array $errors Validation errors
     * @return string XML string
     */
    protected function errorsToXml(array $errors): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<message>Validation failed</message>';
        $xml .= '<errors>';
        
        foreach ($errors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                foreach ($fieldErrors as $error) {
                    $xml .= '<error field="' . htmlspecialchars($field) . '">';
                    $xml .= htmlspecialchars($error);
                    $xml .= '</error>';
                }
            } else {
                $xml .= '<error field="' . htmlspecialchars($field) . '">';
                $xml .= htmlspecialchars($fieldErrors);
                $xml .= '</error>';
            }
        }
        
        $xml .= '</errors>';
        $xml .= '</response>';
        
        return $xml;
    }
    
    /**
     * Convert errors array to HTML
     * 
     * @param array $errors Validation errors
     * @return string HTML string
     */
    protected function errorsToHtml(array $errors): string
    {
        $html = '<!DOCTYPE html>';
        $html .= '<html><head><title>Validation Errors</title>';
        $html .= '<style>';
        $html .= '.error-container { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }';
        $html .= '.error-title { color: #d9534f; }';
        $html .= '.error-list { list-style-type: none; padding: 0; }';
        $html .= '.error-item { margin-bottom: 10px; padding: 10px; background-color: #f2dede; border: 1px solid #ebccd1; border-radius: 4px; }';
        $html .= '.error-field { font-weight: bold; }';
        $html .= '</style>';
        $html .= '</head><body>';
        $html .= '<div class="error-container">';
        $html .= '<h1 class="error-title">Validation Errors</h1>';
        $html .= '<ul class="error-list">';
        
        foreach ($errors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                foreach ($fieldErrors as $error) {
                    $html .= '<li class="error-item">';
                    $html .= '<span class="error-field">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $field))) . ':</span> ';
                    $html .= htmlspecialchars($error);
                    $html .= '</li>';
                }
            } else {
                $html .= '<li class="error-item">';
                $html .= '<span class="error-field">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $field))) . ':</span> ';
                $html .= htmlspecialchars($fieldErrors);
                $html .= '</li>';
            }
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Get the file path for a view
     * 
     * @param string $view View name
     * @return string Full file path
     */
    protected function getViewPath(string $view): string
    {
        // Check in the new MVC structure
        $newPath = __DIR__ . '/../app/Views/' . $view . '.php';
        
        // If not found, check in the old location
        if (!file_exists($newPath)) {
            return __DIR__ . '/../' . $view . '.php';
        }
        
        return $newPath;
    }
    
    /**
     * Generate a simple HTML page for responses
     * 
     * @param string $title Page title
     * @param array $data Data to display
     * @param string $successMessage Success message
     * @param string $errorMessage Error message
     * @return string HTML content
     */
    protected function generateSimpleHtml(string $title, array $data, string $successMessage = '', string $errorMessage = ''): string
    {
        $html = '<!DOCTYPE html>';
        $html .= '<html><head>';
        $html .= '<title>' . htmlspecialchars($title) . '</title>';
        $html .= '<style>';
        $html .= 'body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }';
        $html .= '.success { color: #3c763d; background-color: #dff0d8; border: 1px solid #d6e9c6; padding: 15px; border-radius: 4px; margin-bottom: 20px; }';
        $html .= '.error { color: #a94442; background-color: #f2dede; border: 1px solid #ebccd1; padding: 15px; border-radius: 4px; margin-bottom: 20px; }';
        $html .= '.data-container { background-color: #f5f5f5; border: 1px solid #e3e3e3; padding: 15px; border-radius: 4px; }';
        $html .= '</style>';
        $html .= '</head><body>';
        
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        
        // Display success message if provided
        if (!empty($successMessage)) {
            $html .= '<div class="success">' . htmlspecialchars($successMessage) . '</div>';
        }
        
        // Display error message if provided
        if (!empty($errorMessage)) {
            $html .= '<div class="error">' . htmlspecialchars($errorMessage) . '</div>';
        }
        
        // Display data if provided
        if (!empty($data)) {
            $html .= '<div class="data-container">';
            $html .= '<pre>' . htmlspecialchars(print_r($data, true)) . '</pre>';
            $html .= '</div>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Get instance for singleton pattern (backward compatibility)
     *
     * @return ResponseFormatter
     */
    public static function getInstance()
    {
        static $instance = null;
        
        if ($instance === null) {
            $instance = new self();
        }
        
        return $instance;
    }
}