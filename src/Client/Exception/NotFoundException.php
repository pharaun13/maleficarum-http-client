<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http\Exception;

/**
 * Thrown when server response code is 404
 */
class NotFoundException extends HttpRequestException {
    /**
     * @param string $response HTTP response body
     */
    public function __construct($response = '') {
        parent::__construct($response, 404);
    }
}
