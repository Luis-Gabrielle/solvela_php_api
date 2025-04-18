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

if (empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    // Get unique recipients from transaction history
    $stmt = $conn->prepare("SELECT DISTINCT 
                           r.Id as id, 
                           r.CardHolderName as name, 
                           r.CardNumber as accountNumber, 
                           'Solvela Bank' as bank
                       FROM transactions t 
                       JOIN carddetails r ON t.RecipientId = r.Id 
                       WHERE t.CardId = ? AND t.RecipientId IS NOT NULL AND t.RecipientId != ?
                       GROUP BY r.Id
                       ORDER BY MAX(t.TransactionDate) DESC");
    
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }

    echo json_encode(['success' => true, 'contacts' => $contacts]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>