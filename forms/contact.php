<?php
// forms/contact.php

header('Content-Type: application/json; charset=utf-8');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Simple rate-limiting (very small, file-based) to avoid spam during testing
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$limiterFile = sys_get_temp_dir() . '/contact_rate_' . md5($ip);
if (file_exists($limiterFile) && (time() - filemtime($limiterFile)) < 2) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Slow down — please wait a moment and try again.']);
    exit;
}
@touch($limiterFile);

// Collect + sanitize
function clean($s) {
    return trim(htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}

$name    = clean($_POST['name'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');

// Validate
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

// Destination — CHANGE THIS to your real address
$to = 'you@yourdomain.com';

// Prepare email
$timestamp = date('Y-m-d H:i:s');
$body  = "Website contact form submission\n\n";
$body .= "Time: $timestamp\n";
$body .= "IP: $ip\n\n";
$body .= "Name: $name\n";
$body .= "Email: $email\n";
$body .= "Subject: $subject\n\n";
$body .= "Message:\n$message\n";

$headers  = "From: {$name} <{$email}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Try sending email. On local dev this may return false if no mail server is configured.
$sent = false;
try {
    $sent = @mail($to, $subject, $body, $headers);
} catch (Exception $e) {
    $sent = false;
}

// Fallback for local testing: append to a file (if mail failed)
if (!$sent) {
    $dir = __DIR__ . '/../submissions';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/contact-' . date('Ymd') . '.log';
    $logLine = "-----\n$body\n";
    file_put_contents($file, $logLine, FILE_APPEND | LOCK_EX);
}

// Respond JSON
if ($sent) {
    echo json_encode(['status' => 'success', 'message' => 'Your message has been sent. Thank you!']);
} else {
    // Still return success-like message so UX stays smooth; include hint for admins in console
    echo json_encode(['status' => 'success', 'message' => 'Message recorded. (Mail delivery not configured in this environment.)']);
}
exit;
