<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Game extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
		$this->load->model('game_model');
	    $this->load->library('../controllers/common');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ============         Hangman game         =========== */
	public function user_game_hangman() {

		$data = json_decode(file_get_contents("php://input"),true);		

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->game_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		$user_name = (!empty($data['loggedin_user_name'])) ? $data['loggedin_user_name'] : "user";
  			unset($data['loggedin_user_name']);

    		if($data['api_action'] == "create") {

    			$insert_array = array('from_users_id'=>$data['users_id'],'to_users_id'=>$data['friend_id'],'hangman_word'=>$data['hangman_word'],'hangman_hint'=>$data['hangman_hint'],'hangman_status'=>1,'hangman_updated_date'=>date('Y-m-d H:i:s'));
    			$insert_hangman_data = $this->game_model->insert_hangman_task($insert_array);

    			if($insert_hangman_data['status'] == "true") {

    				//	push notification
  					$user_id = $data['friend_id'];
  					$user_device_details = $this->game_model->get_users_device_details($user_id);

  					// Save notifications
  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $data['friend_id'],'notifications_msg'=> "invited you to start the game.",'notifications_type'=> "game_hangman", "notifications_event_id"=>$insert_hangman_data['insert_id'],'notifications_status'=> 1);
  					$save_notifications = $this->game_model->save_notifications($notification_data);

  					if(!empty($user_device_details)) {

  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
  						$user_device_token = array($user_device_details['logs_device_token']);

		  				if(!empty($user_device_token)) {
			  				$msg = array (
										'title' => "You have a new notification.",
										'message' => $user_name." invited you to start the game.",
										'notifications_type' => "game_hangman",
										'notifications_id' => $save_notifications['insert_id'],
										'notifications_from_id' => $data['users_id'],
										'notifications_event_id' => $insert_hangman_data['insert_id']
										);
	  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$data['friend_id']);
	  					}
					}
    				
    				$response = array("status"=>"true","status_code"=>"200","insert_id"=>$insert_hangman_data['insert_id'],"message"=>"Hangman task created successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>$insert_hangman_data['message']);
    			}
	   		}
	   		else if($data['api_action'] == "details") {

	   			$hangman_details = $this->game_model->hangman_details($data['game_hangman_id']);

	   			if(!empty($hangman_details)) {
	   				$response = array("status"=>"true","status_code"=>"200","server_data"=>$hangman_details,"message"=>"Listed successfully");
	   			}
	   			else {
	   				$response = array("status"=>"false","status_code"=>"400","message"=>"No record(s) found");
	   			}
	   		}
	   		else if($data['api_action'] == "update") {

	   			$hangman_update_data = $this->game_model->update_hangman($data);

	   			if($hangman_update_data['status'] == "true") {

	   				if($hangman_update_data['blocked_count'] == 0) {

	   					//	push notification
  						$user_id = $data['friend_id'];
  						$user_device_details = $this->game_model->get_users_device_details($user_id);

	  					// Save notifications
	  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $data['friend_id'],'notifications_msg'=> "has completed your hangman task.",'notifications_type'=> "game_hangman", "notifications_event_id"=>$data['game_hangman_id'],'notifications_status'=> 1);
	  					$save_notifications = $this->game_model->save_notifications($notification_data);

  						if(!empty($user_device_details)) {

	  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
	  						$user_device_token = array($user_device_details['logs_device_token']);

			  				if(!empty($user_device_token)) {
				  				$msg = array (
											'title' => "You have a new notification.",
											'message' => $user_name." has completed your hangman task.",
											'notifications_type' => "game_hangman",
											'notifications_id' => $save_notifications['insert_id'],
											'notifications_from_id' => $data['users_id'],
											'notifications_event_id' => $data['game_hangman_id']
											);
		  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$data['friend_id']);
		  					}
						}
	   				}

	   				$response = array("status"=>"true","status_code"=>"200","message"=>"Your score updated successfully");
	   			}
	   			else {
	   				$response = array("status"=>"false","status_code"=>"400","message"=>$hangman_update_data['message']);
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

	/* ============         Tic-tac-toe game         =========== */
	public function user_game_tictactoe() {

		$data = json_decode(file_get_contents("php://input"),true);		

		if(!empty($data)) {

			// Check login session
			$unique_id_data['users_id'] = $data['users_id'];
			$unique_id_data['unique_id'] = $data['unique_id'];
			$unique_id_check = $this->game_model->unique_id_verification($unique_id_data);
			if($unique_id_check == 0) {
				$response = array("status"=>"false","status_code"=>"301","message"=>"Session Expired");
				echo json_encode($response);
				exit;
    		}

    		$user_name = (!empty($data['loggedin_user_name'])) ? $data['loggedin_user_name'] : "user";
  			unset($data['loggedin_user_name']);

    		if($data['api_action'] == "create") {

    			$insert_array = array('sender_id'=>$data['users_id'],'receiver_id'=>$data['friend_id'],'tictactoe_question'=>$data['tictactoe_question'],'tictactoe_answer'=>$data['tictactoe_answer'],'tictactoe_status'=>1,'tictactoe_updated_date'=>date('Y-m-d H:i:s'));
    			$insert_tictactoe_data = $this->game_model->insert_tictactoe($insert_array);

    			if($insert_tictactoe_data['status'] == "true") {

    				//	push notification
  					$user_id = $data['friend_id'];
  					$user_device_details = $this->game_model->get_users_device_details($user_id);

  					// Save notifications
  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $data['friend_id'],'notifications_msg'=> "invited you to start the game.",'notifications_type'=> "game_tictactoe", "notifications_event_id"=>$insert_tictactoe_data['insert_id'],'notifications_status'=> 1);
  					$save_notifications = $this->game_model->save_notifications($notification_data);

  					if(!empty($user_device_details)) {

  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
  						$user_device_token = array($user_device_details['logs_device_token']);

		  				if(!empty($user_device_token)) {
			  				$msg = array (
										'title' => "You have a new notification.",
										'message' => $user_name." invited you to start the game.",
										'notifications_type' => "game_tictactoe",
										'notifications_id' => $save_notifications['insert_id'],
										'notifications_from_id' => $data['users_id'],
										'notifications_event_id' => $insert_tictactoe_data['insert_id'],
										'tictactoe_question' => $data['tictactoe_question'],
										'tictactoe_answer' => $data['tictactoe_answer']
										);
	  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$data['friend_id']);
	  					}
					}
    				
    				$response = array("status"=>"true","status_code"=>"200","insert_id"=>$insert_tictactoe_data['insert_id'],"message"=>"Tic-tac-toe request has been updated successfully");
    			}
    			else {
    				$response = array("status"=>"false","status_code"=>"400","message"=>$insert_tictactoe_data['message']);
    			}
	   		}
	   		else if($data['api_action'] == "details") {

	   			$tictactoe_details = $this->game_model->tictactoe_details($data['game_tictactoe_id']);

	   			if(!empty($tictactoe_details)) {
	   				$response = array("status"=>"true","status_code"=>"200","server_data"=>$tictactoe_details,"message"=>"Listed successfully");
	   			}
	   			else {
	   				$response = array("status"=>"false","status_code"=>"400","message"=>"No record(s) found");
	   			}
	   		}
	   		else if($data['api_action'] == "update_answer") {

	   			// answer_status - 1 (correct), 2 (wrong)
	   			if(!empty($data['answer_status']) && $data['answer_status'] == 1) {

	   				$data['beginner_id'] = $data['users_id'];
	   				$notifications_msg = "has to start the game first.";
	   			}
	   			else {
	   				$data['beginner_id'] = $data['friend_id'];
	   				$notifications_msg = "lost the chance to start first. you have to start the game first.";
	   			}

	   			$tictactoe_update_data = $this->game_model->update_tictactoe_answer($data);

	   			if($tictactoe_update_data['status'] == "true") {

   					//	push notification
  					$user_id = $data['friend_id'];
  					$user_device_details = $this->game_model->get_users_device_details($user_id);

  					// Save notifications
  					$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $data['friend_id'],'notifications_msg'=> $notifications_msg,'notifications_type'=> "game_tictactoe", "notifications_event_id"=>$data['game_tictactoe_id'],'notifications_status'=> 1);
  					$save_notifications = $this->game_model->save_notifications($notification_data);

  					if(!empty($user_device_details)) {

  						$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
  						$user_device_token = array($user_device_details['logs_device_token']);

		  				if(!empty($user_device_token)) {
			  				$msg = array (
											'title' => "You have a new notification.",
											'message' => $user_name." ".$notifications_msg,
											'notifications_type' => "game_tictactoe",
											'notifications_id' => $save_notifications['insert_id'],
											'notifications_from_id' => $data['users_id'],
											'notifications_event_id' => $data['game_tictactoe_id']
										);
	  						$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$data['friend_id']);
	  					}
					}
    				
    				$response = array("status"=>"true","status_code"=>"200","message"=>"Tic-tac-toe answer has been updated successfully");
	   			}
	   			else {
	   				$response = array("status"=>"false","status_code"=>"400","message"=>$tictactoe_update_data['message']);
	   			}
	   		}
	   		else if($data['api_action'] == "update_score") {

	   			$tictactoe_update_data = $this->game_model->update_tictactoe_score($data);

				$response = array("status"=>"true","status_code"=>"200","message"=>"Updated successfully");
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

	/* ============         Hangman game notification         =========== */
	public function user_hangman_notification() {

		$data = json_decode(file_get_contents("php://input"),true);		

		if(!empty($data)) {

			$user_name = (!empty($data['loggedin_user_name'])) ? $data['loggedin_user_name'] : "user";
  			unset($data['loggedin_user_name']);

			$update_hangman_data = $this->game_model->update_hangman_notification($data);

			//	push notification
  			$user_id = $data['friend_id'];
  			$user_device_details = $this->game_model->get_users_device_details($user_id);
  			$recent_letter = $data['recent_letter'];
  			$letter_action = $data['letter_action'];

  			// Save notifications
  			$notification_data = array('notifications_from_id'=> $data['users_id'],'notifications_to_id'=> $data['friend_id'],'notifications_msg'=> "found the letter '$recent_letter' as $letter_action.",'notifications_type'=> "hangman_notification", "notifications_event_id"=>$data['game_hangman_id'],'notifications_status'=> 1);
			$save_notifications = $this->game_model->save_notifications($notification_data);

			if(!empty($user_device_details)) {

				$user_device_type = ($user_device_details['logs_device_type'] == 1) ? "android" : "ios";
				$user_device_token = array($user_device_details['logs_device_token']);

				if(!empty($user_device_token)) {
	  				$msg = array (
								'title' => "You have a new notification.",
								'message' => $user_name." found the letter '$recent_letter' as $letter_action.",
								'notifications_type' => "hangman_notification",
								'notifications_id' => $save_notifications['insert_id'],
								'notifications_from_id' => $data['users_id'],
								'notifications_event_id' => $data['game_hangman_id'],
								'recent_word' => $data['recent_word'],
								'original_word' => $data['original_word'],
								'live_word' => $data['live_word'],
								'hint' => $data['hint'],
								);
					$send_notification = $this->common->single_push_notification_service($user_device_type,$user_device_token,$msg,$data['friend_id']);
				}
			}
    				
			$response = array("status"=>"true","status_code"=>"200","message"=>"Notification has been sent successfully");
   		}
 		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
		}
		echo json_encode($response);
	}
	
} // End game controller

