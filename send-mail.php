<?php
declare(strict_types=1);
session_start();

function clean(string $value, int $max = 4000): string {
    $value = trim(str_replace(["\r", "\0"], "", $value));
    return mb_substr($value, 0, $max, "UTF-8");
}
function fail(string $message, int $status = 400): never {
    http_response_code($status);
    $safe = htmlspecialchars($message, ENT_QUOTES, "UTF-8");
    echo "<!doctype html><html lang='en'><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>Enquiry not sent</title><link rel='stylesheet' href='assets/css/style.css'><main class='result-page'><div class='result-card'><p class='eyebrow'>ZARAS PROPERTIES</p><h1>We couldn’t send<br><em>your enquiry.</em></h1><p>{$safe}</p><a class='button button-green' href='javascript:history.back()'>Go back</a></div></main></html>";
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: contact.html"); exit; }
if (!empty($_POST["website"] ?? "")) { fail("Your submission could not be accepted."); }

$name = clean((string)($_POST["name"] ?? ""), 100);
$email = clean((string)($_POST["email"] ?? ""), 150);
$phone = clean((string)($_POST["phone"] ?? ""), 40);
$type = clean((string)($_POST["form_type"] ?? ""), 20);
$subjectChoice = clean((string)($_POST["subject"] ?? ""), 120);
$otherSubject = clean((string)($_POST["other_subject"] ?? ""), 120);
$message = clean((string)($_POST["message"] ?? ""), 4000);
$consent = (string)($_POST["consent"] ?? "");
if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subjectChoice === "" || $message === "" || $consent !== "yes") {
    fail("Please complete all required fields and check your email address.");
}
if (!in_array($type, ["rental", "seller", "general"], true)) { fail("The form type was not recognised."); }
if ($subjectChoice === "Other" && $otherSubject === "") { fail("Please write your subject."); }
$subject = $subjectChoice === "Other" ? $otherSubject : $subjectChoice;
$recipient = "midul.others@gmail.com";
$subjectPrefix = ["rental"=>"Rental waiting-list enquiry", "seller"=>"Property acquisition enquiry", "general"=>"Website contact enquiry"][$type];
$mailSubject = $subjectPrefix . ": " . $subject;
$details = [
    "Enquiry type" => $type,
    "Name" => $name,
    "Email" => $email,
    "Telephone" => $phone ?: "Not provided",
    "Subject" => $subject,
];
foreach (["location"=>"Preferred location", "property_type"=>"Property type", "property_address"=>"Property address / postcode", "seller_type"=>"Relationship to property", "asking_price"=>"Indicative asking price"] as $key=>$label) {
    if (!empty($_POST[$key])) $details[$label] = clean((string)$_POST[$key], 250);
}
$body = "A new enquiry has been submitted through the Zaras Properties website.\n\n";
foreach ($details as $label=>$value) $body .= $label . ": " . $value . "\n";
$body .= "\nMessage:\n" . $message . "\n\nSent from the Zaras Properties website.";
$headers = [
    "From: Zaras Properties Website <website@zarasproperties.com>",
    "Reply-To: " . $name . " <" . $email . ">",
    "Content-Type: text/plain; charset=UTF-8",
    "X-Mailer: PHP/" . phpversion()
];
$sent = @mail($recipient, "=?UTF-8?B?".base64_encode($mailSubject)."?=", $body, implode("\r\n", $headers));
if (!$sent) { fail("The website server could not send the email. Please email info@zarasproperties.com directly or call 07738 940597.", 500); }
header("Location: thank-you.html");
exit;
