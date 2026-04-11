<?php

namespace ISend\Tests;

use PHPUnit\Framework\TestCase;
use ISend\ISendTelegramConnectorClient;

class ISendTelegramConnectorClientTest extends TestCase
{
    public function testConstructorRejectsEmptySecret()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        new ISendTelegramConnectorClient('');
    }

    public function testConstructorRejectsWhitespaceOnlySecret()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        new ISendTelegramConnectorClient('   ');
    }

    public function testConstructorAcceptsSecret()
    {
        $client = new ISendTelegramConnectorClient('test-secret-token');
        $this->assertInstanceOf(ISendTelegramConnectorClient::class, $client);
    }

    public function testIsOkResponseSuccess()
    {
        $this->assertTrue(ISendTelegramConnectorClient::isOkResponse([
            '_http_code' => 200,
            'success' => true,
        ]));
    }

    public function testIsOkResponseHttpError()
    {
        $this->assertFalse(ISendTelegramConnectorClient::isOkResponse([
            '_http_code' => 401,
            'success' => false,
        ]));
    }

    public function testIsOkResponseMissingSuccessKeyMeansOkWhen2xx()
    {
        $this->assertTrue(ISendTelegramConnectorClient::isOkResponse([
            '_http_code' => 200,
            'data' => [],
        ]));
    }

    public function testIsOkResponseExplicitFailure()
    {
        $this->assertFalse(ISendTelegramConnectorClient::isOkResponse([
            '_http_code' => 200,
            'success' => false,
        ]));
    }

    public function testIsOkResponseNull()
    {
        $this->assertFalse(ISendTelegramConnectorClient::isOkResponse(null));
    }
}
