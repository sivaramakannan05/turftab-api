<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
        // $this->load->library('form_validation');
		$this->load->model('profile_model');
        $this->load->library('../controllers/common');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ==========         User profile        ============= */
	public function user_profile()
	{

		if($this->input->post()) {
			$data = $this->input->post();
		}
		else {
			$data = json_decode(file_get_contents("php://input"),true);
		}

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->profile_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

     		if($data['api_action'] == "view") {

    			$profile_data = $this->profile_model->user_profile_data($data);

   				if($profile_data['status'] == "true") {
    				
    				$user_profile_data = array();
	  				$user_profile_data = $profile_data['data'][0];

	  				// friend request,response logic
	    			$frnd_req_sender_id = $user_profile_data['sender_id'];
	    			$frnd_status = $user_profile_data['friends_status'];
	    			if(!empty($frnd_status)) {
	    				if($frnd_status == 1) {
	    					$user_profile_data['friends_status'] = ($frnd_req_sender_id == $data['users_id']) ? "1" : "2";
	    				}
	    				else if($frnd_status == 2) {
	    					$user_profile_data['friends_status'] = "3";
	    				}
	    				else {
	    					$user_profile_data['friends_status'] = "4"; // blocked
	    				}
	    			}

	    			// 1-request sent, 2. ready to accept, 3- friends, empty- none
        			
        			unset($user_profile_data['albums_id'],$user_profile_data['albums_path'],$user_profile_data['file_type']);

    				$user_profile_data['album'] = array();
    			// 	foreach ($profile_data['data'] as $key => $value) {
    			// 		foreach ($value as $k => $v) {
    			// 			if(empty($v)) $user_profile_data[$k] = '';
							// if($k == "albums_id" || $k == "albums_path" || $k == "file_type") {
    			// 				if(!empty($v)) $user_profile_data['album'][$key][$k] = $v;
    			// 				unset($user_profile_data[$k]);
    			// 			}
    			// 		}
    			// 	}

    				foreach ($profile_data['data'] as $al_key => $al_val) {
		
						if(!empty($al_val['albums_id'])) {

							if($al_val['file_type'] == 2) {

								$video_url = $al_val['albums_path'];
								$video_file_name = str_replace(UPLOADS."album/","",$al_val['albums_path']);
								$file_name_split = explode('.',$video_file_name);
								$al_val['albums_path'] = UPLOADS."album/".$file_name_split[0]."_thumb.png";
								$user_profile_data['album'][] = array('albums_id'=>$al_val['albums_id'],'albums_path'=>$al_val['albums_path'],'file_type'=>$al_val['file_type'],'video_url'=>$video_url);
							}
							else {
								$user_profile_data['album'][] = array('albums_id'=>$al_val['albums_id'],'albums_path'=>$al_val['albums_path'],'file_type'=>$al_val['file_type']);
							}
						}
    				}
    				unset($user_profile_data['sender_id']);
    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_profile_data,"message"=>"Listed successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong. Please try again later");
    			}
    		}
			else if($data['api_action'] == "update") {

				if(!empty($data['user_email']) || !empty($data['user_mobile'])) {
					$response = array("status"=>"false","status_code"=>"400","message"=>"You cant update email id or mobile number here");
					echo json_encode($response);
					exit;
				}

				if(!empty($data['user_name'])) {

					$check_username['user_name'] = $data['user_name'];
					$check_username['users_id'] = $data['users_id'];
					$data['user_fullname'] = $data['user_name'];
					$user_name_exist = $this->profile_model->check_username_exist_update('username',$check_username);
					if($user_name_exist > 0) {
						$response = array("status"=>"false","status_code"=>"400","message"=>"Username already taken");
						echo json_encode($response);
						exit;
					}
				}

				// if(!is_dir('./'.UPLOADS.'profile/')) {
				// 	mkdir('./'.UPLOADS.'profile/',0777,true);
				// 	// 1- execute 2- write 4- read
				// 	// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				// }

				if(!empty($_FILES['user_profile_image'])) {

					$file_ext = ".".strtolower(end((explode('.',$_FILES['user_profile_image']['name']))));
					$file_name   = time().mt_rand(000,999).$file_ext;
					$config['upload_path'] = "./".UPLOADS."profile/";
				    $config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->load->library('upload', $config);
					if ($this->upload->do_upload('user_profile_image')) {
						$filepath = $config['upload_path'].$file_name;
						$create_thumb = $this->common->create_thumb($filepath);
						$file_path = str_replace("./", "", $filepath);
         			}
         			$data['user_profile_image'] = $file_path;

         			// Delete original profile image
         			if(file_exists("./".$data['pre_profile_image'])) unlink("./".$data['pre_profile_image']);
         			// Delete thumbnail of profile image
         			// $file_ext = ".".end((explode('.',$data['pre_profile_image'])));
         			// $unlink_thumb = str_replace($file_ext, "_thumb$file_ext", $data['pre_profile_image']);
         			// if(file_exists($unlink_thumb)) unlink($unlink_thumb);
				}
				$user_id = $data['users_id'];
				unset($data['unique_id'],$data['users_id'],$data['api_action'],$data['pre_profile_image']);
				$update_profile = $this->profile_model->update_profile($user_id,$data);
				$response = array("status"=>"true","status_code"=>"200","message"=>"Profile updated successfully");	
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

	/* ==========         User album        ============= */
	public function user_album()
	{

		if($this->input->post()) {
			$data = $this->input->post();
		}
		else {
			$data = json_decode(file_get_contents("php://input"),true);		
		}

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->profile_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "add") { 

    			$album_insert_data = array();
    			// album type - image or video
    			$album_type = (!empty($data['file_type'])) ? $data['file_type'] : 'image';
    			if(!empty($_FILES['user_album'])) {

					$this->load->library('upload');

					// if(!is_dir('./'.UPLOADS.'album/')) {
					// 	mkdir('./'.UPLOADS.'album/',0777,true);
					// 	// 1- execute 2- write 4- read
					// 	// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
					// }
					
    				// $file_count = count($_FILES['user_album']['name']);
    					
   					// Single upload
			        $file_ext = ".".strtolower(end((explode('.',$_FILES['user_album']['name']))));
	   				$file_name_random   = time().mt_rand(00,99);
	   				$file_name   = $file_name_random.$file_ext;

			        $config['upload_path'] = "./".UPLOADS."album/";
					$config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->upload->initialize($config);
					if($this->upload->do_upload('user_album')) {

						$file_path = $config['upload_path'].$file_name;

	    				if($album_type == "video") {

	    					// FFMPEG
	    					// $ffmpeg = 'c://ffmpeg//bin//ffmpeg'; // for localhost
							$ffmpeg = '/usr/bin/ffmpeg'; // for server
							$videofile = $file_path;
							$imagefile = $config['upload_path'].$file_name_random."_thumb".".png";
							$size = "200x180";
							$getfromsecond = "10";
							$cmd = "$ffmpeg -i $videofile -an -ss $getfromsecond -s $size $imagefile";	
							$exec = shell_exec($cmd);
							// if(!$exec)
							// {
							// 		echo "true";
							// }
							// else
							// {
							// 		echo "false";
							// }
							$album_insert_data[0]['file_type'] = 2;
							$video_thumb = str_replace("./", "", $imagefile);
	    				}
	    				else {

	    					/* Create thumbnail image of album start */
	    					$this->load->library('image_lib');

							$config['image_library']  = 'gd2';
							$config['source_image']   = $file_path;
							$config['create_thumb']   = TRUE;
							$config['maintain_ratio'] = TRUE;
							$config['width']          = 100;
							$config['height']         = 100;
							$config['thumb_marker']   = "_thumb";
							$this->image_lib->initialize($config);
							$this->image_lib->resize();
							/* Create thumbnail image of album end */
							$album_insert_data[0]['file_type'] = 1;
	    				}
						$album_insert_data[0]['users_id'] = $data['users_id'];
						$album_insert_data[0]['albums_path'] = str_replace("./", "", $file_path);
						$album_insert_data[0]['albums_status'] = 1;	
						$album_insert_data[0]['album_type'] = 1;
	       			}
 				
    				// 	for($i=0;$i<$file_count;$i++) {
    					
    				// 		// Single upload
    				// 		$_FILES['userfile']['name']= $_FILES['user_album']['name'][$i];
				   	//      $_FILES['userfile']['type']= $_FILES['user_album']['type'][$i];
				   	//      $_FILES['userfile']['tmp_name']= $_FILES['user_album']['tmp_name'][$i];
				   	//      $_FILES['userfile']['error']= $_FILES['user_album']['error'][$i];
				   	//      $_FILES['userfile']['size']= $_FILES['user_album']['size'][$i];

				   	//      $file_ext = ".".strtolower(end((explode('.',$_FILES['userfile']['name']))));
    				// 		$file_name   = time().mt_rand(00,99).$file_ext;

					//      $config['upload_path'] = "./".UPLOADS."album/";
	    			// 		$config['allowed_types'] = '*';
    				// 		$config['file_name']   = $file_name;
    				// 		$this->upload->initialize($config);
    				// 		if($this->upload->do_upload('userfile')) {

				    			//	$file_path = $config['upload_path'].$file_name;

					    		//	/* Create thumbnail image of album start */
							 	// 	$config['image_library']  = 'gd2';
								// $config['source_image']   = $file_path;
								// $config['create_thumb']   = TRUE;
								// $config['maintain_ratio'] = TRUE;
								// $config['width']          = 100;
								// $config['height']         = 100;
								// $config['thumb_marker']   = "_thumb";
								// $this->image_lib->initialize($config);
								// $this->image_lib->resize();
								// /* Create thumbnail image of album end */

								// $album_insert_data[$i]['users_id'] = $data['users_id'];
								// $album_insert_data[$i]['albums_path'] = str_replace("./", "", $file_path);
								// $album_insert_data[$i]['albums_status'] = 1;	
								// $album_insert_data[$i]['album_type'] = 1;
      					//	}
    				// 	}
    			}

    			$insert_user_album = $this->profile_model->insert_user_album($album_insert_data);
    			if(!empty($insert_user_album['album_data'])) {

    				if($album_type == "video") {
    					$insert_user_album['album_data'][0]['video_url'] = $insert_user_album['album_data'][0]['albums_path'];
    					$insert_user_album['album_data'][0]['albums_path'] = $video_thumb;
    				}

    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$insert_user_album['album_data'],"message"=>"Uploaded successfully");	
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in uploading process");	
    			}    			
			}
			else if($data['api_action'] == "remove") {

				$album_ids = explode(',',$data['user_album']);	// comma separeted list
				$album_remove = $this->profile_model->remove_user_album($album_ids);

				foreach ($album_remove as $key => $value) {
					
					// Delete original profile image
	    			if(file_exists("./".$value['albums_path'])) unlink("./".$value['albums_path']);
	    			// Delete thumbnail of profile image
	    			// $file_ext = ".".end((explode('.',$value['albums_path'])));
					// $unlink_thumb = str_replace($file_ext, "_thumb$file_ext", $value['albums_path']);
	    			// if(file_exists($unlink_thumb)) unlink($unlink_thumb);	
				}
				$response = array("status"=>"true","status_code"=>"200","message"=>"Removed successfully");
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

	/* ==========         User block        ============= */
	public function user_block()
	{ 

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->profile_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "blocking") {

    			$insert_data['blocklist_from_id'] = $data['users_id'];
    			$insert_data['blocklist_to_id'] = $data['block_users_id'];
				$user_block = $this->profile_model->user_block($insert_data);
				if($user_block['status'] == "true") {
					$response = array("status"=>"true","status_code"=>"200","message"=>$user_block['message']);
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>$user_block['message']);
				}
			}
			// else if($data['api_action'] == "unblocking") {

			// 	$delete_data['blocklist_from_id'] = $data['users_id'];
   //  			$delete_data['blocklist_to_id'] = $data['block_users_id'];
			// 	$user_block = $this->profile_model->user_unblock($delete_data);
   //  			if($user_block['status'] == "true") {
			// 		$response = array("status"=>"true","status_code"=>"200","message"=>$user_block['message']);
			// 	}
			// 	else {
			// 		$response = array("status"=>"false","status_code"=>"400","message"=>$user_block['message']);
			// 	}	
   //  		}
    // 		else if($data['api_action'] == "blocklist") {

				// $user_blocklist = $this->profile_model->user_blocklist($data);
				// if(!empty($user_blocklist)) {
				// 	$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_blocklist,"message"=>"Listed successfully");		
				// }
				// else {
				// 	$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				// }
    // 		}
			else {
				$response = array("status"=>"false","status_code"=>"400","message"=>"Keyword mismatch");
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ==========         User friends        ============= */
	public function user_friends()
	{ 

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->profile_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "request") {

    			$user_name = $data['loggedin_user_name'];
  				unset($data['loggedin_user_name']);

    			$friend_data = $this->profile_model->user_add_friend($data); 

  				if($friend_data['status'] == "true") {

  					//	push notification
  					$user_id = $data['friend_id'];
  					$user_device_details = $this->profile_model->get_users_device_details($user_id);

  					// Save notifications
  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $data['friend_id'],'notifications_msg'=> "sent a friend request.",'notifications_type'=> "friend_request" ,'notifications_status'=> 1);
  					$save_notifications = $this->profile_model->save_notifications($notification_data);

  					if(!empty($user_device_details)) {

  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
  						$user_device_token = array($user_device_details['logs_device_token']);

		  				if(!empty($user_device_token)) {
			  				$msg = array (
										'title' => "You have a new notification.",
										'message' => $user_name." sent a friend request.",
										'notifications_type' => "friend_request",
										'notifications_id' => $save_notifications['insert_id'],
										'notifications_from_id' => $data['users_id']
										);
	  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$data['friend_id']);
	  					}
					}
					$response = array("status"=>"true","status_code"=>"200","message"=>$friend_data['message']);
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>$friend_data['message']);
    			}
			}
			else if($data['api_action'] == "accept") {

				$user_name = $data['loggedin_user_name'];
  				unset($data['loggedin_user_name']);

				$friend_data = $this->profile_model->user_accept_friend($data);
    			if($friend_data['status'] == "true") {

    				// push notification
  					$user_id = $data['friend_id'];
  					
  					$user_device_details = $this->profile_model->get_users_device_details($user_id);

  					// Save notifications
  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $data['friend_id'],'notifications_msg'=> "accepted your request.",'notifications_type'=> "accept_request" ,'notifications_status'=> 1);
  					$save_notifications = $this->profile_model->save_notifications($notification_data);

					if(!empty($user_device_details)) {
  	
  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
  						$user_device_token = array($user_device_details['logs_device_token']);
	  					if(!empty($user_device_token)) {
	  						$msg = array (
											'title' => "You have a new notification.",
											'message' => $user_name." accepted your request.",
											'notifications_type' => "accept_request",
											'notifications_id' => $save_notifications['insert_id'],
											'notifications_from_id' => $data['users_id']
										);
	  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$data['friend_id']);
	  					}
					}

    				$response = array("status"=>"true","status_code"=>"200","message"=>$friend_data['message']);
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>$friend_data['message']);
    			}
    		}
    		else if($data['api_action'] == "cancel") {

				$friend_data = $this->profile_model->user_cancel_friend($data);
    			if($friend_data['status'] == "true") {
    				$response = array("status"=>"true","status_code"=>"200","message"=>$friend_data['message']);
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>$friend_data['message']);
    			}
    		}
    		else if($data['api_action'] == "unfriend") {

				$friend_data = $this->profile_model->user_remove_friend($data);
    			if($friend_data['status'] == "true") {
    				$response = array("status"=>"true","status_code"=>"200","message"=>$friend_data['message']);
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>$friend_data['message']);
    			}
    		}
    		else if($data['api_action'] == "request_sent") {

				$friend_data = $this->profile_model->user_request_sent($data);
    			if($friend_data['status'] == "true") {
    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$friend_data['data'],"message"=>"Listed successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No request sent");
    			}
    		}
    		else if($data['api_action'] == "request_received") {

				$friend_data = $this->profile_model->user_request_received($data);
    			if($friend_data['status'] == "true") {
    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$friend_data['data'],"message"=>"Listed successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No request received");
    			}
    		}
    		else if($data['api_action'] == "friendlist") {

				$friend_data = $this->profile_model->user_friendlist($data);

				if($friend_data['status'] == "true") {

					if(!empty($data['action_id'])) {
						
						$friends_data = array();
	
						if(!empty($friend_data['user_frndlist'])) {

							$user_id_column = array_column($friend_data['user_frndlist'],'user_id');

							foreach ($friend_data['action_frndlist'] as $key => $value) {

								if(!in_array($value['user_id'],$user_id_column)) {
									$value['friends_status'] = '';
									$friends_data['friends_list'][] = $value;
								}
								else {
									$mkey = array_search($value['user_id'], $user_id_column);

									if($friend_data['user_frndlist'][$mkey]['friends_status'] == 1) 
									{
										$value['friends_status'] = ($friend_data['user_frndlist'][$mkey]['user_request'] == "sender") ? "1" : "2";
										$friends_data['friends_list'][] = $value;
									}
									else {
										// Check this user mutual is enabled or not in user settings
										if($friend_data['mutual_status'] == 1){
											
											$value['friends_status'] = "4";
											$friends_data['friends_list'][] = $value;
										}
									}
								}
							}

							$friendslist['friends_list'] = array();

							if(!empty($friends_data['friends_list'])) {

								$arr = uasort($friends_data['friends_list'], function($a, $b){
								    // return strcmp($a['friends_status'], $b['friends_status']);
    								return $a['friends_status'] <= $b['friends_status'];
								});
								$friendslist['friends_list'] = array_values($friends_data['friends_list']);
							}		

							$response = array("status"=>"true","status_code"=>"200","server_data"=>$friendslist,"message"=>"Listed successfully");
						}
						else if(!empty($friend_data['action_frndlist'])) {

							$friends_data['friends_list'] = array_map(function($arr) { return $arr + array('friends_status' => '','turfmate_status' => ''); }, $friend_data['action_frndlist']);
							$response = array("status"=>"true","status_code"=>"200","server_data"=> $friends_data,"message"=>"Listed successfully");
						}
						else {
							$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
						}
					}
					else {
						
						$friends_data = array();
						
						if(!empty($friend_data['data'])) {

							foreach ($friend_data['data'] as $key => $value) {

								if(!empty($value['friends_status'])) {
				    				if($value['friends_status'] == 1) {
				    					$value['friends_status'] = ($data['users_id'] == $value['sender_id']) ? "1" : "2";
				    				}
				    				else {
				    					$value['friends_status'] = "3";
				    				}
					   			}
					   			unset($value['sender_id']);
								$friends_data['friends_list'][] = $value;
							}
							$response = array("status"=>"true","status_code"=>"200","server_data"=>$friends_data,"message"=>"Listed successfully");
						}
						else {
							$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
						}
					}

    				// 1-request sent, 2. ready to accept, 3- friends, empty- none


					// $friends_data = array();
	
					// if(!empty($friend_data['mutual_frndlist'])) {
					// 	$user_id_column = array_column($friend_data['mutual_frndlist'],'user_id');
					// 	foreach ($friend_data['friendslist'] as $key => $value) {

					// 		if(!in_array($value['user_id'],$user_id_column)) {
					// 			$value['friends_status'] = '';
					// 			$friends_data['friends_list'][] = $value;
					// 		}
					// 		else {
					// 			$mkey = array_search($value['user_id'], $user_id_column);

					// 			if($friend_data['mutual_frndlist'][$mkey]['friends_status'] == 1) 
					// 			{
					// 				$value['friends_status'] = ($friend_data['mutual_frndlist'][$mkey]['user_request'] == "sender") ? 1 : 2;
					// 				$friends_data['friends_list'][] = $value;
					// 			}
					// 			else {
					// 				$value['friends_status'] = 3;
					// 				$friends_data['mutual_friends'][] = $value;
					// 			}
					// 		}
					// 	}
					// }
					// else if(!empty($friend_data['friendslist'])) {
					// 	$friends_data['friends_list'] = array_map(function($arr) { return $arr + array('friends_status' => ''); }, $friend_data['friendslist']);
					// }
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>$friend_data['message']);
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

	/* ==========         User calender events        ============= */
	public function user_calender_events()
	{ 

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->profile_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		$calender_events = $this->profile_model->user_calender_events($data);

    		if($calender_events['status'] == "true") {
    			$response = array("status"=>"true","status_code"=>"200","server_data"=>$calender_events,"message"=>"Listed successfully");
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

	/* ==========         Update user lattitude and longitude        ============= */
	public function update_user_location()
	{ 

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->profile_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}
    		
    		$user_id = $data['users_id'];
    		unset($data['unique_id'],$data['users_id']);
    		$update_location = $this->profile_model->user_location_update($data,$user_id);

    		if($update_location['status'] == "true") {
    			$response = array("status"=>"true","status_code"=>"200","message"=>"Updated successfully");
    		}
    		else {
    			$response = array("status"=>"false","status_code"=>"400","message"=>"Error in updation process");
    		}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	// Test notification - commented by siva	
	// public function sample_push_notification() {

	// 	$android_device_token = array('fYfeYpelFhI:APA91bH7pbVkdcDmwTktWeG7jOl9R4Y2yxRY_5aBMjtzMKpn8FT6elEciOJFBI0VXAO_hl9TKyGB09qwsBVRxbR-0BzWfB0IEPCEv-uZHhVssOxg3kNnhKEBTnhFTEXZRNc9OsOBtLPe');
	// 	// $ios_device_token = array('0edb34cbabdeb0297a43f2a5946614b2b6b1239bd458ca3e7cf1ccda881fa870','0edb34cbabdeb0297a43f2a5946614b2b6b1239bd458ca3e7cf1ccda881fa87011','0edb34cbabdeb0297a43f2a5946614b2b6b1239bd458ca3e7cf1ccda881fa870');
	// 	$ios_device_token = array('0edb34cbabdeb0297a43f2a5946614b2b6b1239bd458ca3e7cf1ccda881fa870');
	// 	$msg = array (
	// 					'message' => "Sample message",
	// 					'title' => "Sample Title",
	// 					'notify_action' => "sample_notify"
	// 				);

	// 	// $android_send_notification = $this->common->single_push_notification_service('android',$android_device_token,$msg,'1');
	// 	$ios_send_notification = $this->common->push_notification_service('ios',$ios_device_token,$msg);
	// }

	
} // End profile controller
