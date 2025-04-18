<?php
header('Content-Type: application/json');
require_once 'db_connection.php';
// Create connection
$conn = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit;
}

$cardNumber = $_POST['cardNumber'];

// Validate input
if (empty($cardNumber)) {
    echo json_encode(['success' => false, 'message' => 'Card number is required']);
    exit;
}

// Query to find the account
$stmt = $conn->prepare("SELECT CardHolderName FROM carddetails WHERE CardNumber = ?");
$stmt->bind_param("s", $cardNumber);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'cardHolderName' => $row['CardHolderName']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Account not found'
    ]);
}

$stmt->close();
$conn->close();
?>