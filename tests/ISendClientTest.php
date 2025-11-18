<?php

namespace ISend\Tests;

use PHPUnit\Framework\TestCase;
use ISend\ISendClient;

class ISendClientTest extends TestCase
{
    public function testConstructorWithValidApiKey()
    {
        $client = new ISendClient('test-api-key');
        $this->assertInstanceOf(ISendClient::class, $client);
    }
    
    public function testConstructorWithEmptyApiKey()
    {
        $this->setExpectedException(\InvalidArgumentException::class, 'API key is required');
        
        new ISendClient('');
    }
    

    
    public function testSendEmail()
    {
        $client = new ISendClient('test-api-key');
        
        // Test that the method exists and has correct signature
        $this->assertTrue(method_exists($client, 'sendEmail'));
    }
} 