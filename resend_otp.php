<?php
include 'config.php';
require './helper/otp_mail.php';

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode(["status" => "error", "message" => "Missing user ID."]);
        exit;
    }

    $stmt = $conn->prepare("SELECT email FROM users WHERE id=? AND is_verified=0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $email = $row['email'];

        $otp = rand(100000, 999999);
        $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        $stmt2 = $conn->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE id=?");
        $stmt2->bind_param("ssi", $otp, $otp_expiry, $user_id);
        $stmt2->execute();

        sendOTPEmail($email, $otp);

        echo json_encode(["status" => "success", "message" => "✅ New OTP sent to $email"]);
    } else {
        echo json_encode(["status" => "error", "message" => "❌ User not found or already verified."]);
    }

}
?>
