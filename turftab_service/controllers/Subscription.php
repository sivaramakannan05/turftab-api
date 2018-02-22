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

	/* ============        Subscription activation         ============= */
	public function user_subscription_activation()
	{ 

      	$data = json_decode(file_get_contents('php://input'),true);

      	if(!empty($data)) {

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

	/* ============        Subscription details         ============= */
	public function user_subscription_details()
	{ 

      	$data = json_decode(file_get_contents('php://input'),true);

      	if(!empty($data)) {

          $data['subscription_type'] = 1;
       		$subscription_details = $this->subscription_model->subscription_details($data);

      		if(!empty($subscription_details)) {
      			$response = array("status"=>"true","status_code"=>"200","server_data"=>$subscription_details,"message"=>"Listed successfully");
      		}
      		else {
      			$response = array("status"=>"false","status_code"=>"400","message"=>"Not yet get subscribed/Your last subscription has been expired");
      		}
      	}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	

} // End subscription controller
