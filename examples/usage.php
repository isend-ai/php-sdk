<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ISend\ISendClient;

// Initialize the client with your API key
$client = new ISendClient('your-api-key-here');

// Send Email
$client->sendEmail(11, 'devmanoharrr@gmail.com', [
    'user_name' => 'Manohar',
    'time' => date('Y-m-d H:i:s'),
    'year' => date('Y'),
    'user_type' => 'Planner'
]);

// Send Telegram Template
$client->sendTelegramTemplate(
    "customer@example.com",
    "template_variable",
    [
        "user_name" => "John Doe",
        "time" => date("Y-m-d H:i:s"),
        "user_type" => "Planner"
    ]
);

// Send Event
$client->sendEvent(3, 'devmanoharrr@gmail.com', [
    'user_name' => 'Manohar',
    'time' => date('Y-m-d H:i:s'),
    'year' => date('Y'),
    'user_type' => 'Planner'
]);

