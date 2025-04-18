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
$otp = $_POST['otp'] ?? null;

if (empty($userId) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Check if OTP exists and is valid
    $stmt = $conn->prepare("SELECT * FROM otp_codes WHERE user_id = ? AND otp = ? AND expiry > NOW()");
    $stmt->bind_param("is", $userId, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
        exit;
    }
    
    echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>