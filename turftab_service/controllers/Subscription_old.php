<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
        // $this->load->library('form_validation');
        $this->load->model('subscription_model');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ============        Subscription plans         ============= */
	public function subscription_list()
	{ 

      	$data = json_decode(file_get_contents('php://input'),true);

      	if(!empty($data)) {

      		// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->subscription_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

      		$subscription_list = $this->subscription_model->subscription_plan_list($data);

      		if(!empty($subscription_list)) {
      			$response = array("status"=>"true","status_code"=>"200","server_data"=>$subscription_list,"message"=>"Listed successfully");
      		}
      		else {
      			$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
      		}
      	}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ============        Subscription plans         ============= */
	public function user_subscription_activation()
	{ 

      	$data = json_decode(file_get_contents('php://input'),true);

      	if(!empty($data)) {

      		// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->subscription_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		$subscription_activation = $this->subscription_model->subscription_user_activation($data);

      		if($subscription_activation['status'] == "true") {
      			
      			$response = array("status"=>"true","status_code"=>"200","message"=>$subscription_activation['message']);
      		}
      		else {
      			$response = array("status"=>"false","status_code"=>"400","message"=>$subscription_activation['message']);
      		}
      	}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	

} // End subscription controller
