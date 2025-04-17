<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the content type to JSON
header("Content-Type: application/json");

// Include the database configuration
require_once '../config.php';

// Get the HTTP method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

// Route the request to the appropriate handler
switch ($endpoint) {
    case 'card-details':
        require 'card_details.php';
        break;
    case 'transactions':
        require 'transactions.php';
        break;
    case 'pin':
        require 'pin.php';
        break;
    default:
        echo json_encode(['error' => 'Invalid endpoint']);
        break;
}
?>