<?php
// forms/contact.php
header('Content-Type: application/json; charset=utf-8');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// tiny rate limit to deter spam during testing
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$limiterFile = sys_get_temp_dir() . '/contact_rate_' . md5($ip);
if (file_exists($limiterFile) && (time() - filemtime($limiterFile)) < 2) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Slow down — please wait a moment and try again.']);
    exit;
}
@touch($limiterFile);

// helpers
function clean($s) {
    return trim(htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}

$name    = clean($_POST['name'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');

// validate
if ($name === '' || $email === '' || $subject === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}

// load config and PHPMailer
$autoload = __DIR__ . '/../vendor/autoload.php';
$configFile = __DIR__ . '/../mail-config.php';

if (!file_exists($autoload)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: missing dependencies. Run composer install.']);
    exit;
}
require $autoload;

if (!file_exists($configFile)) {
    // create a placeholder response rather than exposing internals
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: mail configuration missing.']);
    exit;
}

$config = require $configFile;

// prepare email body
$timestamp = date('Y-m-d H:i:s');
$body  = "Website contact form submission\n\n";
$body .= "Time: $timestamp\n";
$body .= "IP: $ip\n\n";
$body .= "Name: $name\n";
$body .= "Email: $email\n";
$body .= "Subject: $subject\n\n";
$body .= "Message:\n$message\n";

// destination (where site owner receives messages) — set this in mail-config or here
$toAddress = $config['recipient'] ?? ($config['username'] ?? null);
if (!$toAddress) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server misconfiguration: recipient not set.']);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mailSent = false;
$mailError = null;

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $config['host'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['username'] ?? '';
    $mail->Password   = $config['password'] ?? '';
    $mail->SMTPSecure = $config['smtp_secure'] ?? 'tls';
    $mail->Port       = $config['port'] ?? 587;

    // headers & recipients
    $mail->setFrom($config['from_email'] ?? $mail->Username, $config['from_name'] ?? 'Website Contact');
    // reply to the user so you can click-reply in your inbox
    $mail->addReplyTo($email, $name);
    $mail->addAddress($toAddress);

    // content
    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body    = $body;

    // send
    $mail->send();
    $mailSent = true;
} catch (Exception $e) {
    $mailError = $mail->ErrorInfo ?? $e->getMessage();
}

// fallback: log to disk for testing and audit
$logDir = __DIR__ . '/../submissions';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

$logFile = $logDir . '/contact-' . date('Ymd') . '.log';
$logEntry = "-----\nID: " . uniqid('', true) . "\n$body\nSent? " . ($mailSent ? 'yes' : 'no') . "\nError: " . ($mailError ?? 'none') . "\n\n";
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// respond to client (keep UX smooth; don't leak internal errors)
if ($mailSent) {
    echo json_encode(['status' => 'success', 'message' => 'Your message has been sent. Thank you!']);
} else {
    // return success-like message so the user isn't confused, but log the error for you
    echo json_encode(['status' => 'success', 'message' => 'Message recorded. (Mail delivery not configured or failed.)']);
}
exit;
