<?php
$host = 'localhost';
$dbname = 'water_refilling_db';
$username = 'root'; 
$password = ''; 
$conn = new mysqli($host, $username, $password, $dbname);

// Check Connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
}

$email = isset($_GET['email']) ? $_GET['email'] : ""; // Get email from URL

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate passwords match
    if ($new_password !== $confirm_password) {
        echo json_encode(["status" => "error", "message" => "Passwords do not match. Please try again."]);
        exit();
    }

    // Hash password after validation
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password and clear reset code
    $update = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_code_expires = NULL WHERE email = ?");
    $update->bind_param("ss", $hashed_password, $email);
    $update->execute();

    // Show success message before redirecting
    echo "<script>
        alert('Password has been reset successfully! Redirecting to login...');
        window.location.href = '/water_refilling/admin/login.php';
    </script>";
    exit();
}
?>

<!-- Reset Password Form -->
<!-- Reset Password Form -->
<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="new_password" placeholder="New password" required><br>
    <input type="password" name="confirm_password" placeholder="Confirm new password" required><br>
    <button type="submit">Reset Password</button>
</form>

