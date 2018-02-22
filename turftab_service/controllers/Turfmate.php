<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Turfmate extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
        // $this->load->library('form_validation');
		$this->load->model('turfmate_model');
        $this->load->library('../controllers/common');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ============       Turfmate Questions API       ===================== */
	public function user_turfmate_questions() {

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->turfmate_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "new_list") {

    			$questions_list = $this->turfmate_model->user_questions_list($data);

    			$question_answer_list = array();
    			$answerd = false;

    			foreach ($questions_list as $q_key => $q_val) {

    				$option_list = json_decode($q_val['options'],true);

    				if(!empty($q_val['answer_id'])) {

    					$answerd = true; 					
    					$ans_id = $q_val['answer_id'];
    					$q_val['ques_ans_list'] = array_map( function($arr) use ($ans_id) { 
    						if($arr['id'] == $ans_id)
    							return $arr + ['checked'=>true];
    						else
    							return $arr + ['checked'=>false];
    					}, $option_list);
    				}
    				else {
    					$q_val['ques_ans_list'] = array_map( function($arr) { 
								return $arr + ['checked'=>false];
    					}, $option_list);
    				}
    				unset($q_val['options'],$q_val['answer_id']);
    				$question_answer_list[] = $q_val;  				
    			}

    			if(!empty($question_answer_list)) {
    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$question_answer_list,"answerd"=>$answerd,"message"=>"Listed successfully");
    			}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
				}
    		}
    // 		else if($data['api_action'] == "new_answers") {

    // 			$new_answers = $this->turfmate_model->user_answers_new($data);

    // 			if($new_answers['status'] == "true") {

				// 	$response = array("status"=>"true","status_code"=>"200","message"=>"Answers updated successfully");
		 	// 	}
				// else {
				// 	$response = array("status"=>"false","status_code"=>"400","message"=>"You are already answered");
				// }
    // 		}
    // 		else if($data['api_action'] == "prior_list") {

    // 			$prior_list = $this->turfmate_model->user_prior_list($data);

    // 			if($prior_list['status'] == "true") {

    // 				$list_data = array_map(function($arr) { $options = json_decode($arr['options'],true); unset($arr['options']); return $arr+['options'=>$options]; }, $prior_list['data']);
				// 	$response = array("status"=>"true","status_code"=>"200","server_data"=>$list_data,"message"=>"Answers updated successfully");
		 	// 	}
				// else {
				// 	$response = array("status"=>"false","status_code"=>"400","message"=>"You are already answered");
				// }
	   // 		}
    		else if($data['api_action'] == "update_answers") {

    			$update_answers = $this->turfmate_model->user_update_answers($data);

    			if($update_answers['status'] == "true") {

					$response = array("status"=>"true","status_code"=>"200","message"=>"Answers updated successfully");
		 		}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion/updation process");
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
	
	/* ============         Turfmate matching list       ===================== */
	public function user_turfmate_userlist() {

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->turfmate_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		$userlist = $this->turfmate_model->user_turfmate_list($data);

    		if(!empty($userlist)) {
    			$userlist_with_album = array();	
    			
    			foreach ($userlist as $t_key => $t_val) {

					if(!isset($userlist_with_album[$t_val['match_user_id']]))
					{
						$album_id = $t_val['album_id'];
						$album_path = $t_val['albums_path'];
						unset($t_val['match_precent'],$t_val['friend_status'],$t_val['album_id'],$t_val['user_id'],$t_val['albums_path']);
						if(!empty($album_id)) {
							$userlist_with_album[$t_val['match_user_id']] = $t_val;



							$userlist_with_album[$t_val['match_user_id']]['album'][] = array('albums_id'=>$album_id,"albums_path"=>$album_path);
						}
						else {
							$userlist_with_album[$t_val['match_user_id']] = $t_val;
							$userlist_with_album[$t_val['match_user_id']]['album'] = array();
						}		
					}
					else {

						if($t_val['file_type'] == 2) {

							$video_url = $t_val['albums_path'];
							$video_file_name = str_replace(UPLOADS."turfmate_album/","",$t_val['albums_path']);
							$file_name_split = explode('.',$video_file_name);
							$t_val['albums_path'] = UPLOADS."turfmate_album/".$file_name_split[0]."_thumb.png";
							$userlist_with_album[$t_val['match_user_id']]['album'][] = array('albums_id'=>$t_val['album_id'],"albums_path"=>$t_val['albums_path'],'file_type'=>$t_val['file_type'],'video_url'=>$video_url);
						}
						else {
							$userlist_with_album[$t_val['match_user_id']]['album'][] = array('albums_id'=>$t_val['album_id'],"albums_path"=>$t_val['albums_path'],"file_type"=>$t_val['file_type']);
						}
					}
  				}

    			$response = array("status"=>"true","status_code"=>"200","server_data"=>array_values($userlist_with_album),"message"=>"Listed successfully");
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

	/* ============         Turfmate matching list       ===================== */
	public function user_turfmate_profile() {

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
			$unique_id_check = $this->turfmate_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

	    	if($data['api_action'] == "view") {

	    		$turfmate_profile = $this->turfmate_model->user_turfmate_profile($data);

	    		if(!empty($turfmate_profile)) {

	    			$user_profile_data = array();
		  			$user_profile_data = $turfmate_profile[0];

		  			unset($user_profile_data['albums_id'],$user_profile_data['albums_path'],$user_profile_data['file_type']);

					$user_profile_data['album'] = array();
	    
	    // 			foreach ($turfmate_profile as $key => $value) {
					// 	foreach ($value as $k => $v) {
					// 		if(empty($v)) $user_profile_data[$k] = '';
					// 		if($k == "albums_id" || $k == "albums_path" ||  $k == "file_type") {
					// 			if(!empty($v)) $user_profile_data['album'][$key][$k] = $v;
					// 			unset($user_profile_data[$k]);
					// 		}
					// 	}
					// }

					foreach ($turfmate_profile as $al_key => $al_val) {
		
						if(!empty($al_val['albums_id'])) {

							if($al_val['file_type'] == 2) {

								$video_url = $al_val['albums_path'];
								$video_file_name = str_replace(UPLOADS."turfmate_album/","",$al_val['albums_path']);
								$file_name_split = explode('.',$video_file_name);
								$al_val['albums_path'] = UPLOADS."turfmate_album/".$file_name_split[0]."_thumb.png";
								$user_profile_data['album'][] = array('albums_id'=>$al_val['albums_id'],'albums_path'=>$al_val['albums_path'],'file_type'=>$al_val['file_type'],'video_url'=>$video_url);
							}
							else {
								$user_profile_data['album'][] = array('albums_id'=>$al_val['albums_id'],'albums_path'=>$al_val['albums_path'],'file_type'=>$al_val['file_type']);
							}
						}
    				}

    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$user_profile_data,"message"=>"Listed successfully");
	    		}
	    		else {
	    			$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
	    		}
	    	}	
	    	else if($data['api_action'] == "update") {

	   //  		if(!is_dir('./'.UPLOADS.'turfmate_profile/')) {
				// 	mkdir('./'.UPLOADS.'turfmate_profile/',0777,true);
				// 	// 1- execute 2- write 4- read
				// 	// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				// }

	    		if(!empty($_FILES['user_turfmate_image'])) {

	    			$file_ext = ".".strtolower(end((explode('.',$_FILES['user_turfmate_image']['name']))));
					$file_name   = time().mt_rand(000,999).$file_ext;
					$config['upload_path'] = "./".UPLOADS."turfmate_profile/";
				    $config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->load->library('upload', $config);
					if ($this->upload->do_upload('user_turfmate_image')) {
						$filepath = $config['upload_path'].$file_name;
						$create_thumb = $this->common->create_thumb($filepath);
						$file_path = str_replace("./", "", $filepath);
         			}
         			$update_data['user_turfmate_image'] = $file_path;
         			// Delete original profile image
         			if(!empty($data['pre_turfmate_image'])) {
         				if(file_exists("./".$data['pre_turfmate_image'])) unlink("./".$data['pre_turfmate_image']);	
         				// Delete thumbnail of profile image
         				// $file_ext = ".".end((explode('.',$data['pre_turfmate_image'])));
         				// $unlink_thumb = str_replace($file_ext, "_thumb$file_ext", $data['pre_turfmate_image']);
         				// if(file_exists($unlink_thumb)) unlink($unlink_thumb);
         			}
         			$turfmate_profile_update = $this->turfmate_model->user_turfmate_profile_update($update_data,$data['users_id']);
    				$response = array("status"=>"true","status_code"=>"200","message"=>"Updated successfully");
	    		}
	    		else {
	    			$response = array("status"=>"false","status_code"=>"400","message"=>"Kindly upload turfmate profile image");
	    		}
	    	}
	    	else{
	    		$response = array("status"=>"false","status_code"=>"400","message"=>"Keyword mismatch");
	    	}   		
    	}
    	else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);    	
	}

	/* ==========         Turfmate album        ============= */
	public function user_turfmate_album()
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
			$unique_id_check = $this->turfmate_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "add") {

    			$album_insert_data = array();
    			// album type - image or video
    			$album_type = (!empty($data['file_type'])) ? $data['file_type'] : 'image';

    			// if(!is_dir('./'.UPLOADS.'turfmate_album/')) {
				// 	mkdir('./'.UPLOADS.'turfmate_album/',0777,true);
				// 	// 1- execute 2- write 4- read
				// 	// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				// }

    			if(!empty($_FILES['turfmate_album'])) {
    				$this->load->library('upload');
					
   					// Single upload
			        $file_ext = ".".strtolower(end((explode('.',$_FILES['turfmate_album']['name']))));
			        $file_name_random   = time().mt_rand(00,99);
	   				$file_name   = $file_name_random.$file_ext;

			        $config['upload_path'] = "./".UPLOADS."turfmate_album/";
					$config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->upload->initialize($config);
					if($this->upload->do_upload('turfmate_album')) {

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
						$album_insert_data[0]['album_type'] = 2;
       				}
    			}
    			$insert_turfmate_album = $this->turfmate_model->insert_turfmate_album($album_insert_data);
    			if(!empty($insert_turfmate_album)) {

					if($album_type == "video") {
    					$insert_turfmate_album['album_data'][0]['video_url'] = $insert_turfmate_album['album_data'][0]['albums_path'];
    					$insert_turfmate_album['album_data'][0]['albums_path'] = $video_thumb;
    				}

	   				$response = array("status"=>"true","status_code"=>"200","server_data"=>$insert_turfmate_album['album_data'],"message"=>"Uploaded successfully");	
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in uploading process");
    			}
			}
			else if($data['api_action'] == "remove") {

				$album_ids = explode(',',$data['turfmate_album']); // comma separeted list
				$album_remove = $this->turfmate_model->remove_turfmate_album($album_ids);

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

	/* ============         Turfmate matching like or dislike       ===================== */
	public function user_turfmate_invite() {

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->turfmate_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "like") {

    			$user_name = $data['loggedin_user_name'];
  				unset($data['loggedin_user_name']);

    			$turfmate_like_data = $this->turfmate_model->user_turfmate_like($data);

  				if($turfmate_like_data['status'] == "true") {

    				if(!empty($turfmate_like_data['accept'])) {

						$user_profile_data = $this->turfmate_model->user_profile_data($data['users_id']);

	  					//	push notification
	  					$user_id = $data['like_user_id'];
	  					$user_device_details = $this->turfmate_model->get_users_device_details($user_id);

	  					// Save notifications
	  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $data['like_user_id'],'notifications_msg'=> " has accepted your turfmate request.",'notifications_type'=> "turfmate_request" ,'notifications_status'=> 1);
	  					$save_notifications = $this->turfmate_model->save_notifications($notification_data);

	  					if(!empty($user_device_details)) {

	  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
	  						$user_device_token = array($user_device_details['logs_device_token']);

			  				if(!empty($user_device_token)) {
				  				$msg = array (
											'title' => "You have a new notification.",
											'message' => $user_name." has accepted your turfmate request.",
											'notifications_type' => "turfmate_request",
											'notifications_id' => $save_notifications['insert_id'],
											'notifications_from_id' => $data['users_id'],
											'user_name' => $user_name,
											'user_turfmate_image' => $user_profile_data['user_turfmate_image'],
											);
		  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$data['like_user_id']);
		  					}
						}
					}

    				$response = array("status"=>"true","status_code"=>"200","message"=>$turfmate_like_data['message']);
    			}
    			else {

    				$subscription_status = (!empty($turfmate_like_data['subscription']) && $turfmate_like_data['subscription'] == "false") ? false : true;
    				$response = array("status"=>"false","status_code"=>"400","message"=>$turfmate_like_data['message'],"subscription"=>$subscription_status);
    			}
    		}
    		else if($data['api_action'] == "dislike") {

    			$turfmate_dislike_data = $this->turfmate_model->user_turfmate_dislike($data);

    			if($turfmate_dislike_data['status'] == "true") {
    				$response = array("status"=>"true","status_code"=>"200","message"=>$turfmate_dislike_data['message']);
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>$turfmate_dislike_data['message']);
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

	
} // End profile controller
