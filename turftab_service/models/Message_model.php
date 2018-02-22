<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Message_model extends CI_Model {

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

    /* =============       Check profile friend status between this two users        ============== */
    public function check_profile_friend_status($data) {
        
        $where_cond = '((sender_id="'.$data['users_id'].'" AND receiver_id="'.$data['friend_id'].'") OR (sender_id="'.$data['friend_id'].'" AND receiver_id="'.$data['users_id'].'") AND friends_status=2)';
        $friend_status = $this->db->get_where('ct_friends',$where_cond)->num_rows();
        return $friend_status;
    }

    /* =============      Insert profile message         ============== */
    public function insert_profile_message($data) {

        $insert_data = $this->db->insert('ct_conversation',$data);

        if($insert_data) {
            
            $model_data['insert_id'] = $this->db->insert_id();
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
        }

        return $model_data;
    }

   
    

} // End chat model
