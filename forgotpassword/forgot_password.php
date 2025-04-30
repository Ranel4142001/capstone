<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require __DIR__ . '/../vendor/autoload.php'; // Adjust path if needed

$host = 'localhost';
$dbname = 'water_refilling_db';
$username = 'root'; // Change if using a different username
$password = ''; // Change if using a password

$conn = new mysqli($host, $username, $password, $dbname);

// Check Connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $code = rand(100000, 999999); // 6-digit code
    $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Update reset code and expiry
        $update = $conn->prepare("UPDATE users SET reset_code = ?, reset_code_expires = ? WHERE email = ?");
        $update->bind_param("sss", $code, $expires, $email);
        $update->execute();

        // Send email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Use your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'ranellaurentedahil1401@gmail.com'; // Your Gmail address
            $mail->Password = 'festauegttcwghxa'; // Your Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            //Recipients
            $mail->setFrom('ranellaurentedahil1401@gmail.com', 'Water Refilling Inventory');
            $mail->addAddress($email); // Add a recipient

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code';
            $mail->Body    = "<p>Your password reset code is: <strong>$code</strong></p><p>This code will expire in 10 minutes.</p>";
            $mail->AltBody = "Your password reset code is: $code. This code will expire in 10 minutes.";

            $mail->send();
            // ✅ After sending the email, redirect to the verify_code.php page
            header("Location: verify_code.php?email=" . urlencode($email));
            exit();
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Email not sent: " . $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "If this email exists in our system, a reset code has been sent."]);
    }
}
?>
<!-- Form to enter email -->
<form method="POST">
    <input type="email" name="email" placeholder="Enter your email" required>
    <button type="submit">Send Reset Code</button>
</form>
