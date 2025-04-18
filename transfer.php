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
$senderId = $_POST['senderId'] ?? null;
$senderEmail = $_POST['senderEmail'] ?? null;
$recipientCardNumber = $_POST['recipientCardNumber'] ?? null;
$amount = floatval($_POST['amount'] ?? 0);
$description = $_POST['description'] ?? 'Fund Transfer';

// Validate inputs
if (empty($recipientCardNumber) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid recipient or amount']);
    exit;
}

$conn->begin_transaction();

try {
    // Get sender details - try multiple identification methods
    $senderCardId = null;
    $senderBalance = null;
    
    if (!empty($senderId)) {
        // Try to find by userId/Id
        $stmt = $conn->prepare("SELECT Id, CardNumber, CurrentBalance FROM carddetails WHERE Id = ?");
        $stmt->bind_param("i", $senderId);
    } 
    else if (!empty($senderEmail)) {
        // Try to find by Email
        $stmt = $conn->prepare("SELECT Id, CardNumber, CurrentBalance FROM carddetails WHERE Email = ?");
        $stmt->bind_param("s", $senderEmail);
    } 
    else {
        throw new Exception("No valid sender identification provided");
    }
    
    $stmt->execute();
    $senderResult = $stmt->get_result();
    
    if ($senderResult->num_rows === 0) {
        throw new Exception("Sender account not found");
    }
    
    $sender = $senderResult->fetch_assoc();
    $senderCardId = $sender['Id'];
    $senderBalance = $sender['CurrentBalance'];
    $senderCardNumber = $sender['CardNumber']; // Get actual card number
    
    // Continue with rest of the processing...
    if ($senderBalance < $amount) {
        throw new Exception("Insufficient funds");
    }

    // Get recipient details
    $stmt = $conn->prepare("SELECT Id FROM carddetails WHERE CardNumber = ?");
    $stmt->bind_param("s", $recipientCardNumber);
    $stmt->execute();
    $recipientResult = $stmt->get_result();

    if ($recipientResult->num_rows === 0) {
        throw new Exception("Recipient account not found");
    }

    $recipient = $recipientResult->fetch_assoc();
    $recipientCardId = $recipient['Id'];

    // Update sender balance
    $stmt = $conn->prepare("UPDATE carddetails SET CurrentBalance = CurrentBalance - ? WHERE Id = ?");
    $stmt->bind_param("di", $amount, $senderCardId);
    $stmt->execute();

    // Update recipient balance
    $stmt = $conn->prepare("UPDATE carddetails SET CurrentBalance = CurrentBalance + ? WHERE Id = ?");
    $stmt->bind_param("di", $amount, $recipientCardId);
    $stmt->execute();

    // Record sender transaction
    $transactionType = "Transfer Out";
    $stmt = $conn->prepare("INSERT INTO transactions (CardId, Amount, TransactionType, TransactionDate, Description) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("idss", $senderCardId, $amount, $transactionType, $description);
    $stmt->execute();

    // Record recipient transaction
    $transactionType = "Transfer In";
    $stmt = $conn->prepare("INSERT INTO transactions (CardId, Amount, TransactionType, TransactionDate, Description) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("idss", $recipientCardId, $amount, $transactionType, $description);
    $stmt->execute();

    // Commit the transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Transfer completed successfully']);
} catch (Exception $e) {
    // Roll back the transaction
    $conn->rollback();

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>