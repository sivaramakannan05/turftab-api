<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Turfmate_model extends CI_Model {

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
        $blocked_count = $this->db->get_where('ct_turfmate_blocklist',$where_cond)->num_rows();
        return $blocked_count;
    }

    /* =============       Turfmate questions list        ============== */
    public function user_questions_list($data) {
 
        $this->db->select('q.questions_id,q.question,q.options,tm.answer_id');
        $this->db->from('ct_questions q');
        $this->db->join('ct_turfmates_matching tm','q.questions_id=tm.question_id AND tm.users_id='.$data["users_id"].'','left');
        $model_data = $this->db->get()->result_array();

        return $model_data;
    }

    /* =============       Turfmate answers insert        ============== */
    // public function user_answers_new($data) {

    //     $model_data_count = $this->db->get_where('ct_turfmates_matching',array("users_id"=>$data['users_id']))->num_rows();
    //     $user_id = $data['users_id'];

    //     if($model_data_count == 0) {

    //         $answers_list = $data['turfmate_answers'];
    //         $answers_list = array_map(function($arr) use($user_id) { return $arr+['users_id'=>$user_id]; },$answers_list);
    //         $insert_data = $this->db->insert_batch('ct_turfmates_matching',$answers_list);
    //         $model_data['status'] = "true";
    //     }
    //     else {
    //         $model_data['status'] = "false";
    //     }
    //     return $model_data;
    // }

    /* =============       Turfmate prior questions answers list        ============== */
    // public function user_prior_list($data) {

    //     $this->db->select('tm.question_id,q.question,tm.answer_id,q.options');
    //     $this->db->from('ct_turfmates_matching tm');
    //     $this->db->join('ct_questions q','tm.question_id=q.questions_id','inner');
    //     $this->db->where('tm.users_id',$data['users_id']);
    //     $list_data = $this->db->get()->result_array();

    //     if(!empty($list_data)) {

    //         $model_data['data'] = $list_data;
    //         $model_data['status'] = "true";
    //     }
    //     else {
    //        $model_data['status'] = "false";
    //     }
    //     return $model_data;
    // }

    /* =============       Turfmate prior questions answers list        ============== */
    public function user_update_answers($data) {

        $already_answered_count = $this->db->get_where('ct_turfmates_matching',array('users_id'=>$data['users_id']))->num_rows();
        $answers_list = $data['turfmate_answers'];
        $model_data['status'] = "";

        if($already_answered_count == 0) {
            
            $user_id = $data['users_id'];
            $answers_list = array_map(function($arr) use($user_id) { return $arr+['users_id'=>$user_id]; },$answers_list);
            $insert_data = $this->db->insert_batch('ct_turfmates_matching',$answers_list);
            $model_data['status'] = "true";
        }
        else {
            foreach ($answers_list as $key => $value) {

                $where_cond = '(users_id="'.$data['users_id'].'" AND question_id="'.$value['question_id'].'")';
                $update_answers_data = $this->db->where($where_cond)->update('ct_turfmates_matching',array('answer_id'=>$value['answer_id']));
                $model_data['status'] = "true";
            }
        }

        return $model_data;       
    }

    /* =============       Turfmate user list        ============== */
    public function user_turfmate_list($data) {

        $model_data = array();

        $total_questions_count = $this->db->get_where('ct_questions',array('question_status','1'))->num_rows();

        $exclude_where = '((sender_id="'.$data['users_id'].'" AND like_status!=2 AND turfmate_updated_date >= (CURDATE() - INTERVAL 9 DAY)) OR ((sender_id="'.$data['users_id'].'" OR receiver_id="'.$data['users_id'].'") AND like_status=2))';
        $exclude_ids = $this->db->select('(CASE WHEN sender_id!='.$data['users_id'].' THEN sender_id ELSE receiver_id END) as user_id')->get_where('ct_turfmates',$exclude_where)->result_array();  
        $exclude_ids[] = array('user_id'=>$data['users_id']);
        $exclude_user_ids = array_column($exclude_ids, 'user_id');

        $model_data = $this->db->query("select *
                        from (
                            select tmu.users_id as match_user_id,u.user_fullname,IFNULL(u.user_name,'') as user_name,IFNULL(u.user_email,'') as user_email,IFNULL(u.user_mobile,'') as user_mobile,IFNULL(u.user_gender,'') as user_gender,IFNULL(u.user_dob,'') as user_dob,IFNULL(u.user_profile_image,'') as user_profile_image,IFNULL(u.user_turfmate_image,'') as user_turfmate_image,IFNULL(u.user_description,'') as user_description,(SUM(CASE WHEN tm.answer_id=tmu.answer_id THEN 1 ELSE 0 END) / ".$total_questions_count.") * 100 as match_precent,(CASE WHEN t.turfmates_id!='' THEN 1 ELSE 2 END) as friend_status from ct_turfmates_matching as tm,ct_turfmates_matching as tmu
                            left join ct_users as u on tmu.users_id=u.users_id
                            left join ct_turfmates as t on t.sender_id=u.users_id AND t.receiver_id=".$data['users_id']." AND t.like_status=1
                            where tm.question_id=tmu.question_id AND tm.users_id=".$data['users_id']." AND tmu.users_id NOT IN (".implode(',', $exclude_user_ids).")
                            group by match_user_id 
                            having match_user_id NOT IN (select (CASE WHEN blocklist_from_id!=".$data['users_id']." THEN blocklist_from_id ELSE blocklist_to_id END) as block_ids from ct_turfmate_blocklist where blocklist_from_id = ".$data['users_id']." OR blocklist_to_id = ".$data['users_id'].")
                        ) as x
                        left join (
                            select a.albums_id as album_id,a.users_id as user_id,IFNULL(a.file_type,'') as file_type,a.albums_path
                            from ct_albums as a where a.album_type=2 AND a.albums_status=1
                        ) as y on x.match_user_id = y.user_id order by friend_status asc,match_precent desc
                    ")->result_array();

        return $model_data;       
    }

    /* =============       Turfmate user profile        ============== */
    public function user_turfmate_profile($data) {

        $model_data = array();
        $this->db->select('u.users_id as user_id,IFNULL(u.user_fullname,"") as user_fullname,IFNULL(u.user_name,"") as user_name,IFNULL(u.user_country_code,"") as user_country_code,IFNULL(u.user_email,"") as user_email,IFNULL(u.user_mobile,"") as user_mobile,IFNULL(u.user_gender,"") as user_gender,IFNULL(u.user_dob,"") as user_dob,IFNULL(u.user_turfmate_image,"") as user_turfmate_image,IFNULL(u.user_description,"") as user_description,u.user_register_type,u.user_profile_updated_date,u.user_profile_created_date,a.albums_id,a.albums_path,a.file_type');  
        $this->db->from('ct_users u');
        $this->db->join('ct_albums a','u.users_id=a.users_id AND a.album_type=2 AND a.albums_status=1','left');
        $this->db->where('u.users_id',$data['users_id']);
        $model_data = $this->db->get()->result_array();

        return $model_data;
    }

    /* =============       Turfmate user profile        ============== */
    public function user_turfmate_profile_update($data,$user_id) {

        $model_data = $this->db->where('users_id',$user_id)->update('ct_users',$data);
        return TRUE;
    }

    /* =============       Turfmate subscription       ============== */
    public function check_turfmate_subscription($user_id) {

       $user_sub_data = $this->db->get_where('ct_subscription_activation',array('users_id'=>$user_id,'subscription_type'=>1,'subscription_status'=>1))->row_array();

       if(!empty($user_sub_data)) {
            return "success";
       }
       else {

            $user_action_count = 0;

            $user_record = $this->db->select('user_like_date,user_like_count,user_like_total')->get_where('ct_user_credits',array('users_id'=>$user_id))->row_array();

            
            // After clearing the database, it will be deleted (it will created while user registration)  
            // Start      
            if(empty($user_record)) {

                $insert_credits_data = array(
                                    'users_id' => $user_id,
                                    'user_like_date' => date('Y-m-d H:i:s'),
                                    // 'user_like_count' => 15,
                                    // 'user_like_total' => 15,
                                    'user_multimedia_date' => date('Y-m-d H:i:s'),
                                    // 'user_multimedia_post' => 3,
                                    // 'user_multimedia_total' => 3,
                                    'user_credits_status' => 1
                                );
                $user_credits_data = $this->db->insert('ct_user_credits',$insert_credits_data);

                $user_record = $this->db->select('user_like_date,user_like_count,user_like_total')->get_where('ct_user_credits',array('users_id'=>$user_id))->row_array();
            }
            // End

            if(date('Y-m-d') == date('Y-m-d',strtotime($user_record['user_like_date']))) {

                if($user_record['user_like_count'] > 0) {

                    $user_action_count = $user_record['user_like_count'];
                    $update_user_data = $this->db->where('users_id',$user_id)->set('user_like_count','user_like_count-1',FALSE)->update('ct_user_credits');
                }
            }
            else {

                $user_action_count = $user_record['user_like_total'];
                $set_data = array('user_like_date'=>date('Y-m-d H:i:s'));
                $update_user_data = $this->db->where('users_id',$user_id)->set('user_like_count','user_like_total-1',FALSE)->set($set_data)->update('ct_user_credits');
            }
        
            $return_status = ($user_action_count > 0) ? "success" : "failure";
            return $return_status;
        }
    } 

    /* =============       Turfmate user profile        ============== */
    public function user_turfmate_like($data) {

        // Block count
        $blocked_count = $this->user_blocked_count($data['users_id'],$data['like_user_id']);
        $model_data = array();

        if($blocked_count == 0) {

            $check_subscription = $this->check_turfmate_subscription($data['users_id']);

            if($check_subscription == "success") {

                $where_cond = '(sender_id="'.$data['like_user_id'].'" AND receiver_id="'.$data['users_id'].'")';
                $user_like_data = $this->db->get_where('ct_turfmates',$where_cond)->row_array();

                if(!empty($user_like_data) && $user_like_data['like_status'] == 1) {

                    $user_like_data = $this->db->where($where_cond)->update('ct_turfmates',array('like_temp_status'=>2,'turfmate_updated_date'=>date('Y-m-d H:i:s'),'like_status'=>2));

                    $delete_like_data = $this->db->delete('ct_turfmates',array('sender_id'=>$data['users_id'],'receiver_id'=>$data['like_user_id']));

                    $model_data['status'] = "true";
                    $model_data['accept'] = "yes";
                    $model_data['message'] = "Accepted successfully";   
                }
                else if(!empty($user_like_data) && $user_like_data['like_status'] == 2) {

                    $model_data['status'] = "false";
                    $model_data['message'] = "Already friends";
                }
                else {

                    $already_sent_where = '(sender_id="'.$data['users_id'].'" AND receiver_id="'.$data['like_user_id'].'")';
                    $already_sent_data = $this->db->get_where('ct_turfmates',$already_sent_where)->row_array();

                    if(!empty($already_sent_data) && $already_sent_data['like_status']==2) {                                         
                        $model_data['status'] = "false";
                        $model_data['message'] = "Already friends";
                    }
                    else if(!empty($already_sent_data)) {                     
                        
                        $this->db->where($already_sent_where)->update('ct_turfmates',array('turfmate_updated_date'=>date('Y-m-d H:i:s'),'like_temp_status'=>1,'like_status'=>1));
                        $model_data['status'] = "true";
                        $model_data['message'] = "Liked successfully";
                    }
                    else {

                        $this->db->insert('ct_turfmates',array('sender_id'=>$data['users_id'],'receiver_id'=>$data['like_user_id'],'turfmate_updated_date'=>date('Y-m-d H:i:s'),'like_temp_status'=>1,'like_status'=>1));
                        $model_data['status'] = "true";
                        $model_data['message'] = "Liked successfully";
                    }                 
                }
            }
            else {
                $model_data['status'] = "false";
                $model_data['message'] = "Maximum action exceeded";
                $model_data['subscription'] = "false";
            }
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Something went wrong. Please try again later";
        }
        return $model_data;
    }

    /* =============       Turfmate user profile        ============== */
    public function user_turfmate_dislike($data) {

        // Block count
        $blocked_count = $this->user_blocked_count($data['users_id'],$data['like_user_id']);
        $model_data = array();

        if($blocked_count == 0) {

            // $check_subscription = $this->check_turfmate_subscription($data['users_id']);

            // if($check_subscription == "success") {

                $where_cond = '(((sender_id="'.$data['like_user_id'].'" AND receiver_id="'.$data['users_id'].'") OR (sender_id="'.$data['users_id'].'" AND receiver_id="'.$data['like_user_id'].'")) AND like_status=2)';
                $user_like_data = $this->db->get_where('ct_turfmates',$where_cond)->row_array();

               if(!empty($user_like_data)) {

                    $model_data['status'] = "false";
                    $model_data['message'] = "Already friends";
                }
                else {

                    $already_sent_where = '(sender_id="'.$data['users_id'].'" AND receiver_id="'.$data['like_user_id'].'")';
                    $already_sent_data = $this->db->get_where('ct_turfmates',$already_sent_where)->row_array();

                    if(!empty($already_sent_data)) {                     
                        
                        $this->db->where($already_sent_where)->update('ct_turfmates',array('turfmate_updated_date'=>date('Y-m-d H:i:s'),'like_temp_status'=>3,'like_status'=>3));
                        $model_data['status'] = "true";
                        $model_data['message'] = "Disliked successfully";

                    }
                    else {

                        $this->db->insert('ct_turfmates',array('sender_id'=>$data['users_id'],'receiver_id'=>$data['like_user_id'],'turfmate_updated_date'=>date('Y-m-d H:i:s'),'like_temp_status'=>3,'like_status'=>3));
                        $model_data['status'] = "true";
                        $model_data['message'] = "Disliked successfully";
                    }                 
                }
            // }
            // else {
            //     $model_data['status'] = "false";
            //     $model_data['message'] = "Maximum action exceeded"; 
            // }
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Something went wrong. Please try again later";
        }

        return $model_data;
    }

    /* =================     Insert turfmate album       =========== */
    public function insert_turfmate_album($data) {

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

    /* =================     Remove turfmate album       =========== */
    public function remove_turfmate_album($data) {

        $album_paths = $this->db->select('albums_path')->where_in('albums_id',$data)->get('ct_albums')->result_array();
  
        $album_update = $this->db->where_in('albums_id',$data)->update('ct_albums',array('albums_status'=>2));
        return $album_paths;
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

    /* =============       User profile data       ============== */
    public function user_profile_data($user_id) {

        $model_data = $this->db->select('user_turfmate_image')->get_where('ct_users',array('users_id'=>$user_id))->row_array();
        
        return $model_data;
    }


   
} // End turfmate model
