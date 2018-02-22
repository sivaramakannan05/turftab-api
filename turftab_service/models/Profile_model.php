<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile_model extends CI_Model {

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

    /* =================     Insert album       =========== */
    public function insert_user_album($data) {

    	if(!empty($data)) {
    		$insert_album_batch = $this->db->insert_batch('ct_albums',$data);
            $insert_count = $this->db->affected_rows();
            $first_id = $this->db->insert_id();
            $model_data['album_data'] = $this->db->select('albums_id,albums_path,file_type')->from('ct_albums')->where('albums_id >=',$first_id)->limit($insert_count,0)->get()->result_array();
    	}
    	else {
    		$model_data['album_data'] = array();
    	}
    	return $model_data;
    }

    /* =================     Remove album       =========== */
    public function remove_user_album($data) {

        $album_paths = $this->db->select('albums_path')->where_in('albums_id',$data)->get('ct_albums')->result_array();
  
        $album_update = $this->db->where_in('albums_id',$data)->update('ct_albums',array('albums_status'=>2));
        return $album_paths;
    }


    /* =================     User blocked count      =========== */
    public function user_blocked_count($from_id,$to_id) {

        $where_cond = '((blocklist_from_id="'.$from_id.'" AND blocklist_to_id="'.$to_id.'") OR (blocklist_from_id="'.$to_id.'" AND blocklist_to_id="'.$from_id.'"))';
        $blocked_count = $this->db->get_where('ct_blocklist',$where_cond)->num_rows();
        return $blocked_count;
    }
    

    /* =================     Get user profile data       =========== */
    public function user_profile_data($data) {

        $blocked_count = $this->user_blocked_count($data['users_id'],$data['action_id']);
        $model_data = array();

        if($blocked_count == 0) {
            $this->db->select('u.users_id as user_id,IFNULL(u.user_fullname,"") as user_fullname,IFNULL(u.user_name,"") as user_name,IFNULL(u.user_country_code,"") as user_country_code,IFNULL(u.user_email,"") as user_email,IFNULL(u.user_mobile,"") as user_mobile,IFNULL(u.user_gender,"") as user_gender,IFNULL(u.user_dob,"") as user_dob,IFNULL(u.user_profile_image,"") as user_profile_image,IFNULL(u.user_description,"") as user_description,u.user_register_type,u.user_profile_updated_date,u.user_profile_created_date,a.albums_id,a.albums_path,a.file_type,IFNULL(f.friends_status,"") as friends_status,f.sender_id,s.profile_image_show');
            $this->db->from('ct_users u');
            $this->db->join('ct_albums a','u.users_id=a.users_id AND a.album_type=1 AND a.albums_status=1','left');
            $this->db->join('ct_friends f','(u.users_id=f.receiver_id AND f.sender_id="'.$data['users_id'].'") OR (u.users_id=f.sender_id AND f.receiver_id="'.$data['users_id'].'")','left');
            $this->db->join('ct_user_settings s','u.users_id=s.users_id','left');
            $this->db->where('u.users_id',$data['action_id']);
            $model_data['data'] = $this->db->get()->result_array();
            $model_data['status'] = (!empty($model_data['data'])) ? "true" : "false";
        }
        else {
            $model_data['status'] = "false";
        }
        return $model_data;
    }

    /* =================     To block the user       =========== */
    public function user_block($data) {

        $blocked_count = $this->user_blocked_count($data['blocklist_from_id'],$data['blocklist_to_id']);

        if($blocked_count == 0) {
            $block_data = $this->db->insert('ct_blocklist',$data);
            $where_cond = '((sender_id="'.$data['blocklist_from_id'].'" AND receiver_id="'.$data['blocklist_to_id'].'") OR (sender_id="'.$data['blocklist_to_id'].'" AND receiver_id="'.$data['blocklist_from_id'].'"))';
            $update_friendlist = $this->db->where($where_cond)->update('ct_friends',array("friends_status"=>3));
            $model_data['message'] = "User blocked successfully";
            $model_data['status'] = "true";
        }
        else {
            $model_data['message'] = "User already blocked";
            $model_data['status'] = "false";
        }
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

    // /* =================     Blocked userlist       =========== */
    // public function user_blocklist($data) {

    //     $this->db->select('u.users_id as blocked_user_id,u.user_fullname,u.user_name,u.user_profile_image');
    //     $this->db->from('ct_blocklist b');
    //     $this->db->join('ct_users u','b.blocklist_to_id=u.users_id','inner');
    //     $this->db->where('b.blocklist_from_id',$data['users_id']);
    //     $model_data = $this->db->get()->result_array();
    //     return $model_data;
    // }

    /* =================     Add friend       =========== */
    public function user_add_friend($data) {

       $blocked_count = $this->user_blocked_count($data['users_id'],$data['friend_id']);

        if($blocked_count == 0) {

            $where_cond = '((sender_id="'.$data['users_id'].'" AND receiver_id="'.$data['friend_id'].'") OR (sender_id="'.$data['friend_id'].'" AND receiver_id="'.$data['users_id'].'"))';            
            $user_friend_data = $this->db->get_where('ct_friends',$where_cond)->num_rows();
            if($user_friend_data == 0) {

                $insert_data = array("sender_id"=>$data['users_id'],"receiver_id"=>$data['friend_id'],"friends_temp_status"=>1,"friends_status"=>1);
                $insert_friend_data = $this->db->insert('ct_friends',$insert_data);
                $model_data['message'] = "Friend request sent successfully";
                $model_data['status'] = "true"; 
            }
            else {
                $model_data['message'] = "Already sent";
                $model_data['status'] = "false"; 
            }         
        }
        else {
            $model_data['message'] = "Something went wrong. Please try again later";
            $model_data['status'] = "false";            
        }
        return $model_data;
    }

    /* =================     Accept friend       =========== */
    public function user_accept_friend($data) {

       $blocked_count = $this->user_blocked_count($data['users_id'],$data['friend_id']);

        if($blocked_count == 0) {

            $where_cond = '(sender_id="'.$data['friend_id'].'" AND receiver_id="'.$data['users_id'].'" AND friends_status=1)';            
            $user_friend_data = $this->db->get_where('ct_friends',$where_cond)->num_rows();
            if($user_friend_data == 0) {

                $model_data['message'] = "No request found";
                $model_data['status'] = "false"; 
            }
            else {

                $update_friend_data = $this->db->where($where_cond)->update('ct_friends',array("    friends_temp_status"=>2,"friends_status"=>2));
                $model_data['message'] = "Friend request accepted successfully";
                $model_data['status'] = "true";             
            }         
        }
        else {
            $model_data['message'] = "Something went wrong. Please try again later";
            $model_data['status'] = "false";            
        }
        return $model_data;
    }

    /* =================     Cancel friend       =========== */
    public function user_cancel_friend($data) {

       $blocked_count = $this->user_blocked_count($data['users_id'],$data['friend_id']);

        if($blocked_count == 0) {

            $where_cond = '((sender_id="'.$data['friend_id'].'" AND receiver_id="'.$data['users_id'].'" AND friends_status=1) OR (receiver_id="'.$data['friend_id'].'" AND sender_id="'.$data['users_id'].'" AND friends_status=1))';            
            $user_friend_data = $this->db->get_where('ct_friends',$where_cond)->num_rows();
            if($user_friend_data == 0) {

                $model_data['message'] = "No request found";
                $model_data['status'] = "false"; 
            }
            else {

                $delete_friend_data = $this->db->where($where_cond)->delete('ct_friends');
                $model_data['message'] = "Friend request cancelled successfully";
                $model_data['status'] = "true";             
            }         
        }
        else {
            $model_data['message'] = "Something went wrong. Please try again later";
            $model_data['status'] = "false";            
        }
        return $model_data;
    }

    /* =================     Remove friend       =========== */
    public function user_remove_friend($data) {

       $blocked_count = $this->user_blocked_count($data['users_id'],$data['friend_id']);

        if($blocked_count == 0) {

            $where_cond = '((sender_id="'.$data['users_id'].'" AND receiver_id="'.$data['friend_id'].'" AND friends_status=2) OR (sender_id="'.$data['friend_id'].'" AND receiver_id="'.$data['users_id'].'" AND friends_status=2))';     
            $user_friend_data = $this->db->get_where('ct_friends',$where_cond)->num_rows();

            if($user_friend_data == 0) {

                $model_data['message'] = "Friend does not exist";
                $model_data['status'] = "false"; 
            }
            else {

                $delete_friend_data = $this->db->where($where_cond)->delete('ct_friends');
                $model_data['message'] = "Friend removed successfully";
                $model_data['status'] = "true";             
            }         
        }
        else {
            $model_data['message'] = "Something went wrong. Please try again later";
            $model_data['status'] = "false";            
        }
        return $model_data;
    }

    /* =================     Friend request sent list       =========== */
    public function user_request_sent($data) {

        $where_cond = '(f.sender_id="'.$data['users_id'].'" AND f.friends_status=1)';     
        $this->db->select('u.users_id as user_id,u.user_fullname,u.user_name,u.user_profile_image,s.profile_image_show');
        $this->db->from('ct_friends f');
        $this->db->join('ct_users u','f.receiver_id=u.users_id','inner');
        $this->db->join('ct_user_settings s','u.users_id=s.users_id','left');
        $user_friend_data = $this->db->where($where_cond)->get()->result_array();

        if(!empty($user_friend_data)) {

            $model_data['data'] = $user_friend_data;
            $model_data['status'] = "true";
        }
        else {

            $model_data['status'] = "false";             
        }
        return $model_data;
    }

    /* =================     Friend request received list       =========== */
    public function user_request_received($data) {

        $where_cond = '(f.receiver_id="'.$data['users_id'].'" AND f.friends_status=1)';     
        $this->db->select('u.users_id as user_id,u.user_name,u.user_fullname,u.user_profile_image,s.profile_image_show');
        $this->db->from('ct_friends f');
        $this->db->join('ct_users u','f.sender_id=u.users_id','inner');
        $this->db->join('ct_user_settings s','u.users_id=s.users_id','left');
        $user_friend_data = $this->db->where($where_cond)->get()->result_array();

        if(!empty($user_friend_data)) {

            $model_data['data'] = $user_friend_data;
            $model_data['status'] = "true";
        }
        else {

            $model_data['status'] = "false";             
        }
        return $model_data;
    }

    /* =================     User friendlist       =========== */
    public function user_friendlist($data) {

        $model_data = array();

        if(!empty($data['action_id'])) {

            $blocked_count = $this->user_blocked_count($data['users_id'],$data['action_id']);

            if($blocked_count == 0) {

                $settings_mutual_status = $this->db->select('mutual_friends_show')->get_where('ct_user_settings',array('users_id'=>$data['action_id']))->row_array();
                $model_data['mutual_status'] = $settings_mutual_status['mutual_friends_show'];

                $model_data['action_frndlist'] = array();
                $model_data['user_frndlist'] = array();
                $model_data['status'] = "false";
                $model_data['message'] = "No records found";

                $where_cond = '((f.sender_id="'.$data['action_id'].'" OR f.receiver_id="'.$data['action_id'].'") AND f.friends_status=2)';
                $this->db->select('us.users_id as user_id,us.user_name,us.user_fullname,us.user_profile_image,IFNULL(us.user_email,"") as user_email,IFNULL(us.user_mobile,"") as user_mobile,(CASE WHEN t.turfmates_id!="" THEN "true" ELSE "false" END) as turfmate_status,,s.profile_image_show');
                $this->db->from('ct_friends f');
                $this->db->join('ct_user_settings s','(f.sender_id=s.users_id AND f.receiver_id='.$data['action_id'].') OR (f.receiver_id=s.users_id AND f.sender_id='.$data['action_id'].')','left');
                $this->db->join('ct_users us','(f.sender_id=us.users_id AND f.receiver_id='.$data['action_id'].') OR (f.receiver_id=us.users_id AND f.sender_id='.$data['action_id'].')','left');
                $this->db->join('ct_turfmates t','((us.users_id=t.sender_id AND t.receiver_id='.$data['users_id'].') OR (us.users_id=t.receiver_id AND t.sender_id='.$data['users_id'].')) AND t.like_status=2','left');
                $this->db->having('user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$data['users_id'].' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$data['users_id'].' OR blocklist_to_id = '.$data['users_id'].')', NULL, FALSE);
                $this->db->where($where_cond);
                $model_data['action_frndlist'] = $this->db->get()->result_array();

                if(!empty($model_data['action_frndlist'])) {

                    $frnd_list = implode(',',array_column($model_data['action_frndlist'],'user_id'));
                    $model_data['status'] = "true";
                    $where_cond_frndlist = '(((f.sender_id="'.$data['users_id'].'" AND f.receiver_id IN('.$frnd_list.')) OR (f.receiver_id="'.$data['users_id'].'" AND f.sender_id IN('.$frnd_list.')))  AND f.friends_status!=3)';
                    $this->db->select('(CASE WHEN f.sender_id='.$data['users_id'].' THEN f.receiver_id ELSE f.sender_id END) as user_id,(CASE WHEN f.sender_id='.$data['users_id'].' THEN "sender" ELSE "receiver" END) as user_request,f.friends_status');
                    $this->db->from('ct_friends f');
                    $this->db->where($where_cond_frndlist);
                    $model_data['user_frndlist'] = $this->db->get()->result_array();
                }
            }
            else {
                $model_data['status'] = "false";
                $model_data['message'] = "Something went wrong. Please try again later";
            }
        }
        else {

            $where_cond = '(us.users_id!="'.$data['users_id'].'" AND us.user_status=1)';
            $this->db->select('us.users_id as user_id,IFNULL(us.user_fullname,"") as user_fullname,IFNULL(us.user_name,"") as user_name,us.user_profile_image,IFNULL(us.user_email,"") as user_email,IFNULL(us.user_mobile,"") as user_mobile,(CASE WHEN t.turfmates_id!="" THEN "true" ELSE "false" END) as turfmate,IFNULL(f.friends_status,"") as friends_status,f.sender_id,s.profile_image_show');
            $this->db->from('ct_users us');
            $this->db->join('ct_friends f','((f.sender_id=us.users_id AND f.receiver_id="'.$data['users_id'].'")  OR  (f.receiver_id=us.users_id AND f.sender_id="'.$data['users_id'].'")) AND f.friends_status!=3','left');
            $this->db->join('ct_user_settings s','us.users_id=s.users_id','left');
            $this->db->join('ct_turfmates t','((t.sender_id=us.users_id AND t.receiver_id="'.$data['users_id'].'")  OR  (t.receiver_id=us.users_id AND t.sender_id="'.$data['users_id'].'")) AND t.like_status=2 ','left');
            $this->db->having('user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$data['users_id'].' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$data['users_id'].' OR blocklist_to_id = '.$data['users_id'].')', NULL, FALSE);
            $this->db->order_by('friends_status desc');
            $this->db->where($where_cond);
            $model_data['data'] = $this->db->get()->result_array(); 
            $model_data['status'] = "true";            
        }
        
        return $model_data;
    }

    /* =============         Update profile details        ======== */
    public function update_profile($user_id,$data)
    {
        $model_data = $this->db->where('users_id',$user_id)->update('ct_users',$data);
        return TRUE;
    }

    /* ===========         Check username,email,mobile are exists or not for update    ======= */
    public function check_username_exist_update($type,$data)
    {

        $model_data = '';

        if($type == "username") {

            $where_cond = '(user_name="'.$data['user_name'].'" AND users_id!="'.$data['users_id'].'")';
            $model_data = $this->db->get_where('ct_users',$where_cond)->num_rows();
        }       
        return $model_data;
    }

    /* ===========         User calender events - Birthday and events    ======= */
    public function user_calender_events($data)
    {

        $given_date = (!empty($data['given_date'])) ? $data['given_date'] : date('Y-m');
        $given_month = date('m',strtotime($given_date));
        $given_year = date('Y',strtotime($given_date));
        $model_data['birthday_events'] = array();
        $model_data['events'] = array();

        // Birthday events
        $birthday_where_cond = '((f.sender_id="'.$data['users_id'].'" OR f.receiver_id="'.$data['users_id'].'") AND f.friends_status=2 AND MONTH(u.user_dob)="'.$given_month.'")';
        $this->db->select('u.users_id,IFNULL(u.user_fullname,"") as user_fullname,IFNULL(u.user_name,"") as user_name,IFNULL(u.user_gender,"") as user_gender,u.user_dob,IFNULL(u.user_profile_image,"") as user_profile_image,IFNULL(u.user_lattitude,"") as user_lattitude,IFNULL(u.user_longitude,"") as user_longitude,s.profile_image_show');
        $this->db->from('ct_friends f');
        $this->db->join('ct_users u','(f.sender_id=u.users_id OR f.receiver_id=u.users_id) AND u.users_id!="'.$data['users_id'].'"','inner');
        $this->db->join('ct_user_settings s','u.users_id=s.users_id','left');
        $model_data['birthday_events'] = $this->db->where($birthday_where_cond)->get()->result_array();

        // Events
        $events_where_cond = '(e.event_status=1 AND ((MONTH(e.event_startdate)='.$given_month.' AND YEAR(e.event_startdate)='.$given_year.') OR (MONTH(e.event_enddate)='.$given_month.' AND YEAR(e.event_enddate)='.$given_year.')) AND (e.users_id="'.$data['users_id'].'" OR ei.users_id="'.$data['users_id'].'"))';
        $this->db->select('e.events_id,e.users_id as event_user_id,e.event_name,e.event_image,e.event_startdate,e.event_enddate,e.event_address,e.event_details,IFNULL(u.user_fullname,"") as user_fullname,IFNULL(u.user_name,"") as user_name');
        $this->db->from('ct_events e');
        $this->db->join('ct_event_invitation ei','e.events_id=ei.events_id','left');
        $this->db->join('ct_users u','e.users_id=u.users_id','inner');
        $this->db->having('event_user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$data['users_id'].' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$data['users_id'].' OR blocklist_to_id = '.$data['users_id'].')', NULL, FALSE); 
        $model_data['events'] = $this->db->where($events_where_cond)->get()->result_array();


        if(!empty($model_data['birthday_events']) || !empty($model_data['events'])) {
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
        }

        return $model_data;
    }

    /* ===========         Update user lattitude and longitude    ======= */
    public function user_location_update($data,$user_id)
    {

        $update_data = $this->db->where('users_id',$user_id)->update('ct_users',$data);

        if($update_data) {
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
        }

        return $model_data;
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
    


} // End profile model
