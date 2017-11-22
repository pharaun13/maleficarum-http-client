<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http\Exception;

/**
 * Thrown whenever there was some generic Runtime Exception, eg. could not reach the server when making a request.
 */
class RuntimeException extends \RuntimeException implements HttpClientException {
}