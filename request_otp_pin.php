<?php
header('Content-Type: application/json');
require_once 'db_connection.php';
require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set timezone
date_default_timezone_set('Asia/Manila');

// Create connection
$conn = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit;
}

// Get POST data
$userId = $_POST['userId'] ?? null;
$pin = $_POST['pin'] ?? null;
$action = $_POST['action'] ?? '';

if (empty($userId) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // First verify the current PIN
    if (!empty($pin)) {
        $stmt = $conn->prepare("SELECT c.* FROM carddetails c 
                               JOIN pindetails p ON c.Id = p.CardId 
                               WHERE c.Id = ? AND p.Pin = ?");
        $stmt->bind_param("is", $userId, $pin);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid current PIN']);
            exit;
        }

        $user = $result->fetch_assoc();
    } else {
        $stmt = $conn->prepare("SELECT * FROM carddetails WHERE Id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $user = $result->fetch_assoc();
    }

    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(0, 999999));

    // Set expiry time (10 minutes from now)
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Delete any existing OTPs for this user
    $stmt = $conn->prepare("DELETE FROM otp_codes WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Store OTP in database
    $stmt = $conn->prepare("INSERT INTO otp_codes (user_id, otp, expiry) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $otp, $expiry);
    $stmt->execute();

    // Send OTP via email
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
        $mail->addAddress($user['Email'], $user['CardHolderName']);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "Hello {$user['CardHolderName']},<br><br>Your OTP code is: <strong>$otp</strong><br>This code will expire in 10 minutes.<br><br>Thank you.";

        $mail->send();
    } catch (Exception $e) {
        throw new Exception("Failed to send OTP email: " . $mail->ErrorInfo);
    }

    echo json_encode([
        'success' => true,
        'message' => 'OTP sent successfully',
        // Test only - remove in production
        'testOtp' => $otp,
        'expiryTime' => $expiry
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>