<?php
$host = 'localhost';
$dbname = 'water_refilling_db';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $code = trim($_POST['reset_code']);

    if (empty($email) || empty($code)) {
        // Redirect with error via URL query
        header("Location: index.php?step=verify&email=" . urlencode($email) . "&error=missing_fields");
        exit();
    }

    // Check if code matches and not expired
    $stmt = $conn->prepare("SELECT reset_code_expires FROM users WHERE email = ? AND reset_code = ?");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($expiration);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {
        if (strtotime($expiration) > time()) {
            // ✅ Code valid — move to reset step
            header("Location: index.php?step=reset&email=" . urlencode($email));
            exit();
        } else {
            // Code expired
            header("Location: index.php?step=verify&email=" . urlencode($email) . "&error=expired");
            exit();
        }
    } else {
        // Invalid code
        header("Location: index.php?step=verify&email=" . urlencode($email) . "&error=invalid_code");
        exit();
    }
}
?>
