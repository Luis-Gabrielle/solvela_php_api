<?php
// deposit.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include database connection
require_once 'db_connection.php';

// Initialize response array
$response = array();
$response['success'] = false;

// Track transaction state
$transactionStarted = false;

try {
    // Validate request method
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    // Get and validate required parameters
    if (!isset($_POST['userId']) || !isset($_POST['amount'])) {
        throw new Exception("Missing required parameters");
    }

    $userId = intval($_POST['userId']);
    $amount = floatval($_POST['amount']);

    // Validate parameters
    if ($userId <= 0) {
        throw new Exception("Invalid user ID");
    }

    if ($amount <= 0) {
        throw new Exception("Amount must be greater than zero");
    }

    // Begin transaction
    $conn->beginTransaction();
    $transactionStarted = true;

    // Check if user exists and get current balance
    $stmt = $conn->prepare("SELECT Id, CurrentBalance FROM carddetails WHERE Id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception("User not found");
    }

    $currentBalance = floatval($result['CurrentBalance']);
    $newBalance = $currentBalance + $amount;

    // Update balance
    $stmt = $conn->prepare("UPDATE carddetails SET CurrentBalance = ? WHERE Id = ?");
    if (!$stmt->execute([$newBalance, $userId])) {
        throw new Exception("Failed to update balance");
    }

    // Record transaction
    $stmt = $conn->prepare("INSERT INTO transactions (CardId, TransactionType, Amount, TransactionDate) VALUES (?, 'Deposit', ?, NOW())");
    if (!$stmt->execute([$userId, $amount])) {
        throw new Exception("Failed to record transaction");
    }

    // Commit transaction
    $conn->commit();
    $transactionStarted = false;

    // Prepare successful response
    $response['success'] = true;
    $response['message'] = "Deposit successful";
    $response['newBalance'] = $newBalance;

} catch (Exception $e) {
    // Rollback transaction if it was started
    if ($transactionStarted) {
        $conn->rollBack();
    }
    
    $response['success'] = false;
    $response['message'] = $e->getMessage();
} finally {
    // Close database connection if it was opened
    if (isset($conn)) {
        $conn = null; // Close PDO connection
    }
}

// Send response
echo json_encode($response);
?>