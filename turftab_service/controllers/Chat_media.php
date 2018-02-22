<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_media extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
		$this->load->model('chat_media_model');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ==========         To store media files in server and database         ============= */
	public function user_chat_media()
	{

		$data = $this->input->post();

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->chat_media_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		// file upload
    		if($data['chat_media_type'] != 6 && $data['chat_media_type'] != 1) { 

    			$media_data['chat_media_path'] = '';
    			$media_folder_name = ($data['chat_media_type'] == 2) ? "image" : (($data['chat_media_type'] == 3) ? "video" : (($data['chat_media_type'] == 4) ? "audio" : (($data['chat_media_type'] == 5) ? "gif" : (($data['chat_media_type'] == 6) ? "location" : "document"))));

    			if(!is_dir('./'.UPLOADS.'chat_media/'.$media_folder_name.'/')) {
					mkdir('./'.UPLOADS.'chat_media/'.$media_folder_name.'/',0777,true);
					// 1- execute 2- write 4- read
					// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				}

				if(!empty($_FILES['chat_media'])) {

					$file_path = '';
					$file_ext = ".".strtolower(end((explode('.',$_FILES['chat_media']['name']))));
					$file_name_random = time().mt_rand(00,99);
					$file_name   = $file_name_random.$file_ext;

				    $config['upload_path'] = "./".UPLOADS."chat_media/".$media_folder_name."/";
					$config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->load->library('upload',$config);
				}
    		}

			// image
    		if($data['chat_media_type'] == 2) {

				if($this->upload->do_upload('chat_media')) {

					$file_path = $config['upload_path'].$file_name;
					$file_thumb_path = $config['upload_path'].$file_name_random."_thumb".$file_ext;

					$config['image_library']  = 'gd2';
					$config['source_image']   = $file_path;
					$config['create_thumb']   = TRUE;
					$config['maintain_ratio'] = TRUE;
					$config['width']          = 200;
					$config['height']         = 200;
					$config['thumb_marker']   = "_thumb";
					$this->load->library('image_lib', $config);
					$this->image_lib->resize();
					$media_data['chat_media_path'] = str_replace("./", "", $file_path);
					$thumb_path = str_replace("./", "", $file_thumb_path);
				}
				
				$media_data['chat_media_type'] = $data['chat_media_type'];
				$media_data['chat_media_status'] = 1;
				$media_data['users_id'] = $data['users_id'];
		
    			$media_save = $this->chat_media_model->insert_chat_media($media_data);
    			if($media_save) {
					$response = array("status"=>"true","status_code"=>"200","server_data"=>array('chat_media_type'=>$data['chat_media_type'],'chat_media_path'=>$media_data['chat_media_path'],'media_thumb_path'=>$thumb_path),"message"=>"Uploaded successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion process");
    			}
    		}
    		// video
    		else if($data['chat_media_type'] == 3) {

				if($this->upload->do_upload('chat_media')) {

					$file_path = $config['upload_path'].$file_name;
					
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
					$media_data['chat_media_path'] = str_replace("./", "", $file_path);
					$thumb_path = str_replace("./", "", $imagefile);
				}
				
				$media_data['chat_media_type'] = $data['chat_media_type'];
				$media_data['chat_media_status'] = 1;
				$media_data['users_id'] = $data['users_id'];
		
    			$media_save = $this->chat_media_model->insert_chat_media($media_data);
    			if($media_save) {
					$response = array("status"=>"true","status_code"=>"200","server_data"=>array('chat_media_type'=>$data['chat_media_type'],'chat_media_path'=>$media_data['chat_media_path'],'media_thumb_path'=>$thumb_path),"message"=>"Uploaded successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion process");
    			}
    		}
    		// audio
    		else if($data['chat_media_type'] == 4) {

    			if($this->upload->do_upload('chat_media')) {

					$file_path = $config['upload_path'].$file_name;
					$media_data['chat_media_path'] = str_replace("./", "", $file_path);

				 // 	$ffmpeg = 'c://ffmpeg//bin//ffmpeg'; // for localhost
			  //       $cmd = "$ffmpeg -i $file_path -hide_banner 2>&1";
					// $ffmpeg_exec = shell_exec($cmd);
					// if($ffmpeg_exec) {
					// 	$search = "/Duration: (.*?)\./";
					// 	preg_match($search, $ffmpeg, $matches);
					// 	$audio_duration = $matches[1];
					// 	$time_sec = explode(':', $audio_duration);
					// 	$audio_duration = ($time_sec['0']*3600)+($time_sec['1']*60)+$time_sec['2'];
					// }
				}
				
				$media_data['chat_media_type'] = $data['chat_media_type'];
				$media_data['chat_media_status'] = 1;
				$media_data['users_id'] = $data['users_id'];
		
    			$media_save = $this->chat_media_model->insert_chat_media($media_data);
    			if($media_save) {
					$response = array("status"=>"true","status_code"=>"200","server_data"=>array('chat_media_type'=>$data['chat_media_type'],'chat_media_path'=>$media_data['chat_media_path']),"message"=>"Uploaded successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion process");
    			}
    		}
    		// gif
    		else if($data['chat_media_type'] == 5) {

    			if($this->upload->do_upload('chat_media')) {

					$file_path = $config['upload_path'].$file_name;
					
					// // FFMPEG
					// $ffmpeg = 'c://ffmpeg//bin//ffmpeg'; // for localhost
					// // $ffmpeg = '/usr/bin/ffmpeg'; // for server
					// $videofile = $file_path;
					// $imagefile = $config['upload_path'].$file_name_random."_thumb".".png";
					// $size = "200x180";
					// $getfromsecond = "1";
					// $cmd = "$ffmpeg -i $videofile -an -ss $getfromsecond -s $size $imagefile";	
					// $exec = shell_exec($cmd);
					// if(!$exec)
					// {
					// 		echo "true";
					// }
					// else
					// {
					// 		echo "false";
					// }
					$media_data['chat_media_path'] = str_replace("./", "", $file_path);
				}
				
				$media_data['chat_media_type'] = $data['chat_media_type'];
				$media_data['chat_media_status'] = 1;
				$media_data['users_id'] = $data['users_id'];
		
    			$media_save = $this->chat_media_model->insert_chat_media($media_data);
    			if($media_save) {
					$response = array("status"=>"true","status_code"=>"200","server_data"=>array('chat_media_type'=>$data['chat_media_type'],'chat_media_path'=>$media_data['chat_media_path']),"message"=>"Uploaded successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion process");
    			}    			
    		}
    		// location
    		else if($data['chat_media_type'] == 6) {

				$media_data['chat_media_path'] = $data['chat_media'];
				$media_data['chat_media_type'] = $data['chat_media_type'];
				$media_data['chat_media_status'] = 1;
				$media_data['users_id'] = $data['users_id'];
		
    			$media_save = $this->chat_media_model->insert_chat_media($media_data);
    			if($media_save) {
					$response = array("status"=>"true","status_code"=>"200","server_data"=>array('chat_media_type'=>$data['chat_media_type'],'chat_media_path'=>$media_data['chat_media_path']),"message"=>"Uploaded successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion process");
    			}
    		}
    		// document
    		else if($data['chat_media_type'] == 7) {

    			if($this->upload->do_upload('chat_media')) {

					$file_path = $config['upload_path'].$file_name;
					$media_data['chat_media_path'] = str_replace("./", "", $file_path);
				}
				
				$media_data['chat_media_type'] = $data['chat_media_type'];
				$media_data['chat_media_status'] = 1;
				$media_data['users_id'] = $data['users_id'];
		
    			$media_save = $this->chat_media_model->insert_chat_media($media_data);
    			if($media_save) {
					$response = array("status"=>"true","status_code"=>"200","server_data"=>array('chat_media_type'=>$data['chat_media_type'],'chat_media_path'=>$media_data['chat_media_path']),"message"=>"Uploaded successfully");
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

	
	
} // End chat_media controller