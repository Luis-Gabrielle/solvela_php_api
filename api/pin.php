<?php
// pin.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database configuration
require_once '../config.php';

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Handle GET request (fetching PIN details or check if PIN exists)
    $cardId = $_GET['cardId'] ?? null;
    
    if ($cardId) {
        // Get PIN details for a specific card
        try {
            $stmt = $pdo->prepare("SELECT * FROM pindetails WHERE CardId = ?");
            $stmt->execute([$cardId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                echo json_encode($data);
            } else {
                echo json_encode(['message' => 'No PIN found for card ID: ' . $cardId]);
            }
            http_response_code(200); // OK
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            http_response_code(500); // Internal Server Error
        }
    } else {
        // Fetch all PIN details if no cardId is provided
        try {
            $stmt = $pdo->query("SELECT * FROM pindetails");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($data) > 0) {
                echo json_encode($data);
            } else {
                echo json_encode(['message' => 'No PIN details found in the database']);
            }
            http_response_code(200); // OK
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            http_response_code(500); // Internal Server Error
        }
    }
} elseif ($method === 'POST') {
    // Handle POST request (saving PIN or verifying PIN)
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $cardId = $input['CardId'] ?? null;
    $pin = $input['Pin'] ?? null;
    
    if (!$cardId || !$pin) {
        echo json_encode(['error' => 'Missing required fields']);
        http_response_code(400); // Bad Request
        exit;
    }
    
    // Check if this is a verification request
    $verifyMode = isset($_GET['verify']) && $_GET['verify'] === 'true';
    
    try {
        if ($verifyMode) {
            // Verify PIN
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pindetails WHERE CardId = ? AND Pin = ?");
            $stmt->execute([$cardId, $pin]);
            $isValid = $stmt->fetchColumn() > 0;
            
            echo json_encode(['valid' => $isValid]);
            http_response_code(200); // OK
        } else {
            // Check if PIN already exists for this card
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM pindetails WHERE CardId = ?");
            $checkStmt->execute([$cardId]);
            $exists = $checkStmt->fetchColumn() > 0;
            
            if ($exists) {
                // Update existing PIN
                $updateStmt = $pdo->prepare("UPDATE pindetails SET Pin = ? WHERE CardId = ?");
                $updateStmt->execute([$pin, $cardId]);
            } else {
                // Create new PIN
                $insertStmt = $pdo->prepare("INSERT INTO pindetails (CardId, Pin) VALUES (?, ?)");
                $insertStmt->execute([$cardId, $pin]);
            }
            
            echo json_encode(['message' => 'PIN saved successfully']);
            http_response_code(201); // Created
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
} elseif ($method === 'PUT') {
    // Handle PUT request (updating PIN)
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $cardId = $input['CardId'] ?? null;
    $currentPin = $input['CurrentPin'] ?? null;
    $newPin = $input['NewPin'] ?? null;
    
    if (!$cardId || !$currentPin || !$newPin) {
        echo json_encode(['error' => 'Missing required fields']);
        http_response_code(400); // Bad Request
        exit;
    }
    
    try {
        // Verify current PIN is correct
        $verifyStmt = $pdo->prepare("SELECT COUNT(*) FROM pindetails WHERE CardId = ? AND Pin = ?");
        $verifyStmt->execute([$cardId, $currentPin]);
        $isValid = $verifyStmt->fetchColumn() > 0;
        
        if ($isValid) {
            // Update PIN
            $updateStmt = $pdo->prepare("UPDATE pindetails SET Pin = ? WHERE CardId = ?");
            $updateStmt->execute([$newPin, $cardId]);
            
            echo json_encode(['message' => 'PIN updated successfully']);
            http_response_code(200); // OK
        } else {
            echo json_encode(['error' => 'Current PIN is incorrect']);
            http_response_code(401); // Unauthorized
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
} elseif ($method === 'DELETE') {
    // Handle DELETE request (deleting PIN)
    $cardId = $_GET['cardId'] ?? null;
    
    if (!$cardId) {
        echo json_encode(['error' => 'Missing cardId parameter']);
        http_response_code(400); // Bad Request
        exit;
    }
    
    try {
        // Delete PIN
        $stmt = $pdo->prepare("DELETE FROM pindetails WHERE CardId = ?");
        $stmt->execute([$cardId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'PIN deleted successfully']);
        } else {
            echo json_encode(['message' => 'No PIN found for the specified card ID']);
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