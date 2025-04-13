<?php
header('Content-Type: application/json');

require_once 'db_connection.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if userId is provided in the query parameters
    if (!isset($_GET['userId']) || empty($_GET['userId'])) {
        echo json_encode(['success' => false, 'message' => 'Missing or invalid userId parameter.']);
        exit;
    }

    // Get userId from query parameters
    $userId = $_GET['userId'];

    // Query to fetch all card details for the given userId
    $stmt = $pdo->prepare("SELECT CardNumber, CurrentBalance, CardHolderName, ExpiryDate, CVV FROM carddetails WHERE Id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(['success' => true, 'carddetails' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No card details found for this user.']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
?>