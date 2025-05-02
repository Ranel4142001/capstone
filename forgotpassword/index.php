<?php
$email = isset($_GET['email']) ? $_GET['email'] : '';
$step = isset($_GET['step']) && in_array($_GET['step'], ['verify', 'reset']) ? $_GET['step'] : 'request';

// Only handle password update if POST in reset step
if ($_SERVER["REQUEST_METHOD"] == "POST" && $step === 'reset') {
    $host = 'localhost';
    $dbname = 'water_refilling_db';
    $username = 'root'; 
    $password = ''; 
    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
    }

    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        echo "<script>alert('Passwords do not match. Please try again.'); window.history.back();</script>";
        exit();
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_code_expires = NULL WHERE email = ?");
    $update->bind_param("ss", $hashed_password, $email);
    $update->execute();

    echo "<script>
        alert('Password has been reset successfully! Redirecting to login...');
        window.location.href = '/water_refilling/admin/login.php';
    </script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AquaTrack – Forgot Password</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            /* Changed background to a dirty white color */
            background-color:rgba(232, 238, 240, 0.9);
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .forgot-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .forgot-card h2 {
            margin-bottom: 20px;
            color: #007BFF;
            font-weight: bold;
        }
        .form-control {
            margin-bottom: 15px;
            height: 45px;
        }
        .btn-custom {
            background-color: #007BFF;
            color: #fff;
            font-weight: bold;
            border-radius: 25px;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        .back-link {
            display: block;
            margin-top: 15px;
            color: #555;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="forgot-card">
        <h2>
            <?= $step === 'verify' ? 'Verify Code' : ($step === 'reset' ? 'Reset Password' : 'Forgot Password') ?>
        </h2>

        <!-- Show error messages if any -->
        <?php
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
            if ($error === 'invalid_code') {
                echo "<div class='alert alert-danger'>Invalid code. Try again.</div>";
            } elseif ($error === 'expired') {
                echo "<div class='alert alert-warning'>Code expired. Please request a new one.</div>";
            } elseif ($error === 'missing_fields') {
                echo "<div class='alert alert-info'>Please fill in all fields.</div>";
            }
        }
        ?>

        <?php if ($step === 'verify'): ?>
            <!-- Verification Form -->
            <form method="POST" action="verify_code.php">
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                <input type="text" name="reset_code" class="form-control" placeholder="Enter verification code" required>
                <button type="submit" class="btn btn-custom btn-block">Verify Code</button>
            </form>

        <?php elseif ($step === 'reset'): ?>
            <!-- Reset Password Form -->
            <form method="POST">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm New Password" required>
                <button type="submit" class="btn btn-custom btn-block">Reset Password</button>
            </form>

        <?php else: ?>
            <!-- Request Reset Code Form -->
            <form method="POST" action="forgot_password.php">
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                <button type="submit" class="btn btn-custom btn-block">Send Reset Code</button>
            </form>
        <?php endif; ?>

        <a href="../admin/login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>
