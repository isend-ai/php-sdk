<?php

namespace ISend;

/**
 * PHP SDK for isend.ai
 * 
 * Provides methods to send emails, Telegram messages, WhatsApp messages, and events via isend.ai API
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
     * @param int|string $templateIdOrName Template ID (int) or Template Name (string) from isend.ai
     * @param string $to Recipient email address
     * @param array $dataMapping Key-value pairs for template variables (optional)
     * @param string|null $from Sender email address (optional, defaults to noreply@isend.ai)
     * @param int|null $eventId Event ID (optional)
     * @return array|null Response from isend.ai API or null on error
     */
    public function sendEmail($templateIdOrName, string $to, array $dataMapping = [], ?string $from = null, ?int $eventId = null): ?array
    {
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("ISendClient: valid 'to' email is required");
            return null;
        }

        $data = [
            'to' => $to,
        ];

        // Add template identifier (ID or name)
        if (is_numeric($templateIdOrName)) {
            $data['template_id'] = (int)$templateIdOrName;
        } else {
            $data['template_name'] = (string)$templateIdOrName;
        }

        // Add data_mapping (accept both formats for compatibility)
        if (!empty($dataMapping)) {
            $data['data_mapping'] = $dataMapping;
            $data['dataMapping'] = $dataMapping;
        }

        if (!empty($from)) {
            if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
                error_log("ISendClient: valid 'from' email is required");
                return null;
            }
            $data['from'] = $from;
        }

        if (!empty($eventId)) {
            if (!is_numeric($eventId) || $eventId <= 0) {
                error_log("ISendClient: valid event_id is required");
                return null;
            }
            $data['event_id'] = (int)$eventId;
        }

        return $this->makeRequest('/api/send-email', $data, "Send Email (template: {$templateIdOrName}, to: {$to})");
    }

    /**
     * Send Telegram message using template
     * 
     * POST /api/telegram/send-template
     * 
     * @param string $email Customer's email address (must be connected to Telegram bot)
     * @param string $templateVariable Template variable name from isend.ai
     * @param array $dataMapping Key-value pairs for template variables (optional, can be empty)
     * @param int|null $connectorId Optional connector_id if multiple connectors exist
     * @return array|null Response from isend.ai API or null on error
     */
    public function sendTelegramTemplate(string $email, string $templateVariable, array $dataMapping = [], ?int $connectorId = null): ?array
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("ISendClient: valid email is required");
            return null;
        }

        if (empty($templateVariable) || !is_string($templateVariable)) {
            error_log("ISendClient: valid template variable is required");
            return null;
        }

        $data = [
            'template' => $templateVariable,
            'email' => $email,
            'data_mapping' => $dataMapping,
            // Also add dataMapping for compatibility
            'dataMapping' => $dataMapping,
        ];

        if (!empty($connectorId)) {
            if (!is_numeric($connectorId) || $connectorId <= 0) {
                error_log("ISendClient: valid connector_id is required");
                return null;
            }
            $data['connector_id'] = (int)$connectorId;
        }

        return $this->makeRequest('/api/telegram/send-template', $data, "Send Telegram Template (template: {$templateVariable}, email: {$email})");
    }

    /**
     * Send Event (triggers multiple messages - email, Telegram, and/or WhatsApp)
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

        if (empty($eventId) || !is_numeric($eventId) || $eventId <= 0) {
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
     * Send WhatsApp message using template
     * 
     * POST /api/whatsapp/send-template
     * 
     * @param string $email Customer's email address (must be connected to WhatsApp)
     * @param string $templateVariable Template variable name from isend.ai
     * @param array $dataMapping Key-value pairs for template variables (optional, can be empty)
     * @param int|null $connectorId Optional connector_id if multiple connectors exist
     * @return array|null Response from isend.ai API or null on error
     */
    public function sendWhatsAppTemplate(string $email, string $templateVariable, array $dataMapping = [], ?int $connectorId = null): ?array
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("ISendClient: valid email is required");
            return null;
        }

        if (empty($templateVariable) || !is_string($templateVariable)) {
            error_log("ISendClient: valid template variable is required");
            return null;
        }

        $data = [
            'template' => $templateVariable,
            'email' => $email,
            'data_mapping' => $dataMapping,
            // Also add dataMapping for compatibility
            'dataMapping' => $dataMapping,
        ];

        if (!empty($connectorId)) {
            if (!is_numeric($connectorId) || $connectorId <= 0) {
                error_log("ISendClient: valid connector_id is required");
                return null;
            }
            $data['connector_id'] = (int)$connectorId;
        }

        return $this->makeRequest('/api/whatsapp/send-template', $data, "Send WhatsApp Template (template: {$templateVariable}, email: {$email})");
    }

    /**
     * Upload contacts in bulk (tenant/WAP).
     *
     * POST /api/contacts/bulk
     *
     * @param string $tenantCode Tenant wap_code (e.g. wedplan)
     * @param string $userEmail User email (must be linked to tenant and account)
     * @param string $listName Contact list name (created if missing)
     * @param array $contacts Array of [ 'mobile_no' => string, 'name' => ?string, 'email' => ?string ]
     * @param string $platform Platform: "whatsapp" or "email" (optional, default: whatsapp)
     * @return array|null Response with success, message, data.contact_list_id, data.added, data.skipped_duplicate, data.invalid
     */
    public function uploadContactsBulk(string $tenantCode, string $userEmail, string $listName, array $contacts, string $platform = 'whatsapp'): ?array
    {
        if (empty($tenantCode) || empty($userEmail) || empty($listName)) {
            error_log("ISendClient: tenant_code, user_email and list_name are required");
            return null;
        }
        if (!is_array($contacts) || empty($contacts)) {
            error_log("ISendClient: contacts must be a non-empty array");
            return null;
        }

        $data = [
            'tenant_code' => $tenantCode,
            'user_email' => $userEmail,
            'list_name' => $listName,
            'platform' => $platform,
            'contacts' => $contacts,
        ];

        return $this->makeRequest('/api/contacts/bulk', $data, "Upload contacts bulk (tenant: {$tenantCode}, list: {$listName})");
    }

    /**
     * Legacy method name - kept for backward compatibility
     *
     * @deprecated Use sendTelegramTemplate() instead
     */
    public function sendTemplateByEmail(string $email, string $templateVariable, array $dataMapping = [], ?int $connectorId = null): ?array
    {
        return $this->sendTelegramTemplate($email, $templateVariable, $dataMapping, $connectorId);
    }

    /**
     * Create Email Template
     * 
     * POST /api/email-templates/create
     * 
     * @param string $title Template title
     * @param string $subject Email subject line
     * @param int $connectorId Connector ID
     * @param int|null $layoutId Layout ID (optional)
     * @param bool $includeUnsubscribe Whether to include unsubscribe link (optional, default: false)
     * @param string|null $body HTML body of the email template (optional)
     * @param array $tagIds Array of tag IDs (optional)
     * @return array|null Response from isend.ai API or null on error
     */
    public function createEmailTemplate(string $title, string $subject, int $connectorId, ?int $layoutId = null, bool $includeUnsubscribe = false, ?string $body = null, array $tagIds = []): ?array
    {
        if (empty($title) || empty($subject)) {
            error_log("ISendClient: title and subject are required");
            return null;
        }

        if (empty($connectorId) || !is_numeric($connectorId) || $connectorId <= 0) {
            error_log("ISendClient: valid connector_id is required");
            return null;
        }

        $data = [
            'title' => $title,
            'subject' => $subject,
            'connector_id' => (int)$connectorId,
        ];

        if (!empty($layoutId)) {
            if (!is_numeric($layoutId) || $layoutId <= 0) {
                error_log("ISendClient: valid layout_id is required");
                return null;
            }
            $data['layout_id'] = (int)$layoutId;
        }

        if ($includeUnsubscribe) {
            $data['include_unsubscribe'] = true;
        }

        if (!empty($body)) {
            $data['body'] = $body;
        }

        if (!empty($tagIds) && is_array($tagIds)) {
            $data['tag_ids'] = array_filter($tagIds, function ($id) {
                return is_numeric($id) && $id > 0;
            });
        }

        return $this->makeRequest('/api/email-templates/create', $data, "Create Email Template (title: {$title})");
    }

    /**
     * List Email Templates
     * 
     * POST /api/email-templates/list
     * 
     * @param string|null $search Search text for title/subject (optional)
     * @param string|null $status Filter by status: "active" or "inactive" (optional)
     * @param int|null $connectorId Filter by connector ID (optional)
     * @param int|null $layoutId Filter by layout ID (optional)
     * @param array $tags Filter by tag names (optional)
     * @param string|null $dateFrom Filter from date (YYYY-MM-DD) (optional)
     * @param string|null $dateTo Filter to date (YYYY-MM-DD) (optional)
     * @param int $page Page number (optional, default: 1)
     * @param int $perPage Items per page (optional, default: 20)
     * @return array|null Response from isend.ai API or null on error
     */
    public function listEmailTemplates(?string $search = null, ?string $status = null, ?int $connectorId = null, ?int $layoutId = null, array $tags = [], ?string $dateFrom = null, ?string $dateTo = null, int $page = 1, int $perPage = 20): ?array
    {
        $data = [];

        if (!empty($search)) {
            $data['search'] = $search;
        }

        if (!empty($status)) {
            if (!in_array($status, ['active', 'inactive'])) {
                error_log("ISendClient: status must be 'active' or 'inactive'");
                return null;
            }
            $data['status'] = $status;
        }

        if (!empty($connectorId)) {
            if (!is_numeric($connectorId) || $connectorId <= 0) {
                error_log("ISendClient: valid connector_id is required");
                return null;
            }
            $data['connector_id'] = (int)$connectorId;
        }

        if (!empty($layoutId)) {
            if (!is_numeric($layoutId) || $layoutId <= 0) {
                error_log("ISendClient: valid layout_id is required");
                return null;
            }
            $data['layout_id'] = (int)$layoutId;
        }

        if (!empty($tags) && is_array($tags)) {
            $data['tags'] = $tags;
        }

        if (!empty($dateFrom)) {
            $data['date_from'] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $data['date_to'] = $dateTo;
        }

        if ($page > 0) {
            $data['page'] = (int)$page;
        }

        if ($perPage > 0) {
            $data['per_page'] = (int)$perPage;
        }

        return $this->makeRequest('/api/email-templates/list', $data, "List Email Templates");
    }

    /**
     * Update Email Template Version
     * 
     * POST /api/email-templates/update-version
     * 
     * @param int $templateId Template ID to update
     * @param string|null $title New template title (optional)
     * @param string|null $subject New email subject line (optional)
     * @param int|null $connectorId New connector ID (optional)
     * @param int|null $layoutId New layout ID (optional, use null to remove)
     * @param bool|null $includeUnsubscribe Whether to include unsubscribe link (optional)
     * @param array $tagIds New array of tag IDs (optional, replaces existing tags)
     * @return array|null Response from isend.ai API or null on error
     */
    public function updateEmailTemplateVersion(int $templateId, ?string $title = null, ?string $subject = null, ?int $connectorId = null, ?int $layoutId = null, ?bool $includeUnsubscribe = null, array $tagIds = []): ?array
    {
        if (empty($templateId) || !is_numeric($templateId) || $templateId <= 0) {
            error_log("ISendClient: valid template_id is required");
            return null;
        }

        $data = [
            'template_id' => (int)$templateId,
        ];

        if (!empty($title)) {
            $data['title'] = $title;
        }

        if (!empty($subject)) {
            $data['subject'] = $subject;
        }

        if (!empty($connectorId)) {
            if (!is_numeric($connectorId) || $connectorId <= 0) {
                error_log("ISendClient: valid connector_id is required");
                return null;
            }
            $data['connector_id'] = (int)$connectorId;
        }

        if (isset($layoutId)) {
            if ($layoutId === null) {
                $data['layout_id'] = null;
            } elseif (is_numeric($layoutId) && $layoutId > 0) {
                $data['layout_id'] = (int)$layoutId;
            } else {
                error_log("ISendClient: valid layout_id is required");
                return null;
            }
        }

        if (isset($includeUnsubscribe)) {
            $data['include_unsubscribe'] = (bool)$includeUnsubscribe;
        }

        if (!empty($tagIds) && is_array($tagIds)) {
            $data['tag_ids'] = array_filter($tagIds, function ($id) {
                return is_numeric($id) && $id > 0;
            });
        } elseif (isset($tagIds) && is_array($tagIds) && empty($tagIds)) {
            // Explicitly set empty array to remove all tags
            $data['tag_ids'] = [];
        }

        return $this->makeRequest('/api/email-templates/update-version', $data, "Update Email Template Version (template_id: {$templateId})");
    }
}
