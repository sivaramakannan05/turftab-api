<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
        // $this->load->library('form_validation');
		$this->load->model('settings_model');
        $this->load->library('../controllers/common');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ==============         Settings view      ======== */
	public function user_settings() {

		$data = json_decode(file_get_contents('php://input'),true);

		if(!empty($data)) {
			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->settings_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "view") {

    			$user_settings = $this->settings_model->user_settings_details($data);

				if($user_settings['status'] == "true") {

					$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_settings['data'],"message"=>"Listed successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}
    		}
    		else if($data['api_action'] == "update") {

    			$user_id = $data['users_id'];
    			unset($data['unique_id'],$data['users_id'],$data['api_action']);
    			$user_settings = $this->settings_model->update_user_settings($data,$user_id);

    			if($user_settings['status'] == "true") {

					$response = array("status"=>"true","status_code"=>"200","message"=>"Updated successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Error in updation process");
				}
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

	/* ============        Change password      ======== */
	public function user_change_password() {

		$data = json_decode(file_get_contents('php://input'),true);

		if(!empty($data)) {
			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->settings_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		$data['new_password'] = $this->common->custom_encrypt($data['new_password']);
			$data['old_password'] = (!empty($data['old_password'])) ? $this->common->custom_encrypt($data['old_password']) : "";
  			
  			$change_password = $this->settings_model->user_change_password($data);

  			if($change_password['status'] == "true") {
  				$response = array("status"=>"true","status_code"=>"200","message"=>"Password updated successfully");
  			}
  			else {
  				$response = array("status"=>"false","status_code"=>"400","message"=>$change_password['message']);	
  			}    		
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}

		echo json_encode($response);
	}

	/* =============       User blocking      ============ */
	public function user_blocking() {

		$data = json_decode(file_get_contents('php://input'),true);

		if(!empty($data)) {
			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->settings_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "blocklist") {

    			$user_blocklist = $this->settings_model->user_blocklist($data);

    			if(!empty($user_blocklist)) {
					$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_blocklist,"message"=>"Listed succesfully");		
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}		
    		}
    		else if($data['api_action'] == "unblocking") {

    			$delete_data['blocklist_from_id'] = $data['users_id'];
    			$delete_data['blocklist_to_id'] = $data['block_users_id'];

    			$user_block = $this->settings_model->user_unblock($delete_data);
				
				if($user_block['status'] == "true") {
					$response = array("status"=>"true","status_code"=>"200","message"=>"Unblocked successfully");		
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}
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

	/* =============       Email id and mobile number update      ============ */
	public function user_login_update() {

		$data = json_decode(file_get_contents('php://input'),true);

		if(!empty($data)) {
			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->settings_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "mobile_update") {

    			$check_already_exist = $this->settings_model->check_already_exist($data['user_mobile'],"mobile");
    			if($check_already_exist > 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Mobile number already exists");
    				echo json_encode($response);
    				exit;
    			}

    			$user_otp = mt_rand(111111,999999);
    			$update_user['user_otp'] = $user_otp;
				$update_user['user_otp_sent_date'] = date('Y-m-d H:i:s');
				$update_user['secondary_user_mobile'] = $data['user_mobile'];
				$update_user['secondary_user_country_code'] = $data['user_country_code'];

				$update_user_data = $this->settings_model->update_user_data($update_user,$data['users_id']);

				if($update_user_data['status'] == "true") {
					
					$text = "Thanks+for+joining+with+us.+Your+OTP+is+".$user_otp;
					$tomobile = $data['user_country_code'].$data['user_mobile'];
					$verification = $this->common->send_sms($tomobile,$text);
					$response = array("status"=>"true","status_code"=>"200","otp"=>$user_otp,"message"=>"OTP has been sent successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Error in updation process");
				}
    		}
    		else if($data['api_action'] == "email_update") {

    			$check_already_exist = $this->settings_model->check_already_exist($data['user_email'],"email");
    			if($check_already_exist > 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Email address already exists");
    				echo json_encode($response);
    				exit;
    			}

    			$user_otp = mt_rand(111111,999999);
    			$update_user['user_otp'] = $user_otp;
				$update_user['user_otp_sent_date'] = date('Y-m-d H:i:s');
				$update_user['secondary_user_email'] = $data['user_email'];

				$update_user_data = $this->settings_model->update_user_data($update_user,$data['users_id']);

				if($update_user_data['status'] == "true") {

					$mail_sub = "Account Reset";
					$email_data['username'] = $data['user_fullname'];
					$email_data['otp'] = $user_otp;
					$email_data['type'] = "Account Reset";
					$mail_msg = $this->load->view('templates/email_otp',$email_data,true);
					$verification = $this->common->send_mail($data['user_email'],$mail_sub,$mail_msg);
					$response = array("status"=>"true","status_code"=>"200","otp"=>$user_otp,"message"=>"OTP has been sent successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Error in updation process");
				}
    		}
    		else {
    			$response = array("status"=>"true","status_code"=>"400","message"=>"Keyword mismatch");
    		}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}

		echo json_encode($response);
	}

	/* =============       OTP verification      ============ */
	public function user_otp_verification() {

		$data = json_decode(file_get_contents('php://input'),true);

		if(!empty($data)) {
			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->settings_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "otp_verify") {

    			$otp_type = $data['otp_type'];
				$user_data = $this->settings_model->user_otp_verify($data,$otp_type);

				if($user_data['status'] == "true") {

					//-------------otp validation for maximum one minute
					$current_timeF = date('Y-m-d H:i:s');
					$cTime  = strtotime($current_timeF);
					$otpTime = strtotime($user_data['data']['user_otp_sent_date']);
					$differenceInSeconds = $cTime - $otpTime;
					if($differenceInSeconds > 120) //check the total minutes exceeds one minute
					{
						$response = array("status"=>"false", "status_code"=>"400", "message"=>"OTP Expired");
						echo json_encode($response);
						exit;
					}

					if($otp_type == "mobile") {

						$check_already_exist = $this->settings_model->check_already_exist($data['user_mobile'],"mobile");
		    			if($check_already_exist > 0) {
		    				$response = array("status"=>"false","status_code"=>"400","message"=>"Mobile number already exists");
		    				echo json_encode($response);
		    				exit;
		    			}

						$update_data['user_country_code'] = $user_data['data']['secondary_user_country_code'];
						$update_data['user_mobile'] = $data['user_mobile'];
						$update_data['secondary_user_mobile'] = $user_data['data']['user_mobile'];
						$update_data['secondary_user_country_code'] = $user_data['data']['user_country_code'];
						$udpate_user_data = $this->settings_model->update_user_data($update_data,$data['users_id']);
					}
					else {

						$check_already_exist = $this->settings_model->check_already_exist($data['user_email'],"email");
		    			if($check_already_exist > 0) {
		    				$response = array("status"=>"false","status_code"=>"400","message"=>"Email address already exists");
		    				echo json_encode($response);
		    				exit;
		    			}

						$update_data['user_email'] = $data['user_email'];
						$update_data['secondary_user_email'] = $user_data['data']['user_email'];
						$udpate_user_data = $this->settings_model->update_user_data($update_data,$data['users_id']);
					}
					$response = array("status"=>"true","status_code"=>"200","message"=>ucfirst($otp_type)." has been updated successfully");
				}
				else {
					$response = array("status"=>"true","status_code"=>"400","message"=>$user_data['message']);
				}   			
			}
    		else if($data['api_action'] == "resend_otp") {

    			$otp_type = $data['otp_type'];
    			$user_otp = mt_rand(111111,999999);
				$update_user['user_otp'] = $user_otp;
				$update_user['user_otp_sent_date'] = date('Y-m-d H:i:s');

				if($otp_type == "mobile") {

					$update_user_data = $this->settings_model->update_user_data($update_user,$data['users_id']);
					if($update_user_data['status'] == "true") {
					
						$text = "Thanks+for+joining+with+us.+Your+OTP+is+".$user_otp;
						$tomobile = $data['user_country_code'].$data['user_mobile'];
						$verification = $this->common->send_sms($tomobile,$text);
						$response = array("status"=>"true","status_code"=>"200","otp"=>$user_otp,"message"=>"OTP has been sent successfully");
					}
					else {
						$response = array("status"=>"false","status_code"=>"400","message"=>"Error in updation process");
					}
				}
    			else {

					$update_user_data = $this->settings_model->update_user_data($update_user,$data['users_id']);

					if($update_user_data['status'] == "true") {

						$mail_sub = "Account Reset";
						$email_data['username'] = $data['user_fullname'];
						$email_data['otp'] = $user_otp;
						$email_data['type'] = "Account Reset";
						$mail_msg = $this->load->view('templates/email_otp',$email_data,true);
						$verification = $this->common->send_mail($data['user_email'],$mail_sub,$mail_msg);
						$response = array("status"=>"true","status_code"=>"200","message"=>"Email has been sent successfully");
					}
					else {
						$response = array("status"=>"false","status_code"=>"400","message"=>"Error in updation process");
					}
    			}
    		}
    		else {
    			$response = array("status"=>"true","status_code"=>"400","message"=>"Keyword mismatch");
    		}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}

		echo json_encode($response);
	}

	
} // End settings controller


