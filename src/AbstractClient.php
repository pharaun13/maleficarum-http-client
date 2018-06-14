<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http;

use Maleficarum\Client\Http\Exception\CurlException;
use Maleficarum\Client\Http\Exception\TransferException;

/**
 * This class is a base for all HTTP client classes
 */
abstract class AbstractClient {
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';

    private const AVAILABLE_METHODS = [
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_PATCH,
        self::METHOD_DELETE,
    ];

    private const DEFAULT_OPTIONS = [
        \CURLOPT_RETURNTRANSFER => true,
        \CURLOPT_HEADER => true,
        \CURLOPT_FOLLOWLOCATION => true,
        \CURLOPT_MAXREDIRS => 5,
        \CURLOPT_NOSIGNAL => true,
        \CURLOPT_TIMEOUT => 120,
    ];

    /**
     * Internal storage for base url
     *
     * @var string|null
     */
    private $baseUrl;

    /**
     * Internal storage for RAW response
     *
     * @var string|null
     */
    private $rawResponse;

    /**
     * Internal storage for response body
     *
     * @var string|null
     */
    private $body;

    /**
     * Internal storage for parsed response body
     *
     * @var mixed
     */
    private $parsedBody;

    /**
     * Internal storage for response headers
     *
     * @var array
     */
    private $headers;

    /**
     * Internal storage for response status code
     *
     * @var int|null
     */
    private $statusCode;

    /**
     * Internal storage for transfer information
     *
     * @var array
     */
    private $transferInfo;

    /**
     * Internal storage for custom cURL options
     *
     * @var array
     */
    private $customOptions;

    /**
     * Internal storage for middleware
     *
     * @var \Closure[]
     */
    private $middleware;

    /**
     * AbstractClient constructor.
     */
    public function __construct() {
        $this->headers = [];
        $this->transferInfo = [];
        $this->customOptions = [];
        $this->middleware = [];
    }

    /**
     * Encodes request payload
     *
     * @param mixed $data
     *
     * @return mixed
     */
    abstract protected function encodePayload($data);

    /**
     * Decodes response body
     *
     * @param string $responseBody
     *
     * @return mixed
     */
    abstract protected function decodeResponseBody(string $responseBody);

    /**
     * Performs GET request
     *
     * @param string $url
     * @param array $queryParameters
     * @param array $headers
     *
     * @throws \InvalidArgumentException
     * @throws CurlException
     * @throws TransferException
     *
     * @return void
     */
    public function get(string $url, array $queryParameters = [], array $headers = []): void {
        $this->request($url, self::METHOD_GET, [
            'queryParameters' => $queryParameters,
            'headers' => $headers,
        ]);
    }

    /**
     * Performs POST request
     *
     * @param string $url
     * @param array $postParameters
     * @param array $queryParameters
     * @param array $headers
     *
     * @throws \InvalidArgumentException
     * @throws CurlException
     * @throws TransferException
     *
     * @return void
     */
    public function post(string $url, array $postParameters = [], array $queryParameters = [], array $headers = []): void {
        $this->request($url, self::METHOD_POST, [
            'postParameters' => $postParameters,
            'queryParameters' => $queryParameters,
            'headers' => $headers,
        ]);
    }

    /**
     * Performs PUT request
     *
     * @param string $url
     * @param array $postParameters
     * @param array $queryParameters
     * @param array $headers
     *
     * @throws \InvalidArgumentException
     * @throws CurlException
     * @throws TransferException
     *
     * @return void
     */
    public function put(string $url, array $postParameters = [], array $queryParameters = [], array $headers = []): void {
        $this->request($url, self::METHOD_PUT, [
            'postParameters' => $postParameters,
            'queryParameters' => $queryParameters,
            'headers' => $headers,
        ]);
    }

    /**
     * Performs PATCH request
     *
     * @param string $url
     * @param array $postParameters
     * @param array $queryParameters
     * @param array $headers
     *
     * @throws \InvalidArgumentException
     * @throws CurlException
     * @throws TransferException
     *
     * @return void
     */
    public function patch(string $url, array $postParameters = [], array $queryParameters = [], array $headers = []): void {
        $this->request($url, self::METHOD_PATCH, [
            'postParameters' => $postParameters,
            'queryParameters' => $queryParameters,
            'headers' => $headers,
        ]);
    }

    /**
     * Performs DELETE request
     *
     * @param string $url
     * @param array $postParameters
     * @param array $queryParameters
     * @param array $headers
     *
     * @throws \InvalidArgumentException
     * @throws CurlException
     * @throws TransferException
     *
     * @return void
     */
    public function delete(string $url, array $postParameters = [], array $queryParameters = [], array $headers = []): void {
        $this->request($url, self::METHOD_DELETE, [
            'postParameters' => $postParameters,
            'queryParameters' => $queryParameters,
            'headers' => $headers,
        ]);
    }

    /**
     * Performs HTTP request
     *
     * @param string $url
     * @param string $method
     * @param array $options
     *
     * @throws \InvalidArgumentException
     * @throws CurlException
     * @throws TransferException
     *
     * @return void
     */
    public function request(string $url, string $method, array $options = []): void {
        if (\is_string($this->baseUrl)) {
            $url = $this->baseUrl . $url;
        }

        if (false === \filter_var($url, \FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(\sprintf('Provided URL "%s" is invalid', $url));
        }

        if (false === \in_array($method, self::AVAILABLE_METHODS, true)) {
            throw new \InvalidArgumentException(\sprintf('Provided method "%s" is invalid. Available methods: %s', $method, \implode(', ', self::AVAILABLE_METHODS)));
        }

        $queryParameters = $options['queryParameters'] ?? [];
        $queryString = $this->buildQueryString($queryParameters);

        $url .= $queryString;

        $curlOptions = $this->buildCurlOptions($method, $options);
        foreach ($this->middleware as $middleware) {
            $curlOptions = $middleware($curlOptions);
        }

        $this->doRequest($url, $method, $curlOptions);
    }

    /**
     * Set baseUrl
     *
     * @param string $baseUrl
     *
     * @return void
     */
    public function setBaseUrl(string $baseUrl): void {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Get rawResponse
     *
     * @return null|string
     */
    public function getRawResponse(): ?string {
        return $this->rawResponse;
    }

    /**
     * Get body
     *
     * @return null|string
     */
    public function getBody(): ?string {
        return $this->body;
    }

    /**
     * Get parsedBody
     *
     * @return mixed
     */
    public function getParsedBody() {
        return $this->parsedBody;
    }

    /**
     * Get headers
     *
     * @return array
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * Get statusCode
     *
     * @return int|null
     */
    public function getStatusCode(): ?int {
        return $this->statusCode;
    }

    /**
     * Get transferInfo
     *
     * @return array
     */
    public function getTransferInfo(): array {
        return $this->transferInfo;
    }

    /**
     * Adds middleware
     *
     * @param \Closure $middleware
     *
     * @return void
     */
    public function addMiddleware(\Closure $middleware): void {
        $this->middleware[] = $middleware;
    }

    /**
     * Set connectionTimeout
     *
     * @param int $connectionTimeout
     *
     * @return void
     */
    public function setConnectionTimeout(int $connectionTimeout): void {
        $this->customOptions[\CURLOPT_CONNECTTIMEOUT] = $connectionTimeout;
    }

    /**
     * Set operationTimeout
     *
     * @param int $operationTimeout
     *
     * @return void
     */
    public function setOperationTimeout(int $operationTimeout): void {
        $this->customOptions[\CURLOPT_TIMEOUT] = $operationTimeout;
    }

    /**
     * Builds query string
     *
     * @param array $queryParameters
     *
     * @return string
     */
    protected function buildQueryString(array $queryParameters): string {
        if (empty($queryParameters)) {
            return '';
        }

        return '?' . \http_build_query($queryParameters);
    }

    /**
     * Builds cURL options
     *
     * @param string $method
     * @param array $options
     *
     * @return array
     */
    private function buildCurlOptions(string $method, array $options): array {
        $curlOptions = \array_replace(self::DEFAULT_OPTIONS, $this->customOptions, [\CURLOPT_CUSTOMREQUEST => $method]);

        if (self::METHOD_POST === $method) {
            $curlOptions[\CURLOPT_POST] = true;
        }

        if (\in_array($method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_PATCH, self::METHOD_DELETE], true)) {
            $postParameters = $curlOptions['postParameters'] ?? [];
            $curlOptions[\CURLOPT_POSTFIELDS] = $this->encodePayload($postParameters);
        }

        if (false === empty($options['headers'])) {
            $curlOptions[\CURLOPT_HTTPHEADER] = $options['headers'];
        }

        return $curlOptions;
    }

    /**
     * Performs HTTP request using cURL
     *
     * @param string $url
     * @param string $method
     * @param array $curlOptions
     *
     * @throws CurlException
     * @throws TransferException
     *
     * @return void
     */
    private function doRequest(string $url, string $method, array $curlOptions): void {
        $curl = \curl_init($url);
        \curl_setopt_array($curl, $curlOptions);
        $response = \curl_exec($curl);
        $transferInfo = \curl_getinfo($curl);
        $errorCode = \curl_errno($curl);
        $errorMessage = \curl_error($curl);
        \curl_close($curl);

        if (\is_array($transferInfo)) {
            $this->transferInfo = $transferInfo;
        }

        if (\is_string($response)) {
            $this->rawResponse = $response;

            $headerSize = $transferInfo['header_size'];
            $this->body = \mb_substr($response, $headerSize);
            $this->parsedBody = $this->decodeResponseBody($this->body);

            $headers = \mb_substr($response, 0, $headerSize);
            $this->headers = \explode("\r\n", \trim($headers));
        }

        if (\CURLE_OK !== $errorCode) {
            throw new CurlException($errorMessage, $errorCode);
        }

        $this->statusCode = $this->transferInfo['http_code'] ?? null;

        if ($this->statusCode < 200 || $this->statusCode >= 400) {
            throw new TransferException($this->statusCode, $response, $method, $url);
        }
    }
}
