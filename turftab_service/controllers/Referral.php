<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Referral extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
        // $this->load->library('form_validation');
		$this->load->model('referral_model');
		$this->load->library('referral_code');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ============        User referral initiate        =========== */
	public function user_referral_initiate() {

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			$check_user_referral = $this->referral_model->check_user_referral($data['users_id']);

			if($check_user_referral == 0) {

				$data['new_ref_code'] = $this->referral_code->generate_referral_code();

				$user_referral = $this->referral_model->user_referral($data);

				$response = array("status"=>"true","status_code"=>"200","message"=>"Added successfully.","referral_code"=>$data['new_ref_code']);
			}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"Already exists");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}

		echo json_encode($response);
	}

	/* ============        User referral credits        =========== */
	public function user_referral_credits() {

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->referral_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

			$user_referral_credits = $this->referral_model->user_referral_credits($data);

			if(!empty($user_referral_credits)) {

				$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_referral_credits,"message"=>"Listed successfully");
			}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"No record(s) found");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}

		echo json_encode($response);
	}

	/* ============        User reedeem credits        =========== */
	public function user_redeem_credits() {

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->referral_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['redeem_type'] == "like") {
    			$user_redeem_credits = $this->referral_model->user_redeem_credits_like($data);
    			$redeem_type = "likes";
    		}
    		else {
    			$user_redeem_credits = $this->referral_model->user_redeem_credits_post($data);
    			$redeem_type = "multimedia post";
    		}
			
			if($user_redeem_credits['status'] == "true") {

				$response = array("status"=>"true","status_code"=>"200","message"=>"Extra ".$redeem_type." added successfully");
			}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong. Please try again later");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}

		echo json_encode($response);
	}
	
} // End referral controller


