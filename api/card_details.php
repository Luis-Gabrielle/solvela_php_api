<?php
// card_details.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle POST request (saving card details)
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $cardHolderName = $input['CardHolderName'] ?? null;
    $email = $input['Email'] ?? null;
    $cardNumber = $input['CardNumber'] ?? null;
    $expiryDate = $input['ExpiryDate'] ?? null;
    $cvv = $input['CVV'] ?? null;
    $currentBalance = $input['CurrentBalance'] ?? null;
    $savingsBalance = $input['SavingsBalance'] ?? null;

    if (!$cardHolderName || !$email || !$cardNumber || !$expiryDate || !$cvv) {
        echo json_encode(['error' => 'Missing required fields']);
        http_response_code(400); // Bad Request
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO CardDetails (CardHolderName, Email, CardNumber, ExpiryDate, CVV, CurrentBalance, SavingsBalance) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $cardHolderName,
            $email,
            $cardNumber,
            $expiryDate,
            $cvv,
            $currentBalance,
            $savingsBalance
        ]);

        echo json_encode(['message' => 'Card details saved successfully']);
        http_response_code(201); // Created
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET request (fetching card details)
    try {
        $stmt = $pdo->query("SELECT * FROM CardDetails");
        $cardDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($cardDetails);
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