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

// Get POST data
$userId = $_POST['userId'] ?? null;
$amount = floatval($_POST['amount'] ?? 0);

if (empty($userId) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
    exit;
}

// Start a transaction
$conn->begin_transaction();

try {
    // Check if user has enough balance
    $stmt = $conn->prepare("SELECT CurrentBalance FROM carddetails WHERE Id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }

    $user = $result->fetch_assoc();
    $currentBalance = floatval($user['CurrentBalance']);

    if ($currentBalance < $amount) {
        throw new Exception('Insufficient funds');
    }

    // Update the balances
    $stmt = $conn->prepare("UPDATE carddetails SET 
                             CurrentBalance = CurrentBalance - ?, 
                             SavingsBalance = SavingsBalance + ? 
                             WHERE Id = ?");
    $stmt->bind_param("ddi", $amount, $amount, $userId);
    $stmt->execute();

    // Record the transaction - money out from main account
    $transactionType = "Transfer to Savings";
    $description = "Transfer to Savings Account";
    $stmt = $conn->prepare("INSERT INTO transactions (CardId, Amount, TransactionType, TransactionDate, Description) 
                            VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("idss", $userId, $amount, $transactionType, $description);
    $stmt->execute();

    // Record the transaction - money in to savings account
    $transactionType = "Savings Deposit";
    $stmt = $conn->prepare("INSERT INTO transactions (CardId, Amount, TransactionType, TransactionDate, Description) 
                            VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("idss", $userId, $amount, $transactionType, $description);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Money added to savings successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>