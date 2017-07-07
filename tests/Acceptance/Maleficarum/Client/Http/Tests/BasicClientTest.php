<?php

namespace Maleficarum\Client\Http\Tests;

use Maleficarum\Client\Http\BasicClient;
use Maleficarum\Client\Http\Curl\Curl;
use Maleficarum\Client\Http\Curl\Exception\CurlException;
use PHPUnit\Framework\TestCase;

class BasicClientTest extends TestCase
{
    public function testConnectionAndOperationTimeout()
    {
        $timeout = 1;
        $startedAt = time();

        $client = new BasicClient(new Curl(), 'http://slowwly.robertomurray.co.uk');
        $client->setConnectionTimeout($timeout);
        $client->setOperationTimeout($timeout);

        try {
            $client->request('/delay/9000/url/http://www.google.com');
        } catch (CurlException $cex) {
            // it's ok, we expect to have some issues with the request
        }

        $requestDuration = time() - $startedAt;
        $this->assertLessThanOrEqual($timeout, $requestDuration, 'Request should not last longer than set timeout.');
    }
}
