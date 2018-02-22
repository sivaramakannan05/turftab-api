<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Common {

	public function send_mail($to_email,$sub,$msg)
	{
		$this_obj =& get_instance();
		$logger = $this_obj->logging->get_logger('email_logs');

        //Load email library 
        $this_obj->load->library('email');
        $this_obj->email->from(ADMIN_MAIL, ADMIN_NAME); 
        $this_obj->email->to($to_email);
        $this_obj->email->subject($sub); 
        $this_obj->email->message($msg); 
   
        //Send mail 
        if($this_obj->email->send()) {
        	$logs_info = "Date_time : ".date('Y-m-d H:i:s')." --> Status : TRUE --> From_mail : ".ADMIN_MAIL." --> To_mail : ".$to_email."";
        	$logger->info($logs_info);
	     	return true;
        }
        else {
        	$response = array("status"=>"false","status_code"=>"500","message"=>"Email does not send successfully");
        	$logs_info = "Date_time : ".date('Y-m-d H:i:s')." --> Status : FALSE --> From_mail : ".ADMIN_MAIL." --> To_mail : ".$to_email."";
        	$logger->info($logs_info);
			echo json_encode($response);
			exit;
        }
	}

	public function send_sms($mobile,$text)
	{
		
		// $url = 'https://rest.nexmo.com/sms/json?api_key='.SMS_API_KEY.'&api_secret='.SMS_SECRET_KEY.'&to='.$mobile.'&from='.SMS_TITLE.'&text='.$text.'';
		// $gateway_response = json_decode(file_get_contents($url),true);

		// if(isset($gateway_response['messages'][0]['status']) && $gateway_response['messages'][0]['status'] == 0)
		// {
		// 	return TRUE;
		// }
		// else {
		// 	$response = array("status"=>"false","status_code"=>"500","message"=>"OTP not sent successfully");
		// 	echo json_encode($response);
		// 	exit;
		// }
		return TRUE;
	}

	public function random_unique_id()
	{	
		// $token = "";
	 	//    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	 	//    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
	 	//    $codeAlphabet.= "0123456789";
	 	//    //print_r($codeAlphabet);
	 	//    $max = strlen($codeAlphabet); // edited
	 	//    $token = substr(str_shuffle($codeAlphabet),0,$length);
	 	//    return $token;

		// $id1 = time();
		$id2 = round(microtime(true) * 1000); // milliseconds
		$id3 = mt_rand(11,99);
		// $id4 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 3);
		// Unique id
		$unique_id = $id3.$id2;
		// Encoding
		$random_unique_id = rtrim(strtr(base64_encode($unique_id), '+/', '-_'), '=');
		// Decoding
		// $decode_unique_id = base64_decode(str_pad(strtr($random_unique_id, '-_', '+/'), strlen($random_unique_id) % 4, '=', STR_PAD_RIGHT)); 
 		return $random_unique_id;
 	}

 	public function create_thumb($filepath) {
 		
 		$this_obj =& get_instance();
 		$config['image_library']  = 'gd2';
		$config['source_image']   = $filepath;
		$config['create_thumb']   = TRUE;
		$config['maintain_ratio'] = TRUE;
		$config['width']          = 100;
		$config['height']         = 100;
		$config['thumb_marker']   = "_thumb";
		$this_obj->load->library('image_lib', $config);
		$this_obj->image_lib->resize();
		return TRUE;
 	}

 	/* ============        Encryption       ============== */
	public function custom_encrypt($string) {

		$secure_key = "0008754063617000";
	    $output = false;
	    $encrypt_method = "AES-256-CBC";
	    $secret_key =  $secure_key;
	    $secret_iv = strrev($secure_key);
	    // hash
	    $key = hash('sha256', $secret_key);
	    
	    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	    $iv = substr(hash('sha256', $secret_iv), 0, 16);
	    $output_str = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	    $output = rtrim(strtr(base64_encode($output_str), '+/', '-_'), '='); 
	    return $output;
	}

	/* ================== Decryption ================= */
	public function custom_decrypt($string) {

		$secure_key = "0008754063617000";
	    $output = false;
	    $encrypt_method = "AES-256-CBC";
	    $secret_key =  $secure_key;
	    $secret_iv = strrev($secure_key);

	    // hash
	    $key = hash('sha256', $secret_key);
	    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	    $iv = substr(hash('sha256', $secret_iv), 0, 16);
	    $decode_str = base64_decode(str_pad(strtr($string, '-_', '+/'), strlen($string) % 4, '=', STR_PAD_RIGHT)); 
	    $output = openssl_decrypt($decode_str, $encrypt_method, $key, 0, $iv);

	    return $output;
	}

	/* ==================  Single push notification ================= */
	public function single_push_notification_service($type,$device_token,$message,$user_id) {

		$this_obj =& get_instance();
		$logger = $this_obj->logging->get_logger('push_notification_logs');

		if($type == "android") {

			// prepare the bundle
			$fields = array(
							'to' => $device_token[0], // single device token (string format)
							'data' => $message // array values
						);

			// building headers for the request
       		$headers = array(
            				'Authorization: key=' . ANDROID_NOTIFICATION_API_KEY,
            				'Content-Type: application/json'
    					);

       		$ch = curl_init();
    		// Set the url, number of POST vars, POST data
	        curl_setopt( $ch,CURLOPT_URL, ANDROID_NOTIFICATION_API_URL );
        	curl_setopt( $ch,CURLOPT_POST, true );
        	curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        	// Disabling SSL Certificate support temporarly
        	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        	curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($fields));
        	// Execute post
        	$result = curl_exec($ch);
        	$response = json_decode($result,true);
        	
	       	if($response['failure'] == 1) {

        		$error_msg = $response['results'][0]['error'];
        		$logs_info = "Date_time : ".date('Y-m-d H:i:s')." --> Status : FALSE --> Device Token : ".$device_token[0]." --> User ID : ".$user_id." -->  Error Message : ".$error_msg."";
        		$logger->info($logs_info);
        		// die('Curl failed: ' . curl_error($ch));
        	}

        	// Close connection
        	curl_close($ch);  
		}
		else if($type == "ios") {

			$alert = $message['message'];
			unset($message['title']);

			// ssl://gateway.sandbox.push.apple.com:2195 - development server
			// ssl://gateway.push.apple.com:2195 - production server

			if(TURFTAB_ENVIRONMENT == "production") {

				$pemFile = (__DIR__ . '/turftab_pro.pem'); // For Production	
				$apns_url = "ssl://gateway.push.apple.com:2195"; // For Production	
			}
			else {

				$pemFile = (__DIR__ . '/turftab_dev.pem'); // For Development	
				$apns_url = "ssl://gateway.sandbox.push.apple.com:2195"; // For Development
			}

			$passphrase = "";

			$ctx = stream_context_create();
			stream_context_set_option($ctx, 'ssl', 'local_cert', $pemFile);
			stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

			// Server
			$fp = stream_socket_client($apns_url, $err,$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

			// if (!$fp)
				// exit("Failed to connect: $err $errstr" . PHP_EOL);

			$body['aps'] = array(
								'alert' => $alert,
								'sound' => 'default',
								'badge' => 1,
								'result'=> $message
							);
			$payload = json_encode($body);

			if(strlen($device_token[0]) >= 40) {

				// Build the binary notification
				$msg = chr(0) . pack('n', 32) . pack('H*', str_replace(' ', '', $device_token[0])) . pack('n', strlen($payload)) . $payload;	
				$result = fwrite($fp, $msg, strlen($msg));
			}
						
			// $this->checkAppleErrorResponse($fp);

			// Close connection
			fclose($fp);
		}
	}

	// // Function to return error response of ios push notification
	// function checkAppleErrorResponse($fp) {
	
	// 	//byte1=always 8, byte2=StatusCode, bytes3,4,5,6=identifier(rowID).
	// 	// Should return nothing if OK.
	// 	//NOTE: Make sure you set stream_set_blocking($fp, 0) or else fread will pause your script and wait
	// 	// forever when there is no response to be sent.
	// 	$apple_error_response = fread($fp, 6);
	// 	if ($apple_error_response) {
	// 		// unpack the error response (first byte 'command" should always be 8)
	// 		$error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_error_response);
	// 		print_r($error_response);
	// 		// if ($error_response['status_code'] == '0') {
	// 		// $error_response['status_code'] = '0-No errors encountered';
	// 		// } else if ($error_response['status_code'] == '1') {
	// 		// $error_response['status_code'] = '1-Processing error';
	// 		// } else if ($error_response['status_code'] == '2') {
	// 		// $error_response['status_code'] = '2-Missing device token';
	// 		// } else if ($error_response['status_code'] == '3') {
	// 		// $error_response['status_code'] = '3-Missing topic';
	// 		// } else if ($error_response['status_code'] == '4') {
	// 		// $error_response['status_code'] = '4-Missing payload';
	// 		// } else if ($error_response['status_code'] == '5') {
	// 		// $error_response['status_code'] = '5-Invalid token size';
	// 		// } else if ($error_response['status_code'] == '6') {
	// 		// $error_response['status_code'] = '6-Invalid topic size';
	// 		// } else if ($error_response['status_code'] == '7') {
	// 		// $error_response['status_code'] = '7-Invalid payload size';
	// 		// } else if ($error_response['status_code'] == '8') {
	// 		// $error_response['status_code'] = '8-Invalid token';
	// 		// } else if ($error_response['status_code'] == '255') {
	// 		// $error_response['status_code'] = '255-None (unknown)';
	// 		// } else {
	// 		// $error_response['status_code'] = $error_response['status_code'] . '-Not listed';
	// 		// }
	// 		// echo 'Error command' . $error_response['command'] . 'identifier ' . $error_response['identifier'] . ' status code ' . $error_response['status_code'];
	// 	}

	// 	return true;
	// }

	/* ==================  Multiple push notification ================= */
	public function multiple_push_notification_service($device_details,$message) {

		$this_obj =& get_instance();
		$logger = $this_obj->logging->get_logger('push_notification_logs');

		if(!empty($device_details['android'])) {

			$device_token = array_column($device_details['android'], 'logs_device_token');
			$user_ids = array_column($device_details['android'], 'users_id');
			$notification_ids = (!empty($device_details['android'][0]['notification_id'])) ? array_column($device_details['android'], 'notification_id') : array();

			foreach ($device_token as $dev_adk => $and_adv) {

				if(!empty($notification_ids[$dev_adk])) {
					$message['notifications_id'] = $notification_ids[$dev_adk];
				}
				
				// prepare the bundle
				$fields = array(
								'to' => $and_adv, // array values
								'data' => $message // array values
							);

				// building headers for the request
	       		$headers = array(
	            				'Authorization: key=' . ANDROID_NOTIFICATION_API_KEY,
	            				'Content-Type: application/json'
	    					);

	       		$ch = curl_init();
	    		// Set the url, number of POST vars, POST data
		        curl_setopt( $ch,CURLOPT_URL, ANDROID_NOTIFICATION_API_URL );
	        	curl_setopt( $ch,CURLOPT_POST, true );
	        	curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
	        	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	        	// Disabling SSL Certificate support temporarly
	        	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
	        	curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($fields));
	        	// Execute post
	        	$result = curl_exec($ch);
	        	$response = json_decode($result,true);

	        	if($response['failure'] == 1) {

        			$error_msg = $response['results'][0]['error'];
        			$event_id = (!empty($message['notifications_event_id'])) ? $message['notifications_event_id'] : '';
        			$logs_info = "Date_time : ".date('Y-m-d H:i:s')." --> Status : FALSE --> Device Token : ".$and_adv." --> User ID : ".$user_ids[$dev_adk]." --> Event ID : ".$event_id." --> Error Message : ".$error_msg."";
        			$logger->info($logs_info);
        			// die('Curl failed: ' . curl_error($ch));
        		}
        		// close connection
        		curl_close($ch);
			}      	
		}

		if(!empty($device_details['ios'])) {

			$device_token = array_column($device_details['ios'], 'logs_device_token');
			$user_ids = array_column($device_details['ios'], 'users_id');
			$notification_ids = (!empty($device_details['ios'][0]['notification_id'])) ? array_column($device_details['ios'], 'notification_id') : array();

			$alert = $message['message'];
			unset($message['title']);

			// ssl://gateway.sandbox.push.apple.com:2195 - development server
			// ssl://gateway.push.apple.com:2195 - production server

			if(TURFTAB_ENVIRONMENT == "production") {

				$pemFile = (__DIR__ . '/turftab_pro.pem'); // For Production	
				$apns_url = "ssl://gateway.push.apple.com:2195"; // For Production	
			}
			else {

				$pemFile = (__DIR__ . '/turftab_dev.pem'); // For Development	
				$apns_url = "ssl://gateway.sandbox.push.apple.com:2195"; // For Development
			}

			$passphrase = "";

			$ctx = stream_context_create();
			stream_context_set_option($ctx, 'ssl', 'local_cert', $pemFile);
			stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

			// Server
			$fp = stream_socket_client($apns_url, $err,$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

			// if (!$fp)
				// exit("Failed to connect: $err $errstr" . PHP_EOL);

			foreach ($device_token as $dev_key => $dev_tok) {

				if(!empty($notification_ids[$dev_key])) {
					$message['notifications_id'] = $notification_ids[$dev_key];
				}

				$body['aps'] = array(
								'alert' => $alert,
								'sound' => 'default',
								'badge' => 1,
								'result'=> $message
							);

				// print_r($body['aps']);
				// echo "primary key is ".$message['notification_primary_id'];
				// echo "user id is ".$users_id[$dev_key];

				$payload = json_encode($body);
				
				if(strlen($device_token[0]) >= 40) {

					// Build the binary notification
					$msg = chr(0) . pack('n', 32) . pack('H*', str_replace(' ', '', $dev_tok)) . pack('n', strlen($payload)) . $payload;	
					$result = fwrite($fp, $msg, strlen($msg));
					$result_msg[] = $result;
				}
			}
			
			// Close connection
			fclose($fp);
		}
	}

		/* ==================  Multiple push notification - local chat   ================= */
	public function multiple_push_notification_local_chat($device_details,$message) {

		$this_obj =& get_instance();
		$logger = $this_obj->logging->get_logger('push_notification_logs');

		if(!empty($device_details['android'])) {

			$device_token = array_column($device_details['android'], 'logs_device_token');
			$user_ids = array_column($device_details['android'], 'users_id');
			$friends_status = (!empty($device_details['android'][0]['friends_status'])) ? array_column($device_details['android'], 'friends_status') : array();

			foreach ($device_token as $dev_adk => $and_adv) {

				if(!empty($friends_status[$dev_adk])) {
					$message['friends_status'] = $friends_status[$dev_adk];
				}
				
				// prepare the bundle
				$fields = array(
								'to' => $and_adv, // array values
								'data' => $message // array values
							);

				// building headers for the request
	       		$headers = array(
	            				'Authorization: key=' . ANDROID_NOTIFICATION_API_KEY,
	            				'Content-Type: application/json'
	    					);

	       		$ch = curl_init();
	    		// Set the url, number of POST vars, POST data
		        curl_setopt( $ch,CURLOPT_URL, ANDROID_NOTIFICATION_API_URL );
	        	curl_setopt( $ch,CURLOPT_POST, true );
	        	curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
	        	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	        	// Disabling SSL Certificate support temporarly
	        	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
	        	curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($fields));
	        	// Execute post
	        	$result = curl_exec($ch);
	        	$response = json_decode($result,true);

	        	if($response['failure'] == 1) {

        			$error_msg = $response['results'][0]['error'];
        			$event_id = (!empty($message['notifications_event_id'])) ? $message['notifications_event_id'] : '';
        			$logs_info = "Date_time : ".date('Y-m-d H:i:s')." --> Status : FALSE --> Device Token : ".$and_adv." --> User ID : ".$user_ids[$dev_adk]." --> Event ID : ".$event_id." --> Error Message : ".$error_msg."";
        			$logger->info($logs_info);
        			// die('Curl failed: ' . curl_error($ch));
        		}
        		// close connection
        		curl_close($ch);
			}      	
		}

		if(!empty($device_details['ios'])) {

			$device_token = array_column($device_details['ios'], 'logs_device_token');
			$user_ids = array_column($device_details['ios'], 'users_id');
			$friends_status = (!empty($device_details['ios'][0]['friends_status'])) ? array_column($device_details['ios'], 'friends_status') : array();

			$alert = $message['message'];
			unset($message['title']);

			// ssl://gateway.sandbox.push.apple.com:2195 - development server
			// ssl://gateway.push.apple.com:2195 - production server

			if(TURFTAB_ENVIRONMENT == "production") {

				$pemFile = (__DIR__ . '/turftab_pro.pem'); // For Production	
				$apns_url = "ssl://gateway.push.apple.com:2195"; // For Production	
			}
			else {

				$pemFile = (__DIR__ . '/turftab_dev.pem'); // For Development	
				$apns_url = "ssl://gateway.sandbox.push.apple.com:2195"; // For Development
			}

			$passphrase = "";

			$ctx = stream_context_create();
			stream_context_set_option($ctx, 'ssl', 'local_cert', $pemFile);
			stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

			// Server
			$fp = stream_socket_client($apns_url, $err,$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

			// if (!$fp)
				// exit("Failed to connect: $err $errstr" . PHP_EOL);

			foreach ($device_token as $dev_key => $dev_tok) {

				if(!empty($friends_status[$dev_key])) {
					$message['friends_status'] = $friends_status[$dev_key];
				}

				$body['aps'] = array(
								'alert' => $alert,
								'sound' => 'default',
								'badge' => 1,
								'result'=> $message
							);

				// print_r($body['aps']);
				// echo "primary key is ".$message['notification_primary_id'];
				// echo "user id is ".$users_id[$dev_key];

				$payload = json_encode($body);
				
				if(strlen($device_token[0]) >= 40) {

					// Build the binary notification
					$msg = chr(0) . pack('n', 32) . pack('H*', str_replace(' ', '', $dev_tok)) . pack('n', strlen($payload)) . $payload;	
					$result = fwrite($fp, $msg, strlen($msg));
					$result_msg[] = $result;
				}
			}
			
			// Close connection
			fclose($fp);
		}
	}

	// /* ==================  Multiple push notification ================= */
	// public function push_notification_service($type,$device_token,$message) {	

	// 	$alert = $message['title'];
	// 	unset($message['title']);

	// 		// ssl://gateway.sandbox.push.apple.com:2195 - development server
	// 		// ssl://gateway.push.apple.com:2195 - production server

	// 	if(TURFTAB_ENVIRONMENT == "production") {

	// 		$pemFile = (__DIR__ . '/turftab_pro.pem'); // For Development	
	// 		$apns_url = "ssl://gateway.push.apple.com:2195"; // For Development	
	// 	}
	// 	else {

	// 		$pemFile = (__DIR__ . '/turftab_dev.pem'); // For Production	
	// 		$apns_url = "ssl://gateway.sandbox.push.apple.com:2195"; // For Production
	// 	}

	// 	$passphrase = "";

	// 	$ctx = stream_context_create();
	// 	stream_context_set_option($ctx, 'ssl', 'local_cert', $pemFile);
	// 	stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

	// 	// Server
	// 	$fp = stream_socket_client($apns_url, $err,$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

	// 	if (!$fp)
	// 		exit("Failed to connect: $err $errstr" . PHP_EOL);

	// 	$body['aps'] = array(
	// 						'alert' => $alert,
	// 						'sound' => 'default',
	// 						'badge' => 0,
	// 						'result'=> $message
	// 					);
	// 	$payload = json_encode($body);

	// 	// To split device token into individual value
	// 	foreach ($device_token as $dev_tok) {

	// 		// Build the binary notification
	// 		@$msg = chr(0) . pack('n', 32) . pack('H*', $dev_tok) . pack('n', strlen($payload)) . $payload;

	// 		// Send it to the server
	// 		$result = fwrite($fp, $msg, strlen($msg));
	// 		$result_msg[] = $result;
	// 	}

	// 	// Close connection
	// 	fclose($fp);		
	// }




} // Common controller
