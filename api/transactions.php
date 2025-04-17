<?php
// transactions.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database configuration
require_once '../config.php';

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Handle GET request (fetching transactions)
    $cardId = $_GET['cardId'] ?? null;
    
    if ($cardId) {
        // Fetch transactions for a specific card
        try {
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE CardId = ? ORDER BY TransactionDate DESC");
            $stmt->execute([$cardId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($data) > 0) {
                echo json_encode($data);
            } else {
                echo json_encode(['message' => 'No transactions found for this card']);
            }
            http_response_code(200); // OK
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            http_response_code(500); // Internal Server Error
        }
    } else {
        // Fetch all transactions if no CardId is provided
        try {
            $stmt = $pdo->query("SELECT * FROM transactions ORDER BY TransactionDate DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($data) > 0) {
                echo json_encode($data);
            } else {
                echo json_encode(['message' => 'No transactions found in the database']);
            }
            http_response_code(200); // OK
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            http_response_code(500); // Internal Server Error
        }
    }
} elseif ($method === 'POST') {
    // Handle POST request (creating a new transaction)
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $cardId = $input['CardId'] ?? null;
    $amount = $input['Amount'] ?? null;
    $transactionType = $input['TransactionType'] ?? null;
    $transactionDate = $input['TransactionDate'] ?? date('Y-m-d H:i:s');
    $description = $input['Description'] ?? null;
    
    if (!$cardId || !$amount || !$transactionType) {
        echo json_encode(['error' => 'Missing required fields']);
        http_response_code(400); // Bad Request
        exit;
    }
    
    // Insert transaction
    try {
        // Check if card exists
        $checkCard = $pdo->prepare("SELECT COUNT(*) FROM carddetails WHERE Id = ?");
        $checkCard->execute([$cardId]);
        if ($checkCard->fetchColumn() == 0) {
            echo json_encode(['error' => 'Card ID does not exist']);
            http_response_code(400); // Bad Request
            exit;
        }
        
        // Insert the transaction
        $insertStmt = $pdo->prepare("INSERT INTO transactions (CardId, Amount, TransactionType, TransactionDate, Description) 
                                     VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute([$cardId, $amount, $transactionType, $transactionDate, $description]);
        
        $lastId = $pdo->lastInsertId();
        
        echo json_encode([
            'message' => 'Transaction created successfully',
            'id' => $lastId
        ]);
        http_response_code(201); // Created
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
} elseif ($method === 'DELETE') {
    // Handle DELETE request (deleting a transaction)
    $transactionId = $_GET['id'] ?? null;
    
    if (!$transactionId) {
        echo json_encode(['error' => 'Missing transaction ID']);
        http_response_code(400); // Bad Request
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE Id = ?");
        $stmt->execute([$transactionId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Transaction deleted successfully']);
        } else {
            echo json_encode(['message' => 'No transaction found with that ID']);
        }
        http_response_code(200); // OK
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
} else {
    // Handle invalid request method
    echo json_encode(['error' => 'Method not allowed']);
    http_response_code(405); // Method Not Allowed
}
?>