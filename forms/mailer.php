<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\OAuthTokenProvider;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/mail-config.php';


/**
 * Gets an OAuth access token from Microsoft Entra ID
 * using the application's client credentials.
 */
function getMicrosoftAccessToken(array $config): string
{
    $tenantId = $config['tenant_id'] ?? '';
    $clientId = $config['client_id'] ?? '';
    $clientSecret = $config['client_secret'] ?? '';

    if (!$tenantId || !$clientId || !$clientSecret) {
        throw new RuntimeException(
            'Microsoft OAuth configuration is incomplete.'
        );
    }

    $tokenUrl =
        'https://login.microsoftonline.com/' .
        rawurlencode($tenantId) .
        '/oauth2/v2.0/token';

    $postData = http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'scope' => 'https://outlook.office365.com/.default',
        'grant_type' => 'client_credentials',
    ]);

    $ch = curl_init($tokenUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);

        throw new RuntimeException(
            'Could not connect to Microsoft OAuth: ' . $error
        );
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300 || empty($data['access_token'])) {
        $errorDescription = $data['error_description'] ?? 'Unknown OAuth error';

        throw new RuntimeException(
            'Microsoft OAuth failed: ' . $errorDescription
        );
    }

    return $data['access_token'];
}


/**
 * Creates a configured PHPMailer instance.
 */
function createMailer(): PHPMailer
{
    global $config;

    $accessToken = getMicrosoftAccessToken($config);

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->Port = $config['smtp_port'];

    $mail->SMTPAuth = true;
    $mail->AuthType = 'XOAUTH2';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->setFrom(
        $config['from_email'],
        $config['from_name']
    );

    /*
     * PHPMailer needs an OAuth token provider for XOAUTH2.
     */
    $mail->setOAuth(
        new class(
            $config['from_email'],
            $accessToken
        ) implements OAuthTokenProvider {

            private string $username;
            private string $accessToken;

            public function __construct(
                string $username,
                string $accessToken
            ) {
                $this->username = $username;
                $this->accessToken = $accessToken;
            }

            public function getOauth64(): string
            {
                return base64_encode(
                    'user=' . $this->username .
                    "\001auth=Bearer " . $this->accessToken .
                    "\001\001"
                );
            }
        }
    );

    return $mail;
}