<?php
/**
 * This is the basis for all HTTP client classes. All common HTTP functionality is contained here.
 */
declare(strict_types=1);

namespace Maleficarum\Client\Http;

use Maleficarum\ContextTracing\Carrier\Http\HttpHeader;
use Maleficarum\ContextTracing\ContextTracker;

abstract class AbstractClient {
    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * Definitions for supported HTTP methods.
     */
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';

    /**
     * Internal helper constant that defines a list of methods that can be used by this client class.
     */
    protected const AVAILABLE_METHODS = [
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_PATCH,
        self::METHOD_DELETE,
    ];

    /**
     * Default CURL options that will be set for each request unless overridden.
     */
    protected const DEFAULT_OPTIONS = [
        \CURLOPT_RETURNTRANSFER => true,
        \CURLOPT_HEADER => true,
        \CURLOPT_FOLLOWLOCATION => true,
        \CURLOPT_MAXREDIRS => 5,
        \CURLOPT_NOSIGNAL => true,
        \CURLOPT_TIMEOUT => 120,
    ];



    /**
     * This attribute contains a raw http response returned by the last executed request. This will always be a string
     * (unless a request was never executed by the instance in question) as HTTP responses are text by definition.
     * 
     * CAUTION: This contains headers as well.
     *
     * @var string|null
     */
    protected $rawResponse = null;

    /**
     * Similar to the rawResponse attribute but without headers. This is a raw body form - text - with the response 
     * headers stripped away. 
     *
     * EXAMPLE:
     *  - {"status": "OK"}
     *
     * @var string|null
     */
    protected $body = null;

    /**
     * Response body in a parsed form. In contrast to the body attribute that will always be in test format, parsed
     * body will often be a structure. 
     * 
     * EXAMPLE:
     *  - [
     *      'status' => 'OK'
     *  ]
     *
     * @var mixed
     */
    protected $parsedBody = null;

    /**
     * Contains a full list of response headers in array form. Each header will represent a single array entry.
     * Headers will be in raw form so each array entry will be a single string entry that carries both the name
     * of the headers as well as it's value
     * 
     * EXAMPLE:
     *  - [
     *      "Access-Control-Allow-Origin: *"
     *      "Access-Control-Allow-Methods: *" 
     *      "Access-Control-Allow-Headers: Content-Type"
     *  ]
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Contains the HTTP response status code for the last executed http request. (before the first request is executed
     * the value will be null). A list of HTTP status codes and their meaning can be found at: https://httpstatuses.com/
     *
     * @var int|null
     */
    protected $statusCode = null;

    /**
     * Contains the transfer info structure returned by the curl object after the last request was performed. More information
     * can be found at: http://php.net/manual/pl/function.curl-getinfo.php
     *
     * @var array
     */
    protected $transferInfo = null;
    
    /**
     * Contains the base URL for the API this client will connect to. Any requests made using this client will
     * always have this base URL prepended to the path specified in request methods (either directly or via
     * the method helpers like get() or post()). Protocol definition MUST be included in the base URL.
     * 
     * EXAMPLE:
     *  - 'http://www.github.com/'
     *  - 'https://www.github.com/'
     * 
     * @var string
     */
    private $baseUrl = null;

    /**
     * Contains the parsed form of the base URL - and array that's separated into specific URL sections as defined by the
     * parse_url() function (http://php.net/manual/pl/function.parse-url.php)
     * 
     * @var array 
     */
    private $parsedBaseUrl = [];

    /**
     * This pointer is used to reference the next round robin selection when the address definition list has more than one element.
     * First request will be made to the definition determined based on the time() function. Subsequent requests will use the round
     * robin paradigm.
     * 
     * @var int
     */
    private $roundRobinPointer = null;

    /**
     * This array defines a list of custom curl options that can be set using setter methods. Anything set via custom options
     * will override corresponding setting values defined in the DEFAULT_OPTIONS 
     *
     * @var array
     */
    private $customOptions = [];

    /**
     * This array contains all middleware functions that are applied to the request options. Added functions are executed in
     * the same order in which they were added to the middleware definitions. Each function will be called with two parameters:
     *  - the call url
     *  - the already established curl options
     * and the function MUST return a new array that will be used as the new set of curl call options.
     *
     * @var array
     */
    private $middlewareDefinitions = [];

    /* ------------------------------------ Class Property END ----------------------------------------- */
    
    /* ------------------------------------ Abstract methods START ------------------------------------- */
    
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
     * Performs HTTP request.
     *
     * @param string $url
     * @param string $method
     * @param array $curlOptions
     *
     * @return void
     */
    abstract protected function execute(string $url, string $method, array $curlOptions): void;
    
    /* ------------------------------------ Abstract methods END --------------------------------------- */

    /* ------------------------------------ Magic methods START ---------------------------------------- */

    public function __construct(string $baseUrl, array $addressDefinitions = []) {
        if (filter_var($baseUrl, \FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException(sprintf('Invalid API base url specified. %s::__construct()', static::class));
        }

        foreach ($addressDefinitions as $addDef) {
            if (filter_var($addDef, \FILTER_VALIDATE_IP) === false) {
                throw new \InvalidArgumentException(sprintf('Invalid IP address specified ['.(string)$addDef.']. %s::__construct()', static::class));
            }
        } 
        
        $this->baseUrl = $baseUrl;
        $this->parsedBaseUrl = parse_url($this->baseUrl);
        $this->addressDefinitions = $addressDefinitions;

        $this->addMiddleware(static function (string $url, array $options) {
            $headers = $options[\CURLOPT_HTTPHEADER] ?? [];

            $headers = (new HttpHeader())->inject(ContextTracker::getTracer(), $headers);
            $normalizedHeaders = [];
            foreach ($headers as $name => $value) {
                $normalizedHeaders[] = sprintf('%s: %s', $name, $value);
            }
            if (!empty($normalizedHeaders)) {
                $options[\CURLOPT_HTTPHEADER] = $normalizedHeaders;
            }

            return $options;
        });
    }
    
    /* ------------------------------------ Magic methods END ------------------------------------------ */
    
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * Performs GET request
     *
     * @param string $url
     * @param array $queryParameters
     * @param array $headers
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
     * @return void
     */
    public function request(string $url, string $method, array $options = []): void {
        // establish and validate the URL
        $url = $this->baseUrl.$url;

        if (\filter_var($url, \FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException(\sprintf('Provided URL "%s" is invalid', $url));
        }

        if (\in_array($method, self::AVAILABLE_METHODS, true) === false) {
            throw new \InvalidArgumentException(\sprintf('Provided method "%s" is invalid. Available methods: %s', $method, \implode(', ', self::AVAILABLE_METHODS)));
        }

        $queryParameters = $options['queryParameters'] ?? [];
        $queryString = $this->buildQueryString($queryParameters);
        
        $url .= $queryString;

        $curlOptions = $this->buildCurlOptions($method, $options);
        foreach ($this->middlewareDefinitions as $midDef) $curlOptions = $midDef($url, $curlOptions);

        $this->execute($url, $method, $curlOptions);
    }

    /**
     * Build a valid HTTP query string based on the provided value. Supports nested arrays and takes care
     * of the initial separation character.
     *
     * @param array $queryParameters
     *
     * @return string
     */
    private function buildQueryString(array $queryParameters): string {
        if (empty($queryParameters)) {
            return '';
        }

        return '?' . \http_build_query($queryParameters);
    }

    /**
     * Setup the curl options array based on both the default settings const and custom curl options that were set for this client
     * instance. This will also take care of any method specific settings that need to happen before a method request is executed.
     * The options parameter allows for the transference of two key settings:
     *  - headers - a list of HTTP headers to send along side the reuest
     *  - postParameter - an array that will be serialized using the encodePayload() method and send as the request payload
     *
     * @param string $method
     * @param array $options
     *
     * @return array
     */
    private function buildCurlOptions(string $method, array $options): array {
        // setup basic curl options array
        $curlOptions = \array_replace(self::DEFAULT_OPTIONS, $this->customOptions, [\CURLOPT_CUSTOMREQUEST => $method]);

        // override DNS resolving if possible
        if (count($this->addressDefinitions)) {
            $ip = $this->getCurrentAddressDefinition();
            $resolverOverrides = [];

            // these overrides will remove any existing dns cache overrides
            // as defined at https://curl.haxx.se/libcurl/c/CURLOPT_RESOLVE.html
            foreach ($this->addressDefinitions as $addDeff) {
                if ($addDeff !== $ip) {
                    $resolverOverrides[] = '-'.$this->parsedBaseUrl['host'].':80:'.$addDeff;
                    $resolverOverrides[] = '-'.$this->parsedBaseUrl['host'].':443:'.$addDeff;
                }
            }
            
            // these overrides are there to actually force the new override values 
            $resolverOverrides[] = $this->parsedBaseUrl['host'].':80:'.$ip;
            $resolverOverrides[] = $this->parsedBaseUrl['host'].':443:'.$ip;
            
            $curlOptions[\CURLOPT_RESOLVE] = $resolverOverrides;
        }
        
        // make the request a standard POST type for POST method
        if (self::METHOD_POST === $method) {
            $curlOptions[\CURLOPT_POST] = true;
        }

        // add post parameters to requests that support payloads
        if (\in_array($method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_PATCH, self::METHOD_DELETE], true)) {
            $postParameters = $options['postParameters'] ?? [];
            $curlOptions[\CURLOPT_POSTFIELDS] = $this->encodePayload($postParameters);
        }

        // add headers if any where specified
        if (empty($options['headers']) === false) {
            $curlOptions[\CURLOPT_HTTPHEADER] = $options['headers'];
        }

        return $curlOptions;
    }
    
    /**
     * Returns the address definition that should be used during the next curl call. This will automatically cycle 
     * through the entire set of definitions and circle back to the first definition used before starting again.
     * 
     * @return string
     */
    private function getCurrentAddressDefinition(): string {
        if (is_null($this->roundRobinPointer)) {
            $this->roundRobinPointer = time() % count($this->addressDefinitions);
        }
        
        $addDef = $this->addressDefinitions[$this->roundRobinPointer];
        ++$this->roundRobinPointer >= count($this->addressDefinitions) and $this->roundRobinPointer = 0;
        
        return $addDef;
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */

    /* ------------------------------------ Setters & Getters START ------------------------------------ */

    /**
     * Adds a new closure to the middleware stack.
     *
     * @param \Closure $middleware
     *
     * @return \Maleficarum\Client\Http\AbstractClient
     */
    public function addMiddleware(\Closure $middleware): \Maleficarum\Client\Http\AbstractClient {
        $this->middlewareDefinitions[] = $middleware;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUrl():string {
        return $this->baseUrl;
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
     * Set connectionTimeout.
     *
     * @param int $connectionTimeout
     *
     * @return void
     */
    public function setConnectionTimeout(int $connectionTimeout): \Maleficarum\Client\Http\AbstractClient {
        $this->customOptions[\CURLOPT_CONNECTTIMEOUT] = $connectionTimeout;
        return $this;
    }

    /**
     * Set operationTimeout.
     *
     * @param int $operationTimeout
     *
     * @return void
     */
    public function setOperationTimeout(int $operationTimeout): \Maleficarum\Client\Http\AbstractClient {
        $this->customOptions[\CURLOPT_TIMEOUT] = $operationTimeout;
        return $this;
    }
    
    /* ------------------------------------ Setters & Getters END -------------------------------------- */
}