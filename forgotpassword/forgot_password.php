<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require __DIR__ . '/../vendor/autoload.php'; // Adjust path if needed

$host = 'localhost';
$dbname = 'water_refilling_db';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $code = rand(100000, 999999); // 6-digit reset code
    $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Update reset code and expiry in users table
        $update = $conn->prepare("UPDATE users SET reset_code = ?, reset_code_expires = ? WHERE email = ?");
        $update->bind_param("sss", $code, $expires, $email);
        $update->execute();

        // Send email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ranellaurentedahil1401@gmail.com';
            $mail->Password = 'festauegttcwghxa'; // Use App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('ranellaurentedahil1401@gmail.com', 'Water Refilling Inventory');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code';
            $mail->Body = "<p>Your password reset code is: <strong>$code</strong></p><p>This code will expire in 10 minutes.</p>";
            $mail->AltBody = "Your password reset code is: $code. This code will expire in 10 minutes.";

            $mail->send();

            // ✅ Redirect to index.php with step=verify
            header("Location: index.php?step=verify&email=" . urlencode($email));
            exit();
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Email not sent: " . $mail->ErrorInfo]);
        }
    } else {
        // Always respond positively to avoid revealing registered emails
        header("Location: index.php?step=verify&email=" . urlencode($email));
        exit();
    }
}
?>
