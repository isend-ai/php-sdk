# isend.ai PHP SDK

A simple PHP SDK for sending emails through isend.ai using various email connectors like AWS SES, SendGrid, Mailgun, and more.

## Installation

```bash
composer require isend-ai/php-sdk
```

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use ISend\ISendClient;

// Initialize the client
$client = new ISendClient('your-api-key-here');

// Send email using template
$emailData = [
    'template_id' => 124,
    'to' => 'hi@isend.ai',
    'dataMapping' => [
        'name' => 'ISend'
    ]
];

$response = $client->sendEmail($emailData);

print_r($response);
```

## Usage

### Send Email Using Template

```php
$emailData = [
    'template_id' => 124,
    'to' => 'hi@isend.ai',
    'dataMapping' => [
        'name' => 'ISend'
    ]
];

$response = $client->sendEmail($emailData);
```



## API Reference

### IsendClient

#### Constructor
```php
new ISendClient(string $apiKey, array $config = [])
```

#### Methods

##### sendEmail(array $emailData): array
Sends an email using the provided template and data.

**Parameters:**
- `$emailData` (array): Email data including:
  - `template_id` (int): The template ID to use
  - `to` (string): Recipient email address
  - `dataMapping` (array): Data mapping for template variables



## Error Handling

The SDK throws `Exception` for any errors:

```php
try {
    $response = $client->sendEmail([
        'template_id' => 124,
        'to' => 'hi@isend.ai',
        'dataMapping' => [
            'name' => 'ISend'
        ]
    ]);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Examples

See the `examples/` directory for complete usage examples.

## Requirements

- PHP 5.6 or higher
- cURL extension enabled

## License

MIT License