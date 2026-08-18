<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/mailer.php';


function respond(
    bool $success,
    string $message,
    int $status = 200
): never {
    http_response_code($status);

    echo json_encode([
        'status' => $success ? 'success' : 'error',
        'message' => $message
    ]);

    exit;
}


/*
 * Only allow POST requests.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', 405);
}


/*
 * Basic rate limiting.
 */
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$limiterFile =
    sys_get_temp_dir() .
    '/raven_contact_rate_' .
    md5($ip);

if (
    file_exists($limiterFile) &&
    (time() - filemtime($limiterFile)) < 1
) {
    respond(
        false,
        'Please wait a moment before submitting again.',
        429
    );
}

@touch($limiterFile);


/*
 * Clean input.
 */
function clean(string $value): string
{
    return trim(strip_tags($value));
}

$name = clean(
    (string)($_POST['name'] ?? '')
);

$email = trim(
    (string)($_POST['email'] ?? '')
);

$subject = clean(
    (string)($_POST['subject'] ?? '')
);

$message = clean(
    (string)($_POST['message'] ?? '')
);


/*
 * Validate required fields.
 */
if (
    $name === '' ||
    $email === '' ||
    $subject === '' ||
    $message === ''
) {
    respond(
        false,
        'All fields are required.',
        400
    );
}


/*
 * Validate email address.
 */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(
        false,
        'Please enter a valid email address.',
        400
    );
}


/*
 * Build email.
 */
$timestamp = date(
    'Y-m-d H:i:s'
);

$body = <<<TEXT
Website Contact Form Submission

Time: {$timestamp}
Name: {$name}
Email: {$email}
Subject: {$subject}

Message:
{$message}
TEXT;


/*
 * Send through Microsoft 365 OAuth / PHPMailer.
 */
try {

    $mail = createMailer();

    $mail->addAddress(
        $config['recipient'],
        'Raven Fire Protection'
    );

    $mail->addReplyTo(
        $email,
        $name
    );

    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->isHTML(false);

    $mail->send();

    respond(
        true,
        'Your message has been sent. Thank you!'
    );

} catch (Throwable $e) {

    error_log(
        'Raven contact form error: ' .
        $e->getMessage()
    );

    respond(
        false,
        'The message could not be sent. Please call us directly at 253-387-1090.',
        500
    );
}