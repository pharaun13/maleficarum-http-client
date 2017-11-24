<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http\Exception;

/**
 * Thrown whenever you try to use the client in a wrong way.
 * Eg. trying to make a request with unsupported method like 'PORK'
 */
class InvalidArgumentException extends \InvalidArgumentException implements HttpClientException {
}