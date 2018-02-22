<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Referral_model extends CI_Model {

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

    /* ===========     To check whether the user already have referral code or not     ======= */
    public function check_user_referral($user_id)
    {

        $model_data_count = $this->db->get_where('ct_referral',array('users_id'=>$user_id))->num_rows();

        return $model_data_count;       
    }

    /* ===========     Referral code and referral bonus proccess     ======= */
    public function user_referral($data)
    {

        $data['referred_by'] = '';

        // To check whether the app already installed or not in the device
        $device_det = $this->db->get_where('ct_login_devices',array('device_id'=>$data['device_id']))->num_rows();

        if($device_det == 0) {

            $ref_det = $this->db->select('users_id')->get_where('ct_referral',array('referral_code'=>$data['referral_code']))->row_array();

            $data['referred_by'] = (!empty($ref_det['users_id'])) ? $ref_det['users_id'] : '';
           
            // Insert device id
            $insert_device_det = $this->db->insert('ct_login_devices',array('device_id'=>$data['device_id'],'users_id'=>$data['users_id']));
        }
        
        $insert_data = array('users_id'=>$data['users_id'],'referral_code'=>$data['new_ref_code'],'referred_by'=>$data['referred_by'],'referral_status'=>1);
        // Insert referral code
        $insert_user_referral = $this->db->insert('ct_referral',$insert_data);

        return TRUE;       
    }

    /* ===========     User referral credits     ======= */
    public function user_referral_credits($data)
    {

        $referral_count = $this->db->get_where('ct_referral',array('referred_by'=>$data['users_id'],'referral_status'=>1))->num_rows();

        if($referral_count >= 5) {

            $redeem_val = 5;

            $reminder = $referral_count % $redeem_val;

            $limit = $referral_count - $reminder;

            $quotient = $limit / $redeem_val;

            $where_cond = '(referred_by="'.$data['users_id'].'" AND referral_status=1)';
            $referral_count = $this->db->where($where_cond)->limit($limit,0)->update('ct_referral',array('referral_status'=>2));
            $update_credits = $this->db->where('users_id',$data['users_id'])->set('redeem_credits','redeem_credits+'.$quotient.'',false)->set('total_credits','total_credits+'.$quotient.'',false)->update('ct_user_credits');
        }

        $this->db->select('c.user_like_total,c.user_multimedia_total,c.redeem_credits,c.total_credits,r.referral_code');
        $this->db->from('ct_user_credits c');
        $this->db->join('ct_referral r','r.users_id=c.users_id','inner');
        $this->db->where('c.users_id',$data['users_id']);
        $model_data =  $this->db->get()->row_array();

        return $model_data;       
    }

    /* ===========     User redeem credits - like    ======= */
    public function user_redeem_credits_like($data)
    {

        $user_credits = $this->db->select('redeem_credits')->get_where('ct_user_credits',array('users_id'=>$data['users_id']))->row_array();

        if(!empty($user_credits) && $user_credits['redeem_credits'] > 0) {

            $like_count = 8;

            $update_credits = $this->db->where('users_id',$data['users_id'])->set('user_like_count','user_like_count+'.$like_count.'',false)->set('user_like_total','user_like_total+'.$like_count.'',false)->set('redeem_credits','redeem_credits-1',false)->update('ct_user_credits');
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
        }
          
        return $model_data;       
    }

    /* ===========     User redeem credits - post    ======= */
    public function user_redeem_credits_post($data)
    {

        $user_credits = $this->db->select('redeem_credits')->get_where('ct_user_credits',array('users_id'=>$data['users_id']))->row_array();

        if(!empty($user_credits) && $user_credits['redeem_credits'] > 0) {

            $post_count = 3;

            $update_credits = $this->db->where('users_id',$data['users_id'])->set('user_multimedia_post','user_multimedia_post+'.$post_count.'',false)->set('user_multimedia_total','user_multimedia_total+'.$post_count.'',false)->set('redeem_credits','redeem_credits-1',false)->update('ct_user_credits');
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
        }
          
        return $model_data;       
    }

} // End referral model
