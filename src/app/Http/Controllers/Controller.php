<?php

namespace App\Http\Controllers;
use App\Services\Interfaces\ResponseFormatterInterface;
use Illuminate\Http\Response;

abstract class Controller
{
    /**
     * Standard HTTP status codes
     */
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NO_CONTENT = 204;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENT_REDIRECT = 308;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_REQUEST_TIMEOUT = 408;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_UNPROCESSABLE_ENTITY = 422;
    const HTTP_TOO_MANY_REQUESTS = 429;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;

    /**
     * Standard API error codes
     */
    const ERROR_VALIDATION = 'validation_error';
    const ERROR_AUTHENTICATION = 'authentication_error';
    const ERROR_AUTHORIZATION = 'authorization_error';
    const ERROR_NOT_FOUND = 'resource_not_found';
    const ERROR_SERVER = 'server_error';
    const ERROR_INVALID_REQUEST = 'invalid_request';
    const ERROR_RATE_LIMIT = 'rate_limit_exceeded';

    /**
     * Error categories
     */
    const ERROR_CATEGORY_VALIDATION = 'validation';
    const ERROR_CATEGORY_AUTHENTICATION = 'authentication';
    const ERROR_CATEGORY_AUTHORIZATION = 'authorization';
    const ERROR_CATEGORY_RESOURCE = 'resource';
    const ERROR_CATEGORY_SERVER = 'server';
    const ERROR_CATEGORY_DATABASE = 'database';
    const ERROR_CATEGORY_SYSTEM = 'system';

    protected $responseFormatter;
    
    public function __construct(ResponseFormatterInterface $responseFormatter)
    {
        $this->responseFormatter = $responseFormatter;
    }

    /**
     * Format a JSON response
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function json(array $data, int $statusCode = 200){
        $this->responseFormatter->json($data, $statusCode);
    }

    /**
     * Return a JSON error response
     * 
     * @param string $message Error message
     * @param array $errors Additional errors or categorized error data
     * @param int $statusCode HTTP status code
     * @param string $errorCode API error code
     * @return void
     */
    protected function jsonSuccess($message, $data = [], $redirect = null)
    {
        $this->responseFormatter->jsonSuccess($message, $data, $redirect);

    }

    /**
     * Return a JSON error response
     * 
     * @param string $message Error message
     * @param array $errors Additional errors or categorized error data
     * @param int $statusCode HTTP status code
     * @param string $errorCode API error code
     * @return void
     */
    protected function jsonError($message, $errors = [], $statusCode = self::HTTP_BAD_REQUEST, $errorCode = null)
    {
        // Determine error code if not provided
        if ($errorCode === null) {
            $errorCode = $this->getErrorCodeFromStatus($statusCode);
        }

        // Add error code to the errors array
        $formattedErrors = $errors;
        if (!empty($errorCode)) {
            $formattedErrors = array_merge(['code' => $errorCode], $errors);
        }
        $this->responseFormatter->jsonError($message, $formattedErrors, $statusCode);

    }

    protected function isApiRequest(){
        return $this->responseFormatter->isApiRequest();
    }

    /**
     * Get appropriate error code from HTTP status code
     * 
     * @param int $statusCode HTTP status code
     * @return string Error code
     */
    protected function getErrorCodeFromStatus($statusCode)
    {
        switch ($statusCode) {
            case self::HTTP_UNAUTHORIZED:
                return self::ERROR_AUTHENTICATION;
            case self::HTTP_FORBIDDEN:
                return self::ERROR_AUTHORIZATION;
            case self::HTTP_NOT_FOUND:
                return self::ERROR_NOT_FOUND;
            case self::HTTP_UNPROCESSABLE_ENTITY:
                return self::ERROR_VALIDATION;
            case self::HTTP_TOO_MANY_REQUESTS:
                return self::ERROR_RATE_LIMIT;
            case self::HTTP_INTERNAL_SERVER_ERROR:
            case self::HTTP_BAD_GATEWAY:
            case self::HTTP_SERVICE_UNAVAILABLE:
                return self::ERROR_SERVER;
            default:
                return self::ERROR_INVALID_REQUEST;
        }
    }

    /**
     * Store error message in session flash data and handle redirect
     * 
     * @param string $message Error message
     * @param string|null $redirectUrl URL to redirect to (optional)
     * @return void
     */
    protected function withError($message, $redirectUrl = null)
    {
        if ($redirectUrl) {
            $this->responseFormatter->redirect($redirectUrl, self::HTTP_FOUND, ['error' => $message]);
        } else {
            if (isset($_SESSION)) {
                $_SESSION['flash_error'] = $message;
            }
        }
    }

    /**
     * Store success message in session flash data and handle redirect
     * 
     * @param string $message Success message
     * @param string|null $redirectUrl URL to redirect to (optional)
     * @return void
     */
    protected function withSuccess($message, $redirectUrl = null)
    {
        if ($redirectUrl) {
            $this->responseFormatter->redirect($redirectUrl, self::HTTP_FOUND, ['success' => $message]);
        } else {
            if (isset($_SESSION)) {
                $_SESSION['flash_success'] = $message;
            }
        }
    }

    /**
     * Extract validation errors from model objects and store them in session flash data
     * 
     * @param object $model Model object implementing getErrors()
     * @param string|null $redirectUrl URL to redirect to (optional)
     * @return void
     */
    protected function withModelErrors($model, $redirectUrl = null)
    {
        $errors = [];
        
        if (method_exists($model, 'getErrors')) {
            $errors = $model->getErrors();
        }
        
        if ($redirectUrl) {
            $this->responseFormatter->redirect($redirectUrl, self::HTTP_FOUND, ['errors' => $errors]);
        } else {
            if (isset($_SESSION)) {
                $_SESSION['flash_errors'] = $errors;
            }
        }
    }
}