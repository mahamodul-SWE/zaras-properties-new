<?php
ob_start();

// Load PHPMailer
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Clean and limit user input
function clean(string $value, int $max = 4000): string {
    $value = trim(str_replace(["\r", "\n", "\0"], "", $value));

    if (function_exists("mb_substr")) {
        return mb_substr($value, 0, $max, "UTF-8");
    }

    return substr($value, 0, $max);
}


// Display error page
function fail(string $message, int $status = 400): void {
    http_response_code($status);

    $safe = htmlspecialchars($message, ENT_QUOTES, "UTF-8");

    echo "<!doctype html>
    <html lang='en'>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width,initial-scale=1'>
        <title>Enquiry not sent</title>
        <link rel='stylesheet' href='assets/css/style.css'>
    </head>
    <body>
        <main class='result-page'>
            <div class='result-card'>
                <p class='eyebrow'>ZARAS PROPERTIES</p>
                <h1>We couldn’t send<br><em>your enquiry.</em></h1>
                <p>{$safe}</p>
                <a class='button button-green' href='javascript:history.back()'>Go back</a>
            </div>
        </main>
    </body>
    </html>";

    exit;
}


// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contact.html");
    exit;
}


// Honeypot spam protection
if (!empty($_POST["website"] ?? "")) {
    fail("Your submission could not be accepted.");
}


// Collect form data
$name = clean((string)($_POST["name"] ?? ""), 100);
$email = clean((string)($_POST["email"] ?? ""), 150);
$phone = clean((string)($_POST["phone"] ?? ""), 40);
$type = clean((string)($_POST["form_type"] ?? ""), 20);

$subjectChoice = clean((string)($_POST["subject"] ?? ""), 120);
$otherSubject = clean((string)($_POST["other_subject"] ?? ""), 120);
$message = clean((string)($_POST["message"] ?? ""), 4000);

$consent = (string)($_POST["consent"] ?? "");


// Validate form type
$formTypes = ["rental", "seller", "general"];

if (!in_array($type, $formTypes, true)) {
    fail("The form type was not recognised.");
}


// Validate common required fields
if (
    $name === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $subjectChoice === "" ||
    $message === "" ||
    $consent !== "yes"
) {
    fail("Please complete all required fields and check your email address.");
}


// Validate Other subject
if ($subjectChoice === "Other" && $otherSubject === "") {
    fail("Please write your subject.");
}

$subject = ($subjectChoice === "Other")
    ? $otherSubject
    : $subjectChoice;


// Validate telephone
$phoneRequired = in_array($type, ["rental", "seller"], true);

if ($phoneRequired && $phone === "") {
    fail("Please enter your telephone number.");
}

if ($phone !== "" && !preg_match('/^[0-9+\s().-]{7,40}$/', $phone)) {
    fail("Please enter a valid telephone number.");
}


// Collect seller-specific information
$propertyAddress = clean(
    (string)($_POST["property_address"] ?? ""),
    250
);

if ($type === "seller" && $propertyAddress === "") {
    fail("Please enter the property address or postcode.");
}


// Email configuration
$senderEmail = "midul.others@gmail.com";
$recipientEmail = "midul.others@gmail.com";

// IMPORTANT:
// Add a NEW Gmail App Password here.
// Do not reuse the previously shared password.
$smtpPassword = "vvhi vtjn kqfq relr";


// Email subject prefixes
$subjectPrefixes = [
    "rental" => "Rental enquiry",
    "seller" => "Property selling enquiry",
    "general" => "Website contact enquiry"
];

$mailSubject = $subjectPrefixes[$type] . ": " . $subject;


// Build enquiry details
$details = [
    "Enquiry type" => ucfirst($type),
    "Name" => $name,
    "Email" => $email,
    "Telephone" => $phone ?: "Not provided",
    "Subject" => $subject
];


// Optional fields
$optionalFields = [
    "location" => "Preferred location",
    "property_type" => "Property type",
    "property_address" => "Property address / postcode",
    "seller_type" => "Relationship to property",
    "asking_price" => "Indicative asking price"
];

foreach ($optionalFields as $key => $label) {
    $value = clean((string)($_POST[$key] ?? ""), 250);

    if ($value !== "") {
        $details[$label] = $value;
    }
}


// Build email body
$body = "A new enquiry has been submitted through the Zaras Properties website.\n\n";

foreach ($details as $label => $value) {
    $body .= $label . ": " . $value . "\n";
}

$body .= "\nMessage:\n" . $message;
$body .= "\n\nSent from the Zaras Properties website.";


// Send email using PHPMailer
$mail = new PHPMailer(true);

try {

    $mail->isSMTP();

    $mail->Host       = "smtp.gmail.com";
    $mail->SMTPAuth   = true;
	$mail->Username   = $senderEmail;
	$mail->Password   = $smtpPassword;
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->CharSet = "UTF-8";
    $mail->SMTPDebug = 0;
	// $mail->Debugoutput = 'error_log';

    $mail->setFrom($senderEmail, "Zaras Properties");
    $mail->addAddress($recipientEmail);

    // Customer email will be used when replying
    $mail->addReplyTo($email, $name);

    $mail->isHTML(false);

    $mail->Subject = $mailSubject;
    $mail->Body    = $body;

    $mail->send();

} catch (Exception $e) {

    error_log(
        "Zaras Properties email sending failed. Form type: " .
        $type . ". Error: " . $mail->ErrorInfo
    );

    fail(
        "Your enquiry could not be sent at this time. Please try again later.",
        500
    );
}


// Successful submission
error_log("ZARAS REDIRECT TEST: Email sent successfully.");

header("Location: https://zarasproperties.co.uk/thank-you.html", true, 303);

if (headers_sent($file, $line)) {
    error_log("ZARAS REDIRECT FAILED: Output started in $file at line $line");
}

exit;
