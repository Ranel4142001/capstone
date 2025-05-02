<?php require_once('../config.php') ?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
 <?php require_once('inc/header.php') ?>
<body class="hold-transition login-page">
  <script>
    start_loader()
  </script>
  <h2 class="text-center mb-4 pb-4"><?php echo $_settings->info('name') ?></h2>
<div class="login-box">
  <!-- /.login-logo -->
  <div class="card card-outline card-primary">
    <div class="card-body">
      <p class="login-box-msg">Please enter you credentials</p>

      <form id="login-frm" action="" method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control" name="username" placeholder="Username">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
  <input type="password" class="form-control" name="password" placeholder="Password" id="password">
  <div class="input-group-append">
    <div class="input-group-text" style="cursor: pointer;" onclick="togglePassword()">
      <span id="toggleIcon" class="fas fa-lock"></span>
    </div>
  </div>
</div>

        <div class="row align-items-center">
  <div class="col-6">
<button type="submit" class="btn btn-primary btn-block">Sign In</button>
  </div>
  <div class="col-6 text-right">
     <a href="../forgotpassword/index.php" class="btn btn-link p-0">Forgot Password?</a>
  </div>
</div>

      
    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<style>
 body {
  background-size: cover;
  font-family: 'Segoe UI', sans-serif;
}

  .login-box {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  border-radius: 12px;
  }

  .card-outline.card-primary {
  border-top: 4px solid #42a5f5; /* soft blue */
  border-radius: 12px;
  }

  .card-body {
  background: #ffffff;
  border-radius: 0 0 12px 12px;
  padding: 30px;
  }

  .btn-primary {
  background-color: #42a5f5;
  border-color: #42a5f5;
  border-radius: 25px;
  font-weight: 600;
  transition: background-color 0.3s ease;
  }

  .btn.btn-primary:hover {
  color: black !important;
  font-weight: bold; /* optional: makes the text bold on hover */
}

  .input-group .form-control {
  border-radius: 20px;
  box-shadow: none;
  border: 1px solid #90caf9;
  }

  .input-group-text {
  background-color: #e3f2fd;
  border: 1px solid #90caf9;
  border-left: none;
  border-radius: 0 20px 20px 0;
  }

  .login-box-msg {
  color: #1565c0;
  font-weight: 600;
  margin-bottom: 20px;
  }

  h2.text-center {
  color: #0d47a1;
  text-shadow: 1px 1px 2px #b3e5fc;
  font-weight: bold;
  }

  a {
  color: #0d47a1;
  font-weight: 500;
  }

  a:hover {
  text-decoration: underline;
  color: #1565c0;
  }
 </style>


 <script>

function togglePassword() {
  const passwordField = document.getElementById('password');
  const toggleIcon = document.getElementById('toggleIcon');

  if (passwordField.type === 'password') {
    passwordField.type = 'text';
    toggleIcon.classList.remove('fa-lock');
    toggleIcon.classList.add('fa-unlock');
  } else {
    passwordField.type = 'password';
    toggleIcon.classList.remove('fa-unlock');
    toggleIcon.classList.add('fa-lock');
  }
}

  $(document).ready(function(){
  end_loader();
  })
 </script>
 </body>
 </html>