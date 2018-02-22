<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
        // $this->load->library('form_validation');
        $this->load->model('user_model');
        $this->load->library('../controllers/common');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ============        Signup         ============= */
	public function signup()
	{

      	$data = $this->input->post();
		$mobile_count = 0;
		$email_count = 0;

		if(!empty($data)) {

			// Check given field is email or mobile
			if(preg_match('/^[0-9]+$/', $data['user_email_mobile'])) {
				$data['user_mobile'] = $data['user_email_mobile'];
			}
			else {
				$data['user_email'] = $data['user_email_mobile'];
			}
			unset($data['user_email_mobile']);

			$username_count = $this->user_model->check_already_exist($data['user_name'],"username");
			if(!empty($data['user_email'])) {
				$email_count = $this->user_model->check_already_exist($data['user_email'],"email");
			}
			else {
				$mobile_count = $this->user_model->check_already_exist($data['user_mobile'],"mobile");
			}

			if($username_count == 0 && $email_count == 0 && $mobile_count == 0) {

				$file_path = '';

				// Create a new directory if not exists
				// if(!is_dir('./'.UPLOADS.'profile/')) {
				// 	mkdir('./'.UPLOADS.'profile/',0777,true);
				// 	// 1- execute 2- write 4- read
				// 	// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				// }

				if(!empty($_FILES['user_profile_image'])) {

					$file_ext = ".".strtolower(end((explode('.',$_FILES['user_profile_image']['name']))));
					$file_name   = time().mt_rand(000,999).$file_ext;
					$config['upload_path'] = "./".UPLOADS."profile/";
				   	$config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->load->library('upload', $config);
					if ($this->upload->do_upload('user_profile_image')) {
						
						$filepath = $config['upload_path'].$file_name;
						$create_thumb = $this->common->create_thumb($filepath);
						$file_path = str_replace("./", "", $filepath);
         			}
				}

				// User registration values
				$data['user_fullname'] = $data['user_name'];
				$data['user_otp'] = mt_rand(111111,999999);
				$data['user_otp_sent_date'] = date('Y-m-d H:i:s');
				$otp_val = $data['user_otp'];
				$data['user_password'] = $this->common->custom_encrypt($data['user_password']);
				$data['user_profile_image'] = $file_path;
				$data['user_verification'] = 2;
				$data['user_status'] = 2;
				$data['user_register_type'] = 1;
				$data['user_profile_updated_date'] = date('Y-m-d H:i:s');

				$result = $this->user_model->insert_users($data);

				if($result['status'] == "true") {

					$insert_settings = $this->user_model->default_user_settings($result['insert_id']);

					if(!empty($data['user_email'])) {

						$mail_sub = "Account Confirmation";
						$email_data['username'] = $data['user_name'];
						$email_data['otp'] = $otp_val;
						$email_data['type'] = "Account Confirmation";
						$mail_msg = $this->load->view('templates/email_otp',$email_data,true);
						$verification = $this->common->send_mail($data['user_email'],$mail_sub,$mail_msg);
					}
					else {

						$text = "Thanks+for+joining+with+us.+Your+OTP+is+".$data['user_otp'];
						$tomobile = $data['user_country_code'].$data['user_mobile'];
						$verification = $this->common->send_sms($tomobile,$text);
					}

					$response = array("status"=>"true","status_code"=>"200","message"=>"Registered successfully. Please check your email/mobile and confirm your account","otp"=> $otp_val, "insert_id"=>$result['insert_id']);
				}
				else {
					$response = array("status"=>"false","status_code"=>"500","message"=>"Error in insertion process");
				}
			}
			else {

				$message = ($username_count == 1) ? "The username already exist" : (($email_count == 1) ? "The email address already exist" : (($mobile_count == 1) ? "The mobile number already exist" : ''));
				$response = array("status"=>"false","status_code"=>"400","message"=>$message);
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}

		echo json_encode($response);
	}

	/* =========       Social media signup and signin      ============= */
	public function socialmedia_login_signup()
	{

		$data = json_decode(file_get_contents("php://input"),true);
		// $count_total = 13;

		if(!empty($data)) {

			// Check whether email/mobile will come or not
			if(empty($data['user_email_mobile'])) {

				$login_type = ($data['user_register_type'] == 2) ? "facebook" : "gmail";
				$response = array("status"=>"false","status_code"=>"400","message"=>"Your $login_type account has lot of restrictions. Please try again with other option");
				echo json_encode($response);
				exit;
			}

			// Check given field is email or mobile
			if(preg_match('/^[0-9]+$/', $data['user_email_mobile'])) {
				$data['user_mobile'] = $data['user_email_mobile'];
			}
			else {
				$data['user_email'] = $data['user_email_mobile'];
			}
			unset($data['user_email_mobile']);

			// $username_count = $this->user_model->check_already_exist($data['user_name'],"username");

			if(!empty($data['user_email'])) {
				$already_exist = $this->user_model->check_already_exist($data['user_email'],"email");
			}
			else {
				$already_exist = $this->user_model->check_already_exist($data['user_mobile'],"mobile");
			}


			if($already_exist == 0) {

				// $username_already = $this->user_model->check_already_exist($data['user_name'],"username");
				// if($username_already == 0) {

					foreach ($data as $key => $value) {
						if($key == "user_fullname" || $key == "user_email" || $key == "user_mobile" || $key == "user_profile_image" || $key == "user_register_type") {
							$user_data[$key] = $value;
						}
						else {
							$logs_data[$key] = $value;
						}
					}

					// $count_input = count($user_data);
					$user_data['user_verification'] = 1;
					$user_data['user_status'] = 1;
					$user_data['user_profile_updated_date'] = date('Y-m-d H:i:s');
					// $user_data['user_profile_strength'] = floor(($count_input / $count_total) * 100);
					$user_result = $this->user_model->insert_users($user_data);
					if($user_result['status'] == "true") {

						$insert_settings = $this->user_model->default_user_settings($user_result['insert_id']);
						$logs_data['users_id'] = $user_result['insert_id'];
						$logs_data['unique_id'] = $this->common->random_unique_id();
						$logs_data['logs_login_type'] = $user_data['user_register_type'];
						$logs_data['logs_login_status'] = 1;
						$logs_data['logs_login_count'] = 1;
						$logs_data['logs_updated_date'] = date('Y-m-d H:i:s');
						$device_token_update = $this->user_model->device_token_update($logs_data['logs_device_token']);
						$logs_result = $this->user_model->insert_userlogs($logs_data);
						if($user_result['status'] == "true") {
							$response = array("status"=>"true","status_code"=>"200","unique_id"=>$logs_data['unique_id'],"users_id"=>$logs_data['users_id'],"user_fullname"=>$user_data['user_fullname'],"user_name"=>"","user_profile_image"=>$user_data['user_profile_image'],"user_turfmate_image"=>'',"notification_count"=>0,"message"=>"Login successfully");
						}
						else {
							$response = array("status"=>"false","status_code"=>"500","message"=>"Error in insertion process!");
						}
					}
					else {
						$response = array("status"=>"false","status_code"=>"500","message"=>"Error in insertion process!");
					}
				// }
				// else {
				// 	$response = array("status"=>"false","status_code"=>"400","message"=>"Username already exist!");
				// }
			}
			else {

				if(!empty($data['user_email'])) {
					$user_result = $this->user_model->get_user_details($data['user_email'],"email");
				}
				else {
					$user_result = $this->user_model->get_user_details($data['user_mobile'],"mobile");
				}

				$user_id = $user_result['users_id'];
				if($user_result['user_status'] == 3) {
					
					$response = array("status"=>"false","status_code"=>"400","message"=>"Your account deactivated by admin");
					echo json_encode($response);
					exit;
				}
				else if($user_result['user_verification'] == 2 || $user_result['user_status'] == 2) {
					
					$update_user['user_verification'] = 1;
					$update_user['user_status'] = 1;
					$user_det_update = $this->user_model->update_users($user_id,$update_user);
				}
				$notification_count = $this->user_model->user_notification_count($user_id);
				$data['logs_login_type'] = $data['user_register_type'];
				unset($data['user_fullname'],$data['user_email'],$data['user_mobile'],$data['user_profile_image'],$data['user_register_type']);
				$data['unique_id'] = $this->common->random_unique_id();
				$data['logs_login_status'] = 1;
				$data['logs_updated_date'] = date('Y-m-d H:i:s');
				$device_token_update = $this->user_model->device_token_update($data['logs_device_token']);
				$logs_result = $this->user_model->update_userlogs($user_id,$data);
				$turfmate_profile_img = (!empty($user_result['user_turfmate_image'])) ? $user_result['user_turfmate_image'] : '';
				$response = array("status"=>"true","status_code"=>"200","unique_id"=>$data['unique_id'],"users_id"=>$user_id,"user_fullname"=>$user_result['user_fullname'],"user_name"=>$user_result['user_name'],"user_profile_image"=>$user_result['user_profile_image'],"user_turfmate_image"=>$turfmate_profile_img,"notification_count"=>$notification_count,"message"=>"Login successfully");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}
	// Commented by siva -- No need now. Because , now we are sending otp to email address rather than sending mail token.
	/* ==============          Mail verification       =========== */
	// public function mail_verify()
	// {

	// 	if(!empty($_GET['token'])) {
	// 		$result = $this->user_model->mail_verify($_GET['token']);	
	// 	}
	// 	else {
	// 		$result['message'] = "Something went wrong. Please try again later";
	// 	}
	// 	echo "<h2>".$result['message']."</h2>";
	// }

	/* =========       Signin      ============= */
	public function signin()
	{ 

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			$user_email_mobile = $data['user_email_mobile'];

			// Check given field is email or mobile
			if(preg_match('/^[0-9]+$/', $data['user_email_mobile'])) {
				$data['user_mobile'] = $data['user_email_mobile'];
			}
			else {
				$data['user_email'] = $data['user_email_mobile'];
			}
			unset($data['user_email_mobile']);

			$data['user_password'] = $this->common->custom_encrypt($data['user_password']);
			$signin_verify = $this->user_model->signin_verify($data);

			if($signin_verify['status'] == "true") {

				$user_id = $signin_verify['users_id'];
				
				if($signin_verify['user_status'] == 3) {

					$response = array("status"=>"false","status_code"=>"400","message"=>"Your account deactivated by admin");
					echo json_encode($response);
					exit;
				}
				else if($signin_verify['user_verification'] == 2 ||$signin_verify['user_status'] == 2) {

					if(!empty($data['user_email'])) {

						$user_otp = mt_rand(111111,999999);
						$update_user['user_otp'] = $user_otp;
						$update_user['user_otp_sent_date'] = date('Y-m-d H:i:s');
						$mail_sub = "Account Confirmation";
						$email_data['username'] = $signin_verify['user_fullname'];
						$email_data['otp'] = $update_user['user_otp'];
						$email_data['type'] = "Account Confirmation";
						$mail_msg = $this->load->view('templates/email_otp',$email_data,true);
						$verification = $this->common->send_mail($data['user_email'],$mail_sub,$mail_msg);
					}
					else {
						$user_otp = mt_rand(111111,999999);
						$update_user['user_otp'] = $user_otp;
						$update_user['user_otp_sent_date'] = date('Y-m-d H:i:s');
						$text = "Thanks+for+joining+with+us.+Your+OTP+is+".$update_user['user_otp'];
						$tomobile = $signin_verify['user_country_code'].$data['user_mobile'];
						$verification = $this->common->send_sms($tomobile,$text);
					}
					$user_det_update = $this->user_model->update_users($user_id,$update_user);
					$response = array("status"=>"true","status_code"=>"200","otp"=>$user_otp,"message"=>"OTP has been sent successfully");
				}
				else if($signin_verify['user_status'] == 1) {

					$data['logs_login_type'] = 1;
					unset($data['user_password'],$data['user_mobile'],$data['user_email']);
					$data['unique_id'] = $this->common->random_unique_id();
					$data['logs_login_status'] = 1;
					$data['logs_updated_date'] = date('Y-m-d H:i:s');
					$notification_count = $this->user_model->user_notification_count($user_id);
					$device_token_update = $this->user_model->device_token_update($data['logs_device_token']);
					$logs_result = $this->user_model->update_userlogs($user_id,$data);
					$response = array("status"=>"true","status_code"=>"200","unique_id"=>$data['unique_id'],"users_id"=>$user_id,"user_fullname"=>$signin_verify['user_fullname'],"user_name"=>$signin_verify['user_name'],"user_profile_image"=>$signin_verify['user_profile_image'],"user_turfmate_image"=>$signin_verify['user_turfmate_image'],"notification_count"=>$notification_count,"message"=>"Login successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong. Please try again later");	
				}
			}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"Invalid username or password");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* =========       Forgot password      ============= */
	public function user_forgot_password()
	{ 

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check given field is email or mobile
			if(preg_match('/^[0-9]+$/', $data['user_email_mobile'])) {
				$data['user_mobile'] = $data['user_email_mobile'];
				$user_data = $this->user_model->get_user_details($data['user_mobile'],"mobile");
			}
			else {
				$data['user_email'] = $data['user_email_mobile'];
				$user_data = $this->user_model->get_user_details($data['user_email'],"email");
			}
			unset($data['user_email_mobile']);

			if(!empty($data['user_email'])) {

				if(!empty($user_data) && $user_data['user_status'] == 3) {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Your account deactivated by admin.");
					echo  json_encode($response);
					exit;
				}
				else if(!empty($user_data)) {

					$user_id = $user_data['users_id'];
					$update_user['user_otp'] = mt_rand(111111,999999);
					$update_user['user_otp_sent_date'] = date('Y-m-d H:i:s');
					$mail_sub = "Reset Password";
					$email_data['username'] = $user_data['user_fullname'];
					$email_data['otp'] = $update_user['user_otp'];
					$email_data['type'] = "Reset Password";
					$mail_msg = $this->load->view('templates/email_otp',$email_data,true);
					$verification = $this->common->send_mail($data['user_email'],$mail_sub,$mail_msg);
					$user_det_update = $this->user_model->update_users($user_id,$update_user);
					$response = array("status"=>"true","status_code"=>"200","otp"=>$update_user['user_otp'],"message"=>"OTP has been sent successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"User does not exist");
				}
			}
			else {
				
				if(!empty($user_data) && $user_data['user_status'] == 3) {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Your account deactivated by admin");
					echo  json_encode($response);
					exit;
				}
				else if(!empty($user_data)) {

					$user_id = $user_data['users_id'];
					$user_country_code = $user_data['user_country_code'];
					$update_user['user_otp'] = mt_rand(111111,999999);
					$update_user['user_otp_sent_date'] = date('Y-m-d H:i:s');
					$text = "To+reset+your+password+using+OTP+".$update_user['user_otp'];
					$tomobile = $user_country_code.$data['user_mobile'];
					$verification = $this->common->send_sms($tomobile,$text);
					$user_det_update = $this->user_model->update_users($user_id,$update_user);
					$response = array("status"=>"true","status_code"=>"200","otp"=>$update_user['user_otp'],"message"=>"OTP has been sent successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"User does not exist");
				}
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* =========       Forgot password      ============= */
	public function user_reset_password()
	{ 

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {
			// Check given field is email or mobile
			if(preg_match('/^[0-9]+$/', $data['user_email_mobile'])) {
				$data['user_mobile'] = $data['user_email_mobile'];
				$user_data = $this->user_model->get_user_details($data['user_mobile'],"mobile");
			}
			else {
				$data['user_email'] = $data['user_email_mobile'];
				$user_data = $this->user_model->get_user_details($data['user_email'],"email");
			}
			unset($data['user_email_mobile']);

			$update_data['user_password'] = $this->common->custom_encrypt($data['user_password']);
			if(!empty($user_data)) {

				$user_update = $this->user_model->update_users($user_data['users_id'],$update_data);
				$response = array("status"=>"true","status_code"=>"200","message"=>"Reset password successfully");
			}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"User does not exist");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ==============          OTP verification       =========== */
	public function otp_verify()
	{
		$data = json_decode(file_get_contents("php://input"),true);		

		if(!empty($data)) {

			// Check given field is email or mobile
			if(preg_match('/^[0-9]+$/', $data['user_email_mobile'])) {
				$data['user_mobile'] = $data['user_email_mobile'];
			}
			else {
				$data['user_email'] = $data['user_email_mobile'];
			}
			unset($data['user_email_mobile']);

			$user_data = $this->user_model->otp_verify($data);
			if(!empty($user_data)) {

				//-------------otp validation for maximum one minute
				$current_timeF = date('Y-m-d H:i:s');
				$cTime  = strtotime($current_timeF);
				$otpTime = strtotime($user_data['user_otp_sent_date']);
				$differenceInSeconds = $cTime - $otpTime;
				if($differenceInSeconds > 120) //check the total minutes exceeds two minutes
				{
					$response = array("status"=>"false", "status_code"=>"400", "message"=>"OTP Expired");
					echo json_encode($response);
					exit;
				}

				if($user_data['user_status'] == 3)
				{
					$response = array("status"=>"false","status_code"=>"400","message"=>"Your account has been deactivated by admin");
					echo json_encode($response);
					exit;
				}

				if($data['api_action'] == "registration") {

					$user_id = $user_data['users_id'];
					$update_data['user_verification'] = 1;
					$update_data['user_status'] = 1;
					$user_update = $this->user_model->update_users($user_id,$update_data);
					$response = array("status"=>"true","status_code"=>"200","message"=>"Verified successfully");
				}
				else if($data['api_action'] == "forgot_password") {

					if($user_data['user_verification'] == 2 || $user_data['user_status'] == 2)
					{
						$user_id = $user_data['users_id'];
						$update_data['user_verification'] = 1;
						$update_data['user_status'] = 1;
						$user_update = $this->user_model->update_users($user_id,$update_data);
					}
					$response = array("status"=>"true","status_code"=>"200","message"=>"Verified successfully");
				}
				else {
					$response = array("status"=>"true","status_code"=>"200","message"=>"No action happened");	
				}
			}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"Invalid OTP");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ==============          Resend OTP       =========== */
	public function resend_otp()
	{
		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {
			// Check given field is email or mobile
			if(preg_match('/^[0-9]+$/', $data['user_email_mobile'])) {
				$data['user_mobile'] = $data['user_email_mobile'];
				$user_data = $this->user_model->get_user_details($data['user_mobile'],"mobile");
			}
			else {
				$data['user_email'] = $data['user_email_mobile'];
				$user_data = $this->user_model->get_user_details($data['user_email'],"email");
			}
			unset($data['user_email_mobile']);

			if(!empty($user_data) && !empty($data['user_email'])) {

				$user_id = $user_data['users_id'];
				$update_user['user_otp'] = mt_rand(111111,999999);
				$update_user['user_otp_sent_date'] = date('Y-m-d H:i:s');
				if($data['api_action'] == "forgot_password") {
					$mail_sub = "Resend OTP";
					$email_data['username'] = $user_data['user_fullname'];
					$email_data['otp'] = $update_user['user_otp'];
					$email_data['type'] = "Forgot Password";
					$mail_msg = $this->load->view('templates/email_otp',$email_data,true);
					$verification = $this->common->send_mail($data['user_email'],$mail_sub,$mail_msg);
					$user_update = $this->user_model->update_users($user_id,$update_user);
					$response = array("status"=>"true","status_code"=>"200","otp"=>$update_user['user_otp'],"message"=>"OTP has been sent successfully");
				}
				else if($data['api_action'] == "registration") {
					$mail_sub = "Resend OTP";
					$email_data['username'] = $user_data['user_fullname'];
					$email_data['otp'] = $update_user['user_otp'];
					$email_data['type'] = "Account Confirmation";
					$mail_msg = $this->load->view('templates/email_otp',$email_data,true);
					$verification = $this->common->send_mail($data['user_email'],$mail_sub,$mail_msg);
					$user_update = $this->user_model->update_users($user_id,$update_user);
					$response = array("status"=>"true","status_code"=>"200","otp"=>$update_user['user_otp'],"message"=>"OTP has been sent successfully");
				}
				else {
					$response = array("status"=>"true","status_code"=>"200","message"=>"No action happened.");	
				}
			}
			else if(!empty($user_data) && !empty($data['user_mobile'])) {
				$user_id = $user_data['users_id'];
				$user_country_code = $user_data['user_country_code'];
				$update_user['user_otp'] = mt_rand(111111,999999);
				$update_user['user_otp_sent_date'] = date('Y-m-d H:i:s');
				$text = '';
				if($data['api_action'] == "forgot_password") {
					$text = "To+reset+your+password+using+OTP+".$update_user['user_otp'];
				}
				else if($data['api_action'] == "registration") {
					$text = "To+confirm+your+account+using+OTP+".$update_user['user_otp'];
				}
				else {
					$response = array("status"=>"true","status_code"=>"200","message"=>"No action happened");
					echo json_encode($response);
					exit;
				}
				$tomobile = $user_country_code.$data['user_mobile'];
				$verification = $this->common->send_sms($tomobile,$text);
				$user_update = $this->user_model->update_users($user_id,$update_user);
				$response = array("status"=>"true","status_code"=>"200","otp"=>$update_user['user_otp'],"message"=>"OTP has been sent successfully");
			}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"User does not exist");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ==============          Logout       =========== */
	public function user_logout() {

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			$logout_data = $this->user_model->user_logout($data);

			$response = array("status"=>"true","status_code"=>"200","message"=>"Logout successfully");
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);

	}

	

} // End user controller
