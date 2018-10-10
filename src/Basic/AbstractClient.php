<?php
/**
 * This class extends the base, abstract http client with the basic CURL fetching implementation. This is mose useful when implementing functionalities that need to execute a limited number of connections.
 */
declare(strict_types=1);

namespace Maleficarum\Client\Http\Basic;

abstract class AbstractClient extends \Maleficarum\Client\Http\AbstractClient {
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * @see \Maleficarum\Client\Http\AbstractClient::execute()
     */
    protected function execute(string $url, string $method, array $curlOptions): void {
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
            throw new \Maleficarum\Client\Http\Exception\CurlException($errorMessage, $errorCode);
        }

        $this->statusCode = $this->transferInfo['http_code'] ?? null;

        if ($this->statusCode < 200 || $this->statusCode >= 400) {
            throw new \Maleficarum\Client\Http\Exception\TransferException($this->statusCode, $response, $method, $url);
        }
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */
}