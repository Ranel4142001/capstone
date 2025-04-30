<?php
session_start();

require_once('../config.php');


// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $qry = $conn->query("SELECT * FROM users WHERE username = '{$conn->real_escape_string($username)}'");
    if ($qry && $qry->num_rows > 0) {
        $user = $qry->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Save user info to session
            $_SESSION['userdata'] = $user;
           
    header("Location: /water_refilling/admin/index.php");
    exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Username not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" style="height: auto;">
<?php require_once('inc/header.php'); ?>

<body class="hold-transition login-page" style="background: linear-gradient(180deg, #007bff, #004094); position: relative;">
<script> start_loader(); </script>

<!-- AquaTrack Logo -->
<div style="position: absolute; top: 20px; left: 20px; z-index: 10;">
  <img src="../AquaTrack.png" alt="AquaTrack Logo" style="width: 180px; height: auto; border-radius: 50%; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3); filter: drop-shadow(0px 4px 10px rgba(0, 123, 255, 0.5));">
</div>

<!-- Title & Description -->
<div class="text-center mb-3">
  <h1 class="fw-bold text-light mb-1">AquaTrack</h1>
  <h3 class="fw-semibold text-light">Advanced Refill Station</h3>
  <h3 class="fw-semibold text-light mb-3">Inventory & Sales Management</h3>
</div>

<!-- Login Box -->
<div class="login-box" style="width: 450px; max-width: 90%;">
  <div class="card card-outline card-primary shadow-lg rounded-3">
    <div class="card-body">
      <p class="login-box-msg">Please sign in to continue</p>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-sm"><?= $error ?></div>
      <?php endif; ?>

      <form id="login-frm" action="" method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control custom-input" name="username" placeholder="Username" required>
          <div class="input-group-append"><span class="input-group-text fas fa-user"></span></div>
        </div>

        <div class="input-group mb-3">
          <input type="password" class="form-control custom-input" name="password" placeholder="Password" required>
          <div class="input-group-append"><span class="input-group-text fas fa-lock"></span></div>
        </div>

        <div class="row">
          <div class="col-12 mb-2">
            <button type="submit" class="btn btn-primary btn-block btn-lg rounded-pill custom-button">Sign In</button>
          </div>
        </div>
      </form>

      <div class="text-center mt-2">
        <small class="text-muted">Forgot Password?</small>
      </div>
    </div>
  </div>
</div>

<!-- Styles -->
<style>
body { background: linear-gradient(180deg, #007bff 0%, #004094 100%); }
.login-box {
  box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
  border-radius: 10px;
  background-color: #f4f8fb;
}
.custom-input {
  padding: 12px;
  border-radius: 8px;
  border: 1px solid #007bff;
  background: #ffffff;
}
.custom-button {
  background: linear-gradient(45deg, #007bff, #0056b3);
  transition: 0.3s ease-in-out;
  color: white;
  font-weight: bold;
}
.custom-button:hover {
  background: linear-gradient(45deg, #0056b3, #004094);
}
.text-light { color: #ffffff !important; }
</style>

<!-- Scripts -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script> $(document).ready(function(){ end_loader(); }); </script>
</body>
</html>
