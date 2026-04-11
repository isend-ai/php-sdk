# isend.ai PHP SDK

A simple PHP SDK for sending emails, Telegram messages, and events through isend.ai using various email connectors like AWS SES, SendGrid, Mailgun, and more.

For **linking customers to your Telegram bot** (start flow, claim URL, polling status), use `ISendTelegramConnectorClient` with the connector **`api_secret_token`** from the iSend dashboard. For **sending Telegram templates** with your account API key, use `ISendClient::sendTelegramTemplate()` as below.

## Installation

```bash
composer require isend-ai/php-sdk
```

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use ISend\ISendClient;

// Initialize with your isend.ai API key (or ISEND_API_KEY env var)
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

Uses your **account API key** (`ISendClient`). The customer must already be connected to your Telegram bot.

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

### Telegram bot linking (Start flow)

Use **`ISendTelegramConnectorClient`** with the connector **`api_secret_token`** from the iSend dashboard. Requests are authenticated with the `X-ISEND-TELEGRAM-SECRET` header, not `api_key` in the JSON body.

Base URL follows the same rules as `ISendClient`: optional constructor argument, else `ISEND_API_BASE_URL`, else `https://www.isend.ai`.

```php
use ISend\ISendTelegramConnectorClient;

$tg = new ISendTelegramConnectorClient('your-connector-api-secret-token');

// POST /api/telegram/start — returns claim_url (or already_connected), includes _http_code on success
$start = $tg->telegramStart(
    'customer@example.com',  // email (recommended)
    null,                   // user_id (optional)
    null,                   // session_id (optional UUID)
    false                   // force_new_link (optional)
);

// After the user opens the claim link and taps Start in Telegram, poll status:
// POST /api/telegram/start/status — requires at least one of session_id, email, or tg_customer_id
$status = $tg->telegramStartStatus(
    $start['session_id'] ?? null,
    'customer@example.com',
    null  // or tg_customer_id when you have it
);

if (ISendTelegramConnectorClient::isOkResponse($status)) {
    // Linked or in expected state — inspect API payload for details
}
```

On transport failure, `telegramStart()` / `telegramStartStatus()` return `null`. On HTTP responses with valid JSON, you get an array that includes `_http_code` (and often `success`). Invalid JSON from the server is surfaced as an array with `success` false and a message, not `null`. Use `ISendTelegramConnectorClient::isOkResponse()` to treat 2xx and optional `success` in a consistent way.

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

### ISendTelegramConnectorClient

Connector-scoped client for Telegram **start / link** APIs. Does not use `ISendClient` or the account API key.

#### Constructor

```php
new ISendTelegramConnectorClient(string $apiSecretToken, ?string $baseUrl = null)
```

**Parameters:**

- `$apiSecretToken` (string): Connector `api_secret_token` from the iSend dashboard. Empty or whitespace-only values throw `InvalidArgumentException`.
- `$baseUrl` (string|null): API base URL; if omitted, uses `ISEND_API_BASE_URL` or `https://www.isend.ai`.

#### Methods

##### telegramStart(?string $email = null, ?string $userId = null, ?string $sessionId = null, bool $forceNewLink = false): ?array

`POST /api/telegram/start`. Begins the connect flow.

**Returns:** Decoded JSON including `_http_code` on normal API responses, or `null` on transport failure.

##### telegramStartStatus(?string $sessionId = null, ?string $email = null, int|string|null $tgCustomerId = null): ?array

`POST /api/telegram/start/status`. Polls after the user completes the flow in Telegram. At least one of `session_id`, `email`, or `tg_customer_id` should be set to match your integration.

**Returns:** Same semantics as `telegramStart()`.

##### isOkResponse(?array $response): bool (static)

Returns whether the response looks successful: HTTP code in the 2xx range (from `_http_code`, defaulting to 200 if missing) and, if the body includes a `success` key, that value is truthy.

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

##### sendEmail(int|string $templateIdOrName, string $to, array $dataMapping = [], ?string $from = null, ?int $eventId = null): ?array

Sends an email using the provided template.

**Parameters:**
- `$templateIdOrName` (int|string): Template ID (numeric) or template name (string) from isend.ai
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

### ISendClient

`ISendClient` uses `error_log()` for error reporting and returns `null` on errors (including non-200 HTTP responses). Check the return value:

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

Common issues include missing or invalid API key, invalid email addresses, invalid template identifiers, and network or HTTP errors.

### ISendTelegramConnectorClient

Returns `null` only on transport-level failures (for example cURL errors). JSON responses include `_http_code` even when the HTTP status is not 2xx. Use `ISendTelegramConnectorClient::isOkResponse()` for a simple success check, or inspect `_http_code` and the API body yourself.

## Examples

See the `examples/` directory for complete usage examples.

## Requirements

- PHP 7.1 or higher
- cURL extension enabled

## License

MIT License