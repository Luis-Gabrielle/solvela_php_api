<?php
// account_details.php
// Remove any whitespace or comments before the PHP opening tag
error_reporting(0); // Suppress error output to prevent it appearing in the JSON response
header('Content-Type: application/json');
require_once 'db_connection.php';

// Check if userId is provided
if (!isset($_POST['userId']) || empty($_POST['userId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit;
}

$userId = $conn->real_escape_string($_POST['userId']);

// Query to get card details directly using Id
$query = "SELECT CardNumber, CurrentBalance as balance FROM carddetails WHERE Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $account = $result->fetch_assoc();
    
    // Format card number with spaces
    $accountNumber = chunk_split($account['CardNumber'], 4, ' ');
    $accountNumber = trim($accountNumber);
    
    echo json_encode([
        'success' => true,
        'accountNumber' => $accountNumber,
        'balance' => number_format((float)$account['balance'], 2)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No account found for this user'
    ]);
}

$stmt->close();
$conn->close();