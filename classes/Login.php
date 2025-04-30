<?php

require_once '../config.php';
class Login extends DBConnection {
    private $settings;
    public function __construct(){
        global $_settings;
        $this->settings = $_settings;

        parent::__construct();
        ini_set('display_error', 1);
    }
    public function __destruct(){
        parent::__destruct();
    }
    public function index(){
        echo "<h1>Access Denied</h1> <a href='".base_url."'>Go Back.</a>";
    }

    public function login() {
        extract($_POST);
    
        // Validate input
        if (empty($username) || empty($password)) {
            return json_encode(['status' => 'error', 'message' => 'Missing username or password']);
        }
    
        $stmt = $this->conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
    
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $username, $hashed_password);
            $stmt->fetch();
    
            if (password_verify($password, $hashed_password)) {
                $this->settings->set_userdata("user_id", $user_id);
                $this->settings->set_userdata("username", $username);
                $this->settings->set_userdata("login_type", 1);
    
                return json_encode(['status' => 'success', 'redirect' => base_url . 'admin/index.php']);
            } else {
                return json_encode(['status' => 'error', 'message' => 'Invalid password']);
            }
        } else {
            return json_encode(['status' => 'error', 'message' => 'User not found']);
        }
    }
    
    // ✅ UPDATE PASSWORD FUNCTION USING `password_hash()`
    public function save_users(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id', 'password'))){
                if(!empty($data)) $data .= ", ";
                $data .= " `{$k}` = '{$v}' ";
            }
        }
        // ✅ Hash new password before saving
        if(!empty($password)){
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $data .= ", `password` = '{$hashed_password}' ";
        }
        
        if(empty($id)){
            $sql = "INSERT INTO users SET {$data}";
        } else {
            $sql = "UPDATE users SET {$data} WHERE id = $id";
        }

        $save = $this->conn->query($sql);
        if($save){
            $this->settings->set_flashdata('success','User Details successfully saved.');
            return json_encode(array("status" => "success"));
        } else {
            return json_encode(array("status" => "failed", "error" => $this->conn->error));
        }
    }

    public function logout(){
        if($this->settings->sess_des()){
            redirect('admin/login.php');
        }
    }

    function clogin(){
        extract($_POST);

        // ✅ Secure Query for Account Login
        $stmt = $this->conn->prepare("SELECT id, email, password FROM accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($user_id, $email, $hashed_password);
        $stmt->fetch();

        if ($stmt->num_rows > 0) {
            // ✅ Verify Password with `password_verify()`
            if (password_verify($password, $hashed_password)) {
                $this->settings->set_userdata("user_id", $user_id);
                $this->settings->set_userdata("email", $email);
                $this->settings->set_userdata("login_type", 2);

                return json_encode(array("status" => "success"));
            } else {
                return json_encode(array("status" => "incorrect"));
            }
        } else {
            return json_encode(array("status" => "incorrect", "message" => "User not found"));
        }
    }

    public function clogout(){
        if($this->settings->sess_des()){
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
