<?php

namespace ISend;

/**
 * Client for Telegram connector APIs that authenticate with
 * the connector `api_secret_token` from the iSend dashboard (header `X-ISEND-TELEGRAM-SECRET`),
 * not the account API key.
 *
 * Use this for linking customers via Telegram Start / Start status.
 * Use `ISendClient` (same package) for account-key APIs such as sendTelegramTemplate().
 */
class ISendTelegramConnectorClient
{
    /** @var string */
    private $apiSecretToken;

    /** @var string */
    private $baseUrl;

    /**
     * @param string $apiSecretToken Connector api_secret_token from the iSend dashboard
     * @param string|null $baseUrl Base URL (e.g. https://www.isend.ai). Defaults to ISEND_API_BASE_URL env or https://www.isend.ai
     */
    public function __construct(string $apiSecretToken, ?string $baseUrl = null)
    {
        $trimmed = trim($apiSecretToken);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Telegram connector API secret token is required');
        }
        $this->apiSecretToken = $trimmed;
        $envBase = isset($_ENV['ISEND_API_BASE_URL']) ? (string) $_ENV['ISEND_API_BASE_URL'] : '';
        $this->baseUrl = rtrim($baseUrl !== null && $baseUrl !== '' ? $baseUrl : ($envBase !== '' ? $envBase : 'https://www.isend.ai'), '/');
    }

    /**
     * POST /api/telegram/start — begin a connect flow; returns claim_url or already_connected payload.
     *
     * @param string|null $email Customer email (recommended)
     * @param string|null $userId Optional external user id (legacy / alternate match)
     * @param string|null $sessionId Optional UUID to reuse or correlate sessions
     * @param bool $forceNewLink When true, issues a new claim nonce even if a pending session exists
     * @return array|null Decoded JSON; includes _http_code on every response. Null only on transport failure.
     */
    public function telegramStart(?string $email = null, ?string $userId = null, ?string $sessionId = null, $forceNewLink = false)
    {
        $payload = [];
        if ($email !== null && $email !== '') {
            $payload['email'] = $email;
        }
        if ($userId !== null && $userId !== '') {
            $payload['user_id'] = $userId;
        }
        if ($sessionId !== null && $sessionId !== '') {
            $payload['session_id'] = $sessionId;
        }
        if ($forceNewLink) {
            $payload['force_new_link'] = true;
        }

        return $this->postJson('/api/telegram/start', $payload, 'telegram/start');
    }

    /**
     * POST /api/telegram/start/status — poll after user opens claim_url and taps Start in Telegram.
     *
     * The API requires at least one of session_id, email, or tg_customer_id.
     *
     * @param string|null $sessionId Session id returned from telegramStart()
     * @param string|null $email Customer email
     * @param int|string|null $tgCustomerId iSend customer id
     * @return array|null Decoded JSON; includes _http_code. Null only on transport failure.
     */
    public function telegramStartStatus(?string $sessionId = null, ?string $email = null, $tgCustomerId = null)
    {
        $payload = [];
        if ($sessionId !== null && $sessionId !== '') {
            $payload['session_id'] = $sessionId;
        }
        if ($email !== null && $email !== '') {
            $payload['email'] = $email;
        }
        if ($tgCustomerId !== null && $tgCustomerId !== '') {
            $payload['tg_customer_id'] = is_int($tgCustomerId) ? $tgCustomerId : (int) $tgCustomerId;
        }

        return $this->postJson('/api/telegram/start/status', $payload, 'telegram/start/status');
    }

    /**
     * True if the last decoded response looks like an HTTP success (2xx) and API success flag when present.
     *
     * @param array|null $response Return value from telegramStart() or telegramStartStatus()
     */
    public static function isOkResponse($response)
    {
        if (!is_array($response)) {
            return false;
        }
        $code = isset($response['_http_code']) ? (int) $response['_http_code'] : 200;
        if ($code < 200 || $code > 299) {
            return false;
        }
        return !array_key_exists('success', $response) || !empty($response['success']);
    }

    /**
     * @param string $endpoint Path beginning with /api/
     * @param array $payload JSON object body
     * @param string $logContext
     * @return array|null
     */
    private function postJson($endpoint, array $payload, $logContext)
    {
        try {
            $url = $this->baseUrl . $endpoint;
            $ch = curl_init($url);
            if ($ch === false) {
                error_log("ISendTelegramConnectorClient: curl_init failed ({$logContext})");
                return null;
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-ISEND-TELEGRAM-SECRET: ' . $this->apiSecretToken,
            ]);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || $curlError !== '') {
                error_log("ISendTelegramConnectorClient CURL error ({$logContext}): " . $curlError);
                return null;
            }

            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                error_log("ISendTelegramConnectorClient JSON decode error ({$logContext}): " . json_last_error_msg());
                return [
                    '_http_code' => $httpCode,
                    'success' => false,
                    'message' => 'Invalid JSON response from iSend API',
                    '_raw_body' => $response,
                ];
            }

            $decoded['_http_code'] = $httpCode;
            return $decoded;
        } catch (\Exception $e) {
            error_log("ISendTelegramConnectorClient exception ({$logContext}): " . $e->getMessage());
            return null;
        }
    }
}
