<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http\Exception;

/**
 * This exception should be thrown when response status code is other than 2xx or 3xx
 */
class TransferException extends HttpClientException {
    /**
     * Internal storage for response status code
     *
     * @var int
     */
    private $statusCode;

    /**
     * Internal storage for RAW response
     *
     * @var string
     */
    private $rawResponse;

    /**
     * Internal storage for request method
     *
     * @var string
     */
    private $requestMethod;

    /**
     * Internal storage for URL
     *
     * @var string
     */
    private $url;

    /**
     * TransferException constructor.
     *
     * @param int $statusCode
     * @param string $rawResponse
     * @param string $requestMethod
     * @param string $url
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(int $statusCode, string $rawResponse, string $requestMethod, string $url, string $message = '', int $code = 0, \Throwable $previous = null) {
        $this->statusCode = $statusCode;
        $this->rawResponse = $rawResponse;
        $this->requestMethod = $requestMethod;
        $this->url = $url;

        $message = \sprintf('HttpError | %s %s %s ' . PHP_EOL . ' %s', $statusCode, $requestMethod, $url, $rawResponse) . $message;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get statusCode
     *
     * @return int
     */
    public function getStatusCode(): int {
        return $this->statusCode;
    }

    /**
     * Get rawResponse
     *
     * @return string
     */
    public function getRawResponse(): string {
        return $this->rawResponse;
    }

    /**
     * Get requestMethod
     *
     * @return string
     */
    public function getRequestMethod(): string {
        return $this->requestMethod;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }
}
