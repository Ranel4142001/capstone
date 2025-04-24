			<?php
			// Include Configurations
			require_once('../config.php');

			// Define Master Class for Handling Database Operations
			Class Master extends DBConnection
	{
			private $settings;

			 // Constructor Method
			public function __construct()
	{
			global $_settings;
			$this->settings = $_settings;

			// Destructor Method
			parent::__construct();
	}
			public function __destruct()
	{
			parent::__destruct();
	}		 // Capture Errors (for debugging database queries)
			function capture_err()
	{
			if(!$this->conn->error)
			return false;
			else
	{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			if(isset($sql))
			$resp['sql'] = $sql;
			return json_encode($resp);
			exit;
	}
	}
			// Function to Save or Update Jar Types and Pricing
			function save_jar_types()
	{
			extract($_POST);
			$data ="";
			foreach($_POST as $k => $v)
	{
			if(!in_array($k,array('id','password')))
	{
			if(!empty($data)) $data.= ", ";
			$data.= " {$k} = '{$v}' ";
	}
	}		 // Insert or Update Query
			if(empty($id))
	{
			$sql = "INSERT INTO `jar_types` set {$data}";
	}		else{
			$sql = "UPDATE `jar_types` set {$data} where id = {$id}";
	}
			$save =  $this->conn->query($sql);
			$this->capture_err();
			if($save){
			$resp['status']='success';
			$this->settings->set_flashdata('success',' Jar Type & Pricing successfully saved.');
	}
			return json_encode($resp);
	}
			// Function to Delete a Jar Type
			function delete_jar_types()
	{
			extract($_POST);
			$del= $this->conn->query("DELETE FROM `jar_types` where id = '{$id}'");
			if($del)
	{
			$this->settings->set_flashdata('success',' Jar Type & Pricing successfully deleted.');
			$resp['status'] = 'success';
	}		else
	{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
	}
			return json_encode($resp);
	}
			// Function to Save Sales Transactions	
			function save_sales()
	{
			extract($_POST);
			$data ="";
			foreach($_POST as $k => $v)
	{
			if(!in_array($k,array('id','password')))
	{
			if(!is_array($_POST[$k]))
	{
			if(!empty($data)) $data.= ", ";
			$data.= " {$k} = '{$v}' ";
	}
	}
	}		// Insert or Update Sales Record
			if(empty($id))
	{
			$sql = "INSERT INTO `sales` set {$data}";
	}		else
	{
			$sql = "UPDATE `sales` set {$data} where id = {$id}";
	}		
			$save = $this->conn->query($sql);
			$this->capture_err();
			if($save)
	{
			$id = empty($id) ? $this->conn->insert_id : $id;
			$data = "";

			// Delete Previous Items & Add New Sales Items
			$this->conn->query("DELETE FROM sales_items where sales_id = '{$id}' ");
			for($i = 0; $i < count($quantity); $i++)
	{
			if(!empty($data)) $data .= " , ";
			$data .= " ('{$id}','{$jar_type_id[$i]}','{$quantity[$i]}','{$price[$i]}','{$total_amount[$i]}') ";
	}		
	 		// Insert Sales Items
			$sql2 = $this->conn->query("INSERT INTO `sales_items` (`sales_id`,`jar_type_id`,`quantity`,`price`,`total_amount`) VALUES {$data} ");
			if($sql2)
	{
			$this->settings->set_flashdata("success", " Sales Transaction successfully saved");
			$resp['status'] = 'success';
	}		else
	{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured while saving the data";
			$resp['error'] = $this->conn->error;
	}
	}		else
	{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured while saving the data";
			$resp['error'] = $this->conn->error;
	}
			return json_encode($resp);
	}
			 // Function to Delete a Sales Record
			function delete_sales()
	{
			extract($_POST);
			$del= $this->conn->query("DELETE FROM `sales` where id = '{$id}'");
			if($del)
	{
			$this->settings->set_flashdata('success',' Sales Transaction successfully deleted.');
			$resp['status'] = 'success';
	}		else
	{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
	}
			return json_encode($resp);
	}
			// Function to record a production entry (tracks daily, weekly, monthly, yearly data)
			function save_production(){
			extract($_POST);
			$date = $this->conn->real_escape_string($date);
			$quantity = (int) $quantity;

	 		 // Check if production entry already exists for the given date
			$check = $this->conn->query("SELECT id FROM production WHERE date = '{$date}'");
			if ($check->num_rows > 0) 
	{		
			// Update existing record with additional quantity
		 	$this->conn->query("UPDATE production SET quantity = quantity + {$quantity} WHERE date = '{$date}'");
	} 		else 
	{		
			// Insert a new production entry
		  	$this->conn->query("INSERT INTO production (date, quantity) VALUES ('{$date}', '{$quantity}')");
	}
	  		// Error handling
			if ($this->conn->error) 
	{
		  	return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
	}
			return json_encode(['status' => 'success']);
	}
	  

	
	}
			// Instantiate the Master Class
			$Master = new Master();
			$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
			$sysset = new SystemSettings();

			// Switch Statement for Handling Requests
			switch ($action) 
	{
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

			default:
			// echo $sysset->index();
			break;
	}