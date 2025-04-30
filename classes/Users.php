<?php
require_once('../config.php');
Class Users extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function save_users(){
		extract($_POST);
		$data = '';
		foreach($_POST as $k => $v){
			if(!in_array($k,array('id','password'))){
				if(!empty($data)) $data .=" , ";
				$data .= " {$k} = '{$v}' ";
			}
		}
		if(!empty($password) && !empty($id)){
			$password = password_hash($password, PASSWORD_DEFAULT); // Secure hashing
			if(!empty($data)) $data .=" , ";
			$data .= " `password` = '{$password}' ";
		}
		

		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
				$fname = 'uploads/'.strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
				$move = move_uploaded_file($_FILES['img']['tmp_name'],'../'. $fname);
				if($move){
					$data .=" , avatar = '{$fname}' ";
					if(isset($_SESSION['userdata']['avatar']) && is_file('../'.$_SESSION['userdata']['avatar']))
						unlink('../'.$_SESSION['userdata']['avatar']);
				}
		}
		if(empty($id)){
			$qry = $this->conn->query("INSERT INTO users set {$data}");
			if($qry){
				$this->settings->set_flashdata('success','User Details successfully saved.');
				foreach($_POST as $k => $v){
					if($k != 'id'){
						if(!empty($data)) $data .=" , ";
						$this->settings->set_userdata($k,$v);
					}
				}
				return 1;
			}else{
				return 2;
			}

		}else{
			$qry = $this->conn->query("UPDATE users set $data where id = {$id}");
			if($qry){
				$this->settings->set_flashdata('success','User Details successfully updated.');
				foreach($_POST as $k => $v){
					if($k != 'id'){
						if(!empty($data)) $data .=" , ";
						$this->settings->set_userdata($k,$v);
					}
				}
				if(isset($fname) && isset($move))
				$this->settings->set_userdata('avatar',$fname);

				return 1;
			}else{
				return "UPDATE users set $data where id = {$id}";
			}
			
		}
	}
	public function delete_users(){
		extract($_POST);
		$qry = $this->conn->query("DELETE FROM users where id = $id");
		if($qry){
			$this->settings->set_flashdata('success','User Details successfully deleted.');
			return 1;
		}else{
			return false;
		}
	}
	public function save_client(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','password'))){
				if(!empty($data)) $data .= ", ";
				$data .= " `{$k}` = '{$v}' ";
			}
		}
			$data .= ", `password` = '".md5($password)."' ";
			$data .= ", `generated_password` = '' ";
		
			$sql = "UPDATE accounts set {$data} where id = $id";
			$save = $this->conn->query($sql);

			if($save){
			$this->settings->set_flashdata('success','User Details successfully updated.');
			foreach($_POST as $k => $v){
				if(!in_array($k,array('id','password'))){
					if(!empty($data)) $data .=" , ";
					$this->settings->set_userdata($k,$v);
				}
			}
			return 1;
			}else{
				$resp['error'] = $sql;
				return json_encode($resp);
			}

	} 
	public function update_user(){
		extract($_POST);
	
		// ✅ Ensure ID exists before updating
		if (empty($id)) {
			return json_encode(["error" => "No user ID provided"]);
		}
	
		$data = [];
		foreach($_POST as $k => $v){
			if (!in_array($k, ["id", "password"])) {
				$data[] = "`{$k}` = '{$this->conn->real_escape_string($v)}'";
			}
		}
	
		// ✅ Hash new password if provided
		if (!empty($password)) {
			$hashed_password = password_hash($password, PASSWORD_DEFAULT);
			$data[] = "`password` = '{$hashed_password}'";
		}
	
		// ✅ Handle Avatar Upload
		if (isset($_FILES['img']) && $_FILES['img']['tmp_name'] != '') {
			$fname = 'uploads/'.strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			if (move_uploaded_file($_FILES['img']['tmp_name'], '../'. $fname)) {
				$data[] = "`avatar` = '{$fname}'";
				if (isset($_SESSION['userdata']['avatar']) && is_file('../'.$_SESSION['userdata']['avatar'])) {
					unlink('../'.$_SESSION['userdata']['avatar']);
				}
			}
		}
	
		// ✅ Build & Execute SQL Query
		$sql = "UPDATE users SET " . implode(", ", $data) . " WHERE id = '{$id}'";
		$qry = $this->conn->query($sql);
	
		if ($qry) {
			$this->settings->set_flashdata('success', 'User details successfully updated.');
			foreach ($_POST as $k => $v) {
				if (!in_array($k, ["id", "password"])) {
					$this->settings->set_userdata($k, $v);
				}
			}
	
			if (isset($fname)) {
				$this->settings->set_userdata('avatar', $fname);
			}
	
			return 1;
		} else {
			return json_encode(["error" => $this->conn->error]); // Debugging error message
		}
	}
}

$users = new users();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
switch ($action) {
    case 'save':
        echo $users->save_users();
        break;
    case 'update':
        echo $users->update_user();
        break;
    case 'save_client':
        echo $users->save_client();
        break;
    case 'delete':
        echo $users->delete_users();
        break;
    default:
        echo json_encode(["error" => "Invalid action"]);
        break;
}

