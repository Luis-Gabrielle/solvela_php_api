<?php
// filepath: c:\xampp\htdocs\solvela\get_transactions.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    // Validate and sanitize input
    if (!isset($_GET['userId']) || empty($_GET['userId'])) {
        echo json_encode(['success' => false, 'message' => 'Card ID is required']);
        exit;
    }

    $userId = $_GET['userId'];
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';

    // Build the query
    $query = "SELECT Id as id, DATE_FORMAT(TransactionDate, '%M %d, %Y') as date,
              Description as description, Amount as amount, TransactionType as type
              FROM transactions WHERE cardId = :userId";

    if (!empty($search)) {
        $query .= " AND (Description LIKE :search OR Amount LIKE :search)";
    }

    if (!empty($filter)) {
        if ($filter == 'date') {
            $query .= " ORDER BY TransactionDate DESC";
        } else {
            $query .= " AND TransactionType = :filter";
        }
    }

    $query .= " LIMIT 50";

    $stmt = $conn->prepare($query);

    // Bind parameters
    $stmt->bindParam(':userId', $userId, PDO::PARAM_STR); // Changed to PARAM_STR

    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }

    if (!empty($filter) && $filter != 'date') {
        $stmt->bindParam(':filter', $filter, PDO::PARAM_STR);
    }

    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'transactions' => $transactions]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}