<?php
// login.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// If it's a preflight OPTIONS request, stop here
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_connection.php';
$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'error' => 'Connection failed: ' . $conn->connect_error
    ]));
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['cardNumber']) || !isset($data['pin'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields'
    ]);
    exit;
}

$cardNumber = $conn->real_escape_string($data['cardNumber']);
$pin = $conn->real_escape_string($data['pin']);

try {
    // Get card details
    $stmt = $conn->prepare("SELECT c.Id, c.CardHolderName, c.Email, c.CurrentBalance, c.SavingsBalance 
                           FROM carddetails c 
                           WHERE c.CardNumber = ?");
    $stmt->bind_param("s", $cardNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $card = $result->fetch_assoc();

    if (!$card) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid card number'
        ]);
        exit;
    }

    // Verify PIN
    $pinStmt = $conn->prepare("SELECT Pin FROM pindetails WHERE CardId = ?");
    $pinStmt->bind_param("i", $card['Id']);
    $pinStmt->execute();
    $pinResult = $pinStmt->get_result();
    $pinRecord = $pinResult->fetch_assoc();

    if (!$pinRecord || $pinRecord['Pin'] != $pin) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid PIN'
        ]);
        exit;
    }

    // Return successful login
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'cardId' => $card['Id'],
            'name' => $card['CardHolderName'],
            'email' => $card['Email'],
            'currentBalance' => $card['CurrentBalance'],
            'savingsBalance' => $card['SavingsBalance']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>