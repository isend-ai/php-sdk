<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ISend\ISendClient;

// Initialize the client with your API key
$client = new ISendClient('your-api-key-here');

// Example: Send email using template
try {
    $emailData = [
        'template_id' => 124,
        'to' => 'hi@isend.ai',
        'dataMapping' => [
            'name' => 'ISend'
        ]
    ];
    
    $response = $client->sendEmail($emailData);
    
    echo "Email sent successfully!\n";
    print_r($response);
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n";
} 