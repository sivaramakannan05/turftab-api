<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
		$this->load->model('chat_model');
        $this->load->library('../controllers/common');
		$this->load->library('upload');
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

			$group_members_limit = 0;

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

    			$user_name = (!empty($data['loggedin_user_name'])) ? $data['loggedin_user_name'] : 'user';

    			$insert_group_data = array('chat_group_name'=>$data['chat_group_name'],'chat_group_admin_id'=>$data['users_id'],'chat_group_maximum'=>$group_members_limit,'chat_group_status'=>1,'chat_group_updated_date'=>date('Y-m-d H:i:s'));
    			$group_creation = $this->chat_model->user_group_creation($insert_group_data);

    			if($group_creation['status'] == "true") {

    				$group_members_data[] = array('chat_group_id'=>$group_creation['insert_id'],'group_member_id'=>$data['users_id'],'user_role'=>1,'added_by'=>$data['users_id'],'group_members_status'=>1);

    				$group_members_list = explode(',',$data['group_members_list']);
					foreach ($group_members_list as $gk => $gv) {
						$group_members_data[] = array('chat_group_id'=>$group_creation['insert_id'],'group_member_id'=>$gv,'user_role'=>3,'added_by'=>$data['users_id'],'group_members_status'=>1);

						// Save notifications
						// $notification_data[] = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $gv,'notifications_msg'=> "has been created a new group and added you.",'notifications_type'=> "group_create",'notifications_event_id'=>$group_creation['insert_id'],'notifications_status'=> 1);
					}

					$group_members_add = $this->chat_model->user_group_members_add($group_members_data,"new");

					// $save_notifications = $this->chat_model->save_notifications_batch($notification_data);
					// $user_device_details = $this->chat_model->get_users_device_details($group_members_list,"multiple");
					// $notification_ids = (!empty($save_notifications['insert_ids'])) ? $save_notifications['insert_ids'] : array();

					// $device_details = array();

					// if(!empty($user_device_details)) {

					// 	foreach ($user_device_details as $key => $value) {

					// 		$notification_id_key = array_search($value['users_id'], array_column($notification_ids, 'user_id'));
					// 		$value['notification_id'] = $notification_ids[$notification_id_key]['notifications_id'];

					// 		if($value['logs_device_type'] == 1) {
							
					// 			$device_details['android'][] = $value;
					// 		}
					// 		else {
					// 			$device_details['ios'][] = $value;
					// 		}
					// 	}

					// 	// Send notifications
					// 	if(!empty($device_details)) {

					// 		$msg = array (
					// 			'title' => "You have a new notification.",
					// 			'message' =>  $user_name." has been created a new group and added you.",
					// 			'notifications_type' => "group_create",
					// 			'notifications_from_id' => $data['users_id'],
					// 			'notifications_event_id' => $group_creation['insert_id']
					// 		);
					// 		// $send_notification = $this->common->multiple_push_notification_service($device_details,$msg);
					// 	}
					// }

					$user_profile_restriction = $this->chat_model->user_profile_restriction($data['users_id']);
	  				$response = array("status"=>"true","status_code"=>"200","insert_id"=>$group_creation['insert_id'],"profile_image_show"=>$user_profile_restriction,"message"=>"Group created successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion process");
    			}
    		}
    		else if($data['api_action'] == "update") {

    			$group_status = $this->chat_model->check_group_status($data['chat_group_id']);

    			if($group_status['status'] == "false") {

					$response = array("status"=>"false","status_code"=>"400","message"=>"The group is not available");
					echo json_encode($response);
					exit;
    			}    			

    			$group_id = $data['chat_group_id'];
    			$data['chat_group_updated_date'] = date('Y-m-d H:i:s');
    			unset($data['unique_id'],$data['users_id'],$data['chat_group_id'],$data['api_action']);
				$user_group_update = $this->chat_model->user_group_update($data,$group_id);

				if($user_group_update) {
					$response = array("status"=>"true","status_code"=>"200","message"=>"Group updated successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong in updation proces. Please try again later");
				}
    		}
    		else if($data['api_action'] == "update_theme") {

    			$group_status = $this->chat_model->check_group_status($data['chat_group_id']);

    			if($group_status['status'] == "false") {

					$response = array("status"=>"false","status_code"=>"400","message"=>"The group is not available");
					echo json_encode($response);
					exit;
    			}    			

    			$group_id = $data['chat_group_id'];
    			$data['chat_group_updated_date'] = date('Y-m-d H:i:s');
    			unset($data['unique_id'],$data['users_id'],$data['chat_group_id'],$data['api_action']);


				// if(!is_dir('./'.UPLOADS.'chat_group/theme')) {
				// 	mkdir('./'.UPLOADS.'chat_group/theme',0777,true);
				// 	// 1- execute 2- write 4- read
				// 	// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				// }
    			$file_path = '';
				if(!empty($_FILES['theme'])) {

					$file_ext = ".".strtolower(end((explode('.',$_FILES['theme']['name']))));
					$file_name   = time().mt_rand(000,999).$file_ext;
					$config['upload_path'] = "./".UPLOADS."chat_group/theme/";
				    $config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->upload->initialize($config);
					if ($this->upload->do_upload('theme')) {
						$filepath = $config['upload_path'].$file_name;
						$create_thumb = $this->common->create_thumb($filepath);
						$file_path = str_replace("./", "", $filepath);
         			}
         			$data['chat_group_theme'] = $file_path;

         			// Delete original profile image
         			if(!empty($data['pre_theme']) && file_exists("./".$data['pre_theme'])) unlink("./".$data['pre_theme']);
         			// Delete thumbnail of profile image
         			// $file_ext = ".".end((explode('.',$data['pre_theme'])));
         			// $unlink_thumb = str_replace($file_ext, "_thumb$file_ext", $data['pre_theme']);
         			// if(file_exists($unlink_thumb)) unlink($unlink_thumb);
				}

				$user_group_update = $this->chat_model->user_group_update($data,$group_id);

				if($user_group_update) {
					$response = array("status"=>"true","status_code"=>"200","message"=>"Group theme updated successfully","theme"=>$data['chat_group_theme']);
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong in updation proces. Please try again later");
				}
    		}
    		else if($data['api_action'] == "add_members") {

    			$group_status = $this->chat_model->check_group_status($data['chat_group_id']);

    			if($group_status['status'] == "false") {

					$response = array("status"=>"false","status_code"=>"400","message"=>"The group is not available");
					echo json_encode($response);
					exit;
    			}

    			$is_admin_user = $this->chat_model->check_admin_user($data['users_id'],$data['chat_group_id']);

    			if($is_admin_user == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"You don't have access rights");
    				echo json_encode($response);
    				exit;	
    			}

    			$group_members_limit = explode(',',$data['group_members_list']);
    			$group_members_data['chat_group_id'] = $data['chat_group_id'];
				foreach ($group_members_limit as $gk => $gv) {
					$group_members_data[] = array('chat_group_id'=>$data['chat_group_id'],'group_member_id'=>$gv,'user_role'=>3,'added_by'=>$data['users_id'],'group_members_status'=>1);
				}
				$group_members_add = $this->chat_model->user_group_members_add($group_members_data,"add");
					
				if($group_members_add['status'] == "true") {
					$response = array("status"=>"true","status_code"=>"200","message"=>"Added successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>$group_members_add['message']);
				}
    		}
    		else if($data['api_action'] == "remove" || $data['api_action'] == "exit") {

    			$group_status = $this->chat_model->check_group_status($data['chat_group_id']);

    			if($group_status['status'] == "false") {

					$response = array("status"=>"false","status_code"=>"400","message"=>"The group is not available");
					echo json_encode($response);
					exit;
    			}

    			if($data['users_id'] != $data['group_member_id']) {

    				$action = "remove";

    				$is_admin_user = $this->chat_model->check_admin_user($data['users_id'],$data['chat_group_id']);

	    			if($is_admin_user == 0) {
	    				$response = array("status"=>"false","status_code"=>"400","message"=>"You don't have access rights");
	    				echo json_encode($response);
	    				exit;	
	    			}
    			}
    			else {

    				$action = "exit";

    				$is_admin_user = $this->chat_model->check_admin_user($data['users_id'],$data['chat_group_id']);

	    			if($is_admin_user == 1) {
	    				$response = array("status"=>"false","status_code"=>"400","message"=>"Please make any one of your friend as admin");
	    				echo json_encode($response);
	    				exit;	
	    			}
    			}
	
				$user_remove = $this->chat_model->user_remove($data);

				if($user_remove) { 
					
					$action_done = ($action == "exit") ? "exited" : "removed";

					$response = array("status"=>"true","status_code"=>"200","action"=>$action,"message"=>"User ".$action_done." successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong in updation proces. Please try again later");
				}
    		}
    		else if($data['api_action'] == "group_list") {

    			$group_list = $this->chat_model->user_joined_grouplist($data);

				$response = array("status"=>"true","status_code"=>"200","server_data"=>$group_list,"message"=>"Listed successfully");

				// if($group_list) {
				// 	$response = array("status"=>"true","status_code"=>"200","server_data"=>$group_list,"message"=>"Listed successfully");
				// }
				// else {
				// 	$response = array("status"=>"false","status_code"=>"400","message"=>"No record(s) found");
				// }
    		}
    		else if($data['api_action'] == "group_details") {

    			$group_details = $this->chat_model->user_group_details($data);

				if($group_details) {

					// Admin id
					$chat_group_admin_id = (!empty($group_details['members'])) ? array_search('1', array_column($group_details['members'], 'user_role')) : '';
					$group_details['chat_group_admin_id'] = ($chat_group_admin_id=='' && $chat_group_admin_id!=0) ? '' : $group_details['members'][$chat_group_admin_id]['users_id'];

					$response = array("status"=>"true","status_code"=>"200","server_data"=>$group_details,"message"=>"Listed successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No record(s) found");
				}
    		}
    		else if($data['api_action'] == "make_admin") {

    			$group_status = $this->chat_model->check_group_status($data['chat_group_id']);

    			if($group_status['status'] == "false") {

					$response = array("status"=>"false","status_code"=>"400","message"=>"The group is not available");
					echo json_encode($response);
					exit;
    			}
    			
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
    		else if($data['api_action'] == "make_super_admin") {

    			$group_status = $this->chat_model->check_group_status($data['chat_group_id']);

    			if($group_status['status'] == "false") {

					$response = array("status"=>"false","status_code"=>"400","message"=>"The group is not available");
					echo json_encode($response);
					exit;
    			}
    			
  				$is_admin_user = $this->chat_model->check_admin_user($data['users_id'],$data['chat_group_id']);

    			if($is_admin_user == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"You don't have access rights");
    				echo json_encode($response);
    				exit;	
    			}
				
				$make_admin = $this->chat_model->user_make_super_admin($data);

				if($make_admin) {
					$response = array("status"=>"true","status_code"=>"200","message"=>"The user made as super admin successfully");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong in updation proces. Please try again later");
				}
    		}
    		else if($data['api_action'] == "delete_group") {

  				$group_admin = $this->chat_model->check_admin_user($data['users_id'],$data['chat_group_id']);

    			if($group_admin == 0) {

    				$response = array("status"=>"false","status_code"=>"400","message"=>"You can't delete the group");
    				echo json_encode($response);
    				exit;	
    			}
				
				$delete_group = $this->chat_model->user_delete_group($data);

				$response = array("status"=>"true","status_code"=>"200","message"=>"Group deleted successfully");
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
	
	/* ==========         User chat conversation        ============= */
	public function user_local_chat_conversation()
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

			$limit = 10;
			$index = (!empty($data['index'])) ? $data['index'] : 1;
			$start = ($index > 1) ? ($index-1) * $limit : 0;

			$conversation = $this->chat_model->user_local_chat_conversation($data,$start,$limit);
			$media_restriction = $this->chat_model->user_multimedia_post($data);
			$media_count = (!empty($media_restriction['count']) ? $media_restriction['count'] : '');

			$messages = (!empty($conversation['data'])) ? array_reverse($conversation['data']) : array();

			if(!empty($messages)) {
				$response = array("status"=>"true","status_code"=>"200","server_data"=>$messages,"total_count"=>$conversation['total_count'],"message"=>"Listed successfully","post_count"=>$media_count);
			}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"No record(s) found");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ==========         User local message manage        ============= */
	public function user_local_message()
	{

		$data = $this->input->post();

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

			$thumbnail_path = '';
			$loggedin_user_name = (!empty($data['loggedin_user_name'])) ? $data['loggedin_user_name'] : 'user';
			unset($data['loggedin_user_name']);
			$user_name = (!empty($data['is_anonymous']) && $data['is_anonymous'] == 1) ? 'user' : $loggedin_user_name;

			// "content_type" - 1(text), 2(image), 3(video), 4(audio), 5(gif), 6(location), 7(document)

			// file upload
    		if($data['content_type'] != 6 && $data['content_type'] != 1) { 

				$media_restriction = $this->chat_model->chat_media_restriction($data);

				if($media_restriction['status'] == "false") {

					$response = array("status"=>"false","status_code"=>"400","message"=>"Maximum action exceeded","subscription"=>false);
					echo json_encode($response);
					exit;
				}


    			$chat_content = '';
    			$media_folder_name = ($data['content_type'] == 2) ? "image" : (($data['content_type'] == 3) ? "video" : (($data['content_type'] == 4) ? "audio" : (($data['content_type'] == 5) ? "gif" : (($data['content_type'] == 6) ? "location" : "document"))));

    			if(!is_dir('./'.UPLOADS.'chat_media/'.$media_folder_name.'/')) {
					mkdir('./'.UPLOADS.'chat_media/'.$media_folder_name.'/',0777,true);
					// 1- execute 2- write 4- read
					// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				}

				if(!empty($_FILES['content'])) {

					$file_path = '';
					$file_ext = ".".strtolower(end((explode('.',$_FILES['content']['name']))));
					$file_name_random = time().mt_rand(00,99);
					$file_name   = $file_name_random.$file_ext;

				    $config['upload_path'] = "./".UPLOADS."chat_media/".$media_folder_name."/";
					$config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->upload->initialize($config);
	    			if($this->upload->do_upload('content')) {
						$file_path = $config['upload_path'].$file_name;
						$chat_content = str_replace("./", "", $file_path);
						if($data['content_type'] == 2 || $data['content_type'] == 3) {
							
							$file_ext = ($data['content_type'] == 2) ? $file_ext : '.png';
							$thumbnail_path = $config['upload_path'].$file_name_random."_thumb".$file_ext;
							$thumbnail = $this->create_chat_thumbnail($data['content_type'],$file_path,$thumbnail_path);
						}
					}
				}
				$data['content'] = $chat_content;
    		}
    		$data['local_conversation_from_id'] = $data['users_id'];
    		unset($data['users_id'],$data['unique_id']);
    		$thumb_image = str_replace("./", "", $thumbnail_path);

    		$insert_message = $this->chat_model->insert_local_message($data);

			if($insert_message['status'] == "true") {

				$user_profile_data = $this->chat_model->user_profile_data($data['local_conversation_from_id']);

				$user_profile_image = (!empty($data['is_anonymous']) && $data['is_anonymous'] == 1) ? '' : ((!empty($user_profile_data['user_profile_image'])) ? $user_profile_data['user_profile_image'] : '');

				if(!empty($insert_message['user_ids'])) {

					$user_device_details = $this->chat_model->local_chat_user_details($insert_message['user_ids'],$data['local_conversation_from_id']);

					if(!empty($user_device_details)) {

						$device_details = array();

						foreach ($user_device_details as $key => $value) {

							if($value['logs_device_type'] == 1) {
								$device_details['android'][] = $value;
							}
							else {
								$device_details['ios'][] = $value;
							}
						}

						// Save notifications
						if(!empty($device_details)) {

							$msg = array (
								'title' => "You have a message notification.",
								'message' => $user_name." sent a message in local chat.",
								'notifications_type' => "local_chat",
								'notifications_from_id' => $data['local_conversation_from_id'],
								'local_conversation_id' => $insert_message['insert_id'],
								'content' => $data['content'],
								'content_type' => $data['content_type'],
								'user_name' => $user_name,
								'user_profile_image' => $user_profile_image,
								'profile_image_show' => (!empty($user_profile_data['profile_image_show']) ? $user_profile_data['profile_image_show'] : '')
							);
							$send_notification = $this->common->multiple_push_notification_local_chat($device_details,$msg);
						}
					}
				}

				$message_array[] = array('local_conversation_id'=>$insert_message['insert_id'],"thumb"=>$thumb_image,"content"=>$data['content'],"content_type"=>$data['content_type'],"user_id"=>$data['local_conversation_from_id'],"user_name"=>$user_name,"user_profile_image"=>$user_profile_image);

				$media_count = (isset($media_restriction['count']) && $media_restriction['count'] !=0) ? $media_restriction['count'] : '';

				$response = array("status"=>"true","status_code"=>"200","server_data"=>$message_array,"message"=>"Message sent successfully","post_count"=>$media_count);
			}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion process");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ==========         Create thumbnail        ============= */
	public function create_chat_thumbnail($type,$upath,$thumbnail)
	{

		if($type == 2) {

			$config['image_library']  = 'gd2';
			$config['source_image']   = $upath;
			$config['create_thumb']   = TRUE;
			$config['maintain_ratio'] = TRUE;
			$config['width']          = 200;
			$config['height']         = 200;
			$config['thumb_marker']   = "_thumb";
			$this->load->library('image_lib', $config);
			if($this->image_lib->resize()) {
				$this->image_lib->clear();
				return TRUE;
			}
			$this->image_lib->clear();
			return FALSE;
		}
		else {

			// FFMPEG
			// $ffmpeg = 'c://ffmpeg//bin//ffmpeg'; // for localhost
			$ffmpeg  = '/usr/bin/ffmpeg'; // for server
			$videofile = $upath;
			$imagefile = $thumbnail;
			$size = "200x180";
			$getfromsecond = "10";
			$cmd = "$ffmpeg -i $videofile -an -ss $getfromsecond -s $size $imagefile";	
			$exec = shell_exec($cmd);
			return TRUE;
		}
	}

} // End chat controller
