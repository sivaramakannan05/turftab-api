<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
        // $this->load->library('form_validation');
		$this->load->model('notification_model');
	}

	public function index()
	{
		echo "welcome";
	}

	/* ==========         User profile        ============= */
	public function user_notifications()
	{

		if($this->input->post()) {
			$data = $this->input->post();
		}
		else {
			$data = json_decode(file_get_contents("php://input"),true);
		}

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->notification_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "history") {

    			$notification_history = $this->notification_model->user_notifications_history($data['users_id']);
    			if(!empty($notification_history['data'])) {
    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$notification_history['data'],"unread_count"=>$notification_history['unread_count'],"message"=>"Listed successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
    			}
    		}
    		else if($data['api_action'] == "view") {

    			$notification_update = $this->notification_model->user_notifications_update($data,"view");
    			$response = array("status"=>"true","status_code"=>"200","server_data"=>$notification_update['data'],"message"=>"Updated successfully");
    		}
    		else if($data['api_action'] == "delete") {

    			$notification_update = $this->notification_model->user_notifications_update($data,"delete");
    			$response = array("status"=>"true","status_code"=>"200","message"=>"Deleted successfully");
    		}
    		else if($data['api_action'] == "delete_all") {

    			$notification_update = $this->notification_model->user_notifications_update($data,"delete_all");
    			$response = array("status"=>"true","status_code"=>"200","message"=>"Deleted successfully");
    		}
    		else {
    			$response = array("status"=>"false","status_code"=>"400","message"=>"Keyword mismatch");
    		}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}
	
} // End notification controller