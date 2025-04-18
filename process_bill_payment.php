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
$biller = $_POST['biller'] ?? 'Unknown';
$accountNumber = $_POST['accountNumber'] ?? '';

if (empty($userId) || $amount <= 0 || empty($biller) || empty($accountNumber)) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters']);
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

    // Deduct amount from balance
    $stmt = $conn->prepare("UPDATE carddetails SET CurrentBalance = CurrentBalance - ? WHERE Id = ?");
    $stmt->bind_param("di", $amount, $userId);
    $stmt->execute();

    // Record the transaction
    $transactionType = "PayBills";
    $description = "Bill payment to " . $biller . " (Acc: " . $accountNumber . ")";
    
    $stmt = $conn->prepare("INSERT INTO transactions (CardId, Amount, TransactionType, TransactionDate, Description) 
                           VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("idss", $userId, $amount, $transactionType, $description);
    $stmt->execute();

    // Commit the transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Payment processed successfully']);

} catch (Exception $e) {
    // Rollback the transaction
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>