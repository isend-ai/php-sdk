<?php

namespace ISend;

/**
 * Simple PHP SDK for isend.ai
 */
class ISendClient
{
    private const API_BASE_URL = 'https://www.isend.ai/api';
    
    private $apiKey;
    private $timeout;
    
    /**
     * Create a new ISendClient instance
     *
     * @param string $apiKey Your isend.ai API key
     * @param array $config Additional configuration options
     */
    public function __construct($apiKey, array $config = [])
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key is required');
        }
        
        $this->apiKey = $apiKey;
        $this->timeout = isset($config['timeout']) ? $config['timeout'] : 30;
    }
    
    /**
     * Send an email using isend.ai
     *
     * @param array $emailData Email data including template_id, to, dataMapping, etc.
     * @return array Response from the API
     * @throws \Exception
     */
    public function sendEmail(array $emailData)
    {
        $url = self::API_BASE_URL . '/send-email';
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: isend-ai-php-sdk/1.0.0'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($emailData),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }
        
        if ($httpCode >= 400) {
            throw new \Exception('HTTP error: ' . $httpCode . ' - ' . $response);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        
        return $data;
    }
} 