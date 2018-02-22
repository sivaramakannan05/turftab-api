<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Game_model extends CI_Model {

    /* Constructor */
    public function __construct()
    {
        parent::__construct();
    }
    
    /* =============       Unique id verification        ============== */
    public function unique_id_verification($data) {
        $where_cond = array("users_id"=>$data['users_id'],"unique_id"=>$data['unique_id'],"logs_login_status"=>1);
        $record_count = $this->db->get_where('ct_user_logs',$where_cond)->num_rows();
        return $record_count;
    }

    /* =============       User device token for multiple users        ============== */
    public function get_users_device_details($user_id) {

        $where_cond = '(logs_login_status=1 AND users_id ="'.$user_id.'")';
        $user_device_details = $this->db->select('logs_device_type,logs_device_token')->from('ct_user_logs')->where($where_cond)->get()->row_array();
        return $user_device_details;
    }

    /* ===========         Save notifications    ======= */
    public function save_notifications($data)
    {

        $insert_data = $this->db->insert('ct_notifications',$data);

        if($insert_data) {
            $model_data['insert_id'] = $this->db->insert_id();
        }
        else {
            $model_data['insert_id'] = '';
        }

        return $model_data;
    }

    /* =================     User blocked count      =========== */
    public function user_blocked_count($from_id,$to_id) {

        $where_cond = '((blocklist_from_id="'.$from_id.'" AND blocklist_to_id="'.$to_id.'") OR (blocklist_from_id="'.$to_id.'" AND blocklist_to_id="'.$from_id.'"))';
        $blocked_count = $this->db->get_where('ct_blocklist',$where_cond)->num_rows();
        return $blocked_count;
    }
    
    /* =============       Insert hangman game task        ============== */
    public function insert_hangman_task($data) {

        $blocked_count = $this->user_blocked_count($data['from_users_id'],$data['to_users_id']);
        $model_data = array();

        if($blocked_count == 0) {

            $insert_data = $this->db->insert('ct_game_hangman',$data);
            if($insert_data) {
                $model_data['status'] = "true";
                $model_data['insert_id'] = $this->db->insert_id();
            }
            else {
                $model_data['status'] = "false";
                $model_data['message'] = "Error in insertion process"; 
            }
        }
        else {
           $model_data['status'] = "false"; 
           $model_data['message'] = "Something went wrong. Please try again later";
        }

        return $model_data;
    }

    /* =================     To get hangman game details by game id      =========== */
    public function hangman_details($game_id) {

        $model_data = $this->db->select('(SELECT IFNULL(SUM(hangman_score),"") FROM ct_game_hangman as s1 WHERE s1.to_users_id=from_users_id) as from_user_total_score,(SELECT IFNULL(SUM(hangman_score),"") FROM ct_game_hangman as s2 WHERE s2.to_users_id=to_users_id) as to_user_total_score,game_hangman_id,from_users_id,to_users_id,hangman_word,hangman_hint,hangman_attempts,hangman_score,hangman_status,hangman_updated_date,hangman_created_date')->get_where('ct_game_hangman',array('game_hangman_id'=>$game_id))->row_array();
        
        return $model_data;
    }

    /* =============       Update hangman game score        ============== */
    public function update_hangman($data) {

        $model_data['blocked_count'] = $this->user_blocked_count($data['users_id'],$data['friend_id']);

        $hangman_count = $this->db->get_where('ct_game_hangman',array('game_hangman_id'=>$data['game_hangman_id'],'to_users_id'=>$data['users_id'],'hangman_status'=>1))->num_rows();

        if($hangman_count == 1) {

            $update_data = $this->db->where('game_hangman_id',$data['game_hangman_id'])->update('ct_game_hangman',array('hangman_attempts'=>$data['hangman_attempts'],'hangman_score'=>$data['hangman_score'],'hangman_status'=>2,'hangman_updated_date'=>date('Y-m-d H:i:s')));
            $model_data['status'] = "true";
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Something went wrong. Please try again later";
        }

        return $model_data;
    }

    /* =============       Tic tac toe game creation        ============== */
    public function insert_tictactoe($data) {

        $blocked_count = $this->user_blocked_count($data['sender_id'],$data['receiver_id']);
        $model_data = array();

        if($blocked_count == 0) {

            $insert_data = $this->db->insert('ct_game_tictactoe',$data);
            if($insert_data) {
                $model_data['status'] = "true";
                $model_data['insert_id'] = $this->db->insert_id();
            }
            else {
                $model_data['status'] = "false";
                $model_data['message'] = "Error in insertion process"; 
            }
        }
        else {
           $model_data['status'] = "false"; 
           $model_data['message'] = "Something went wrong. Please try again later";
        }

        return $model_data;
    }

    /* =================     To get tictactoe game details by game id      =========== */
    public function tictactoe_details($game_id) {

        $model_data = $this->db->select('(SELECT IFNULL(SUM(s1.sender_score),"") FROM ct_game_tictactoe as s1 WHERE s1.tictactoe_status=3 AND s1.winner_id=sender_id) as sender_total_score,(SELECT IFNULL(SUM(s2.receiver_score),"") FROM ct_game_tictactoe as s2 WHERE s2.tictactoe_status=3 AND s2.winner_id=receiver_id) as receiver_total_score,game_tictactoe_id,sender_id,receiver_id,tictactoe_question,tictactoe_answer,IFNULL(beginner_id,"") as beginner_id,IFNULL(sender_score,"") as sender_score,IFNULL(receiver_score,"") as receiver_score,IFNULL(winner_id,"") as winner_id,tictactoe_status,tictactoe_updated_date,tictactoe_created_date')->get_where('ct_game_tictactoe',array('game_tictactoe_id'=>$game_id))->row_array();
        
        return $model_data;
    }

    /* =============       Update tic-tac-toe answer       ============== */
    public function update_tictactoe_answer($data) {

        $blocked_count = $this->user_blocked_count($data['users_id'],$data['friend_id']);

        if($blocked_count == 0) {

            $tictactoe_count = $this->db->get_where('ct_game_tictactoe',array('game_tictactoe_id'=>$data['game_tictactoe_id'],'receiver_id'=>$data['users_id'],'tictactoe_status'=>1))->num_rows();

            if($tictactoe_count == 1) {

                $update_data = $this->db->where('game_tictactoe_id',$data['game_tictactoe_id'])->update('ct_game_tictactoe',array('beginner_id'=>$data['beginner_id'],'tictactoe_status'=> 2,'tictactoe_updated_date'=>date('Y-m-d H:i:s')));
                $model_data['status'] = "true";
            }
            else {
                $model_data['status'] = "false";
                $model_data['message'] = "Something went wrong. Please try again later";
            }
        }
        else {
           $model_data['status'] = "false"; 
           $model_data['message'] = "Something went wrong. Please try again later"; 
        }   

        return $model_data;
    }

    /* =============       Update tictactoe game score        ============== */
    public function update_tictactoe_score($data) {

        $model_data = $this->db->where('game_tictactoe_id',$data['game_tictactoe_id'])->update('ct_game_tictactoe',array('sender_score'=>$data['sender_score'],'receiver_score'=>$data['receiver_score'],'tictactoe_status'=>3));

        return TRUE;
    }

    /* =============       Update hangman notification        ============== */
    public function update_hangman_notification($data) {

        $check_already = $this->db->get_where('ct_hangman_notifications',array('game_hangman_id'=>$data['game_hangman_id']))->num_rows();

        if($check_already == 0) {

            $model_data = $this->db->insert('ct_hangman_notifications',array('game_hangman_id'=>$data['game_hangman_id'],'original_word'=>$data['original_word'],'recent_word'=>$data['recent_word'],'live_word'=>$data['live_word'],'hint'=>$data['hint']));
        }
        else {
            $model_data = $this->db->where('game_hangman_id',$data['game_hangman_id'])->update('ct_hangman_notifications',array('recent_word'=>$data['recent_word'],'live_word'=>$data['live_word']));
        }

        return TRUE;
    }

} // End game model
