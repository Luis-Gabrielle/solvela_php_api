<?php
// Start output buffering to capture any unexpected output
ob_start();

// Turn off error display
error_reporting(0);
ini_set('display_errors', 0);

// Set timezone to ensure consistency - SAME AS IN REQUEST_OTP.PHP
date_default_timezone_set('Asia/Manila');

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Create database connection
$host = "localhost";
$user = "u591433413_solvela";
$password = "Solvela_Bank123$";
$database = "u591433413_solvela";

try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get and decode JSON input
    $inputData = file_get_contents('php://input');
    $data = json_decode($inputData, true);
    
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON format");
    }
    
    // Validate required fields
    if (!isset($data['userId']) || !isset($data['otp'])) {
        throw new Exception("Missing required fields");
    }
    
    $userId = intval($data['userId']);
    // Ensure OTP is properly formatted (string with 6 digits)
    $otp = (string)$conn->real_escape_string($data['otp']);
    $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);
    
    // Verify OTP
    $stmt = $conn->prepare("SELECT * FROM otp_codes WHERE user_id = ? AND otp = ? AND expiry > NOW()");
    $stmt->bind_param("is", $userId, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Check if user exists with this ID
        $checkUser = $conn->prepare("SELECT COUNT(*) as count FROM otp_codes WHERE user_id = ?");
        $checkUser->bind_param("i", $userId);
        $checkUser->execute();
        $userExists = $checkUser->get_result()->fetch_assoc()['count'];
        
        // Check if OTP exists but expired
        $checkExpired = $conn->prepare("SELECT COUNT(*) as count FROM otp_codes WHERE user_id = ? AND otp = ? AND expiry <= NOW()");
        $checkExpired->bind_param("is", $userId, $otp);
        $checkExpired->execute();
        $isExpired = $checkExpired->get_result()->fetch_assoc()['count'];
        
        if ($userExists == 0) {
            throw new Exception("No OTP found for this user");
        } else if ($isExpired > 0) {
            throw new Exception("OTP has expired");
        } else {
            throw new Exception("Invalid OTP");
        }
    }
    
    // OTP is valid, get user data
    $userStmt = $conn->prepare("SELECT c.Id, c.CardHolderName, c.Email, c.CardNumber, c.CurrentBalance, c.SavingsBalance 
                               FROM carddetails c
                               WHERE c.Id = ?");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $userData = $userResult->fetch_assoc();
    
    // Delete the used OTP
    $conn->query("DELETE FROM otp_codes WHERE user_id = $userId");
    
    // Clear output buffer before sending JSON
    ob_end_clean();
    $pinStmt = $conn->prepare("SELECT Pin FROM pindetails WHERE CardId = ?");
    $pinStmt->bind_param("i", $userId);
    $pinStmt->execute();
    $pinResult = $pinStmt->get_result();

    $pin = null;
    if ($pinResult->num_rows > 0) {
        $pinData = $pinResult->fetch_assoc();
        $pin = $pinData['Pin'];
    }
    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully',
        'data' => [
            'userId' => $userData['Id'],
            'name' => $userData['CardHolderName'],
            'email' => $userData['Email'],
            'cardNumber' => $userData['CardNumber'],
            'currentBalance' => $userData['CurrentBalance'],
            'savingsBalance' => $userData['SavingsBalance'],
            'pin' => $pin, // Include PIN in response
        ]
    ]);
    
} catch (Exception $e) {
    // Clear output buffer before sending error JSON
    ob_end_clean();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>