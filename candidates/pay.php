<?php
session_start();
require_once __DIR__ . '/../inc/db.php';

// 1. Get data from choose_position.php
// We need the position AND an email to tell Paystack who is paying
if (!isset($_POST['position_id'])) {
    die("Error: Please select a position first.");
}

$position_id = (int)$_POST['position_id'];
$email = $_POST['email'] ?? ''; // This must come from your choose_position form

if (empty($email)) {
    die("Error: An email address is required to process payment.");
}

// 2. Fetch Position Price
$stmt = $db->prepare("SELECT * FROM positions WHERE position_id = ?");
$stmt->execute([$position_id]);
$position = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$position) {
    die("Error: Position not found.");
}

// 3. Set Sessions (To remember the position after payment)
$_SESSION['candidate_position_id'] = $position_id;
$_SESSION['candidate_email'] = $email; 
$reference = uniqid("FORM_"); 
$_SESSION['payment_ref'] = $reference;

// 4. Paystack Initialization
$amount = $position['form_price'] * 100; 
$paystack_url = "https://api.paystack.co/transaction/initialize";

$data = [
    "email" => $email,
    "amount" => $amount,
    "reference" => $reference,
    "callback_url" => "http://localhost/FcahptVotes/candidates/verify_payment.php"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paystack_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer sk_test_48397be907da5a4be3f5cc9e8516ea6cb103e3fc",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result && $result['status'] === true) {
    header("Location: " . $result['data']['authorization_url']);
    exit;
} else {
    die("Paystack Failed: " . ($result['message'] ?? "Error"));
}