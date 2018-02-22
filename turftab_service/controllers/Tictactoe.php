<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tictactoe extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
		$this->load->model('tictactoe_model');
	    $this->load->library('../controllers/common');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ============         Tic tac toe game         =========== */
	public function user_tictactoe_activities() {

		$data = json_decode(file_get_contents('php://input'),true);

		// static_data
		$data['users_id'] = 1;
		$data['user_name'] = "siva";
		$data['opponent_id'] = 2;
		$data['opponent_name'] = "chachoose";
		$data['beginner_id'] = 1;
		$data['api_action'] = "game_start";
		$data['game_tictactoe_id'] = 1;

  		if(!empty($data)) {

			if($data['api_action'] == "initiate") {

				$user_id = $data['users_id'];
				$game_id = $data['game_tictactoe_id'];

				$game_details = $this->tictactoe_model->check_game_details($user_id,$game_id);

				if($game_details['status'] == "true") {
					$response = array('status'=>true,'status_code'=>'200','server_data'=>$game_details['data'],'message'=>"Listed successfully.");
					echo json_encode($response);

				}
				else {
					$response = array('status'=>false,'status_code'=>'400','message'=>"Something went wrong. Please try again later.");
					echo json_encode($response);
				}
			}
			else if($data['api_action'] == "game_start") {

				$game_status = $this->tictactoe_model->get_game_status($data['game_tictactoe_id']);
				$data['tictactoe_status'] = $game_status;
				$input_data['game'] = $data;
				$this->load->view('tictactoe',$input_data);
			}
		}
		else {
			$response = array("status"=>"false","status_code"=>"404","message"=>"Fields must not be Empty");
			echo json_encode($response);
		}
	}

	/* ============         Tic tac toe game status for each 5sec      =========== */
	public function user_game_update() {

		$game_id = $this->input->post('game_id');
		$fetch_data = $this->tictactoe_model->user_game_update_status($game_id);

		echo json_encode($fetch_data);
	}

	/* ============         Tic tac toe game status update      =========== */
	public function user_update_game_status() {

		$data = $this->input->post();
		$update_status = $this->tictactoe_model->user_update_game_status($data);

		echo json_encode($update_status);
	}

	/* ============         Tic tac toe game end stauts update      =========== */
	public function user_game_end() {

		$data = $this->input->post();
		$update_status = $this->tictactoe_model->user_game_end($data);

		echo json_encode($update_status);
	}

} // End tictac toe controller

