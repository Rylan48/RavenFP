<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/mailer.php';


/*
 * JSON response helper.
 */
function respond(bool $success, string $message, int $status = 200): never
{
    http_response_code($status);

    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);

    exit;
}


/*
 * Only accept POST requests.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', 405);
}


/*
 * Honeypot.
 */
if (!empty($_POST['website'] ?? '')) {
    respond(true, 'Request received.');
}


/*
 * Clean input.
 */
function clean(string $value): string
{
    return trim(strip_tags($value));
}


/*
 * Get submitted values.
 */
$service = clean((string)($_POST['service'] ?? ''));
$propertyType = clean((string)($_POST['property_type'] ?? ''));
$residenceType = clean((string)($_POST['residence_type'] ?? ''));

$first = clean((string)($_POST['first_name'] ?? ''));
$last = clean((string)($_POST['last_name'] ?? ''));
$phone = clean((string)($_POST['phone'] ?? ''));
$emailRaw = trim((string)($_POST['email'] ?? ''));

$projectName = clean((string)($_POST['project_name'] ?? ''));
$address = clean((string)($_POST['address'] ?? ''));
$city = clean((string)($_POST['city'] ?? ''));
$zip = clean((string)($_POST['zip'] ?? ''));

$parcelNumber = clean((string)($_POST['parcel_number'] ?? ''));
$buildingPermit = clean((string)($_POST['building_permit'] ?? ''));
$squareFootage = clean((string)($_POST['square_footage'] ?? ''));

$projectType = clean((string)($_POST['project_type'] ?? ''));
$well = clean((string)($_POST['well'] ?? ''));
$waterDistrict = clean((string)($_POST['water_district'] ?? ''));
$details = clean((string)($_POST['details'] ?? ''));


/*
 * Required fields.
 *
 * These match the fields that are actually required
 * in the HTML form.
 */
$required = [
    'service' => $service,
    'property_type' => $propertyType,
    'first_name' => $first,
    'last_name' => $last,
    'phone' => $phone,
    'email' => $emailRaw,
    'project_name' => $projectName,
    'address' => $address,
    'city' => $city,
    'zip' => $zip,
    'project_type' => $projectType,
    'well' => $well
];


/*
 * Check standard required fields.
 */
foreach ($required as $field => $value) {

    if ($value === '') {
        respond(
            false,
            "Missing required field: {$field}",
            422
        );
    }
}


/*
 * Residential projects must specify
 * Single Family or Multi Family.
 *
 * Commercial projects do not need residence_type.
 */
if ($propertyType === 'Residential' && $residenceType === '') {

    respond(
        false,
        'Please select whether the residential property is Single Family or Multi Family.',
        422
    );
}


/*
 * Validate property type.
 */
if (!in_array($propertyType, ['Residential', 'Commercial'], true)) {

    respond(
        false,
        'Please select a valid property type.',
        422
    );
}


/*
 * Validate residence type when applicable.
 */
if (
    $propertyType === 'Residential' &&
    !in_array(
        $residenceType,
        ['Single Family', 'Multi Family'],
        true
    )
) {

    respond(
        false,
        'Please select a valid residential property type.',
        422
    );
}


/*
 * Validate email.
 */
$email = filter_var(
    $emailRaw,
    FILTER_VALIDATE_EMAIL
);

if (!$email) {

    respond(
        false,
        'Please enter a valid email address.',
        422
    );
}


/*
 * Validate project type.
 */
$validProjectTypes = [
    'New Construction',
    'Retrofit – New Sprinkler System',
    'Addition to Existing Sprinkler'
];

if (!in_array($projectType, $validProjectTypes, true)) {

    respond(
        false,
        'Please select a valid project type.',
        422
    );
}


/*
 * Validate well selection.
 */
if (!in_array($well, ['Yes', 'No'], true)) {

    respond(
        false,
        'Please select whether the property is serviced by a well.',
        422
    );
}


/*
 * Email destination.
 */
$to = $config['recipient'];


/*
 * Email subject.
 */
$subject =
    'Raven Fire Protection - Service Request: ' .
    $service;


/*
 * Build email.
 */
$body = <<<TEXT
NEW SERVICE REQUEST
===================

SERVICE
-------
Service: {$service}

PROPERTY TYPE
-------------
Property Type: {$propertyType}
Residential Type: {$residenceType}


CONTACT
-------
First Name: {$first}
Last Name: {$last}
Phone: {$phone}
Email: {$email}


PROPERTY / PROJECT
------------------
Project / Property Name: {$projectName}
Street Address: {$address}
City: {$city}
ZIP / Postal Code: {$zip}

Parcel #: {$parcelNumber}
Building Permit #: {$buildingPermit}
Project Sq. Ft.: {$squareFootage}


PROJECT DETAILS
---------------
Project Type: {$projectType}
Serviced by Well: {$well}
Water District: {$waterDistrict}


ADDITIONAL DETAILS
------------------
{$details}


ATTACHMENTS
-----------
Any uploaded plans, photos, or documents are attached to this email.
TEXT;


/*
 * Send through Microsoft OAuth / PHPMailer.
 */
try {

    $mail = createMailer();


    /*
     * Recipient.
     */
    $mail->addAddress(
        $to,
        'Raven Fire Protection'
    );


    /*
     * Allow Raven to reply directly to
     * the person who submitted the form.
     */
    $mail->addReplyTo(
        $email,
        "{$first} {$last}"
    );


    /*
     * Email settings.
     */
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->isHTML(false);


    /*
     * Attachments.
     */
    if (
        isset($_FILES['attachments']) &&
        isset($_FILES['attachments']['name']) &&
        is_array($_FILES['attachments']['name'])
    ) {

        $count = count($_FILES['attachments']['name']);

        for ($i = 0; $i < $count; $i++) {

            /*
             * No file selected.
             */
            if (
                ($_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE)
                === UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }


            /*
             * Ignore failed uploads.
             */
            if (
                ($_FILES['attachments']['error'][$i] ?? null)
                !== UPLOAD_ERR_OK
            ) {
                continue;
            }


            /*
             * 15 MB maximum per attachment.
             */
            if (
                ($_FILES['attachments']['size'][$i] ?? 0)
                > 15 * 1024 * 1024
            ) {
                continue;
            }


            $tmp = $_FILES['attachments']['tmp_name'][$i];

            $filename = basename(
                (string)$_FILES['attachments']['name'][$i]
            );


            /*
             * Make sure this is actually an uploaded file.
             */
            if (is_uploaded_file($tmp)) {

                $mail->addAttachment(
                    $tmp,
                    $filename
                );
            }
        }
    }


    /*
     * Send email.
     */
    $mail->send();


    /*
     * Success.
     */
    respond(
        true,
        'Request received successfully.'
    );


} catch (Throwable $e) {

    /*
     * Log the actual error on the server.
     * Don't expose internal mailer details
     * to the visitor.
     */
    error_log(
        'Raven request service form error: ' .
        $e->getMessage()
    );


    respond(
        false,
        'The request could not be sent. Please call us directly at 253-387-1090.',
        500
    );
}