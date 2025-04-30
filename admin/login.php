<?php
session_start();

require_once('../config.php');


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

      <form method="POST" action="login.php">
<div class="input-group mb-3">
  <input type="text" class="form-control custom-input" name="username" placeholder="Username" required>
  <div class="input-group-append">
    <span class="input-group-text icon-background">
      <i class="fas fa-user"></i>
    </span>
  </div>
</div>

<div class="input-group mb-3">
  <input type="password" class="form-control custom-input" name="password" id="password" placeholder="Password" required>
  <div class="input-group-append">
    <span class="input-group-text icon-background toggle-password" id="togglePassword">
      <i class="fas fa-lock"></i>
    </span>
  </div>
</div>


        <div class="row">
          <div class="col-12 mb-2">
            <button type="submit" class="btn btn-primary btn-block btn-lg rounded-pill custom-button">Sign In</button>
          </div>
        </div>
      </form>

      <div class="text-center mt-2">
    <small class="text-muted">
        <a href="../forgotpassword/index.php" class="text-muted">Forgot Password?</a>
    </small>
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


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    $("#togglePassword").click(function () {
        $.ajax({
            type: "POST",
            url: "toggle_password.php",
            data: { toggle: true },
            success: function (response) {
                var passwordField = $("#password");
                var icon = $("#togglePassword i");

                if (passwordField.attr("type") === "password") {
                    passwordField.attr("type", "text"); // Show password
                    icon.removeClass("fa-lock").addClass("fa-unlock-alt"); // Change icon
                } else {
                    passwordField.attr("type", "password"); // Hide password
                    icon.removeClass("fa-unlock-alt").addClass("fa-lock"); // Change back
                }
            },
            error: function () {
                console.log("Error processing request.");
            }
        });
    });
});
</script>



<!-- Scripts -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script> $(document).ready(function(){ end_loader(); }); </script>
</body>
</html>
