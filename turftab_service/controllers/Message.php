<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Message extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
		$this->load->model('message_model');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ==========         User send message        ============= */
	public function user_send_message()
	{

		$data = $this->input->post();

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->message_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
			}

    		if($data['api_action'] == "profile_chat") {

    			// Check login session
				$friend_status['users_id'] = $data['users_id'];
				$friend_status['friend_id'] = $data['friend_id'];
				$check_profile_friend_status = $this->message_model->check_profile_friend_status($block_chat);
				if($check_profile_friend_status == 1) {
					$response = array("status"=>"false","status_code"=>"400","message"=>"Message doesn't send");
					echo json_encode($response);
					exit;
				}

    			$file_path = '';

				// if(!is_dir('./'.UPLOADS.'profile_chat/')) {
				// 	mkdir('./'.UPLOADS.'profile_chat/',0777,true);
				// 	// 1- execute 2- write 4- read
				// 	// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				// }

				if($data['content_type'] != "text" && $data['content_type'] != "location") {

					if(!empty($_FILES['content'])) {

    					$this->load->library('upload');
						$file_ext = ".".strtolower(end((explode('.',$_FILES['content']['name']))));
						$file_name_random   = time().mt_rand(000,999);
						$file_name   = $file_name_random.$file_ext;
						$config['upload_path'] = "./".UPLOADS."profile_chat/";
				    	$config['allowed_types'] = '*';
						$config['file_name']   = $file_name;
						$this->upload->initialize($config);
						
						if($this->upload->do_upload('content')) {

							$filepath = $config['upload_path'].$file_name;
							$data['content'] = str_replace("./", "", $filepath);

							if($data['content_type'] == "video") {

	    						// FFMPEG
	    						$ffmpeg = 'c://ffmpeg//bin//ffmpeg'; // for localhost
								// $ffmpeg = '/usr/bin/ffmpeg'; // for server
								$videofile = $filepath;
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
	    					}
		    				else if($data['content_type'] == "image") {

		    					/* Create thumbnail image of album start */
		    					$this->load->library('image_lib');

								$config['image_library']  = 'gd2';
								$config['source_image']   = $filepath;
								$config['create_thumb']   = TRUE;
								$config['maintain_ratio'] = TRUE;
								$config['width']          = 100;
								$config['height']         = 100;
								$config['thumb_marker']   = "_thumb";
								$this->image_lib->initialize($config);
								$this->image_lib->resize();
								/* Create thumbnail image of album end */
		    				}
        				}
    				}
				}

    			$insert_message_data = array('conversation_from_id'=>$data['users_id'],'conversation_to_id'=>$data['friend_id'],'content'=>$data['content'],'content_type'=>$data['content_type'],'conversation_from_id_status'=>1,'conversation_to_id_status'=>1,'conversation_status'=>1);
    			$message_data = $this->message_model->insert_profile_message($insert_message_data);

    			if($message_data['status'] == "true") {

    				
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion process");
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

	
	
} // End message controller
