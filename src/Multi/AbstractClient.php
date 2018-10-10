<?php
/**
 * This class extends the base, abstract client class with CURL fetching using the multi API implementation as described here:
 * https://ec.haxx.se/libcurl-connectionreuse.html
 *
 * This is most useful whe implementing high quantity connection functionalities that call the same server. This will make use of the
 * inherent multi API connection caching to limit the amount of connections that are actually created.
 */
declare(strict_types=1);

namespace Maleficarum\Client\Http\Multi;

abstract class AbstractClient extends \Maleficarum\Client\Http\AbstractClient {
    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * Contains all used curl multi api resource handles. Each handle is stored under an index that uniquely
     * identifies an actual IP connection. This differs based on whether resolving is done via DNS or via
     * static address definitions.
     * 
     * For DNS resolving the handle storage will always contain a single handle stored under an index defined
     * by the base API URL. 
     * 
     * EXAMPLE:
     *  - ['https://giithub.com/' => resource] 
     * 
     * When a static IP resolve map is provided each IP will have it's own handle and each handle will be stored
     * under an index defined by static IP route.
     * 
     * EXAMPLE:
     *  - [
     *      '127.0.0.1' => resource,
     *      '127.0.0.2' => resource,
     *      '127.0.0.3' => resource
     *  ]
     * 
     * @var array
     */
    private $handles = [];
    
    /* ------------------------------------ Class Property END ----------------------------------------- */
        
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * @see \Maleficarum\Client\Http\AbstractClient::execute()
     */
    protected function execute(string $url, string $method, array $curlOptions): void {
        // choose handle base on the incoming curl options
        if (isset($curlOptions[\CURLOPT_RESOLVE]) && count($curlOptions[\CURLOPT_RESOLVE])) {
            $handleName = explode(":", $curlOptions[\CURLOPT_RESOLVE][count($curlOptions[\CURLOPT_RESOLVE])-1]);
            $handleName = array_pop($handleName);
        } else {
            $handleName = $this->getBaseUrl();
        }
        
        // get the multi handle for future use
        if (!array_key_exists($handleName, $this->handles)) {
            $this->handles[$handleName] = \curl_multi_init();
        }
        $handle = $this->handles[$handleName];
        
        // establish the local curl handle for this specific request
        $curl = \curl_init();
        \curl_setopt_array($curl, $curlOptions);
        \curl_setopt($curl, \CURLOPT_URL, $url);
        
        curl_multi_add_handle($handle, $curl);
        
        $running = null;
        do {
            curl_multi_exec($handle, $running);
            curl_multi_select($handle);
        } while ($running > 0);

        
        $response = curl_multi_getcontent($curl);
        $transferInfo = \curl_getinfo($curl);
        $errorCode = \curl_errno($curl);
        $errorMessage = \curl_error($curl);
        $multiErrorCode = \curl_multi_info_read($handle)['result'];
        
        \curl_multi_remove_handle($handle, $curl);
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

        // handle multi api resource level errors
        if (\CURLE_OK !== $multiErrorCode) {
            throw new \Maleficarum\Client\Http\Exception\CurlException($errorMessage, $multiErrorCode);
        }
        
        // handle single curl resource level errors
        if (\CURLE_OK !== $errorCode) {
            throw new \Maleficarum\Client\Http\Exception\CurlException($errorMessage, $errorCode);
        }

        $this->statusCode = $this->transferInfo['http_code'] ?? null;

        if ($this->statusCode < 200 || $this->statusCode >= 400) {
            throw new \Maleficarum\Client\Http\Exception\TransferException($this->statusCode, $response, $method, $url);
        }
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */
}