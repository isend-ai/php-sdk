<?php

namespace ISend;

/**
 * PHP SDK for isend.ai
 * 
 * Provides methods to send emails, Telegram messages, and events via isend.ai API
 */
class ISendClient
{
    private $apiKey;
    private $baseUrl;

    /**
     * Create a new ISendClient instance
     *
     * @param string|null $apiKey Your isend.ai API key (optional, will use ISEND_API_KEY env var if not provided)
     * @param string|null $baseUrl Base URL for API (optional, will use ISEND_API_BASE_URL env var or default if not provided)
     */
    public function __construct(?string $apiKey = null, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['ISEND_API_KEY'] ?? null;
        $this->baseUrl = $baseUrl ?? $_ENV['ISEND_API_BASE_URL'] ?? 'https://www.isend.ai';
    }

    /**
     * Get API key (from instance or environment)
     */
    private function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * Get base URL (from instance or environment)
     */
    private function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Make a cURL request to iSend.ai API
     *
     * @param string $endpoint API endpoint (e.g., '/api/send-email')
     * @param array $data Request data
     * @param string $logContext Context for logging
     * @return array|null Response data or null on error
     */
    private function makeRequest(string $endpoint, array $data, string $logContext): ?array
    {
        try {
            $apiKey = $this->getApiKey();
            
            if (!$apiKey) {
                error_log("ISendClient: API key not found. Provide it in constructor or set ISEND_API_KEY environment variable");
                return null;
            }

            // Add API key to data if not already present
            if (!isset($data['api_key'])) {
                $data['api_key'] = $apiKey;
            }

            $url = $this->getBaseUrl() . $endpoint;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                error_log("ISendClient CURL error ({$logContext}): " . $curlError);
                return null;
            }

            if ($httpCode !== 200) {
                error_log("ISendClient HTTP error ({$logContext}): " . $httpCode . " - " . $response);
                return null;
            }

            $responseData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("ISendClient JSON decode error ({$logContext}): " . json_last_error_msg());
                return null;
            }

            return $responseData;

        } catch (\Exception $e) {
            error_log("ISendClient exception ({$logContext}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send Email using template
     * 
     * POST /api/send-email
     * 
     * @param int $templateId Template ID from isend.ai
     * @param string $to Recipient email address
     * @param array $dataMapping Key-value pairs for template variables (optional)
     * @param string|null $from Sender email address (optional, defaults to noreply@isend.ai)
     * @param int|null $eventId Event ID (optional, defaults to 1)
     * @return array|null Response from isend.ai API or null on error
     */
    public function sendEmail(int $templateId, string $to, array $dataMapping = [], ?string $from = null, ?int $eventId = null): ?array
    {
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("ISendClient: valid 'to' email is required");
            return null;
        }

        if (empty($templateId) || !is_numeric($templateId)) {
            error_log("ISendClient: valid template_id is required");
            return null;
        }

        $data = [
            'template_id' => (int)$templateId,
            'to' => $to,
        ];

        // Add data_mapping (accept both formats)
        if (!empty($dataMapping)) {
            $data['data_mapping'] = $dataMapping;
            // Also add dataMapping for compatibility
            $data['dataMapping'] = $dataMapping;
        }

        if (!empty($from)) {
            $data['from'] = $from;
        }

        if (!empty($eventId)) {
            $data['event_id'] = (int)$eventId;
        }

        return $this->makeRequest('/api/send-email', $data, "Send Email (template_id: {$templateId}, to: {$to})");
    }

    /**
     * Send Telegram message using template
     * 
     * POST /api/telegram/send-template
     * 
     * @param string $email Customer's email address (must be connected to Telegram bot)
     * @param int $templateId Template ID from isend.ai
     * @param array $dataMapping Key-value pairs for template variables (optional, can be empty)
     * @param int|null $connectorId Optional connector_id if multiple connectors exist
     * @return array|null Response from isend.ai API or null on error
     */
    public function sendTelegramTemplate(string $email, int $templateId, array $dataMapping = [], ?int $connectorId = null): ?array
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("ISendClient: valid email is required");
            return null;
        }

        if (empty($templateId) || !is_numeric($templateId)) {
            error_log("ISendClient: valid template_id is required");
            return null;
        }

        $data = [
            'template_id' => (int)$templateId,
            'email' => $email,
            'data_mapping' => $dataMapping,
            // Also add dataMapping for compatibility
            'dataMapping' => $dataMapping,
        ];

        if (!empty($connectorId)) {
            $data['connector_id'] = (int)$connectorId;
        }

        return $this->makeRequest('/api/telegram/send-template', $data, "Send Telegram Template (template_id: {$templateId}, email: {$email})");
    }

    /**
     * Send Event (triggers multiple messages - email and/or Telegram)
     * 
     * POST /api/send-event
     * 
     * @param int $eventId Event ID from isend.ai
     * @param string $to Recipient email address (can also use 'email' parameter)
     * @param array $dataMapping Key-value pairs for template variables (required for all templates in event)
     * @return array|null Response from isend.ai API or null on error
     */
    public function sendEvent(int $eventId, string $to, array $dataMapping = []): ?array
    {
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("ISendClient: valid 'to' email is required");
            return null;
        }

        if (empty($eventId) || !is_numeric($eventId)) {
            error_log("ISendClient: valid event_id is required");
            return null;
        }

        $data = [
            'event_id' => (int)$eventId,
            'to' => $to,
            // Also include 'email' for compatibility
            'email' => $to,
            'data_mapping' => $dataMapping,
            // Also add dataMapping for compatibility
            'dataMapping' => $dataMapping,
        ];

        return $this->makeRequest('/api/send-event', $data, "Send Event (event_id: {$eventId}, to: {$to})");
    }

    /**
     * Legacy method name - kept for backward compatibility
     * 
     * @deprecated Use sendTelegramTemplate() instead
     */
    public function sendTemplateByEmail(string $email, int $templateId, array $dataMapping = [], ?int $connectorId = null): ?array
    {
        return $this->sendTelegramTemplate($email, $templateId, $dataMapping, $connectorId);
    }

    // here we need to add the api to send the whatsapp messsage also
}
