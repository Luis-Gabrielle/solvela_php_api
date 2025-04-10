<?php
// Turn off error display (prevents HTML errors breaking JSON)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // Ensure you have PHPMailer installed via Composer
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

// Set timezone to ensure consistency
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
$user = "root";
$password = "";
$database = "atmd_db";

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
    if (empty($data['fullName']) || empty($data['email'])) {
        throw new Exception("Missing required fields");
    }
    
    $fullName = $conn->real_escape_string($data['fullName']);
    $email = $conn->real_escape_string($data['email']);
    
    // Verify user exists
    $stmt = $conn->prepare("SELECT Id FROM carddetails WHERE CardHolderName = ? AND Email = ?");
    $stmt->bind_param("ss", $fullName, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("No account found with this name and email");
    }
    
    $user = $result->fetch_assoc();
    $userId = $user['Id'];
    
    // Generate 6-digit OTP
    $otp = sprintf('%06d', mt_rand(0, 999999));
    
    // Create OTP table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS otp_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        otp VARCHAR(6) NOT NULL,
        expiry DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES carddetails(Id)
    )");
    
    // Delete existing OTPs for this user
    $conn->query("DELETE FROM otp_codes WHERE user_id = $userId");
    
    // Set 15-minute expiry
    $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Store OTP in database
    $stmtOtp = $conn->prepare("INSERT INTO otp_codes (user_id, otp, expiry) VALUES (?, ?, ?)");
    $stmtOtp->bind_param("iss", $userId, $otp, $expiry);
    
    if (!$stmtOtp->execute()) {
        throw new Exception("Failed to store OTP");
    }
    
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'solvela.bank@gmail.com'; // Replace with your Gmail address
        $mail->Password = 'fdhl neac ncqi cose'; // Replace with your Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('solvela.bank@gmail.com', 'Solvela'); // Replace with your email and app name
        $mail->addAddress($email, $fullName);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "Hello $fullName,<br><br>Your OTP code is: <strong>$otp</strong><br>This code will expire in 5 minutes.<br><br>Thank you.";

        $mail->send();
    } catch (Exception $e) {
        throw new Exception("Failed to send OTP email: " . $mail->ErrorInfo);
    }
    // For testing purposes, return the OTP in response
    // In production, you would send via email and not return it here
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'OTP generated successfully',
        'userId' => $userId,
        'testOtp' => $otp, // Remove this in production
        'expiryTime' => $expiry, // Include this for debugging
        'serverTime' => date('Y-m-d H:i:s') // Include current server time
    ]);
    
} catch (Exception $e) {
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