<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Event_model extends CI_Model {

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
    public function get_users_device_details($user_ids,$type) {

        $user_device_details = array();

        if($type == "single") {

            $where_cond = '(logs_login_status=1 AND users_id ="'.$user_ids.'")';
            $user_device_details = $this->db->select('logs_device_type,logs_device_token')->from('ct_user_logs')->where($where_cond)->get()->row_array();
            return $user_device_details;
        }
        else {
            if(!empty($user_ids)) {
                $where_cond = '(logs_login_status=1 AND users_id IN ('.implode(',', $user_ids).'))';
                $user_device_details = $this->db->select('logs_device_type,logs_device_token,users_id')->from('ct_user_logs')->where($where_cond)->get()->result_array();
            }
        }

        return $user_device_details;
    }

    /* =================     User blocked count      =========== */
    public function user_blocked_count($from_id,$to_id) {

        $where_cond = '((blocklist_from_id="'.$from_id.'" AND blocklist_to_id="'.$to_id.'") OR (blocklist_from_id="'.$to_id.'" AND blocklist_to_id="'.$from_id.'"))';
        $blocked_count = $this->db->get_where('ct_blocklist',$where_cond)->num_rows();
        return $blocked_count;
    }

    /* =================     User friendlist       =========== */
    public function friendlist_by_user_id($user_id,$event_id='') {

        if(!empty($event_id)) {
            $where_cond = '((f.sender_id="'.$user_id.'" OR f.receiver_id="'.$user_id.'") AND f.friends_status=2 AND FIND_IN_SET(us.users_id,e.event_invited_members) = 0)';
        }
        else {
            $where_cond = '((f.sender_id="'.$user_id.'" OR f.receiver_id="'.$user_id.'") AND f.friends_status=2)';    
        }
        
        $this->db->select('us.user_fullname,us.user_name,us.users_id as user_id,us.user_profile_image,s.profile_image_show');
        $this->db->from('ct_friends f');
        $this->db->join('ct_users us','(f.sender_id=us.users_id OR f.receiver_id=us.users_id) AND us.users_id!='.$user_id.'','inner');
        $this->db->join('ct_user_settings s','(f.sender_id=s.users_id OR f.receiver_id=s.users_id) AND s.users_id!='.$user_id.'','left');
        if(!empty($event_id)) {
            $this->db->join('ct_events e','e.events_id='.$event_id.' AND us.users_id!=e.users_id','left');
        }
        $this->db->where($where_cond);
        $model_data = $this->db->get()->result_array();
        return $model_data;
    }

    /* =============       Event creation        ============== */
    public function create_user_events($data) {

        if($data['event_invited_members'] == "public") {
            $friends_data = $this->friendlist_by_user_id($data['users_id']);
            $data['event_invited_members'] = (!empty($friends_data)) ? implode(',',array_column($friends_data, 'user_id')) : '';
        }
        $this->db->insert('ct_events',$data);
        $model_data['insert_id'] = $this->db->insert_id();
        $model_data['invited_ids'] = $data['event_invited_members'];
        $model_data['status'] = "true";
        return $model_data; 
    }

    /* =============       Event updation        ============== */
    public function update_user_events($event_id,$data) {

        $event_data = $this->db->get_where('ct_events',array('events_id'=>$event_id))->row_array();

        $model_data['already_invited_ids'] = $event_data['event_invited_members'];

        if(!empty($data['event_type']) && ($data['event_type'] == 2 || $data['event_type'] == 1) && $event_data['event_type'] == 2) {

            unset($data['event_type'],$data['event_invited_members']);
        }

        if(!empty($data['event_type']) && $data['event_type'] == 2) {

            $friends_data = $this->friendlist_by_user_id($data['users_id'],$event_id);
            $already_friends_data = (!empty($event_data['event_invited_members'])) ? explode(',',$event_data['event_invited_members']) : array();

            if(!empty($friends_data)) {

                $friends_user_data = array_column($friends_data, 'user_id');
                $new_ids = array_diff($friends_user_data,$already_friends_data);
                $data['event_invited_members'] = implode(',',array_merge($already_friends_data,$new_ids));
                $model_data['notification_ids'] = $new_ids;
            }
            else {
                unset($data['event_invited_members']);
            }
        }
        else if(!empty($data['event_type']) && $data['event_type'] == 1) {

            $already_friends_data = (!empty($event_data['event_invited_members'])) ? explode(',',$event_data['event_invited_members']) : array();

            if(!empty($data['event_invited_members'])) {

                $friends_user_data = explode(',',$data['event_invited_members']);
                $new_ids = array_diff($friends_user_data,$already_friends_data);
                $data['event_invited_members'] = implode(',',array_merge($already_friends_data,$new_ids));
                $model_data['notification_ids'] = $new_ids;
            }
            else {
                unset($data['event_invited_members']);
            }
        }
        
        $this->db->where('events_id',$event_id)->update('ct_events',$data);
        $model_data['status'] = "true";
        return $model_data; 
    }

    /* =============       Current events list        ============== */
    public function user_current_events($data) {

        $user_settings_radius = $this->db->select('user_radius')->get_where('ct_user_settings',array('users_id'=>$data['users_id']))->row_array();
        $distance = $user_settings_radius['user_radius'];
        $latitude = $data['latitude'];
        $longitude = $data['longitude'];
        $model_data['own_events'] = array();
        $model_data['accepted_events'] = array();
        $model_data['nearby_events'] = array();
        $event_cat = (!empty($data['event_category'])) ? $data['event_category'] : "all";

        if($event_cat != "all") {
            $where_cond_own = '(e.users_id="'.$data['users_id'].'" AND e.event_status=1 AND e.event_category="'.$event_cat.'")';
        }
        else {
            $where_cond_own = '(e.users_id="'.$data['users_id'].'" AND e.event_status=1)';       
        }

        $this->db->select('e.events_id as event_id,e.users_id as event_user_id,e.event_name,e.event_image,e.event_startdate,e.event_enddate,e.event_address,e.event_lattitude,e.event_longitude,e.event_category,e.event_type,e.event_updated_date,e.event_created_date,u.user_name,u.user_fullname,e.event_status');
        $this->db->from('ct_events e');
        $this->db->join('ct_users u','e.users_id=u.users_id','inner');
        $model_data['own_events'] = $this->db->where($where_cond_own)->get()->result_array();

        if($event_cat != "all") {
            $where_cond_accepted = '(ei.users_id="'.$data['users_id'].'" AND ei.event_invitation_status=1 AND e.event_status=1 AND e.event_category="'.$event_cat.'")';
        }
        else {
             $where_cond_accepted = '(ei.users_id="'.$data['users_id'].'" AND ei.event_invitation_status=1 AND e.event_status=1)';
        }
   
        $this->db->select('e.events_id as event_id,e.users_id as event_user_id,e.event_name,e.event_image,e.event_startdate,e.event_enddate,e.event_address,e.event_lattitude,e.event_longitude,e.event_category,e.event_type,e.event_updated_date,e.event_created_date,u.user_name,u.user_fullname,e.event_status');
        $this->db->from('ct_event_invitation ei');
        $this->db->join('ct_events e','ei.events_id=e.events_id','inner');
        $this->db->join('ct_users u','e.users_id=u.users_id','inner');
        $this->db->having('event_user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$data['users_id'].' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$data['users_id'].' OR blocklist_to_id = '.$data['users_id'].')', NULL, FALSE); 
        $model_data['accepted_events'] = $this->db->where($where_cond_accepted)->get()->result_array();

        if($event_cat != "all") {
            $where_cond_nearby = "e.users_id!='".$data['users_id']."' AND e.event_status=1 AND e.event_type=2 AND e.events_id NOT IN (SELECT ei.events_id FROM ct_event_invitation ei WHERE ei.users_id='".$data['users_id']."' AND ei.event_invitation_status=1) AND e.event_category='".$event_cat."'";
        }
        else {
            $where_cond_nearby = "e.users_id!='".$data['users_id']."' AND e.event_status=1 AND e.event_type=2 AND e.events_id NOT IN (SELECT ei.events_id FROM ct_event_invitation ei WHERE ei.users_id='".$data['users_id']."' AND ei.event_invitation_status=1)";
        }

        $nearby_query = "SELECT e.events_id as event_id,e.users_id as event_user_id,e.event_name,e.event_image,e.event_startdate,e.event_enddate,e.event_address,e.event_lattitude,e.event_longitude,e.event_category,e.event_type,e.event_updated_date,e.event_created_date,u.user_name,u.user_fullname,e.event_status, 3956 * 2 * ASIN(SQRT( POWER(SIN(($latitude - event_lattitude) * pi()/180 / 2), 2) + COS($latitude * pi()/180) * COS(event_lattitude * pi()/180) *POWER(SIN(($longitude - event_longitude) * pi()/180 / 2), 2) )) as distance FROM ct_events as e LEFT JOIN ct_users as u ON e.users_id=u.users_id WHERE $where_cond_nearby HAVING distance <= $distance AND event_user_id NOT IN (select (CASE WHEN blocklist_from_id!=".$data['users_id']." THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = ".$data['users_id']." OR blocklist_to_id = ".$data['users_id'].")";
        $model_data['nearby_events'] = $this->db->query($nearby_query)->result_array();
        return $model_data;
    }

    /* =============       Past events list        ============== */
    public function user_past_events($data) {

        $model_data['own_events'] = array();
        $model_data['accepted_events'] = array();
        $event_cat = (!empty($data['event_category'])) ? $data['event_category'] : "all";

        if($event_cat != "all") {
            $where_cond_own = '(e.users_id="'.$data['users_id'].'" AND e.event_status=2 AND e.event_category="'.$event_cat.'")';
        }
        else {
            $where_cond_own = '(e.users_id="'.$data['users_id'].'" AND e.event_status=2)';
        }

        $this->db->select('e.events_id as event_id,e.users_id as event_user_id,e.event_name,e.event_image,e.event_startdate,e.event_enddate,e.event_address,e.event_lattitude,e.event_longitude,e.event_category,e.event_type,e.event_updated_date,e.event_created_date,u.user_name,u.user_fullname,e.event_status');
        $this->db->from('ct_events e');
        $this->db->join('ct_users u','e.users_id=u.users_id','left');
        $model_data['own_events'] = $this->db->where($where_cond_own)->get()->result_array();

        if($event_cat != "all") {
            $where_cond_accepted = '(ei.users_id="'.$data['users_id'].'" AND ei.event_invitation_status=1 AND e.event_status=2 AND e.event_category="'.$event_cat.'")';
        }
        else {
            $where_cond_accepted = '(ei.users_id="'.$data['users_id'].'" AND ei.event_invitation_status=1 AND e.event_status=2)';
        }

        $this->db->select('e.events_id as event_id,e.users_id as event_user_id,e.event_name,e.event_image,e.event_startdate,e.event_enddate,e.event_address,e.event_lattitude,e.event_longitude,e.event_category,e.event_type,e.event_updated_date,e.event_created_date,u.user_name,u.user_fullname,e.event_status');
        $this->db->from('ct_event_invitation ei');
        $this->db->join('ct_events e','ei.events_id=e.events_id','inner');
        $this->db->join('ct_users u','e.users_id=u.users_id','inner');
        $this->db->having('event_user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$data['users_id'].' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$data['users_id'].' OR blocklist_to_id = '.$data['users_id'].')', NULL, FALSE); 
        $model_data['accepted_events'] = $this->db->where($where_cond_accepted)->get()->result_array();

        return $model_data;
    }

    /* =============       Event details        ============== */
    public function user_events_details($data) {

        $blocked_count = $this->user_blocked_count($data['users_id'],$data['event_user_id']);

        if($blocked_count == 0) {

            $where_cond = '(e.events_id="'.$data['event_id'].'")';
            $this->db->select('e.events_id as event_id,e.users_id as event_user_id,e.event_name,e.event_image,e.event_startdate,e.event_enddate,e.event_address,event_lattitude,e.event_longitude,e.event_details,e.event_category,e.event_type,e.event_comments,e.event_updated_date,e.event_created_date,IFNULL(ei.event_invitation_status,"") as event_user_status,u.user_name,u.user_fullname');
            $this->db->from('ct_events e');
            $this->db->join('ct_users u','e.users_id=u.users_id','inner');
            $this->db->join('ct_event_invitation ei','e.events_id=ei.events_id AND ei.users_id='.$data['users_id'].'','left');
            $this->db->where($where_cond);
            $model_data['data'] = $this->db->get()->row_array();
            $model_data['status'] = (!empty($model_data['data'])) ? "true" : "false";
        }
        else {
            $model_data['status'] = "false";
        }
        return $model_data;
    }

    /* =============       Event interest      ============== */
    public function user_events_interest($data) {

        $blocked_count = $this->user_blocked_count($data['users_id'],$data['event_user_id']);

        if($blocked_count == 0) {

            $event_acceptance_count = $this->db->get_where('ct_event_invitation',array("events_id"=>$data['event_id'],"users_id"=>$data['users_id']))->num_rows();
            if($event_acceptance_count == 0) {

                $this->db->insert('ct_event_invitation',array("events_id"=>$data['event_id'],"users_id"=>$data['users_id'],"event_invitation_status"=>1));
                $model_data['insert_id'] = $this->db->insert_id();
                $model_data['status'] = "true";
            }
            else {
             
                $where_cond = '(events_id="'.$data['event_id'].'" AND users_id="'.$data['users_id'].'")';
                $this->db->where($where_cond)->update('ct_event_invitation',array("event_invitation_status"=>1));
                $model_data['status'] = "true";
            }
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Something went wrong. Please try again later";
        }      
        return $model_data; 
    }

    /* =============       Event not interest        ============== */
    public function user_events_not_interest($data) {

        $blocked_count = $this->user_blocked_count($data['users_id'],$data['event_user_id']);

        if($blocked_count == 0) {
            $event_acceptance_count = $this->db->get_where('ct_event_invitation',array("events_id"=>$data['event_id'],"users_id"=>$data['users_id']))->num_rows();
            if($event_acceptance_count == 0) {

                $this->db->insert('ct_event_invitation',array("events_id"=>$data['event_id'],"users_id"=>$data['users_id'],"event_invitation_status"=>2));
                $model_data['insert_id'] = $this->db->insert_id();
                $model_data['status'] = "true";
            }
            else {

                $where_cond = '(events_id="'.$data['event_id'].'" AND users_id="'.$data['users_id'].'")';
                $this->db->where($where_cond)->update('ct_event_invitation',array("event_invitation_status"=>2));
                $model_data['status'] = "true";
            }
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Something went wrong. Please try again later";
        }
        return $model_data; 
    }

    /* =============       Event invite        ============== */
    public function invite_user_events($data) {

        $blocked_count = $this->user_blocked_count($data['users_id'],$data['event_user_id']);

        if($blocked_count == 0) {
            
            $event_invited_ids = $this->db->select('event_type,event_invited_members')->get_where('ct_events',array('events_id'=>$data['event_id']))->row_array();

            if(($event_invited_ids['event_type'] == 2 && in_array($data['users_id'],explode(',',$event_invited_ids['event_invited_members']))) || ($data['users_id'] == $data['event_user_id'])) {
                
                // Update ids
                $new_ids = array_diff(explode(',',$data['event_invited_members']),explode(',',$event_invited_ids['event_invited_members']));
                $update_invite_ids = array_merge(explode(',',$event_invited_ids['event_invited_members']),$new_ids);
                $model_data['notification_ids'] = $new_ids;
                $event_invitation_update = $this->db->where('events_id',$data['event_id'])->update('ct_events',array("event_invited_members"=>implode(',',$update_invite_ids)));
                $model_data['status'] = "true";
            }
            else {
                $model_data['status'] = "false";
                $model_data['message'] = "You can't invite members to the event";  
            }
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Something went wrong. Please try again later";
        }  
        
        return $model_data;
    }

    /* =============       Event accept      ============== */
    // public function accept_user_events($data) {


    //     $blocked_count = $this->user_blocked_count($data['users_id'],$data['event_user_id']);

    //     if($blocked_count == 0) {

    //         $event_acceptance_count = $this->db->get_where('ct_event_invitation',array("events_id"=>$data['event_id'],"users_id"=>$data['users_id'],"event_invitation_status"=>1))->num_rows();
    //         if($event_acceptance_count == 0) {

    //             $this->db->insert('ct_event_invitation',array("events_id"=>$data['event_id'],"users_id"=>$data['users_id'],"event_invitation_status"=>1));
    //             $model_data['insert_id'] = $this->db->insert_id();
    //             $model_data['status'] = "true";
    //         }
    //         else {
    //             $model_data['status'] = "false";
    //             $model_data['message'] = "Already accepted ";
    //         }
    //     }
    //     else {
    //         $model_data['status'] = "false";
    //         $model_data['message'] = "Something went wrong. Please try again later";
    //     }      
    //     return $model_data; 
    // }

    // /* =============       Event decline        ============== */
    // public function decline_user_events($data) {

    //      $blocked_count = $this->user_blocked_count($data['users_id'],$data['event_user_id']);

    //     if($blocked_count == 0) {
    //         $event_acceptance_count = $this->db->get_where('ct_event_invitation',array("events_id"=>$data['event_id'],"users_id"=>$data['users_id'],"event_invitation_status"=>1))->num_rows();
    //         if($event_acceptance_count == 0) {

    //             $model_data['status'] = "false";
    //             $model_data['message'] = "No records found";
    //         }
    //         else {

    //             $this->db->delete('ct_event_invitation',array("events_id"=>$data['event_id'],"users_id"=>$data['users_id'],"event_invitation_status"=>1));
    //             $model_data['status'] = "true";
    //         }      
    //     }
    //     else {
    //         $model_data['status'] = "false";
    //         $model_data['message'] = "Something went wrong. Please try again later";
    //     }
    //     return $model_data; 
    // }

    /* =============       Event add comment        ============== */
    public function user_events_add_comment($data) {

        $blocked_count = $this->user_blocked_count($data['users_id'],$data['event_user_id']);

        if($blocked_count == 0) {

            $event_comments = $this->db->select('event_comments')->get_where('ct_events',array('events_id'=>$data['event_id']))->row_array();
            if(!empty($event_comments['event_comments'])) {
                $event_comments_array = json_decode($event_comments['event_comments'],true);

                $end_comment_count = explode('_',end($event_comments_array)['message_id'])[1];
                $new_comment_id = $end_comment_count+1;
                $event_comments_array[] = array("message_id"=>$data['users_id']."_".$new_comment_id,"user_id"=>$data['users_id'],"user_name"=>$data['user_name'],"user_fullname"=>$data['user_fullname'],"message"=>$data['message']);
                $this->db->where('events_id',$data['event_id'])->update('ct_events',array('event_comments'=>json_encode($event_comments_array)));
            }
            else {
                $comment[] = array("message_id"=>$data['users_id']."_1","user_id"=>$data['users_id'],"user_name"=>$data['user_name'],"user_fullname"=>$data['user_fullname'],"message"=>$data['message']);
                $this->db->where('events_id',$data['event_id'])->update('ct_events',array('event_comments'=>json_encode($comment)));
            }
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
        }

       return $model_data;
    }

    /* =============       Event delete comment        ============== */
    public function user_events_delete_comment($data) {

        $event_comments = $this->db->select('event_comments')->get_where('ct_events',array('events_id'=>$data['event_id']))->row_array();

        if(!empty($event_comments['event_comments'])) {
            $event_comments_array = json_decode($event_comments['event_comments'],true);
            $comment_search_key = array_search($data['message_id'], array_column($event_comments_array, 'message_id'));
            if(trim($comment_search_key) == '') {
                $model_data['status'] = "false"; 
            }
            else {
                unset($event_comments_array[$comment_search_key]);
                $json_comments = json_encode(array_values($event_comments_array));
                $this->db->where('events_id',$data['event_id'])->update('ct_events',array('event_comments'=>$json_comments));
                $model_data['status'] = "true";
            }
        }
        else {
            $model_data['status'] = "false";
        }
        return $model_data;
    }

    /* ===========    Delete all comments of event    ======= */
    public function user_events_delete_all_comment($data) {

        $event_comments = $this->db->select('event_comments')->get_where('ct_events',array('events_id'=>$data['event_id']))->row_array();

        if(!empty($event_comments['event_comments'])) {
             $this->db->where('events_id',$data['event_id'])->update('ct_events',array('event_comments'=>''));
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
        }
        return $model_data;
    }
    
    /* =============       Event list based on given location        ============== */
    public function user_location_events_filter($data) {


        // // event_country // event_al_1 // event_al_2 // event_sl_1 // event_sl_2 // event_locality // event_street // event_category  
    
        // // foreach ((array)$data as $key => $value) {

        // //     if(!empty($value)) {
        // //         // $where_cond .= ($i == $len) ? "" : " AND ";
        // //         $where_cond .= " AND e."."$key = '$value'";
        // //     }
        // //     $i++;
        // // }

        // $this->db->select('e.events_id as event_id,e.users_id as event_user_id,e.event_name,e.event_image,e.event_startdate,e.event_enddate,e.event_address,e.event_lattitude,e.event_longitude,e.event_details,e.event_category,e.event_type,e.event_updated_date,e.event_created_date,u.user_name,u.user_fullname');
        // $this->db->from('ct_events e');
        // $this->db->join('ct_users u','e.users_id=u.users_id','inner');
        // $this->db->having('event_user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$data['users_id'].' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$data['users_id'].' OR blocklist_to_id = '.$data['users_id'].')', NULL, FALSE);
        // $this->db->where($where_cond);
        // $model_data = $this->db->get()->result_array();








        $model_data['own_events'] = array();
        $model_data['nearby_events'] = array();
        $event_cat = (!empty($data['event_category'])) ? $data['event_category'] : "all";

        $where_cond_own = "e.users_id='".$data['users_id']."' AND e.event_status=1 AND e.event_address LIKE '%".$data['keyword']."%'";

        if($data['event_category'] != "all") {
            $where_cond_own .= " AND e.event_category='".$event_cat."'";   
        }

        $this->db->select('e.events_id as event_id,e.users_id as event_user_id,e.event_name,e.event_image,e.event_startdate,e.event_enddate,e.event_address,e.event_lattitude,e.event_longitude,e.event_details,e.event_category,e.event_type,e.event_updated_date,e.event_created_date,u.user_name,u.user_fullname,e.event_status');
        $this->db->from('ct_events e');
        $this->db->join('ct_users u','e.users_id=u.users_id','inner');
        $model_data['own_events'] = $this->db->where($where_cond_own)->get()->result_array();   
       
        $where_cond_nearby = "e.users_id!='".$data['users_id']."' AND e.event_address LIKE '%".$data['keyword']."%' AND e.event_status=1 AND e.events_id NOT IN (SELECT ei.events_id FROM ct_event_invitation ei WHERE ei.users_id='".$data['users_id']."' AND ei.event_invitation_status=1)";

        if($data['event_category'] != "all") {
            $where_cond_nearby .= " AND e.event_category='".$event_cat."'";   
        }

        $nearby_query = "SELECT e.events_id as event_id,e.users_id as event_user_id,e.event_name,e.event_image,e.event_startdate,e.event_enddate,e.event_address,e.event_lattitude,e.event_longitude,e.event_details,e.event_category,e.event_type,e.event_updated_date,e.event_created_date,u.user_name,u.user_fullname,e.event_status FROM ct_events as e INNER JOIN ct_users as u ON e.users_id=u.users_id WHERE $where_cond_nearby HAVING event_user_id NOT IN (select (CASE WHEN blocklist_from_id!=".$data['users_id']." THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = ".$data['users_id']." OR blocklist_to_id = ".$data['users_id'].")";
        $model_data['nearby_events'] = $this->db->query($nearby_query)->result_array();

        return $model_data;
    }

    /* ===========         Save notifications batch   ======= */
    public function save_notifications_batch($data)
    {

        $insert_data = $this->db->insert_batch('ct_notifications',$data);

        if($insert_data) {
            $first_id = $this->db->insert_id();
            $affected_rows = $this->db->affected_rows();
            for($i=0;$i<$affected_rows;$i++) {
                $user_ids[] = $first_id+$i;
            }
            $model_data['insert_ids'] = $this->db->select('notifications_id,notifications_to_id as user_id')->where_in('notifications_id',$user_ids)->get('ct_notifications')->result_array();
        }
        else {
            $model_data['insert_ids'] = '';
        }

        return $model_data;
    }

    /* ===========         Save notifications   ======= */
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
    
    /* ===========         Check event status   ======= */
    public function check_event_status($event_id)
    {

        $model_data = $this->db->get_where('ct_events',array('events_id'=>$event_id,'event_status'=>1))->num_rows();

        return $model_data;
    }

    /* ===========         Check event type   ======= */
    public function check_event_type($user_id,$event_id)
    {

        if(!empty($event_id)) {
            $model_data = $this->db->select('users_id,event_type')->get_where('ct_events',array('events_id'=>$event_id))->row_array();

            if(!empty($model_data) && $model_data['event_type'] == 2) {
                return TRUE;
            }
            else if(!empty($model_data) && $model_data['event_type'] == 1 && $model_data['users_id'] == $user_id) 
            {
                return TRUE;
            }
            else {
                return FALSE;
            }
        }
        else {
            return TRUE;
        }
    }


} // End event model
