<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http\Curl\Exception;

/**
 * Thrown whenever there was some issue with using the curl - not a programmer's mistake.
 * Eg. connection could not be established.
 */
final class RuntimeException extends \RuntimeException implements CurlException {
}