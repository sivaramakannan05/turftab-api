<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_media_model extends CI_Model {

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

    /* =============       Insert media files        ============== */
    public function insert_chat_media($data) {

        $insert_media = $this->db->insert('ct_chat_media',$data);
        if($insert_media) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    
   
} // End chat media model
