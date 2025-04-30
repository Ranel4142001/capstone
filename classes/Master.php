<?php
require_once('../config.php');

// Define Master Class for Handling Database Operations
class Master extends DBConnection {
    private $settings;

    public function __construct() {
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();
    }

    public function __destruct() {
        parent::__destruct();
    }

    function capture_err() {
        if (!$this->conn->error)
            return false;
        else {
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
            if (isset($sql))
                $resp['sql'] = $sql;
            return json_encode($resp);
            exit;
        }
    }

    function save_jar_types() {
        extract($_POST);
        $data = "";
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id', 'password'])) {
                if (!empty($data)) $data .= ", ";
                $data .= " {$k} = '{$v}' ";
            }
        }
        if (empty($id)) {
            $sql = "INSERT INTO `jar_types` set {$data}";
        } else {
            $sql = "UPDATE `jar_types` set {$data} where id = {$id}";
        }
        $save = $this->conn->query($sql);
        $this->capture_err();
        if ($save) {
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', ' Jar Type & Pricing successfully saved.');
        }
        return json_encode($resp);
    }

    function delete_jar_types() {
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `jar_types` where id = '{$id}'");
        if ($del) {
            $this->settings->set_flashdata('success', ' Jar Type & Pricing successfully deleted.');
            $resp['status'] = 'success';
        } else {
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    function save_sales() {
        extract($_POST);
        $data = "";
        
        // Prepare the sales data (excluding arrays and non-column fields)
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id', 'password', 'quantity', 'jar_type_id', 'price', 'total_amount']) && !is_array($v)) {
                if (!empty($data)) $data .= ", ";
                $v = $this->conn->real_escape_string($v); // Prevent SQL injection
                $data .= " `{$k}` = '{$v}' ";
            }
        }
    
        // Insert or Update the sales record
        if (empty($id)) {
            $sql = "INSERT INTO `sales` SET {$data}";
        } else {
            $sql = "UPDATE `sales` SET {$data} WHERE id = {$id}";
        }
    
        $save = $this->conn->query($sql);
        $this->capture_err();
    
        if ($save) {
            $id = empty($id) ? $this->conn->insert_id : $id;
    
            // Clear old sales items
            $this->conn->query("DELETE FROM sales_items WHERE sales_id = '{$id}'");
    
            // Re-insert sales items
            $data = "";
            for ($i = 0; $i < count($quantity); $i++) {
                if (!empty($data)) $data .= ", ";
                $sales_id = $this->conn->real_escape_string($id);
                $jar_id = $this->conn->real_escape_string($jar_type_id[$i]);
                $qty = $this->conn->real_escape_string($quantity[$i]);
                $price_val = $this->conn->real_escape_string($price[$i]);
                $total_val = $this->conn->real_escape_string($total_amount[$i]);
                $data .= "('{$sales_id}','{$jar_id}','{$qty}','{$price_val}','{$total_val}')";
            }
    
            $sql2 = $this->conn->query("INSERT INTO `sales_items` (`sales_id`,`jar_type_id`,`quantity`,`price`,`total_amount`) VALUES {$data}");
            
            if ($sql2) {
                $this->settings->set_flashdata("success", "Sales Transaction successfully saved");
                $resp['status'] = 'success';
            } else {
                $resp['status'] = 'failed';
                $resp['msg'] = "An error occurred while saving the sales items.";
                $resp['error'] = $this->conn->error;
            }
    
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = "An error occurred while saving the sales data.";
            $resp['error'] = $this->conn->error;
        }
    
        return json_encode($resp);
    
    }

    function delete_sales() {
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `sales` where id = '{$id}'");
        if ($del) {
            $this->settings->set_flashdata('success', ' Sales Transaction successfully deleted.');
            $resp['status'] = 'success';
        } else {
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    public function send_reset_code() {
        extract($_POST);
        $resp = ['status' => 'failed'];
        $email = $this->conn->real_escape_string($email);
        $qry = $this->conn->query("SELECT * FROM users WHERE email = '{$email}'");

        if ($qry->num_rows > 0) {
            $code = rand(100000, 999999);
            $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));
            $this->conn->query("UPDATE users SET reset_code = '{$code}', reset_code_expires = '{$expiry}' WHERE email = '{$email}'");

            // Send email using PHPMailer
            require_once(__DIR__ . '/../includes/PHPMailer/PHPMailerAutoload.php');
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = 'smtp.yourserver.com'; // Update this
            $mail->SMTPAuth = true;
            $mail->Username = 'your_email@example.com'; // Update this
            $mail->Password = 'your_password'; // Update this
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('your_email@example.com', 'AquaTrack');
            $mail->addAddress($email);
            $mail->Subject = 'AquaTrack Password Reset Code';
            $mail->isHTML(true);
            $mail->Body = "Your verification code is: <b>{$code}</b><br>It will expire in 10 minutes.";

            if ($mail->send()) {
                $resp['status'] = 'success';
            } else {
                $resp['error'] = 'Failed to send email.';
            }
        } else {
            $resp['error'] = 'Email not found.';
        }

        return json_encode($resp);
    }

    // Verify Reset Code
    public function verify_code() {
        extract($_POST);
        $resp = ['status' => 'failed'];
        $qry = $this->conn->query("SELECT * FROM users WHERE email = '{$email}' AND reset_code = '{$code}'");

        if ($qry->num_rows > 0) {
            $user = $qry->fetch_assoc();
            if (strtotime($user['reset_code_expires']) >= time()) {
                $_SESSION['reset_email'] = $email;
                $resp['status'] = 'success';
            } else {
                $resp['error'] = 'Code expired.';
            }
        } else {
            $resp['error'] = 'Invalid code.';
        }
        return json_encode($resp);
    }

    // Reset Password
    public function reset_password() {
        extract($_POST);
        $resp = ['status' => 'failed'];

        if (!isset($_SESSION['reset_email'])) {
            $resp['error'] = 'Unauthorized.';
            return json_encode($resp);
        }

        $email = $_SESSION['reset_email'];
        $password = md5($password);
        $qry = $this->conn->query("UPDATE users SET password = '{$password}', reset_code = NULL, reset_code_expires = NULL WHERE email = '{$email}'");

        if ($qry) {
            unset($_SESSION['reset_email']);
            $resp['status'] = 'success';
        } else {
            $resp['error'] = 'Password reset failed.';
        }

        return json_encode($resp);
    }
    


      // Save or Update Production
	  function save_production() {
        extract($_POST);
        $date = $this->conn->real_escape_string($date);
        $quantity = (int) $quantity;

        // If the ID is provided, we update the existing record; else, we insert a new one
        if (isset($id) && !empty($id)) {
            // Update existing record
            $sql = "UPDATE production SET date = '{$date}', quantity = '{$quantity}' WHERE id = '{$id}'";
            $update = $this->conn->query($sql);
        } else {
            // Insert new record
            $check = $this->conn->query("SELECT id FROM production WHERE date = '{$date}'");
            if ($check->num_rows > 0) {
                // If the production date already exists, just update the quantity
                $this->conn->query("UPDATE production SET quantity = quantity + {$quantity} WHERE date = '{$date}'");
            } else {
                // Otherwise, insert a new production record
                $this->conn->query("INSERT INTO production (date, quantity) VALUES ('{$date}', '{$quantity}')");
            }
        }

        if ($this->conn->error) {
            return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
        }
        return json_encode(['status' => 'success']);
    }

    // Delete Production Entry
    function delete_production() {
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `production` WHERE id = '{$id}'");
        if ($del) {
            $this->settings->set_flashdata('success', 'Production entry successfully deleted.');
            $resp['status'] = 'success';
        } else {
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

}

// === ROUTING ===
$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();

switch ($action) {
    // Existing operations (save_jar_types, delete_jar_types, etc.)
    case 'save_jar_types':
        echo $Master->save_jar_types();
        break;
    case 'delete_jar_type':
        echo $Master->delete_jar_types();
        break;
    case 'save_sales':
        echo $Master->save_sales();
        break;
    case 'delete_sales':
        echo $Master->delete_sales();
        break;
    case 'save_production':
        echo $Master->save_production();
        break;
    case 'delete_production':
        echo $Master->delete_production();
        break;

    // ✅ Forgot Password Actions
    case 'send_reset_code':
        echo $Master->send_reset_code();
        break;
    case 'verify_code':
        echo $Master->verify_code();
        break;
    case 'reset_password':
        echo $Master->reset_password();
        break;

    default:
        // echo $sysset->index();
        break;
}