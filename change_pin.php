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
$currentPin = $_POST['currentPin'] ?? '';
$newPin = $_POST['newPin'] ?? '';
$otp = $_POST['otp'] ?? '';

if (empty($userId) || empty($currentPin) || empty($newPin) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Verify OTP
    $stmt = $conn->prepare("SELECT * FROM otp_codes WHERE user_id = ? AND otp = ? AND expiry > NOW()");
    $stmt->bind_param("is", $userId, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Invalid or expired OTP');
    }

    // Verify current PIN
    $stmt = $conn->prepare("SELECT * FROM pindetails WHERE CardId = ? AND Pin = ?");
    $stmt->bind_param("is", $userId, $currentPin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Current PIN is incorrect');
    }

    // Update PIN in pindetails table
    $stmt = $conn->prepare("UPDATE pindetails SET Pin = ? WHERE CardId = ?");
    $stmt->bind_param("si", $newPin, $userId);
    $stmt->execute();

    // Clear used OTP
    $stmt = $conn->prepare("DELETE FROM otp_codes WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'PIN updated successfully']);

} catch (Exception $e) {
    // Roll back transaction
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>