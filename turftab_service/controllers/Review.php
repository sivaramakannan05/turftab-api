<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Review extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
        // $this->load->library('form_validation');
		$this->load->model('review_model');
        $this->load->library('../controllers/common');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ============         User Review list          =========== */
	public function user_review_list()
	{

		$data = json_decode(file_get_contents("php://input"),true);		

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->review_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		$review_list = $this->review_model->user_review_list($data);

     		if($review_list['status'] == "true") {

				$result['event'] = $review_list['event_data'];

				$reviews_data['event_review'] = array();
				$reviews_data['host_review'] = array();

				if(!empty($review_list['reviews_data'])) {

					foreach($review_list['reviews_data'] as $rev_key => $rev_val) {

						$review_type = ($rev_val['reviews_type'] == 1) ? 'event_review' : 'host_review';
						$review_id = $rev_val['reviews_id'];

						if(!isset($reviews_data[$review_type][$review_id])) {

							if(!empty($rev_val['reply_id'])) {

								// $reply_data = array('reply_id'=>$rev_val['reply_id'],'reply'=>$rev_val['reply'],'reply_created_date'=>$rev_val['reply_created_date'],'user_id'=>$rev_val['admin_user_id'],'user_fullname'=>$rev_val['admin_user_fullname'],'user_name'=>$rev_val['admin_user_name'],'user_profile_image'=>$rev_val['admin_user_profile_image']);
								$reply_data = $rev_val['reply']."/n";
								unset($rev_val['reply_id'],$rev_val['reply'],$rev_val['reply_created_date'],$rev_val['admin_user_id'],$rev_val['admin_user_fullname'],$rev_val['admin_user_name'],$rev_val['admin_user_profile_image']);

								$reviews_data[$review_type][$review_id] = $rev_val;
								$reviews_data[$review_type][$review_id]['reply_data'][] = $reply_data;
							}
							else {
								unset($rev_val['reply_id'],$rev_val['reply'],$rev_val['reply_created_date'],$rev_val['admin_user_id'],$rev_val['admin_user_fullname'],$rev_val['admin_user_name'],$rev_val['admin_user_profile_image']);
								$reviews_data[$review_type][$review_id] = $rev_val;
								$reviews_data[$review_type][$review_id]['reply_data'] = array();
							}					
						}
						else {

							unset($rev_val['reviews_id'],$rev_val['review'],$rev_val['reviews_type'],$rev_val['reviews_created_date'],$rev_val['user_id'],$rev_val['user_fullname'],$rev_val['user_name'],$rev_val['user_profile_image']);
							$reviews_data[$review_type][$review_id]['reply_data'][] = $rev_val['reply']."/n";
						}
					}
				}

				$result['event_review'] = array_reverse(array_values($reviews_data['event_review']));
				$result['host_review'] = array_reverse(array_values($reviews_data['host_review']));

				$response = array("status"=>"true","status_code"=>"200","server_data"=>$result,"message"=>"Listed successfully");
	   		}
    		else {
    			$response = array("status"=>"false","status_code"=>"400","message"=>$review_list['message']);
	  		}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}

	/* ============         User Review          =========== */
	public function user_review() {

		$data = json_decode(file_get_contents("php://input"),true);

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->review_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}
			
			if($data['api_action'] == "post_review") {

    			$user_name = $data['loggedin_user_name'];
  				unset($data['loggedin_user_name']);

    			$check_review_status = $this->review_model->check_review_status($data);
    			if($check_review_status == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"You can't able to post your review");
    				echo json_encode($response);
    				exit;
    			}

    			$insert_review_data = array("users_id"=>$data['users_id'],"events_id"=>$data['event_id'],"comments"=>$data['comments'],"event_user_id"=>$data['event_user_id'],"reviews_type"=>$data['reviews_type'],"reviews_status"=>1);
	   			$insert_review = $this->review_model->insert_review($insert_review_data,$data['event_user_id']);

	   			if($insert_review['status'] == "true") {

	   				//	push notification
  					$user_id = $data['event_user_id'];
  					$user_device_details = $this->review_model->get_users_device_details($user_id);

  					// Save notifications
  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $data['event_user_id'],'notifications_msg'=> "has posted review on your event.",'notifications_event_id'=>$data['event_id'],'event_review_type'=> $data['reviews_type'],'notifications_type'=> "event_review" ,'notifications_status'=> 1);
  					$save_notifications = $this->review_model->save_notifications($notification_data);

  					if(!empty($user_device_details)) {

  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
  						$user_device_token = array($user_device_details['logs_device_token']);

		  				if(!empty($user_device_token)) {
			  				$msg = array (
										'title' => "You have a new notification",
										'message' => $user_name." has posted review on your event.",
										'notifications_type' => "event_review",
										'notifications_id' => $save_notifications['insert_id'],
										'notifications_from_id' => $data['users_id'],
										'notifications_event_id' => $data['event_id'],
										'event_user_id' => $data['event_user_id'],
										'event_review_type' => $data['reviews_type']
										);

			  				// add event id, event user id, review type
	  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$data['event_user_id']);
	  					}
					}
					$response = array("status"=>"true","status_code"=>"200","insert_id"=>$insert_review['insert_id'],"message"=>"Your review has been posted successfully");
	   			}
	   			else {
	   				$response = array("status"=>"false","status_code"=>"400","message"=>"Something went wrong. Please try again later");
	   			}
    		}
    		else if($data['api_action'] == "delete_review") {

    			$delete_review = $this->review_model->delete_review($data);

    			if($delete_review['status'] == "true") {
    				$response = array("status"=>"true","status_code"=>"200","message"=>"Deleted successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>$delete_review['message']);
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

	/* ============         Event Owner Reply          =========== */
	public function user_reply() {

		$data = json_decode(file_get_contents("php://input"),true);		

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->review_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		if($data['api_action'] == "admin_reply") {

    			$user_name = $data['loggedin_user_name'];
  				unset($data['loggedin_user_name']);

    			$check_admin_event_status = $this->review_model->check_admin_event_status($data);
    			if($check_admin_event_status == 0) {
    				$response = array("status"=>"false","status_code"=>"400","message"=>"You can't reply to a post that is not active");
    				echo json_encode($response);
    				exit;
    			}

    			$insert_reply_data = array("users_id"=>$data['users_id'],"reviews_id"=>$data['reviews_id'],"comments"=>$data['comments'],"reply_status"=>1);
	   			$insert_reply = $this->review_model->insert_reply($insert_reply_data);

	   			if($insert_reply['insert_id'] != '') {

	   				//	push notification
  					$user_id = $this->review_model->get_user_id($data['reviews_id']);
  					$user_device_details = $this->review_model->get_users_device_details($user_id);

  					// Save notifications
  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $user_id,'notifications_event_id'=>$data['event_id'],'event_review_type'=> $data['reviews_type'],'notifications_msg'=> "replied to your review.",'notifications_type'=> "event_reply" ,'notifications_status'=> 1);
  					$save_notifications = $this->review_model->save_notifications($notification_data);

  					if(!empty($user_device_details)) {

  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
  						$user_device_token = array($user_device_details['logs_device_token']);

		  				if(!empty($user_device_token)) {
			  				$msg = array (
										'title' => "You have a new notification.",
										'message' => $user_name." replied to your review.",
										'notifications_type' => "event_reply",
										'notifications_id' => $save_notifications['insert_id'],
										'notifications_from_id' => $data['users_id'],
										'notifications_event_id' => $data['event_id'],
										'event_user_id' => $data['users_id'],
										'event_review_type' => $data['reviews_type']
										);
	  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$user_id);
	  					}
					}
					$response = array("status"=>"true","status_code"=>"200","insert_id"=>$insert_reply['insert_id'],"message"=>"Reply message has been updated successfully");
	   			}
	   			else {
	   				$response = array("status"=>"false","status_code"=>"400","message"=>"Error in insertion process");
	   			}
    		}
    		else if($data['api_action'] == "delete_reply") {

    			$delete_reply = $this->review_model->delete_reply($data);

    			if($delete_reply['status'] == "true") {
    				$response = array("status"=>"true","status_code"=>"200","message"=>"Deleted successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>$delete_reply['message']);
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

	// reviews view api is pending
	// to send the thumb image path of video and image
	
	
} // End review controller
