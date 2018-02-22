<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
    }

    /* ======     Check record already exist or not by username or mobile or email    ====== */
	public function check_already_exist($val,$type)
	{
		if($type == "email") {
			$where_cond = array("user_email"=>$val);
		}
		else if($type == "mobile") {
			$where_cond = array("user_mobile"=>$val);
		}
		else {
			$where_cond = array("user_name"=>$val);
		}
		$model_data = $this->db->get_where('ct_users',$where_cond)->num_rows();
		return $model_data;
	}

    /* =============         Insert user details        ======== */
	public function insert_users($data)
	{
		$model_action = $this->db->insert('ct_users',$data);
		if($model_action) {
			$model_data['insert_id'] = $this->db->insert_id();
			$model_data['status'] = "true";
		}
		else {
			$model_data['status'] = "false";
		}
		return $model_data;
	}

	/* =============         Insert user logs        ======== */
	public function insert_userlogs($data)
	{
		$this->db->insert('ct_user_logs',$data);
		$model_data['insert_id'] = $this->db->insert_id();
		$model_data['status'] = TRUE;
		return $model_data;
	}


	// /* =============         Mail verification using token        ======== */
	// public function mail_verify($token)
	// {
	// 	$record_count = $this->db->get_where('ct_users',array("user_mail_token"=>$token))->num_rows();

	// 	if($record_count == 1) {
	// 		$update_data = array("user_verification"=>1,"user_status"=>1);
	// 		$update_status = $this->db->where('user_mail_token',$token)->update('ct_users',$update_data);
	// 		$model_data['status'] = "true";
	// 		$model_data['message'] =  "You are verified successfully. Thank you for joining with us";
	// 	}
	// 	else {
	// 		$model_data['status'] = "false";
	// 		$model_data['message'] =  "Token is invalid. Please try again with correct link";
	// 	}

	// 	return $model_data;
	// }

	/* =============         Get user details        ======== */
	public function get_user_details($val,$type)
	{
		$model_data = array();
		if($type == "email") {
			$where_cond = array("user_email"=>$val);
		}
		else if($type == "mobile") {
			$where_cond = array("user_mobile"=>$val);
		}
		else {
			$where_cond = array("users_id"=>$val);	
		}
		$model_data = $this->db->get_where('ct_users',$where_cond)->row_array();
		return $model_data;
	}

	/* =============         Update user details        ======== */
	public function update_users($user_id,$data)
	{
		$model_data = $this->db->where('users_id',$user_id)->update('ct_users',$data);
		return TRUE;
	}

	/*    =============         Insert or Update user logs        ========     */
	public function update_userlogs($user_id,$data)
	{
		$model_data_count = $this->db->get_where('ct_user_logs',array('users_id'=>$user_id))->num_rows();
		if($model_data_count == 0) {
			$data['users_id'] = $user_id;
			$data['logs_login_count'] = 1;
			$model_data = $this->insert_userlogs($data);
		}
		else {
			$this->db->where('users_id',$user_id);
			$this->db->set('logs_login_count','logs_login_count+1',FALSE);
			$this->db->set($data);
			$model_data = $this->db->update('ct_user_logs');
		}
		return TRUE;
	}

	/* =============         Signin verification        ======== */
	public function signin_verify($data)
	{

		if(!empty($data['user_email'])) {
			$where_cond = '(user_email="'.$data['user_email'].'" AND user_password="'.$data['user_password'].'")';
			$result_data = $this->db->get_where('ct_users',$where_cond)->row_array();
		}
		else {
			$where_cond = '(user_mobile="'.$data['user_mobile'].'" AND user_password="'.$data['user_password'].'")';
			$result_data = $this->db->get_where('ct_users',$where_cond)->row_array();
		}

		if(!empty($result_data)) {
			$model_data['status'] = "true";
			$model_data['users_id'] = $result_data['users_id'];
			$model_data['user_status'] = $result_data['user_status'];
			$model_data['user_verification'] = $result_data['user_verification'];
			$model_data['user_fullname'] = $result_data['user_fullname'];
			$model_data['user_name'] = $result_data['user_name'];
			$model_data['user_profile_image'] = $result_data['user_profile_image'];
			$model_data['user_turfmate_image'] = (!empty($result_data['user_turfmate_image'])) ? $result_data['user_turfmate_image'] : '';
			$model_data['user_country_code'] = $result_data['user_country_code'];
		}
		else {
			$model_data['status'] = "false";
		}
		return $model_data;
	}

	/* =============         OTP verification        ======== */
	public function otp_verify($data)
	{
		$model_data = array();
		if(!empty($data['user_email'])) {
			$where_cond = '(user_email="'.$data['user_email'].'" AND user_otp="'.$data['user_otp'].'")';
		}
		else {
			$where_cond = '(user_mobile="'.$data['user_mobile'].'" AND user_otp="'.$data['user_otp'].'")';
		}
		$model_data = $this->db->get_where('ct_users',$where_cond)->row_array();
		return $model_data;
	}

	/* ========    If device token already exists means, update that as empty  ======== */
	public function device_token_update($device_token)
	{
		$model_data = $this->db->where('logs_device_token',$device_token)->update('ct_user_logs',array('logs_device_token'=>''));
		return TRUE;
	}

	/* ========    New notification count  ======== */
	public function user_notification_count($user_id)
	{
		
		$model_data = $this->db->get_where('ct_notifications',array('notifications_to_id'=>$user_id,'notifications_status'=>1))->num_rows();
		
		return $model_data;
	}

	/* ========    Insert default settings for all users  ======== */
	public function default_user_settings($user_id)
	{

		$insert_settings_data = array(
									'users_id' => $user_id,
									'profile_image_show' => 1,
									'profile_album_show' => 1,
									'mutual_friends_show' => 1,
									'user_radius' => 10,
									'user_settings_updated_date' => date('Y-m-d H:i:s')
								);
		$model_settings_data = $this->db->insert('ct_user_settings',$insert_settings_data);

		$insert_credits_data = array(
									'users_id' => $user_id,
									'user_like_date' => date('Y-m-d H:i:s'),
									// 'user_like_count' => 15,
									// 'user_like_total' => 15,
									'user_multimedia_date' => date('Y-m-d H:i:s'),
									// 'user_multimedia_post' => 3,
									// 'user_multimedia_total' => 3,
									'user_credits_status' => 1
								);
		$modal_credits_data = $this->db->insert('ct_user_credits',$insert_credits_data);

		return TRUE;
	}

	/* ========                  Logout          ======== */
	public function user_logout($data)
	{

		$update_data = $this->db->where('users_id',$data['users_id'])->update('ct_user_logs',array('logs_login_status'=>2));
		
		return TRUE;
	}





} // End user model
