<?php

declare(strict_types=1);

$config = require __DIR__ . '/mail-config.php';


/**
 * Get an OAuth access token for Microsoft Graph.
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
        'scope' => 'https://graph.microsoft.com/.default',
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

    if (
        $httpCode < 200 ||
        $httpCode >= 300 ||
        empty($data['access_token'])
    ) {
        $errorDescription =
            $data['error_description'] ?? 'Unknown OAuth error';

        throw new RuntimeException(
            'Microsoft OAuth failed: ' . $errorDescription
        );
    }

    return $data['access_token'];
}


/**
 * Send an email through Microsoft Graph.
 *
 * Attachments must be supplied as:
 *
 * [
 *     [
 *         'path' => '/tmp/example.pdf',
 *         'name' => 'example.pdf',
 *         'type' => 'application/pdf'
 *     ]
 * ]
 */
function sendMicrosoftGraphEmail(
    string $subject,
    string $body,
    string $replyTo,
    array $attachments = []
): void {

    global $config;

    $accessToken = getMicrosoftAccessToken($config);

    $message = [
        'subject' => $subject,

        'body' => [
            'contentType' => 'Text',
            'content' => $body,
        ],

        'toRecipients' => [
            [
                'emailAddress' => [
                    'address' => $config['recipient'],
                ],
            ],
        ],

        'replyTo' => [
            [
                'emailAddress' => [
                    'address' => $replyTo,
                ],
            ],
        ],
    ];


    /*
     * Add file attachments.
     */
    if (!empty($attachments)) {

        $message['attachments'] = [];

        foreach ($attachments as $attachment) {

            $path = $attachment['path'] ?? '';
            $name = $attachment['name'] ?? 'attachment';
            $type = $attachment['type'] ?? 'application/octet-stream';

            if (!is_file($path)) {
                continue;
            }

            $content = file_get_contents($path);

            if ($content === false) {
                throw new RuntimeException(
                    'Could not read attachment: ' . $name
                );
            }

            $message['attachments'][] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $name,
                'contentType' => $type,
                'contentBytes' => base64_encode($content),
            ];
        }
    }


    $payload = json_encode([
        'message' => $message,
        'saveToSentItems' => true,
    ], JSON_THROW_ON_ERROR);


    /*
     * Microsoft Graph sendMail endpoint.
     */
    $url =
        'https://graph.microsoft.com/v1.0/users/' .
        rawurlencode($config['from_email']) .
        '/sendMail';


    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],

        CURLOPT_TIMEOUT => 30,
    ]);


    $response = curl_exec($ch);

    if ($response === false) {

        $error = curl_error($ch);

        curl_close($ch);

        throw new RuntimeException(
            'Microsoft Graph connection failed: ' . $error
        );
    }


    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);


    /*
     * Microsoft Graph returns 202 Accepted
     * when sendMail is successfully accepted.
     */
    if ($httpCode < 200 || $httpCode >= 300) {

        throw new RuntimeException(
            'Microsoft Graph sendMail failed. HTTP ' .
            $httpCode .
            ': ' .
            $response
        );
    }
}