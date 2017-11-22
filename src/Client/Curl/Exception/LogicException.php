<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http\Curl\Exception;

/**
 * Thrown whenever Curl wrapper is being used in a wrong way - programmer's mistake.
 */
final class LogicException extends \LogicException implements CurlException {
}