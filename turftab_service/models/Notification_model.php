<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends CI_Model {

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

    /* ======     User notification history    ====== */
	public function user_notifications_history($user_id)
	{
		
		$model_data = array();
		$where_cond = '(n.notifications_to_id="'.$user_id.'" AND n.notifications_status!=3)';

		$this->db->select('n.notifications_id,n.notifications_from_id as user_id,u.user_fullname,u.user_name,IFNULL(n.notifications_event_id,"") as notifications_event_id,IFNULL(e.users_id,"") as event_user_id,IFNULL(e.event_name,"") as event_name,(CASE WHEN n.notifications_event_id != "" THEN IFNULL(e.event_image,"") ELSE IFNULL(u.user_profile_image,"") END) as notification_image,CONCAT(u.user_fullname," ",n.notifications_msg) as notification_msg,n.notifications_type,n.notifications_status,n.notifications_created_date,s.profile_image_show,IFNULL(n.event_review_type,"") as event_review_type');
		$this->db->from('ct_notifications n');
		$this->db->join('ct_users u','n.notifications_from_id=u.users_id','left');
		$this->db->join('ct_events e','n.notifications_event_id=e.events_id','left');
		$this->db->join('ct_user_settings s','u.users_id=s.users_id','left');
		$this->db->having('user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$user_id.' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$user_id.' OR blocklist_to_id = '.$user_id.')', NULL, FALSE);
		$model_data['data'] = $this->db->where($where_cond)->order_by('notifications_id desc')->get()->result_array();
		if(!empty($model_data['data'])) {
			$count_result_array = array_filter($model_data['data'], function ($item) {
			    if ($item['notifications_status'] == 1) {
			        return true;
			    }
			    return false;
			});
			$model_data['unread_count'] = count($count_result_array);
		}
		
		return $model_data;
	}

	/* ======     Update notification status    ====== */
	public function user_notifications_update($data,$type)
	{
		if($type == "delete") {
			$model_data['status'] = $this->db->where('notifications_id',$data['notifications_id'])->update('ct_notifications',array('notifications_status'=>3));	
		}
		else if($type == "delete_all") {
			$model_data['status'] = $this->db->where('notifications_to_id',$data['users_id'])->update('ct_notifications',array('notifications_status'=>3));	
		}
		else {
			$model_data['status'] = $this->db->where('notifications_id',$data['notifications_id'])->update('ct_notifications',array('notifications_status'=>2));
		
			$this->db->select('n.notifications_from_id,u.user_fullname,u.user_name,u.user_turfmate_image,IFNULL(n.notifications_event_id,"") as notifications_event_id,n.notifications_type,IFNULL(n.event_review_type,"") as event_review_type,IFNULL(e.users_id,"") as event_user_id,IFNULL(g.beginner_id,"") as beginner_id,IFNULL(g.tictactoe_status,"") as tictactoe_status,IFNULL(hn.original_word,"") as original_word,IFNULL(hn.recent_word,"") as recent_word,IFNULL(hn.live_word,"") as live_word,IFNULL(hn.hint,"") as hint');
			$this->db->from('ct_notifications n');
			$this->db->join('ct_events e','n.notifications_event_id=e.events_id','left');
			$this->db->join('ct_game_tictactoe g','n.notifications_event_id=g.game_tictactoe_id AND n.notifications_type="game_tictactoe"','left');
			$this->db->join('ct_users u','u.users_id=n.notifications_from_id','left');
			$this->db->join('ct_hangman_notifications hn','n.notifications_event_id=hn.game_hangman_id AND n.notifications_type="hangman_notification"','left');
			$this->db->where('n.notifications_id',$data['notifications_id']);
			$model_data['data'] = $this->db->get()->row_array();
		}
		
		return $model_data;
	}


} // End notification model
