<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http\Curl\Exception;

use Maleficarum\Client\Http\Exception\HttpClientException;

/**
 * Thrown whenever there has been an issue with using the Curl wrapper.
 */
interface CurlException extends HttpClientException {
}
