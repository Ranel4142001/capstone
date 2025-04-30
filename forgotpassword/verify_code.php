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
    $email = trim($_POST['email']);
    $code = trim($_POST['reset_code']);

    // Ensure email and code exist before querying
    if (empty($email) || empty($code)) {
        echo json_encode(["status" => "error", "message" => "Email and code are required."]);
        exit();
    }

    // Query to verify reset code & expiration
    $stmt = $conn->prepare("SELECT reset_code_expires FROM users WHERE email = ? AND reset_code = ?");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($expiration);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {
        // Check if the code is expired
        if (strtotime($expiration) > time()) {
            header("Location: reset_password.php?email=" . urlencode($email));
            exit();
        } else {
            echo json_encode(["status" => "error", "message" => "Code expired at $expiration. Please request a new one."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid code. Please try again."]);
    }
}
?>

<!-- Verification Form -->
<form method="POST">
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Enter your email" required>
    <input type="text" name="reset_code" placeholder="Enter verification code" required>
    <button type="submit">Verify Code</button>
</form>
