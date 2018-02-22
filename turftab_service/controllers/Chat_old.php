<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
		$this->load->model('chat_model');
        $this->load->library('../controllers/common');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ==========         User profile        ============= */
	public function user_group()
	{

		if($this->input->post()) {
			$data = $this->input->post();
		}
		else {
			$data = json_decode(file_get_contents("php://input"),true);
		}

		if(!empty($data)) {

			$group_members_limit = 200;

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->chat_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
			}

    		if($data['api_action'] == "create") {

    			$file_path = '';

				// if(!is_dir('./'.UPLOADS.'chat_group/')) {
				// 	mkdir('./'.UPLOADS.'chat_group/',0777,true);
				// 	// 1- execute 2- write 4- read
				// 	// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				// }

    			if(!empty($_FILES['chat_group_image'])) {

    				$this->load->library('upload');
					$file_ext = ".".strtolower(end((explode('.',$_FILES['chat_group_image']['name']))));
					$file_name   = time().mt_rand(000,999).$file_ext;
					$config['upload_path'] = "./".UPLOADS."chat_group/";
				    $config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->upload->initialize($config);
					
					if ($this->upload->do_upload('chat_group_image')) {

						$filepath = $config['upload_path'].$file_name;
						$create_thumb = $this->common->create_thumb($filepath);
						$file_path = str_replace("./", "", $filepath);
         			}
    			}
    			$data['chat_group_image'] = $file_path;

    			$insert_group_data = array('chat_group_name'=>$data['chat_group_name'],'chat_group_description'=>$data['chat_group_description'],'chat_group_image'=>$data['chat_group_image'],'chat_group_admin_id'=>$data['users_id'],'chat_group_maximum'=>$group_members_limit,'chat_group_status'=>1);
    			$group_creation = $this->chat_model->user_group_creation($insert_group_data);

    			if($group_creation['status'] == "true") {

    				$group_members_data[] = array('chat_group_id'=>$group_creation['insert_id'],'group_member_id'=>$data['users_id'],'is_admin'=>1,'added_by'=>$data['users_id'],'group_members_status'=>1);

    				$group_members_list = explode(',',$data['group_members_list']);
					foreach ($group_members_list as $gk => $gv) {
						$group_members_data[] = array('chat_group_id'=>$group_creation['insert_id'],'group_member_id'=>$gv,'is_admin'=>2,'added_by'=>$data['users_id'],'group_members_status'=>1);
					}

					$group_members_add = $this->chat_model->user_group_members_add($group_members_data,"new");
    				$response = array("status"=>"true","status_code"=>"200","insert_id"=>$group_creation['insert_id'],"message"=>"Group created successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion process");
    			}
    		}
    		else if($data['api_action'] == "add_members") {

    			$is_admin_user = $this->chat_model->check_admin_user($data['users_id'],$data['chat_group_id']);

    			if($is_admin_user == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"You don't have access rights");
    				echo json_encode($response);
    				exit;	
    			}

    			$group_members_limit = explode(',',$data['group_members_list']);
    			$group_members_data['chat_group_id'] = $data['chat_group_id'];
				foreach ($group_members_limit as $gk => $gv) {
					$group_members_data[] = array('chat_group_id'=>$data['chat_group_id'],'group_member_id'=>$gv,'is_admin'=>2,'added_by'=>$data['users_id'],'group_members_status'=>1);
				}
				$group_members_add = $this->chat_model->user_group_members_add($group_members_data,"add");
					
				if($group_members_add['status'] == "true") {
					$response = array("status"=>"true","status_code"=>"200","message"=>"Added successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>$group_members_add['message']);
				}
    		}
    		else if($data['api_action'] == "make_admin") {

    			// No need to check whether the group is active or inactive (rare case)

    			$is_admin_user = $this->chat_model->check_admin_user($data['users_id'],$data['chat_group_id']);

    			if($is_admin_user == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"You don't have access rights");
    				echo json_encode($response);
    				exit;	
    			}
				
				$make_admin = $this->chat_model->user_make_admin($data);

				if($make_admin) {
					$response = array("status"=>"true","status_code"=>"200","message"=>"The user made as admin successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong in updation proces. Please try again later");
				}
    		}
    		else if($data['api_action'] == "remove") {

    			// No need to check whether the group is active or inactive (rare case)

    			if($data['users_id'] != $data['group_member_id']) {

    				$is_admin_user = $this->chat_model->check_admin_user($data['users_id'],$data['chat_group_id']);

	    			if($is_admin_user == 0) {
	    				$response = array("status"=>"false","status_code"=>"400","message"=>"You don't have access rights");
	    				echo json_encode($response);
	    				exit;	
	    			}
    			}
	
				$user_remove = $this->chat_model->user_remove($data);

				if($user_remove) {
					$response = array("status"=>"true","status_code"=>"200","message"=>"User removed successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong in updation proces. Please try again later");
				}
    		}
    		else if($data['api_action'] == "update") {

    			$group_status = $this->chat_model->check_group_status($data['chat_group_id']);

    			if($group_status['status'] == "false") {

					$response = array("status"=>"false","status_code"=>"400","message"=>"The group is inactive");
					echo json_encode($response);
					exit;
    			}

    			$file_path = '';

				// if(!is_dir('./'.UPLOADS.'chat_group/')) {
				// 	mkdir('./'.UPLOADS.'chat_group/',0777,true);
				// 	// 1- execute 2- write 4- read
				// 	// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				// }

    			if(!empty($_FILES['chat_group_image'])) {

    				$this->load->library('upload');
					$file_ext = ".".strtolower(end((explode('.',$_FILES['chat_group_image']['name']))));
					$file_name   = time().mt_rand(000,999).$file_ext;
					$config['upload_path'] = "./".UPLOADS."chat_group/";
				    $config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->upload->initialize($config);
					
					if ($this->upload->do_upload('chat_group_image')) {

						$filepath = $config['upload_path'].$file_name;
						$create_thumb = $this->common->create_thumb($filepath);
						$file_path = str_replace("./", "", $filepath);

						// Delete original group image
						if(file_exists("./".$data['pre_group_image'])) unlink("./".$data['pre_group_image']);
						// Delete thumbnail of profile image
						// $file_ext = ".".end((explode('.',$data['pre_group_image'])));
						// $unlink_thumb = str_replace($file_ext, "_thumb$file_ext", $data['pre_group_image']);
						// if(file_exists($unlink_thumb)) unlink($unlink_thumb);
         			}
         			$data['chat_group_image'] = $file_path;
    			}
    			

    			$group_id = $data['chat_group_id'];
    			$data['chat_group_updated_date'] = date('Y-m-d H:i:s');
    			unset($data['unique_id'],$data['users_id'],$data['chat_group_id'],$data['pre_group_image'],$data['api_action']);
				$user_group_update = $this->chat_model->user_group_update($data,$group_id);

				if($user_group_update) {
					$response = array("status"=>"true","status_code"=>"200","message"=>"Group updated successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong in updation proces. Please try again later");
				}
    		}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"Keyword mismatch");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ==========         User friends list        ============= */
	public function user_friends_list()
	{

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->chat_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
			
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
			}

			$userlist = $this->chat_model->user_friends_list($data);

			if(!empty($userlist)) {
				$response = array("status"=>"true","status_code"=>"200","server_data"=>$userlist,"message"=>"Listed successfully");
			}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ==========         User chat media        ============= */
	public function user_chat_media()
	{ 

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->chat_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
			}

    		if($data['api_action'] == "group_chat") {

    			$group_details = $this->chat_model->user_groupchat_media($data);

    			if(!empty($group_details)) {

    				$group_media = $group_details['group_details'][0];
    				unset($group_media['group_member_id'],$group_media['user_fullname'],$group_media['user_name'],$group_media['user_profile_image'],$group_media['profile_image_show'],$group_media['is_admin'],$group_media['group_members_created_date']);
    				$group_media['members'] = array();
					foreach ((array)$group_details['group_details'] as $g_key => $g_val) {
						
						if(!empty($g_val['group_member_id'])) {

							$group_media['members'][] = array('group_member_id'=>$g_val['group_member_id'],'user_fullname'=>$g_val['user_fullname'],'user_name'=>$g_val['user_name'],'user_profile_image'=>$g_val['user_profile_image'],'profile_image_show'=>$g_val['profile_image_show'],'is_admin'=>$g_val['is_admin'],'group_members_created_date'=>$g_val['group_members_created_date']);
						}
					}

					$group_media['members_count'] = count($group_media['members']);
					$group_media['media'] = $group_details['media'];
				
    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$group_media,"message"=>"Listed successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
    			}
    		}
    		else if($data['api_action'] == "profile_chat") {

    			$profilechat_details = $this->chat_model->user_profilechat_media($data);

    			if(!empty($profilechat_details)) {

    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$profilechat_details,"message"=>"Listed successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
    			}
    		}
    		else if($data['api_action'] == "turfmate_chat") {

    			$turfmatechat_details = $this->chat_model->user_turfmatechat_media($data);

    			if(!empty($turfmatechat_details)) {

    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$turfmatechat_details,"message"=>"Listed successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
    			}
    		}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"Keyword mismatch");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ==========         User conversation list        ============= */
	public function user_conversation_list()
	{ 

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->chat_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
			}

    		if($data['api_action'] == "group_chat") {

    			$user_groupconversation_list = $this->chat_model->user_groupconversation_list($data);

				if(!empty($user_groupconversation_list)) {

					$user_groupconversation_count = count($user_groupconversation_list);
					$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_groupconversation_list,"count"=>$user_groupconversation_count,"message"=>"Listed successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}
    		}
    		else if($data['api_action'] == "profile_chat") {

				$user_profileconversation_list = $this->chat_model->user_profileconversation_list($data);

				if(!empty($user_profileconversation_list)) {

					$user_profileconversation_count = count($user_profileconversation_list);
					$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_profileconversation_list,"count"=>$user_profileconversation_count,"message"=>"Listed successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}
  			}
  			else if($data['api_action'] == "turfmate_chat") {

				$user_turfmateconversation_list = $this->chat_model->user_turfmateconversation_list($data);

				if(!empty($user_turfmateconversation_list)) {

					$user_turfmateconversation_count = count($user_turfmateconversation_list);
					$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_turfmateconversation_list,"count"=>$user_turfmateconversation_count,"message"=>"Listed successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}
  			}    	
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"Keyword mismatch");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ==========         User conversation history        ============= */
	public function user_conversation_history()
	{ 

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->chat_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
			}

    		if($data['api_action'] == "group_chat") {

    			$limit = 3;
				$index = (!empty($data['index'])) ? $data['index'] : 1;
				$start = ($index > 1) ? ($index-1) * $limit : 0;

				$user_groupconversation_history = $this->chat_model->user_groupconversation_history($data,$start,$limit);

				if(!empty($user_groupconversation_history)) {

					$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_groupconversation_history,"message"=>"Listed successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}
    		}
    		else if($data['api_action'] == "profile_chat") {

    			$limit = 3;
				$index = (!empty($data['index'])) ? $data['index'] : 1;
				$start = ($index > 1) ? ($index-1) * $limit : 0;

				$user_profileconversation_history = $this->chat_model->user_profileconversation_history($data,$start,$limit);

				if(!empty($user_profileconversation_history)) {

					$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_profileconversation_history,"message"=>"Listed successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}
    		}
    		else if($data['api_action'] == "turfmate_chat") {

    			$limit = 3;
				$index = (!empty($data['index'])) ? $data['index'] : 1;
				$start = ($index > 1) ? ($index-1) * $limit : 0;

				$user_turfmateconversation_history = $this->chat_model->user_turfmateconversation_history($data,$start,$limit);

				if(!empty($user_turfmateconversation_history)) {

					$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_turfmateconversation_history,"message"=>"Listed successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}
    		}
    		else if($data['api_action'] == "local_chat") {

    			$limit = 3;
				$index = (!empty($data['index'])) ? $data['index'] : 1;
				$start = ($index > 1) ? ($index-1) * $limit : 0;

				$user_localconversation_history = $this->chat_model->user_localconversation_history($data,$start,$limit);

				if(!empty($user_localconversation_history)) {

					$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_localconversation_history,"message"=>"Listed successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}
    		}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"Keyword mismatch");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	
	
} // End chat controller
