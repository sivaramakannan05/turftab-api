<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Event extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
        // $this->load->library('form_validation');
		$this->load->model('event_model');
        $this->load->library('../controllers/common');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ============         User events          =========== */
	public function user_event() {

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
			$unique_id_check = $this->event_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "create") {

    			$user_name = $data['loggedin_user_name'];
    			unset($data['unique_id'],$data['api_action'],$data['loggedin_user_name']);

    			// if(!is_dir('./'.UPLOADS.'event/')) {
				// 	mkdir('./'.UPLOADS.'event/',0777,true);
				// 	// 1- execute 2- write 4- read
				// 	// second parameter - First val is always zero,second one for owner,third one for owner user group,fourth one for everybody
				// }

    			if(!empty($_FILES['event_image'])) {

					$file_path = '';
				 	$file_ext = ".".strtolower(end((explode('.',$_FILES['event_image']['name']))));
					$file_name   = time().mt_rand(00,99).$file_ext;

			        $config['upload_path'] = "./".UPLOADS."event/";
					$config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->load->library('upload',$config);
					if($this->upload->do_upload('event_image')) {

						$file_path = $config['upload_path'].$file_name;
						$config['image_library']  = 'gd2';
						$config['source_image']   = $file_path;
						$config['create_thumb']   = TRUE;
						$config['maintain_ratio'] = TRUE;
						$config['width']          = 200;
						$config['height']         = 200;
						$config['thumb_marker']   = "_thumb";
						$this->load->library('image_lib', $config);
						$this->image_lib->resize();
					}
					$data['event_image'] = str_replace("./", "", $file_path);
    			}
    			$data['event_status'] = 1;
    			$data['event_updated_date'] = date('Y-m-d H:i:s');

    			$event_data = $this->event_model->create_user_events($data);

    			// push notification
				$user_ids = (!empty($event_data['invited_ids'])) ? explode(',',$event_data['invited_ids']) : array();
				
				if(!empty($user_ids)) {

					$message = ($data['event_type'] == 1) ? "invited you privately to his event." : " created a new event.";
					foreach ($user_ids as $uk => $uv) {
						
						// Save notifications
						$notification_data[] = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $uv,'notifications_event_id'=> $event_data['insert_id'],'notifications_msg'=> $message,'notifications_type'=> "event_create" ,'notifications_status'=> 1);
					}

					$save_notifications = $this->event_model->save_notifications_batch($notification_data);
					$user_device_details = $this->event_model->get_users_device_details($user_ids,"multiple");
					$notification_ids = (!empty($save_notifications['insert_ids'])) ? $save_notifications['insert_ids'] : array();

					if(!empty($user_device_details)) {

						$device_details = array();

						foreach ($user_device_details as $key => $value) {

							$notification_id_key = array_search($value['users_id'], array_column($notification_ids, 'user_id'));
							$value['notification_id'] = $notification_ids[$notification_id_key]['notifications_id'];

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
								'title' => "You have a new notification.",
								'message' => ($data['event_type'] == 1) ? $user_name." invited you privately to his event." : $user_name." created a new event.",
								'notifications_type' => "event_create",
								'notifications_from_id' => $data['users_id'],
								'notifications_event_id' => $event_data['insert_id']
							);
							$send_notification = $this->common->multiple_push_notification_service($device_details,$msg);
						}
					}		
				}

    			$response = array("status"=>"true","status_code"=>"200","insert_id"=>$event_data['insert_id'],"message"=>"Event created successfully"); 			
    		}
    		else if($data['api_action'] == "update") {

    			$event_status = $this->event_model->check_event_status($data['event_id']);
    			if($event_status == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Event has been expired. You can't able to update");
    				echo json_encode($response);
    				exit;
    			}
		
				$user_name = $data['loggedin_user_name'];
    			unset($data['unique_id'],$data['api_action'],$data['loggedin_user_name']);

	  			if(!empty($_FILES['event_image'])) {

					$file_path = '';
				 	$file_ext = ".".strtolower(end((explode('.',$_FILES['event_image']['name']))));
					$file_name   = time().mt_rand(00,99).$file_ext;

			        $config['upload_path'] = "./".UPLOADS."event/";
					$config['allowed_types'] = '*';
					$config['file_name']   = $file_name;
					$this->load->library('upload',$config);
					if($this->upload->do_upload('event_image')) {

						$file_path = $config['upload_path'].$file_name;
						$config['image_library']  = 'gd2';
						$config['source_image']   = $file_path;
						$config['create_thumb']   = TRUE;
						$config['maintain_ratio'] = TRUE;
						$config['width']          = 200;
						$config['height']         = 200;
						$config['thumb_marker']   = "_thumb";
						$this->load->library('image_lib', $config);
						$this->image_lib->resize();
					}
					$data['event_image'] = str_replace("./", "", $file_path);

         			if(file_exists("./".$data['pre_event_image'])) unlink("./".$data['pre_event_image']);
         			// $file_ext = ".".end((explode('.',$data['pre_event_image'])));
         			// $unlink_thumb = str_replace($file_ext, "_thumb$file_ext", $data['pre_event_image']);
         			// if(file_exists($unlink_thumb)) unlink($unlink_thumb);
    			}

    			$event_id = $data['event_id'];
    			unset($data['unique_id'],$data['api_action'],$data['pre_event_image'],$data['event_id']);
    			$data['event_updated_date'] = date('Y-m-d H:i:s');

    			$event_data = $this->event_model->update_user_events($event_id,$data);

	   			// Push notificaiton to already invited users
    			if(!empty($event_data['already_invited_ids'])) {

    				$user_ids = explode(',',$event_data['already_invited_ids']);

					foreach ($user_ids as $uk => $uv) {
					
						// Save notifications
						$notification_data[] = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $uv,'notifications_event_id'=> $event_id,'notifications_msg'=> "has been updated his event.",'notifications_type'=> "event_update" ,'notifications_status'=> 1);
					}

					$save_notifications = $this->event_model->save_notifications_batch($notification_data);
					$user_device_details = $this->event_model->get_users_device_details($user_ids,"multiple");
					$notification_ids = (!empty($save_notifications['insert_ids'])) ? $save_notifications['insert_ids'] : array();

					$device_details = array();

					if(!empty($user_device_details)) {

						foreach ($user_device_details as $key => $value) {

							$notification_id_key = array_search($value['users_id'], array_column($notification_ids, 'user_id'));
							$value['notification_id'] = $notification_ids[$notification_id_key]['notifications_id'];

							if($value['logs_device_type'] == 1) {
							
								$device_details['android'][] = $value;
							}
							else {
								$device_details['ios'][] = $value;
							}
						}

						// Send notifications
						if(!empty($device_details)) {

							$msg = array (
								'title' => "You have a new notification.",
								'message' =>  $user_name." has been updated his event.",
								'notifications_type' => "event_update",
								'notifications_from_id' => $data['users_id'],
								'notifications_event_id' => $event_id
							);
							$send_notification = $this->common->multiple_push_notification_service($device_details,$msg);
						}
					}
	   			}

	   			// Push notificaiton to newly invited users
    			if(!empty($event_data['notification_ids'])) {

    				$user_ids = $event_data['notification_ids'];
    				$message = ($data['event_type'] == 1) ? $user_name." invited you privately to his event." : "created a event and invited you.";

					foreach ($user_ids as $uk => $uv) {
					
						// Save notifications
						$notification_data[] = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $uv,'notifications_event_id'=> $event_id,'notifications_msg'=> $message,'notifications_type'=> "event_update" ,'notifications_status'=> 1);
					}

					$save_notifications = $this->event_model->save_notifications_batch($notification_data);
					$user_device_details = $this->event_model->get_users_device_details($user_ids,"multiple");
					$notification_ids = (!empty($save_notifications['insert_ids'])) ? $save_notifications['insert_ids'] : array();

					$device_details = array();

					if(!empty($user_device_details)) {

						foreach ($user_device_details as $key => $value) {

							$notification_id_key = array_search($value['users_id'], array_column($notification_ids, 'user_id'));
							$value['notification_id'] = $notification_ids[$notification_id_key]['notifications_id'];

							if($value['logs_device_type'] == 1) {
							
								$device_details['android'][] = $value;
							}
							else {
								$device_details['ios'][] = $value;
							}
						}

						// Send notifications
						if(!empty($device_details)) {

							$msg = array (
								'title' => "You have a new notification.",
								'message' =>  ($data['event_type'] == 1) ? $user_name." invited you privately to his event." : "created a event and invited you.",
								'notifications_type' => "event_update",
								'notifications_from_id' => $data['users_id'],
								'notifications_event_id' => $event_id
							);
							$send_notification = $this->common->multiple_push_notification_service($device_details,$msg);
						}
					}
	   			}

  				$response = array("status"=>"true","status_code"=>"200","message"=>"Event updated successfully"); 			
    		}
    		else if($data['api_action'] == "current_events") {

    			$events_data = $this->event_model->user_current_events($data);

       			if(!empty($events_data['own_events']) || !empty($events_data['accepted_events']) || !empty($events_data['nearby_events'])) {

	 				$response = array("status"=>"true","status_code"=>"200","server_data"=>$events_data,"message"=>"Listed successfully");	
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
    			}			
	   		}
	   		else if($data['api_action'] == "past_events") {

    			$events_data = $this->event_model->user_past_events($data);

       			if(!empty($events_data['own_events']) || !empty($events_data['accepted_events'])) {

	 				$response = array("status"=>"true","status_code"=>"200","server_data"=>$events_data,"message"=>"Listed successfully");	
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
    			}				
	   		}
	   		else if($data['api_action'] == "details") {

    			$event_details_data = $this->event_model->user_events_details($data);
	
	   			if($event_details_data['status'] == "true") {

    				$event_details = $event_details_data['data'];
    				$event_details['comments'] = (!empty($event_details['event_comments'])) ? json_decode($event_details['event_comments'],true): array();
    				unset($event_details['event_comments']);
    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$event_details,"message"=>"Listed successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
    			}
	   		}
	   		else if($data['api_action'] == "interested") {

	   			$user_name = $data['loggedin_user_name'];
  				unset($data['loggedin_user_name']);

  				$event_status = $this->event_model->check_event_status($data['event_id']);
    			if($event_status == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Event has been expired. You can't able to change");
    				echo json_encode($response);
    				exit;
    			}

    			$event_accept_data = $this->event_model->user_events_interest($data);
				
				if($event_accept_data['status'] == "true") {

					//	push notification
  					$user_id = $data['event_user_id'];
  					$user_device_details = $this->event_model->get_users_device_details($user_id,"single");

  					// Save notifications
  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $user_id,'notifications_event_id'=> $data['event_id'],'notifications_msg'=> "interested on your event.",'notifications_type'=> "event_interested" ,'notifications_status'=> 1);
  					$save_notifications = $this->event_model->save_notifications($notification_data);

  					if(!empty($user_device_details)) {

  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
  						$user_device_token = array($user_device_details['logs_device_token']);

		  				if(!empty($user_device_token)) {
			  				$msg = array (
											'title' => "You have a new notification.",
											'message' => $user_name." interested on your event.",
											'notifications_type' => "event_interested",
											'notifications_id' => $save_notifications['insert_id'],
											'notifications_from_id' => $data['users_id'],
											'notifications_event_id' => $data['event_id']
										);
	  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$user_id);
	  					}
					}

					$response = array("status"=>"true","status_code"=>"200","message"=>"You are interested on the event");	
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>$event_accept_data['message']);
				}
    		}
    		else if($data['api_action'] == "not_interested") {

    			$event_status = $this->event_model->check_event_status($data['event_id']);
    			if($event_status == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Event has been expired. You can't able to update");
    				echo json_encode($response);
    				exit;
    			}

    			$event_accept_data = $this->event_model->user_events_not_interest($data);
				
				if($event_accept_data['status'] == "true") {
					$response = array("status"=>"true","status_code"=>"200","message"=>"You are not interested on the event");
				}
				else {
					$response = array("status"=>"false","status_code"=>"400","message"=>$event_accept_data['message']);
				}
    		}
    		else if($data['api_action'] == "add_comment") {

    			$event_status = $this->event_model->check_event_status($data['event_id']);
    			if($event_status == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Event has been expired. You can't able to post your comment");
    				echo json_encode($response);
    				exit;
    			}

	    		$user_name = $data['user_fullname'];

    			$event_comment_data = $this->event_model->user_events_add_comment($data);
    			
    			if($event_comment_data['status'] == "true") {

    				if($data['users_id'] != $data['event_user_id']) {

    					//	push notification
	  					$user_id = $data['event_user_id'];
	  					$user_device_details = $this->event_model->get_users_device_details($user_id,"single");

	  					// Save notifications
	  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $user_id,'notifications_event_id'=> $data['event_id'],'notifications_msg'=> "commented on your event.",'notifications_type'=> "event_comment" ,'notifications_status'=> 1);
	  					$save_notifications = $this->event_model->save_notifications($notification_data);

	  					if(!empty($user_device_details)) {

	  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
	  						$user_device_token = array($user_device_details['logs_device_token']);

			  				if(!empty($user_device_token)) {
				  				$msg = array (
												'title' => "You have a new notification.",
												'message' => $user_name." commented on your event.",
												'notifications_type' => "event_comment",
												'notifications_id' => $save_notifications['insert_id'],
												'notifications_from_id' => $data['users_id'],
												'notifications_event_id' => $data['event_id']
											);
		  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$user_id);
		  					}
						}
    				}

	   				$response = array("status"=>"true","status_code"=>"200","message"=>"Comment posted successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong. Please try again later");
    			}  			
	   		}
    		else if($data['api_action'] == "delete_comment") {

    			$event_comment_data = $this->event_model->user_events_delete_comment($data);
    			
    			if($event_comment_data['status'] == "true") {
    				$response = array("status"=>"true","status_code"=>"200","message"=>"Comment deleted successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
    			}  			
	   		}
	   		else if($data['api_action'] == "delete_all_comment") {

    			$event_comment_data = $this->event_model->user_events_delete_all_comment($data);
    			
    			if($event_comment_data['status'] == "true") {
    				$response = array("status"=>"true","status_code"=>"200","message"=>"Comments deleted successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
    			}  			
	   		}
	   		else if($data['api_action'] == "invite") {

	   			$user_name = (!empty($data['loggedin_user_name'])) ? $data['loggedin_user_name'] : 'user';
  				unset($data['loggedin_user_name']);

  				$event_status = $this->event_model->check_event_status($data['event_id']);
    			if($event_status == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"Event has been expired. You can't able to invite");
    				echo json_encode($response);
    				exit;
    			}

    			$event_invite_data = $this->event_model->invite_user_events($data);

    			if($event_invite_data['status'] == "true") {

    				// push notification
    				if(!empty($event_invite_data['notification_ids'])) {

    					$user_ids = $event_invite_data['notification_ids'];
					
						foreach ($user_ids as $uk => $uv) {
						
							// Save notifications
							$notification_data[] = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $uv,'notifications_event_id'=> $data['event_id'],'notifications_msg'=> "invited you to the event.",'notifications_type'=> "event_invite" ,'notifications_status'=> 1);
						}

						$save_notifications = $this->event_model->save_notifications_batch($notification_data);
						$user_device_details = $this->event_model->get_users_device_details($user_ids,"multiple");
						$notification_ids = (!empty($save_notifications['insert_ids'])) ? $save_notifications['insert_ids'] : array();

						if(!empty($user_device_details)) {

							$device_details = array();

							foreach ($user_device_details as $key => $value) {

								$notification_id_key = array_search($value['users_id'], array_column($notification_ids, 'user_id'));
								$value['notification_id'] = $notification_ids[$notification_id_key]['notifications_id'];

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
									'title' => "You have a new notification.",
									'message' => $user_name." invited you to the event.",
									'notifications_type' => "event_invite",
									'notifications_from_id' => $data['users_id'],
									'notifications_event_id' => $data['event_id']
								);
								$send_notification = $this->common->multiple_push_notification_service($device_details,$msg);
							}
						}		
	   				}
					
	  				$response = array("status"=>"true","status_code"=>"200","message"=>"Invited successfully");	
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>$event_invite_data['message']);
    			}
	   		}
	   		else if($data['api_action'] == "location_filter") {

    			// unset($data['unique_id'],$data['users_id'],$data['api_action']);

    			$location_based_events = $this->event_model->user_location_events_filter($data);
    			
    			if(!empty($location_based_events)) {
    				$response = array("status"=>"true","status_code"=>"200","server_data"=>$location_based_events,"message"=>"Listed successfully");
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

	/* ============         User events          =========== */
	public function user_event_invite_list() {

		$data = json_decode(file_get_contents("php://input"),true);		

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->event_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		$data['event_id'] = (!empty($data['event_id'])) ? $data['event_id'] : '';

    		$event_permission = $this->event_model->check_event_type($data['users_id'],$data['event_id']);

    		if($event_permission) {

    			$event_frndlist = $this->event_model->friendlist_by_user_id($data['users_id'],$data['event_id']);
	    		if(!empty($event_frndlist)) {
	    			$response = array("status"=>"true","status_code"=>"200","server_data"=>$event_frndlist,"message"=>"Listed successfully");
	    		}
	    		else {
	    			$response = array("status"=>"false","status_code"=>"400","message"=>"No records found");
	    		}
    		}
    		else {
    			$response = array("status"=>"false","status_code"=>"400","message"=>"You don't have access to invite for the event");
    		}   		
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	
	
} // End event controller
