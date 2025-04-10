<?php
// get_transactions.php
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

if (!isset($_GET['cardId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Card ID is required']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT Id, Amount, TransactionType, TransactionDate, Description 
                           FROM transactions 
                           WHERE CardId = ? 
                           ORDER BY TransactionDate DESC");
    $stmt->execute([$_GET['cardId']]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $transactions
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve transactions: ' . $e->getMessage()]);
}
?>