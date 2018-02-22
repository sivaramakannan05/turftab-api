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

    /* =============       To check the user is admin or not        ============== */
    public function check_admin_user($user_id,$group_id) {

        $model_data = $this->db->get_where('ct_group_members',array('chat_group_id'=>$group_id,'group_member_id'=>$user_id,'is_admin'=>1,'group_members_status'=>1))->num_rows();
  
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

                $model_data['message'] = "The group is inactive";
                $model_data['status'] = "false";
            }
            else {

                $group_strength = $this->db->get_where('ct_group_members',array('chat_group_id'=>$group_id,'group_members_status'=>1))->num_rows();

                if($group_data['chat_group_maximum'] >= ($group_strength+count($data))) {

                    $members_ids = array_column($data, 'group_member_id');
                    $where_cond = '(group_member_id IN ('.implode(',', $members_ids).') AND group_members_status=1)';
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
                }
                else {

                    $model_data['message'] = "Maximum users exceeded";
                    $model_data['status'] = "false";    
                }  
            }
        }

        return $model_data;
    }

    /* =============       To make a user as admin        ============== */
    public function user_make_admin($data) {

        $where_cond = '(chat_group_id="'.$data['chat_group_id'].'" AND group_member_id="'.$data['group_member_id'].'" AND group_members_status=1)';
        $model_data = $this->db->where($where_cond)->update('ct_group_members',array('is_admin'=>1));  
        return TRUE;
    }

    /* =============       To remove a user        ============== */
    public function user_remove($data) {

        $where_cond = '(chat_group_id="'.$data['chat_group_id'].'" AND group_member_id="'.$data['group_member_id'].'" AND group_members_status=1)';
        $model_data = $this->db->where($where_cond)->update('ct_group_members',array('left_by'=>$data['users_id'],'group_members_status'=>2));
        return TRUE;
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

    /* =============       To check the group status        ============== */
    public function check_group_status($group_id) {

        $model_data['status'] = "false";
        $where_cond = '(chat_group_id="'.$group_id.'" AND chat_group_status=1))';
        $output_data = $this->db->select('*')->get_where('ct_chat_group',$where_cond)->row_array();

        if(!empty($output_data)) {
            $model_data['status'] = "true";
        }

        return $model_data;
    }

    /* ===============                Media details start       ================ */

    // Group chat
    public function user_groupchat_media($data) {

        $model_data['group_details'] = array();
        $model_data['media'] = array();

        $where_cond = '(g.chat_group_status=1 AND g.chat_group_id="'.$data['chat_group_id'].'")';
        $this->db->select('g.chat_group_id,g.chat_group_name,ug.user_fullname as admin_fullname,IFNULL(ug.user_name,"") as admin_username,g.chat_group_description,g.chat_group_image,g.chat_group_maximum,g.chat_group_updated_date,g.chat_group_created_date,gm.group_member_id,u.user_fullname,IFNULL(u.user_name,"") as user_name,u.user_profile_image,s.profile_image_show,gm.is_admin,gm.group_members_created_date');
        $this->db->from('ct_chat_group g');
        $this->db->join('ct_group_members gm','g.chat_group_id=gm.chat_group_id AND gm.group_members_status=1','left');
        $this->db->join('ct_users u','gm.group_member_id=u.users_id','left');
        $this->db->join('ct_users ug','gm.group_member_id=ug.users_id','left');
        $this->db->join('ct_user_settings s','gm.group_member_id=s.users_id','left');
        $this->db->where($where_cond);
        $model_data['group_details'] = $this->db->order_by('gm.group_members_created_date desc')->get()->result_array();

        $where_cond = '(gc.group_conversation_status=1 AND gc.conversation_group_id="'.$data['chat_group_id'].'" AND (gc.content_type="text" OR gc.content_type="video") AND gc.group_conversation_id NOT IN (select del.group_conversation_id from ct_deleted_history as del where del.users_id="'.$data['users_id'].'") AND gc.group_conversation_created_date >= (select gm.group_members_created_date from ct_group_members as gm where gm.group_member_id="'.$data['users_id'].'" AND gm.chat_group_id="'.$data['chat_group_id'].'"))';
        $this->db->select('gc.group_conversation_id,gc.conversation_from_id as user_id,gc.content,gc.content_type,gc.group_conversation_created_date,u.user_fullname,u.user_name');
        $this->db->from('ct_group_conversation gc');
        $this->db->join('ct_users u','gc.conversation_from_id=u.users_id','left');
        $this->db->where($where_cond);
        $model_data['media'] = $this->db->order_by('gc.group_conversation_id desc')->get()->result_array();

        return $model_data;
    }

    // Profile chat
    public function user_profilechat_media($data) {

        $model_data['media'] = array();
        $model_data['joined_group'] = array();

        // media details        
        $where_cond = '(c.conversation_status=1 AND ((c.conversation_from_id="'.$data['users_id'].'" AND c.conversation_from_id_status!=4 AND c.conversation_to_id="'.$data['friend_id'].'") OR (c.conversation_to_id="'.$data['users_id'].'" AND c.conversation_to_id_status!=4 AND c.conversation_from_id="'.$data['friend_id'].'")) AND (c.content_type="image" OR c.content_type="text"))';
        $this->db->select('c.conversation_id,c.conversation_from_id as user_id,c.content ,c.content_type,c.conversation_created_date,u.user_fullname,u.user_name');
        $this->db->from('ct_conversation c');
        $this->db->join('ct_users u','c.conversation_from_id=u.users_id','left');
        $this->db->where($where_cond);
        $model_data['media'] = $this->db->order_by('c.conversation_id desc')->get()->result_array();

        // group list
        $where_cond = '(gm.group_member_id="'.$data['friend_id'].'" AND gm.group_members_status=1)';
        $this->db->select('g.chat_group_name,g.chat_group_description,g.chat_group_image');
        $this->db->from('ct_group_members gm');
        $this->db->join('ct_chat_group g','gm.chat_group_id=g.chat_group_id AND g.chat_group_status=1','inner');
        $this->db->where($where_cond);
        $model_data['joined_group'] = $this->db->order_by('gm.group_members_created_date desc')->get()->result_array();

        return $model_data;
    }

    // Turfmate chat
    public function user_turfmatechat_media($data) {

        $model_data['media'] = array();

        // media details        
        $where_cond = '(c.conversation_status=1 AND ((c.conversation_from_id="'.$data['users_id'].'" AND c.conversation_from_id_status!=4 AND c.conversation_to_id="'.$data['friend_id'].'") OR (c.conversation_to_id="'.$data['users_id'].'" AND c.conversation_to_id_status!=4 AND c.conversation_from_id="'.$data['friend_id'].'")) AND (c.content_type="image" OR c.content_type="text"))';
        $this->db->select('c.conversation_id,c.conversation_from_id as user_id,c.content ,c.content_type,c.conversation_created_date,u.user_fullname,u.user_name');
        $this->db->from('ct_turfmate_conversation c');
        $this->db->join('ct_users u','c.conversation_from_id=u.users_id','left');
        $this->db->where($where_cond);
        $model_data['media'] = $this->db->order_by('c.conversation_id desc')->get()->result_array();

        return $model_data;
    }
    
    /* ===============                Media details end       ================ */

    /* ===============                Conversation list start       ================ */

    // Profile chat
    public function user_profileconversation_list($data) {

        $model_data = array();
     
        $where_cond = '(c.conversation_status=1 AND ((c.conversation_from_id="'.$data['users_id'].'" AND c.conversation_from_id_status!=4) OR (c.conversation_to_id="'.$data['users_id'].'" AND c.conversation_to_id_status!=4)) AND c.conversation_status=1 AND c1.conversation_id is NULL)';
        $this->db->select('c.conversation_from_id,u.users_id as user_id,u.user_fullname,u.user_name,u.user_profile_image,c.conversation_id,c.content,c.content_type,c.conversation_created_date,s.profile_image_show,(SELECT count(*) FROM ct_conversation as c1 WHERE c1.conversation_from_id=u.users_id AND c1.conversation_from_id_status=2) as unseen_count');
        $this->db->from('ct_conversation c');
        $this->db->join('ct_conversation c1','c.conversation_id<c1.conversation_id AND ((c.conversation_from_id=c1.conversation_from_id AND c.conversation_to_id=c1.conversation_to_id) OR (c.conversation_from_id=c1.conversation_to_id AND c.conversation_to_id=c1.conversation_from_id))','left');
        $this->db->join('ct_users u','((c.conversation_from_id=u.users_id AND c.conversation_to_id="'.$data['users_id'].'") OR (c.conversation_to_id=u.users_id AND c.conversation_from_id="'.$data['users_id'].'"))','left');
        $this->db->join('ct_user_settings s','((c.conversation_from_id=s.users_id AND c.conversation_to_id="'.$data['users_id'].'") OR (c.conversation_to_id=s.users_id AND c.conversation_from_id="'.$data['users_id'].'"))','left');
        $this->db->having('user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$data['users_id'].' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$data['users_id'].' OR blocklist_to_id = '.$data['users_id'].')', NULL, FALSE);
        $this->db->where($where_cond);
        $this->db->order_by('conversation_id desc');
        $model_data = $this->db->get()->result_array();

        return $model_data;
    }

    // Group chat
    public function user_groupconversation_list($data) {

        $model_data = array();
     
        $where_cond = '(gm.group_member_id="'.$data['users_id'].'" AND gm.group_members_status=1)';
        $this->db->select('g.chat_group_id,g.chat_group_name,g.chat_group_description,g.chat_group_image,(select count(*) from ct_group_conversation as gc where gc.group_conversation_id > (select (CASE WHEN gcs.conversation_id!="" THEN gcs.conversation_id ELSE count(*) END) as last_message_id from ct_group_conversation_seen as gcs where gcs.users_id="'.$data['users_id'].'" AND gcs.chat_group_id=g.chat_group_id) AND gc.conversation_group_id=g.chat_group_id AND group_conversation_status=1) as unseen_count');
        $this->db->from('ct_group_members gm');
        $this->db->join('ct_chat_group g','gm.chat_group_id=g.chat_group_id AND g.chat_group_status=1','inner');
        $this->db->where($where_cond);
        $model_data = $this->db->get()->result_array();

        return $model_data;
    }

    // Turfmate chat
    public function user_turfmateconversation_list($data) {

        $model_data = array();
     
        $where_cond = '(c.conversation_status=1 AND ((c.conversation_from_id="'.$data['users_id'].'" AND c.conversation_from_id_status!=4) OR (c.conversation_to_id="'.$data['users_id'].'" AND c.conversation_to_id_status!=4)) AND c.conversation_status=1 AND c1.conversation_id is NULL)';
        $this->db->select('c.conversation_from_id,u.users_id as user_id,u.user_fullname,u.user_name,u.user_profile_image,c.conversation_id,c.content,c.content_type,c.conversation_created_date,s.profile_image_show,(SELECT count(*) FROM ct_turfmate_conversation as c1 WHERE c1.conversation_from_id=u.users_id AND c1.conversation_from_id_status=2) as unseen_count');
        $this->db->from('ct_turfmate_conversation c');
        $this->db->join('ct_turfmate_conversation c1','c.conversation_id<c1.conversation_id AND ((c.conversation_from_id=c1.conversation_from_id AND c.conversation_to_id=c1.conversation_to_id) OR (c.conversation_from_id=c1.conversation_to_id AND c.conversation_to_id=c1.conversation_from_id))','left');
        $this->db->join('ct_users u','((c.conversation_from_id=u.users_id AND c.conversation_to_id="'.$data['users_id'].'") OR (c.conversation_to_id=u.users_id AND c.conversation_from_id="'.$data['users_id'].'"))','left');
        $this->db->join('ct_user_settings s','((c.conversation_from_id=s.users_id AND c.conversation_to_id="'.$data['users_id'].'") OR (c.conversation_to_id=s.users_id AND c.conversation_from_id="'.$data['users_id'].'"))','left');
        $this->db->having('user_id NOT IN (select (CASE WHEN blocklist_from_id!='.$data['users_id'].' THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_blocklist where blocklist_from_id = '.$data['users_id'].' OR blocklist_to_id = '.$data['users_id'].')', NULL, FALSE);
        $this->db->where($where_cond);
        $this->db->order_by('conversation_id desc');
        $model_data = $this->db->get()->result_array();

        return $model_data;
    }

    /* ===============                Conversation list end       ================ */   

    /* ===============                Conversation history start       ================ */   

    // Group chat
    public function user_groupconversation_history($data,$start,$limit) {

        $model_data = array();

        $where_cond = '(gc.conversation_group_id="'.$data['chat_group_id'].'" AND gc.   group_conversation_status=1 AND gc.group_conversation_id NOT IN (select del.group_conversation_id from ct_deleted_history as del where del.users_id="'.$data['users_id'].'") AND gc.group_conversation_created_date >= (select gm.group_members_created_date from ct_group_members as gm where gm.group_member_id="'.$data['users_id'].'" AND gm.chat_group_id="'.$data['chat_group_id'].'"))';        
        $this->db->select('u.users_id as user_id,u.user_fullname,u.user_name,gc.group_conversation_id,gc. conversation_group_id,gc.content,gc.content_type,gc.group_conversation_created_date');
        $this->db->from('ct_group_conversation gc');
        $this->db->join('ct_users u','gc.conversation_from_id=u.users_id','left');
        $this->db->where($where_cond);
        $model_data = $this->db->order_by('gc.group_conversation_id desc')->limit($limit,$start)->get()->result_array();

        return $model_data;
    }

    // Profile chat
    public function user_profileconversation_history($data,$start,$limit) {

        $model_data = array();

        $where_cond = '(c.conversation_status=1 AND ((c.conversation_from_id="'.$data['users_id'].'" AND c.conversation_from_id_status!=4 AND c.conversation_to_id="'.$data['friend_id'].'") OR (c.conversation_to_id="'.$data['users_id'].'" AND c.conversation_to_id_status!=4 AND c.conversation_from_id="'.$data['friend_id'].'")))';
        $this->db->select('u.users_id as user_id,u.user_fullname,u.user_name,c.conversation_id,,c.content ,c.content_type,c.conversation_from_id_status as message_status,c.conversation_created_date');
        $this->db->from('ct_conversation c');
        $this->db->join('ct_users u','c.conversation_from_id=u.users_id','left');
        $this->db->where($where_cond);
        $model_data = $this->db->order_by('c.conversation_id desc')->limit($limit,$start)->get()->result_array();

        return $model_data;
    }

    // Turfmate chat
    public function user_turfmateconversation_history($data,$start,$limit) {

        $model_data = array();

        $where_cond = '(tc.conversation_status=1 AND ((tc.conversation_from_id="'.$data['users_id'].'" AND tc.conversation_from_id_status!=4 AND tc.conversation_to_id="'.$data['friend_id'].'") OR (tc.conversation_to_id="'.$data['users_id'].'" AND tc.conversation_to_id_status!=4 AND tc.conversation_from_id="'.$data['friend_id'].'")))';
        $this->db->select('u.users_id as user_id,u.user_fullname,u.user_name,tc.conversation_id,,tc.content ,tc.content_type,tc.conversation_from_id_status as message_status,tc.conversation_created_date');
        $this->db->from('ct_turfmate_conversation tc');
        $this->db->join('ct_users u','tc.conversation_from_id=u.users_id','left');
        $this->db->where($where_cond);
        $model_data = $this->db->order_by('tc.conversation_id desc')->limit($limit,$start)->get()->result_array();

        return $model_data;
    }

    // Local chat
    public function user_localconversation_history($data,$start,$limit) {

        $model_data = array();

        $where_cond = '(lc.local_conversation_status=1 AND FIND_IN_SET("'.$data['users_id'].'",lc.conversation_users) !=0)';
        $this->db->select('u.users_id as user_id,(CASE WHEN lc.is_anonymous=1 THEN "" ELSE u.user_fullname END) as user_fullname,(CASE WHEN lc.is_anonymous=1 THEN "" ELSE u.user_name END) as user_name,lc.local_conversation_id,lc.content,lc.content_type,lc.local_conversation_created_date');
        $this->db->from('ct_local_conversation lc');
        $this->db->join('ct_users u','lc.local_conversation_from_id=u.users_id','left');
        $this->db->where($where_cond);
        $model_data = $this->db->order_by('lc.local_conversation_id desc')->limit($limit,$start)->get()->result_array();

        return $model_data;
    }

    

    /* ===============                Conversation history end       ================ */





   
    

} // End chat model
