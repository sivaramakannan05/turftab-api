<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Review_model extends CI_Model {

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

    /* =============       User device token for multiple users        ============== */
    public function get_users_device_details($user_id) {

        $where_cond = '(logs_login_status=1 AND users_id ="'.$user_id.'")';
        $user_device_details = $this->db->select('logs_device_type,logs_device_token')->from('ct_user_logs')->where($where_cond)->get()->row_array();
        return $user_device_details;
    }

    /* =================     User blocked count      =========== */
    public function user_blocked_count($from_id,$to_id) {

        $where_cond = '((blocklist_from_id="'.$from_id.'" AND blocklist_to_id="'.$to_id.'") OR (blocklist_from_id="'.$to_id.'" AND blocklist_to_id="'.$from_id.'"))';
        $blocked_count = $this->db->get_where('ct_blocklist',$where_cond)->num_rows();
        return $blocked_count;
    }

    /* ===========         Save notifications    ======= */
    public function save_notifications($data)
    {

        $insert_data = $this->db->insert('ct_notifications',$data);

        if($insert_data) {
            $model_data['insert_id'] = $this->db->insert_id();
        }
        else {
            $model_data['insert_id'] = '';
        }

        return $model_data;
    }

    /* =============       Check whether the user can post review or not        ============== */
    public function check_review_status($data) {

        $where_cond = '(ei.events_id="'.$data['event_id'].'" AND ei.users_id="'.$data['users_id'].'" AND ei.event_invitation_status=1)';
        $this->db->select('*');
        $this->db->from('ct_event_invitation ei');
        $this->db->join('ct_events e','ei.events_id=e.events_id AND e.event_status=2','inner');
        $model_data = $this->db->where($where_cond)->get()->num_rows();

        return $model_data;
    }

    /* =============       Insert review        ============== */
    public function insert_review($data,$event_user_id) {

        $blocked_count = $this->user_blocked_count($data['users_id'],$event_user_id);

        if($blocked_count == 0) {
            $insert_review_data = $this->db->insert('ct_reviews',$data);
            $model_data['status'] = "true";
            $model_data['insert_id'] = $this->db->insert_id();
        }
        else {
            $model_data['status'] = "false";            
        }

        return $model_data;
    }

    /* =============       Delete review        ============== */
    public function delete_review($data) {

        $blocked_count = $this->user_blocked_count($data['users_id'],$data['event_user_id']);

        if($blocked_count == 0) {

            $review_count = $this->db->get_where('ct_reviews',array('reviews_id'=>$data['reviews_id'],'users_id'=>$data['users_id'],'reviews_status'=>1))->num_rows();

            if($review_count == 1) {
                $delete_review = $this->db->where('reviews_id',$data['reviews_id'])->update('ct_reviews',array('reviews_status'=>2));
                $model_data['status'] = "true";
            }
            else {
                $model_data['status'] = "false";
                $model_data['message'] = "No record(s) found";
            }
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Something went wrong. Please try again later";
        }

        return $model_data;
    }

    /* =============       Check whether the admin of event is reply or not        ============== */
    public function check_admin_event_status($data) {

        $model_data = $this->db->get_where('ct_reviews',array('reviews_id'=>$data['reviews_id'],'event_user_id'=>$data['users_id'],'reviews_status'=>1))->num_rows();

        return $model_data;
    }

    /* =============       Insert reply        ============== */
    public function insert_reply($data) {

        $insert_reply_data = $this->db->insert('ct_reply',$data);
        $model_data['insert_id'] = $this->db->insert_id();
        return $model_data;
    }

    /* =============       Get user id of user id who posted the review        ============== */
    public function get_user_id($review_id) {

        $model_data = $this->db->select('users_id')->get_where('ct_reviews',array('reviews_id'=>$review_id))->row_array();

        return $model_data['users_id'];
    }

    /* =============       Delete reply        ============== */
    public function delete_reply($data) {

        $review_count = $this->db->get_where('ct_reply',array('reply_id'=>$data['reply_id'],'users_id'=>$data['users_id'],'reply_status'=>1))->num_rows();

        if($review_count == 1) {
            $delete_review = $this->db->where('reply_id',$data['reply_id'])->update('ct_reply',array('reply_status'=>2));
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "No record(s) found";
        }
    
        return $model_data;
    }

    /* =============    User Review list       ============== */
    public function user_review_list($data) {

        $model_data['event_data'] =array();
        $model_data['reviews_data'] =array();

        $blocked_count = $this->user_blocked_count($data['users_id'],$data['event_user_id']);

        if($blocked_count == 0) {

            $model_data['event_data'] = $this->db->select('events_id as event_id,users_id as event_user_id,event_name,event_image,event_startdate,event_enddate,event_address,event_lattitude,event_longitude,event_details,event_category,event_type,event_updated_date,event_created_date')->get_where('ct_events',array('events_id'=>$data['event_id'],'event_status'=>2))->row_array();

            if(!empty($model_data['event_data'])) {

                $where_cond = '(r.events_id="'.$data['event_id'].'" AND r.reviews_status=1)';
                $this->db->select('r.reviews_id,r.comments as review,r.reviews_type,r.reviews_created_date,rp.reply_id,rp.comments as reply,rp.reply_created_date,u.users_id as user_id,u.user_fullname,u.user_name,u.user_profile_image,ua.users_id as admin_user_id,ua.user_fullname as admin_user_fullname,ua.user_name as admin_user_name,ua.user_profile_image as admin_user_profile_image');
                $this->db->from('ct_reviews r');
                $this->db->join('ct_reply rp','r.reviews_id=rp.reviews_id AND rp.reply_status=1','left');
                $this->db->join('ct_users u','r.users_id=u.users_id','left');
                $this->db->join('ct_users ua','r.event_user_id=ua.users_id','left');
                $model_data['reviews_data'] = $this->db->where($where_cond)->order_by('r.reviews_id desc,rp.reply_id asc')->get()->result_array();
                $model_data['status'] = "true";
            }
            else {
                $model_data['status'] = "false";
                $model_data['message'] = "No record(s) found";
            }
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Something went wrong. Please try again later";
        }

        return $model_data;
    }
    

} // End review model
