<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_model extends CI_Model {

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

    /* =============       Group creation        ============== */
    public function user_group_creation($data) {

        $insert_data = $this->db->insert('ct_chat_group',$data);
        if($insert_data) {
            $model_data['insert_id'] = $this->db->insert_id();
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
            $model_data['insert_id'] = '';
        }

        return $model_data;
    }

     /* =============       Add members to group        ============== */
    public function user_group_members_add($data,$type) {

        if($type == "new") {

            $insert_data = $this->db->insert_batch('ct_group_members',$data);
            if($insert_data) {
                $model_data['insert_id'] = $this->db->insert_id();
                $model_data['status'] = "true";
            }
            else {
                $model_data['status'] = "false";
                $model_data['insert_id'] = '';
            }    
        }
        else {

            $group_id = $data['chat_group_id'];
            unset($data['chat_group_id']);

            $group_data = $this->db->get_where('ct_chat_group',array('chat_group_id'=>$group_id))->row_array();

            if($group_data['chat_group_status'] == 2 || $group_data['chat_group_status'] == 3) {

                $model_data['message'] = "The group is not available";
                $model_data['status'] = "false";
            }
            else {

                // $group_strength = $this->db->get_where('ct_group_members',array('chat_group_id'=>$group_id,'group_members_status'=>1))->num_rows();

                // if($group_data['chat_group_maximum'] >= ($group_strength+count($data))) {

                    $members_ids = array_column($data, 'group_member_id');
                    $where_cond = '(chat_group_id="'.$group_id.'" AND group_member_id IN ('.implode(',', $members_ids).') AND group_members_status=1)';
                    $group_strength = $this->db->get_where('ct_group_members',$where_cond)->num_rows();

                    if($group_strength > 0) {
                        $model_data['status'] = "false";
                        $model_data['message'] = "Some people(s) are already added in the group";
                    }
                    else {
                        $insert_data = $this->db->insert_batch('ct_group_members',$data);
                        $model_data['status'] = "true";
                        $model_data['message'] = "Added successfully";
                    }
                // }
                // else {

                //     $model_data['message'] = "Maximum users exceeded";
                //     $model_data['status'] = "false";    
                // }  
            }
        }

        return $model_data;
    }

    /* =============       To check the group status        ============== */
    public function check_group_status($group_id) {

        $model_data['status'] = "false";
        $where_cond = '(chat_group_id="'.$group_id.'" AND chat_group_status=1)';
        $output_data = $this->db->get_where('ct_chat_group',$where_cond)->num_rows();

        if($output_data == 1) {
            $model_data['status'] = "true";
        }

        return $model_data;
    }

    /* =============       To update a group        ============== */
    public function user_group_update($data,$group_id) {

        $model_data = $this->db->where('chat_group_id',$group_id)->update('ct_chat_group',$data);
        return TRUE;
    }

    /* =============       User friends list       ============== */
    public function user_friends_list($data) {

        $model_data = array();

        $where_cond = '((f.sender_id="'.$data['users_id'].'" OR f.receiver_id="'.$data['users_id'].'") AND f.friends_status=2)';
        
        $this->db->select('u.users_id as user_id,u.user_fullname,IFNULL(u.user_name,"") as user_name,IFNULL(u.user_email,"") as user_email,IFNULL(u.user_mobile,"") as user_mobile,IFNULL(u.user_profile_image,"") as user_profile_image,(CASE WHEN t.turfmates_id!="" THEN "yes" ELSE "no" END) as is_turfmate,s.profile_image_show');
        $this->db->from('ct_friends f');
        $this->db->join('ct_users u','(f.sender_id=u.users_id OR f.receiver_id=u.users_id) AND u.users_id!="'.$data['users_id'].'"','left');
        $this->db->join('ct_turfmates t','((t.sender_id=u.users_id AND t.receiver_id="'.$data['users_id'].'") OR (t.receiver_id=u.users_id AND t.sender_id="'.$data['users_id'].'")) AND t.like_status=2','left');
        $this->db->join('ct_user_settings s','u.users_id=s.users_id','left');
        if(!empty($data['chat_group_id'])) {
            $this->db->having('user_id NOT IN (select group_member_id from ct_group_members g where g.chat_group_id="'.$data['chat_group_id'].'" AND g.group_members_status=1)',NULL,false);
        }
        $this->db->where($where_cond);
        $model_data = $this->db->get()->result_array();

        return $model_data;
    }

    /* =============       To check the user is admin or not        ============== */
    public function check_admin_user($user_id,$group_id) {

        $model_data = $this->db->get_where('ct_group_members',array('chat_group_id'=>$group_id,'group_member_id'=>$user_id,'user_role'=>1,'group_members_status'=>1))->num_rows();
  
        return $model_data;
    }

    /* =============       To remove a user        ============== */
    public function user_remove($data) {

        $where_cond = '(chat_group_id="'.$data['chat_group_id'].'" AND group_member_id="'.$data['group_member_id'].'" AND group_members_status=1)';
        $model_data = $this->db->where($where_cond)->update('ct_group_members',array('left_by'=>$data['users_id'],'group_members_status'=>2));
        return TRUE;
    }

    /* =============       To make a user as admin        ============== */
    public function user_make_admin($data) {

        $where_cond = '(chat_group_id="'.$data['chat_group_id'].'" AND group_member_id="'.$data['group_member_id'].'" AND group_members_status=1)';
        $model_data = $this->db->where($where_cond)->update('ct_group_members',array('user_role'=>2));  
        return TRUE;
    }

    /* =============       To make a user as super admin        ============== */
    public function user_make_super_admin($data) {

        $where_cond_old_admin = '(chat_group_id="'.$data['chat_group_id'].'" AND group_member_id="'.$data['users_id'].'")';
        $where_cond_new_admin = '(chat_group_id="'.$data['chat_group_id'].'" AND group_member_id="'.$data['group_member_id'].'" AND group_members_status=1)';

        $model_data = $this->db->where($where_cond_old_admin)->update('ct_group_members',array('group_members_status'=>2));
        $model_data = $this->db->where($where_cond_new_admin)->update('ct_group_members',array('user_role'=>1));  

        return TRUE;
    }

    

    // /* ===============                Media details start       ================ */

    // // Group chat
    // public function user_groupchat_media($data) {

    //     $model_data['group_details'] = array();
    //     $model_data['media'] = array();

    //     $where_cond = '(g.chat_group_status=1 AND g.chat_group_id="'.$data['chat_group_id'].'")';
    //     $this->db->select('g.chat_group_id,g.chat_group_name,ug.user_fullname as admin_fullname,IFNULL(ug.user_name,"") as admin_username,g.chat_group_description,g.chat_group_image,g.chat_group_maximum,g.chat_group_updated_date,g.chat_group_created_date,gm.group_member_id,u.user_fullname,IFNULL(u.user_name,"") as user_name,u.user_profile_image,s.profile_image_show,gm.is_admin,gm.group_members_created_date');
    //     $this->db->from('ct_chat_group g');
    //     $this->db->join('ct_group_members gm','g.chat_group_id=gm.chat_group_id AND gm.group_members_status=1','left');
    //     $this->db->join('ct_users u','gm.group_member_id=u.users_id','left');
    //     $this->db->join('ct_users ug','gm.group_member_id=ug.users_id','left');
    //     $this->db->join('ct_user_settings s','gm.group_member_id=s.users_id','left');
    //     $this->db->where($where_cond);
    //     $model_data['group_details'] = $this->db->order_by('gm.group_members_created_date desc')->get()->result_array();

    //     $where_cond = '(gc.group_conversation_status=1 AND gc.conversation_group_id="'.$data['chat_group_id'].'" AND (gc.content_type="text" OR gc.content_type="video") AND gc.group_conversation_id NOT IN (select del.group_conversation_id from ct_deleted_history as del where del.users_id="'.$data['users_id'].'") AND gc.group_conversation_created_date >= (select gm.group_members_created_date from ct_group_members as gm where gm.group_member_id="'.$data['users_id'].'" AND gm.chat_group_id="'.$data['chat_group_id'].'"))';
    //     $this->db->select('gc.group_conversation_id,gc.conversation_from_id as user_id,gc.content,gc.content_type,gc.group_conversation_created_date,u.user_fullname,u.user_name');
    //     $this->db->from('ct_group_conversation gc');
    //     $this->db->join('ct_users u','gc.conversation_from_id=u.users_id','left');
    //     $this->db->where($where_cond);
    //     $model_data['media'] = $this->db->order_by('gc.group_conversation_id desc')->get()->result_array();

    //     return $model_data;
    // }

    // // Profile chat
    // public function user_profilechat_media($data) {

    //     $model_data['media'] = array();
    //     $model_data['joined_group'] = array();

    //     // media details        
    //     $where_cond = '(c.conversation_status=1 AND ((c.conversation_from_id="'.$data['users_id'].'" AND c.conversation_from_id_status!=4 AND c.conversation_to_id="'.$data['friend_id'].'") OR (c.conversation_to_id="'.$data['users_id'].'" AND c.conversation_to_id_status!=4 AND c.conversation_from_id="'.$data['friend_id'].'")) AND (c.content_type="image" OR c.content_type="text"))';
    //     $this->db->select('c.conversation_id,c.conversation_from_id as user_id,c.content ,c.content_type,c.conversation_created_date,u.user_fullname,u.user_name');
    //     $this->db->from('ct_conversation c');
    //     $this->db->join('ct_users u','c.conversation_from_id=u.users_id','left');
    //     $this->db->where($where_cond);
    //     $model_data['media'] = $this->db->order_by('c.conversation_id desc')->get()->result_array();

    //     // group list
    //     $where_cond = '(gm.group_member_id="'.$data['friend_id'].'" AND gm.group_members_status=1)';
    //     $this->db->select('g.chat_group_name,g.chat_group_description,g.chat_group_image');
    //     $this->db->from('ct_group_members gm');
    //     $this->db->join('ct_chat_group g','gm.chat_group_id=g.chat_group_id AND g.chat_group_status=1','inner');
    //     $this->db->where($where_cond);
    //     $model_data['joined_group'] = $this->db->order_by('gm.group_members_created_date desc')->get()->result_array();

    //     return $model_data;
    // }

    // // Turfmate chat
    // public function user_turfmatechat_media($data) {

    //     $model_data['media'] = array();

    //     // media details        
    //     $where_cond = '(c.conversation_status=1 AND ((c.conversation_from_id="'.$data['users_id'].'" AND c.conversation_from_id_status!=4 AND c.conversation_to_id="'.$data['friend_id'].'") OR (c.conversation_to_id="'.$data['users_id'].'" AND c.conversation_to_id_status!=4 AND c.conversation_from_id="'.$data['friend_id'].'")) AND (c.content_type="image" OR c.content_type="text"))';
    //     $this->db->select('c.conversation_id,c.conversation_from_id as user_id,c.content ,c.content_type,c.conversation_created_date,u.user_fullname,u.user_name');
    //     $this->db->from('ct_turfmate_conversation c');
    //     $this->db->join('ct_users u','c.conversation_from_id=u.users_id','left');
    //     $this->db->where($where_cond);
    //     $model_data['media'] = $this->db->order_by('c.conversation_id desc')->get()->result_array();

    //     return $model_data;
    // }
    
    // /* ===============                Media details end       ================ */

    // /* ===============                Conversation list start       ================ */

    // // Profile chat
    // public function user_profileconversation_list($data) {

    //     $model_data = array();
     
    //     $where_cond = '(c.conversation_status=1 AND ((c.conversation_from_id="'.$data['users_id'].'" AND c.conversation_from_id_status!=4) OR (c.conversation_to_id="'.$data['users_id'].'" AND c.conversation_to_id_status!=4)) AND c.conversation_status=1 AND c1.conversation_id is NULL)';
    //     $this->db->select('c.conversation_from_id,u.users_id as user_id,u.user_fullname,u.user_name,u.user_profile_image,c.conversation_id,c.content,c.content_type,c.conversation_created_date,s.profile_image_show,(SELECT count(*) FROM ct_conversation as c1 WHERE c1.conversation_from_id=u.users_id AND c1.conversation_from_id_status=2) as unseen_count');
    //     $this->db->from('ct_conversation c');
    //     $this->db->join('ct_conversation c1','c.conversation_id<c1.conversation_id AND ((c.conversation_from_id=c1.conversation_from_id AND c.conversation_to_id=c1.conversation_to_id) OR (c.conversation_from_id=c1.conversation_to_id AND c.conversation_to_id=c1.conversation_from_id))','left');
    //     $this->db->join('ct_users u','((c.conversation_from_id=u.users_id AND c.conversation_to_id="'.$data['users_id'].'") OR (c.conversation_to_id=u.users_id AND c.conversation_from_id="'.$data['users_id'].'"))','left');
    //     $this->db->join('ct_user_settings s','((c.conversation_from_id=s.users_id AND c.conversation_to_id="'.$data['users_id'].'") OR (c.conversation_to_id=s.users_id AND c.conversation_from_id="'.$data['users_id'].'"))','left');
    //     $this->db->having('user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$data['users_id'].' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$data['users_id'].' OR blocklist_to_id = '.$data['users_id'].')', NULL, FALSE);
    //     $this->db->where($where_cond);
    //     $this->db->order_by('conversation_id desc');
    //     $model_data = $this->db->get()->result_array();

    //     return $model_data;
    // }

    // // Group chat
    // public function user_groupconversation_list($data) {

    //     $model_data = array();
     
    //     $where_cond = '(gm.group_member_id="'.$data['users_id'].'" AND gm.group_members_status=1)';
    //     $this->db->select('g.chat_group_id,g.chat_group_name,g.chat_group_description,g.chat_group_image,(select count(*) from ct_group_conversation as gc where gc.group_conversation_id > (select (CASE WHEN gcs.conversation_id!="" THEN gcs.conversation_id ELSE count(*) END) as last_message_id from ct_group_conversation_seen as gcs where gcs.users_id="'.$data['users_id'].'" AND gcs.chat_group_id=g.chat_group_id) AND gc.conversation_group_id=g.chat_group_id AND group_conversation_status=1) as unseen_count');
    //     $this->db->from('ct_group_members gm');
    //     $this->db->join('ct_chat_group g','gm.chat_group_id=g.chat_group_id AND g.chat_group_status=1','inner');
    //     $this->db->where($where_cond);
    //     $model_data = $this->db->get()->result_array();

    //     return $model_data;
    // }

    // // Turfmate chat
    // public function user_turfmateconversation_list($data) {

    //     $model_data = array();
     
    //     $where_cond = '(c.conversation_status=1 AND ((c.conversation_from_id="'.$data['users_id'].'" AND c.conversation_from_id_status!=4) OR (c.conversation_to_id="'.$data['users_id'].'" AND c.conversation_to_id_status!=4)) AND c.conversation_status=1 AND c1.conversation_id is NULL)';
    //     $this->db->select('c.conversation_from_id,u.users_id as user_id,u.user_fullname,u.user_name,u.user_profile_image,c.conversation_id,c.content,c.content_type,c.conversation_created_date,s.profile_image_show,(SELECT count(*) FROM ct_turfmate_conversation as c1 WHERE c1.conversation_from_id=u.users_id AND c1.conversation_from_id_status=2) as unseen_count');
    //     $this->db->from('ct_turfmate_conversation c');
    //     $this->db->join('ct_turfmate_conversation c1','c.conversation_id<c1.conversation_id AND ((c.conversation_from_id=c1.conversation_from_id AND c.conversation_to_id=c1.conversation_to_id) OR (c.conversation_from_id=c1.conversation_to_id AND c.conversation_to_id=c1.conversation_from_id))','left');
    //     $this->db->join('ct_users u','((c.conversation_from_id=u.users_id AND c.conversation_to_id="'.$data['users_id'].'") OR (c.conversation_to_id=u.users_id AND c.conversation_from_id="'.$data['users_id'].'"))','left');
    //     $this->db->join('ct_user_settings s','((c.conversation_from_id=s.users_id AND c.conversation_to_id="'.$data['users_id'].'") OR (c.conversation_to_id=s.users_id AND c.conversation_from_id="'.$data['users_id'].'"))','left');
    //     $this->db->having('user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$data['users_id'].' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$data['users_id'].' OR blocklist_to_id = '.$data['users_id'].')', NULL, FALSE);
    //     $this->db->where($where_cond);
    //     $this->db->order_by('conversation_id desc');
    //     $model_data = $this->db->get()->result_array();

    //     return $model_data;
    // }

    // /* ===============                Conversation list end       ================ */   

    // /* ===============                Conversation history start       ================ */   

    // // Group chat
    // public function user_groupconversation_history($data,$start,$limit) {

    //     $model_data = array();

    //     $where_cond = '(gc.conversation_group_id="'.$data['chat_group_id'].'" AND gc.   group_conversation_status=1 AND gc.group_conversation_id NOT IN (select del.group_conversation_id from ct_deleted_history as del where del.users_id="'.$data['users_id'].'") AND gc.group_conversation_created_date >= (select gm.group_members_created_date from ct_group_members as gm where gm.group_member_id="'.$data['users_id'].'" AND gm.chat_group_id="'.$data['chat_group_id'].'"))';        
    //     $this->db->select('u.users_id as user_id,u.user_fullname,u.user_name,gc.group_conversation_id,gc. conversation_group_id,gc.content,gc.content_type,gc.group_conversation_created_date');
    //     $this->db->from('ct_group_conversation gc');
    //     $this->db->join('ct_users u','gc.conversation_from_id=u.users_id','left');
    //     $this->db->where($where_cond);
    //     $model_data = $this->db->order_by('gc.group_conversation_id desc')->limit($limit,$start)->get()->result_array();

    //     return $model_data;
    // }

    // // Profile chat
    // public function user_profileconversation_history($data,$start,$limit) {

    //     $model_data = array();

    //     $where_cond = '(c.conversation_status=1 AND ((c.conversation_from_id="'.$data['users_id'].'" AND c.conversation_from_id_status!=4 AND c.conversation_to_id="'.$data['friend_id'].'") OR (c.conversation_to_id="'.$data['users_id'].'" AND c.conversation_to_id_status!=4 AND c.conversation_from_id="'.$data['friend_id'].'")))';
    //     $this->db->select('u.users_id as user_id,u.user_fullname,u.user_name,c.conversation_id,,c.content ,c.content_type,c.conversation_from_id_status as message_status,c.conversation_created_date');
    //     $this->db->from('ct_conversation c');
    //     $this->db->join('ct_users u','c.conversation_from_id=u.users_id','left');
    //     $this->db->where($where_cond);
    //     $model_data = $this->db->order_by('c.conversation_id desc')->limit($limit,$start)->get()->result_array();

    //     return $model_data;
    // }

    // // Turfmate chat
    // public function user_turfmateconversation_history($data,$start,$limit) {

    //     $model_data = array();

    //     $where_cond = '(tc.conversation_status=1 AND ((tc.conversation_from_id="'.$data['users_id'].'" AND tc.conversation_from_id_status!=4 AND tc.conversation_to_id="'.$data['friend_id'].'") OR (tc.conversation_to_id="'.$data['users_id'].'" AND tc.conversation_to_id_status!=4 AND tc.conversation_from_id="'.$data['friend_id'].'")))';
    //     $this->db->select('u.users_id as user_id,u.user_fullname,u.user_name,tc.conversation_id,,tc.content ,tc.content_type,tc.conversation_from_id_status as message_status,tc.conversation_created_date');
    //     $this->db->from('ct_turfmate_conversation tc');
    //     $this->db->join('ct_users u','tc.conversation_from_id=u.users_id','left');
    //     $this->db->where($where_cond);
    //     $model_data = $this->db->order_by('tc.conversation_id desc')->limit($limit,$start)->get()->result_array();

    //     return $model_data;
    // }

    // // Local chat
    // public function user_localconversation_history($data,$start,$limit) {

    //     $model_data = array();

    //     $where_cond = '(lc.local_conversation_status=1 AND FIND_IN_SET("'.$data['users_id'].'",lc.conversation_users) !=0)';
    //     $this->db->select('u.users_id as user_id,(CASE WHEN lc.is_anonymous=1 THEN "" ELSE u.user_fullname END) as user_fullname,(CASE WHEN lc.is_anonymous=1 THEN "" ELSE u.user_name END) as user_name,lc.local_conversation_id,lc.content,lc.content_type,lc.local_conversation_created_date');
    //     $this->db->from('ct_local_conversation lc');
    //     $this->db->join('ct_users u','lc.local_conversation_from_id=u.users_id','left');
    //     $this->db->where($where_cond);
    //     $model_data = $this->db->order_by('lc.local_conversation_id desc')->limit($limit,$start)->get()->result_array();

    //     return $model_data;
    // }

    

    /* ===============                Conversation history end       ================ */

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

    /* =============       Local chat notification        ============== */
    public function local_chat_user_details($user_ids,$logged_in_userid) {

        $where_cond = '(logs_login_status=1 AND users_id IN ('.implode(',', $user_ids).'))';
    
        $this->db->select('l.logs_device_type,l.logs_device_token,l.users_id,(CASE WHEN f.friends_status=2 THEN "true" ELSE "false" END) as friends_status');
        $this->db->from('ct_user_logs l');
        $this->db->join('ct_friends f','(l.users_id=f.receiver_id AND f.sender_id="'.$logged_in_userid.'") OR (l.users_id=f.sender_id AND f.receiver_id="'.$logged_in_userid.'")','left');
        $this->db->where($where_cond);
        $user_device_details = $this->db->get()->result_array();
        
        return $user_device_details;
    }

    /* =============       User joined group list        ============== */
    public function user_joined_grouplist($data) {

        $where_cond = '(gm.group_member_id="'.$data['users_id'].'" AND gm.group_members_status=1)';
        $this->db->select('u.user_fullname,u.user_name,gm.user_role,g.chat_group_id,g.chat_group_admin_id,g.chat_group_name,g.chat_group_theme,g.chat_group_status,g.chat_group_updated_date,g.chat_group_created_date');
        $this->db->from('ct_group_members gm');
        $this->db->join('ct_chat_group g','gm.chat_group_id=g.chat_group_id AND g.chat_group_status=1','inner');
        $this->db->join('ct_users u','g.chat_group_admin_id=u.users_id','inner');
        $model_data = $this->db->where($where_cond)->get()->result_array();

        return $model_data;
    }

    /* =============       User local chat conversation       ============== */
    public function user_local_chat_conversation($data,$start,$limit) {

        $model_data = array();

        $where_cond = '(lc.local_conversation_status=1 AND FIND_IN_SET("'.$data['users_id'].'",lc.conversation_users) !=0)';
        $this->db->select('u.users_id as user_id,(CASE WHEN lc.is_anonymous=1 THEN "" ELSE u.user_fullname END) as user_name,(CASE WHEN lc.is_anonymous=1 THEN "" ELSE u.user_profile_image END) as user_profile_image,s.profile_image_show,lc.local_conversation_id,lc.content,lc.content_type,lc.local_conversation_created_date,(CASE WHEN f.friends_status=2 THEN "true" ELSE "false" END) as friends_status');
        $this->db->from('ct_local_conversation lc');
        $this->db->join('ct_users u','lc.local_conversation_from_id=u.users_id','left');
        $this->db->join('ct_user_settings s','u.users_id=s.users_id','left');
        $this->db->join('ct_friends f','(u.users_id=f.receiver_id AND f.sender_id="'.$data['users_id'].'") OR (u.users_id=f.sender_id AND f.receiver_id="'.$data['users_id'].'")','left');
        $this->db->where($where_cond);
        $message_data = $this->db->order_by('lc.local_conversation_id desc');
        $tempdb = clone $this->db;
        $model_data['total_count'] = $tempdb->get()->num_rows();
        $model_data['data'] = $message_data->limit($limit,$start)->get()->result_array();

        return $model_data;
    }

    /* =============       Insert local messages       ============== */
    public function insert_local_message($data) {

        $radius = $this->db->select('user_radius')->get_where('ct_user_settings',array('users_id'=>$data['local_conversation_from_id']))->row_array();
        $distance = $radius['user_radius'];
        $latitude = $data['conversation_lattitude'];
        $longitude = $data['conversation_longitude'];;

        $nearby_users = "SELECT users_id, 3956 * 2 * ASIN(SQRT( POWER(SIN(($latitude - user_lattitude) * pi()/180 / 2), 2) + COS($latitude * pi()/180) * COS(user_lattitude * pi()/180) *POWER(SIN(($longitude - user_longitude) * pi()/180 / 2), 2) )) as distance FROM ct_users WHERE users_id!=".$data['local_conversation_from_id']." HAVING distance <= $distance AND users_id NOT IN (select (CASE WHEN blocklist_from_id!=".$data['local_conversation_from_id']." THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = ".$data['local_conversation_from_id']." OR blocklist_to_id = ".$data['local_conversation_from_id'].")";
        $nearby_users_list = $this->db->query($nearby_users)->result_array();

        $user_ids = (!empty($nearby_users_list)) ? array_column($nearby_users_list,'users_id') : array();
        $conversation_users_ids = $user_ids;
        $conversation_users_ids[] = $data['local_conversation_from_id'];;
        $data['conversation_users'] = implode(',', $conversation_users_ids);

        $insert_message = $this->db->insert('ct_local_conversation',$data);

        if($insert_message) {

            $model_data['status'] = "true"; 
            $model_data['user_ids'] = $user_ids;
            $model_data['insert_id'] = $this->db->insert_id();
        }
        else {
            $model_data['status'] = "false"; 
        }

        return $model_data;
    }

    /* =============       User profile restriction       ============== */
    public function user_profile_restriction($user_id) {

        $model_data = $this->db->select('profile_image_show')->get_where('ct_user_settings',array('users_id'=>$user_id))->row_array();
        $profile_image_status = (!empty($model_data['profile_image_show'])) ? $model_data['profile_image_show'] : '1';
        return $profile_image_status;
    }

    /* =============       User profile data       ============== */
    public function user_profile_data($user_id) {

        $this->db->select('u.user_profile_image,s.profile_image_show');
        $this->db->from('ct_users u');
        $this->db->join('ct_user_settings s','u.users_id=s.users_id','inner');
        $this->db->where('u.users_id',$user_id);
        $model_data = $this->db->get()->row_array();
        
        return $model_data;
    }

    /* =============       Group details by group id       ============== */
    public function user_group_details($data) {

        $model_data = array();
    
        // Group details    
        $group_details = $this->db->select('chat_group_id,chat_group_name,IFNULL(chat_group_theme,"") as chat_group_theme,chat_group_updated_date,chat_group_created_date')->get_where('ct_chat_group',array('chat_group_id'=>$data['chat_group_id'],'chat_group_status'=>1))->row_array();
        
        // Member details
        if(!empty($group_details)) {

            $where_cond = '(gm.chat_group_id="'.$data['chat_group_id'].'" AND gm.group_members_status=1)';
    
            $this->db->select('gm.group_members_id,gm.group_member_id as users_id,gm.user_role,gm.group_members_created_date,u.user_fullname,u.user_name,u.user_profile_image,s.profile_image_show,(CASE WHEN f.friends_status=2 THEN "true" ELSE "false" END) as friends_status');
            $this->db->from('ct_group_members gm');
            $this->db->join('ct_users u','gm.group_member_id=u.users_id','inner');
            $this->db->join('ct_user_settings s','gm.group_member_id=s.users_id','inner');
            $this->db->join('ct_friends f','(gm.group_member_id=f.receiver_id AND f.sender_id="'.$data['users_id'].'") OR (gm.group_member_id=f.sender_id AND f.receiver_id="'.$data['users_id'].'")','left');
            $members_details = $this->db->where($where_cond)->get()->result_array();

            if(!empty($members_details)) {

                $model_data = $group_details;
                $model_data['members'] = $members_details;
            }
        }
        
        return $model_data;
    }

    /* =============       Delete group by admin if he is only admin       ============== */
    public function user_delete_group($data) {

        
        $update_data = $this->db->where('chat_group_id',$data['chat_group_id'])->update('ct_chat_group',array('chat_group_status'=>3));
        
        return TRUE;
    }

    /* =============       Media restriction in local chat       ============== */
    public function chat_media_restriction($data) {

        $user_sub_data = $this->db->get_where('ct_subscription_activation',array('users_id'=>$data['users_id'],'subscription_type'=>2,'subscription_status'=>1))->row_array();
        
        if(!empty($user_sub_data)) {

            $model_data['status'] = "true";
            $model_data['count'] = "unlimited";          
        }
        else {

            $user_action_count = 0;

            $user_record = $this->db->select('user_multimedia_date,user_multimedia_post,user_multimedia_total')->get_where('ct_user_credits',array('users_id'=>$data['users_id']))->row_array();
         
            if(date('Y-m-d') == date('Y-m-d',strtotime($user_record['user_multimedia_date']))) {

                if($user_record['user_multimedia_post'] > 0) {

                    $user_action_count = $user_record['user_multimedia_post'];
                    $update_user_data = $this->db->where('users_id',$data['users_id'])->set('user_multimedia_post','user_multimedia_post-1',FALSE)->update('ct_user_credits');
                }
            }
            else {

                $user_action_count = $user_record['user_multimedia_total'];
                $set_data = array('user_multimedia_date'=>date('Y-m-d H:i:s'));
                $update_user_data = $this->db->where('users_id',$data['users_id'])->set('user_multimedia_post','user_multimedia_total-1',FALSE)->set($set_data)->update('ct_user_credits');
            }
            
            $model_data['status'] = ($user_action_count > 0) ? "true" : "false";
            $model_data['count'] = $user_action_count;
        }

        return $model_data;
    }

    /* =============       Multimedia post       ============== */
    public function user_multimedia_post($data) {

        $user_sub_data = $this->db->get_where('ct_subscription_activation',array('users_id'=>$data['users_id'],'subscription_type'=>2,'subscription_status'=>1))->row_array();
        
        if(!empty($user_sub_data)) {

            $model_data['count'] = "unlimited";          
        }
        else {

            $user_record = $this->db->select('user_multimedia_date,user_multimedia_post,user_multimedia_total')->get_where('ct_user_credits',array('users_id'=>$data['users_id']))->row_array();
         
            if(date('Y-m-d') == date('Y-m-d',strtotime($user_record['user_multimedia_date']))) {

                $model_data['count'] = $user_record['user_multimedia_post'];
            }
            else {

                $model_data['count'] = $user_record['user_multimedia_total'];
            }
        }

        return $model_data;
    }


} // End chat model