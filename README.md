# isend.ai PHP SDK

A simple PHP SDK for sending emails, Telegram messages, and events through isend.ai using various email connectors like AWS SES, SendGrid, Mailgun, and more.

## Installation

```bash
composer require isend-ai/php-sdk
```

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use ISend\ISendClient;

// Initialize the client (API key can also be set via ISEND_API_KEY environment variable)
$client = new ISendClient('your-api-key-here');

// Send email using template
$response = $client->sendEmail(
    124,  // template_id
    'hi@isend.ai',  // recipient email
    [  // template variables
        'name' => 'ISend',
        'user_name' => 'John Doe'
    ]
);

print_r($response);
```

## Usage

### Configuration

The SDK can be configured via constructor parameters or environment variables:

```php

// Set ISEND_API_KEY
$client = new ISendClient();
```
### Send Email Using Template

```php
$response = $client->sendEmail(
    124,  // template_id (required)
    'recipient@example.com',  // to (required)
    [  // dataMapping (optional)
        'user_name' => 'John Doe',
        'time' => date('Y-m-d H:i:s'),
        'year' => date('Y')
    ],
    'sender@example.com',  // from (optional, defaults to noreply@isend.ai)
    1  // event_id (optional)
);
```

### Send Telegram Message Using Template

```php
$response = $client->sendTelegramTemplate(
    'customer@example.com',  // email (required) - must be connected to Telegram bot
    'template_variable',  // template variable name (required)
    [  // dataMapping (optional)
        'user_name' => 'John Doe',
        'time' => date('Y-m-d H:i:s'),
        'user_type' => 'Planner'
    ]
);
```

### Send Event

Events can trigger multiple messages (email and/or Telegram) based on your event configuration:

```php
$response = $client->sendEvent(
    3,  // event_id (required)
    'recipient@example.com',  // to (required)
    [  // dataMapping (required for all templates in event)
        'user_name' => 'John Doe',
        'time' => date('Y-m-d H:i:s'),
        'year' => date('Y'),
        'user_type' => 'Planner'
    ]
);
```

## API Reference

### ISendClient

#### Constructor
```php
new ISendClient(?string $apiKey = null, ?string $baseUrl = null)
```

Creates a new ISendClient instance.

**Parameters:**
- `$apiKey` (string|null): Your isend.ai API key. If not provided, will use `ISEND_API_KEY` environment variable.
- `$baseUrl` (string|null): Base URL for API. If not provided, will use `ISEND_API_BASE_URL` environment variable or default to `https://www.isend.ai`.

#### Methods

##### sendEmail(int $templateId, string $to, array $dataMapping = [], ?string $from = null, ?int $eventId = null): ?array

Sends an email using the provided template.

**Parameters:**
- `$templateId` (int): Template ID from isend.ai
- `$to` (string): Recipient email address
- `$dataMapping` (array): Key-value pairs for template variables (optional)
- `$from` (string|null): Sender email address (optional, defaults to noreply@isend.ai)
- `$eventId` (int|null): Event ID (optional)

**Returns:** Response array from isend.ai API or `null` on error.

##### sendTelegramTemplate(string $email, string $templateVariable, array $dataMapping = [], ?int $connectorId = null): ?array

Sends a Telegram message using a template.

**Parameters:**
- `$email` (string): Customer's email address (must be connected to Telegram bot)
- `$templateVariable` (string): Template variable name from isend.ai
- `$dataMapping` (array): Key-value pairs for template variables (optional, can be empty)
- `$connectorId` (int|null): Optional connector_id if multiple connectors exist

**Returns:** Response array from isend.ai API or `null` on error.

##### sendEvent(int $eventId, string $to, array $dataMapping = []): ?array

Sends an event that triggers multiple messages (email and/or Telegram).

**Parameters:**
- `$eventId` (int): Event ID from isend.ai
- `$to` (string): Recipient email address
- `$dataMapping` (array): Key-value pairs for template variables (required for all templates in event)

**Returns:** Response array from isend.ai API or `null` on error.

## Error Handling

The SDK uses `error_log()` for error reporting and returns `null` on errors. Check the return value:

```php
$response = $client->sendEmail(124, 'recipient@example.com', [
    'name' => 'ISend'
]);

if ($response === null) {
    // Error occurred - check error logs for details
    echo "Failed to send email. Check error logs for details.";
} else {
    // Success
    print_r($response);
}
```

Errors are logged with descriptive messages. Common errors include:
- Missing or invalid API key
- Invalid email addresses
- Invalid template ID or template variable
- Network/HTTP errors

## Examples

See the `examples/` directory for complete usage examples.

## Requirements

- PHP 7.1 or higher
- cURL extension enabled

## License

MIT License