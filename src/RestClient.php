<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http;

/**
 * This class provides functionality of performing HTTP request
 */
class RestClient extends AbstractClient {
    /**
     * @inheritDoc
     */
    public function request(string $url, string $method, array $options = []): void {
        $headers = $options['headers'] ?? [];
        $options['headers'] = \array_merge($headers, ['Content-Type: application/json']);

        parent::request($url, $method, $options);
    }

    /**
     * @inheritDoc
     */
    protected function encodePayload($data) {
        return \json_encode($data);
    }

    /**
     * @inheritDoc
     */
    protected function decodeResponseBody(string $responseBody) {
        return \json_decode($responseBody, true);
    }
}
