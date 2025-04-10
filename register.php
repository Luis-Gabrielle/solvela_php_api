<?php
// register.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// If it's a preflight OPTIONS request, stop here
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "atmd_db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'error' => 'Connection failed: ' . $conn->connect_error
    ]));
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['fullName']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields'
    ]);
    exit;
}

$fullName = $conn->real_escape_string($data['fullName']);
$email = $conn->real_escape_string($data['email']);
$password = $conn->real_escape_string($data['password']);

// Generate unique data
$cardNumber = '';
for ($i = 0; $i < 4; $i++) {
    $cardNumber .= sprintf('%04d', mt_rand(0, 9999));
    if ($i < 3) $cardNumber .= ' ';
}

// Expiry date (MM/YY format) 2 years from now
$expiryDate = date('m/y', strtotime('+2 years'));

// 3-digit CVV
$cvv = sprintf('%03d', mt_rand(0, 999));

// Generate 4-digit PIN from password
$pin = sprintf('%04d', mt_rand(0, 9999));

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert card details with email
    $stmt = $conn->prepare("INSERT INTO carddetails (CardHolderName, Email, CardNumber, ExpiryDate, CVV) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $fullName, $email, $cardNumber, $expiryDate, $cvv);
    $stmt->execute();

    if ($stmt->affected_rows <= 0) {
        throw new Exception("Failed to create card");
    }

    // Get card ID
    $cardId = $conn->insert_id;

    // Insert PIN
    $pinStmt = $conn->prepare("INSERT INTO pindetails (CardId, Pin) VALUES (?, ?)");
    $pinStmt->bind_param("is", $cardId, $pin);
    $pinStmt->execute();

    if ($pinStmt->affected_rows <= 0) {
        throw new Exception("Failed to create PIN");
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'data' => [
            'cardId' => $cardId,
            'cardNumber' => $cardNumber,
            'expiryDate' => $expiryDate,
            'pin' => $pin,
            'currentBalance' => 1000.00
        ]
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>