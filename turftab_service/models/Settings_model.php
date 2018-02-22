<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings_model extends CI_Model {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
    }

    /* =============       Unique id verification        ============== */
    public function unique_id_verification($data) {
    	$where_cond = array("users_id"=>$data['users_id'],"unique_id"=>$data['unique_id'],"logs_login_status"=>1);
    	$record_count = $this->db->get_where('ct_user_logs',$where_cond)->num_rows();
    	return $record_count;
    }

    /* =============       User settings view        ============== */
    public function user_settings_details($data) {
       
        $where_cond = '(s.users_id="'.$data['users_id'].'")';
        $settings_data = $this->db->select('s.profile_image_show,s.mutual_friends_show,s.user_radius,IFNULL(u.user_email,"") as user_email,IFNULL(u.user_country_code,"") as user_country_code,IFNULL(u.user_mobile,"") as user_mobile,IFNULL(u.user_password,"") as user_password');
        $this->db->from('ct_user_settings s');
        $this->db->join('ct_users u','s.users_id=u.users_id AND u.user_password!=""','left');
        $settings_data = $this->db->where($where_cond)->get()->row_array();

        if(!empty($settings_data)) {

            $password_exist = $settings_data['user_password'];
            unset($settings_data['user_password']);
            $settings_data['password_exist'] = (!empty($password_exist)) ? true : false;
            $model_data['status'] = "true";
            $model_data['data'] = $settings_data;
        }
        else {
            $model_data['status'] = "false";
        }

        return $model_data;
    }

    /* =============       User settings update        ============== */
    public function update_user_settings($data,$user_id) {
       
        $update_data = $this->db->where('users_id',$user_id)->update('ct_user_settings',$data);

        if($update_data) {
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
        }

        return $model_data;
    }

    /* =============       User change password        ============== */
    public function user_change_password($data) {

        $user_data = 1;
        if(!empty($data['old_password'])) {
            $user_data = $this->db->get_where('ct_users',array('users_id'=>$data['users_id'],'user_password'=>$data['old_password']))->num_rows();
        }

        if($user_data == 0) {
            $model_data['status'] = "false";
            $model_data['message'] = "Please enter correct password";
        }
        else {
            $update_data = $this->db->where('users_id',$data['users_id'])->update('ct_users',array('user_password'=>$data['new_password']));    
            $model_data['status'] = "true";
        }
        
        return $model_data;    
    }

    /* =================     Blocked userlist       =========== */
    public function user_blocklist($data) {

        $this->db->select('u.users_id as blocked_user_id,u.user_fullname,u.user_name,u.user_profile_image,s.profile_image_show');
        $this->db->from('ct_blocklist b');
        $this->db->join('ct_users u','b.blocklist_to_id=u.users_id','inner');
        $this->db->join('ct_user_settings s','b.blocklist_to_id=s.users_id','left');
        $this->db->where('b.blocklist_from_id',$data['users_id']);
        $model_data = $this->db->get()->result_array();
        return $model_data;
    }

    /* =================     To unblock the user       =========== */
    public function user_unblock($data) {

       $blocked_count = $this->db->get_where('ct_blocklist',$data)->num_rows();

        if($blocked_count == 0) {
            $model_data['message'] = "No records found";
            $model_data['status'] = "false";
        }
        else {

            $user_block_data = $this->db->delete('ct_blocklist',$data);
            $where_cond = '((sender_id="'.$data['blocklist_from_id'].'" AND receiver_id="'.$data['blocklist_to_id'].'") OR (sender_id="'.$data['blocklist_to_id'].'" AND receiver_id="'.$data['blocklist_from_id'].'"))';
            $update_friendlist = $this->db->set('friends_status','friends_temp_status',FALSE)->where($where_cond)->update('ct_friends');
            $model_data['message'] = "User unblocked successfully";
            $model_data['status'] = "true";
        }
        return $model_data;
    }

    /* =================     Update user data       =========== */
    public function update_user_data($data,$user_id) {

        $update_data = $this->db->where('users_id',$user_id)->update('ct_users',$data);

        if($update_data) {
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
        }

        return $model_data;
    }

    /* =================     User otp verification       =========== */
    public function user_otp_verify($data,$otp_type) {

        if($otp_type == "email") {

            $where_cond = '(secondary_user_email="'.$data['user_email'].'" AND user_otp="'.$data['user_otp'].'")';
        }
        else if($otp_type == "mobile") {
            $where_cond = '(secondary_user_mobile="'.$data['user_mobile'].'" AND user_otp="'.$data['user_otp'].'")';
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Keyword mismatch";
            return $model_data;
        }
        $user_data = $this->db->select('user_email,user_mobile,user_country_code,user_otp_sent_date,secondary_user_country_code')->get_where('ct_users',$where_cond)->row_array();
        if(!empty($user_data)) {
            $model_data['status'] = "true";
            $model_data['data'] = $user_data;
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Invalid OTP";
        }

        return $model_data;
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


    



    
    


} // End profile model
