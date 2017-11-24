<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http\Exception;

/**
 * HTTP request exception.
 * This is raised when the HTTP Client did his job right but the Server could not be reached or the response was "invalid".
 *
 * Please note that in many cases more specific exceptions like `ForbiddenException` are thrown.
 */
class HttpRequestException extends RuntimeException {
    /**
     * @param string $response HTTP response body
     * @param int    $httpResponseCode
     */
    public function __construct($response = '', $httpResponseCode = 0) {
        parent::__construct("Response code: {$httpResponseCode}; Response: {$response}", $httpResponseCode);
    }
}
