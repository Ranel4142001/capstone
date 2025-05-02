<?php
require_once '../config.php';

class Login extends DBConnection {
    private $settings;

    public function __construct() {
        global $_settings;
        $this->settings = $_settings;

        parent::__construct();
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    }

    public function __destruct() {
        parent::__destruct();
    }

    public function index() {
        echo "<h1>Access Denied</h1> <a href='" . base_url . "'>Go Back.</a>";
    }

    public function login() {
        extract($_POST);

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $inputPassword = $password;

            // Check both password_hash and old md5
            if (password_verify($inputPassword, $user['password']) || md5($inputPassword) === $user['password']) {
                // Auto-upgrade md5 password to hashed version
                if (md5($inputPassword) === $user['password']) {
                    $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
                    $update = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update->bind_param("si", $newHash, $user['id']);
                    $update->execute();
                }

                foreach ($user as $k => $v) {
                    if (!is_numeric($k) && $k != 'password') {
                        $this->settings->set_userdata($k, $v);
                    }
                }
                $this->settings->set_userdata('login_type', 1);
                return json_encode(['status' => 'success']);
            } else {
                return json_encode(['status' => 'incorrect', 'message' => 'Invalid password']);
            }
        } else {
            return json_encode(['status' => 'incorrect', 'message' => 'User not found']);
        }
    }

    public function logout() {
        if ($this->settings->sess_des()) {
            redirect('admin/login.php');
        }
    }

    public function clogin() {
        extract($_POST);
        $stmt = $this->conn->prepare("SELECT * FROM `accounts` WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $resp = [];

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password']) || $user['generated_password'] === $password) {
                foreach ($user as $k => $v) {
                    $this->settings->set_userdata($k, $v);
                }
                $this->settings->set_userdata('login_type', 2);
                $resp['status'] = 'success';
            } else {
                $resp['status'] = 'incorrect';
            }
        } else {
            $resp['status'] = 'incorrect';
        }

        if ($this->conn->error) {
            $resp['status'] = 'failed';
            $resp['_error'] = $this->conn->error;
        }

        return json_encode($resp);
    }

    public function clogout() {
        if ($this->settings->sess_des()) {
            redirect('index.php');
        }
    }
}

$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = new Login();
switch ($action) {
    case 'login':
        echo $auth->login();
        break;
    case 'clogin':
        echo $auth->clogin();
        break;
    case 'logout':
        echo $auth->logout();
        break;
    case 'clogout':
        echo $auth->clogout();
        break;
    default:
        echo $auth->index();
        break;
}
