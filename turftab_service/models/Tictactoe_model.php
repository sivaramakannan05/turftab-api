<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tictactoe_model extends CI_Model {

    /* Constructor */
    public function __construct()
    {
        parent::__construct();
    }

    public function check_game_details($user_id,$game_id) {

        $where_cond = '(gt.game_tictactoe_id="'.$game_id.'" AND gt.tictactoe_status!=3)';
        $this->db->select('gt.game_tictactoe_id,(CASE WHEN u.users_id='.$user_id.' THEN u.users_id ELSE ur.users_id END) as user_id,(CASE WHEN u.users_id='.$user_id.' THEN u.user_fullname ELSE ur.user_fullname END) as user_name,(CASE WHEN u.users_id='.$user_id.' THEN ur.users_id ELSE u.users_id END) as opponent_id,(CASE WHEN u.users_id='.$user_id.' THEN ur.user_fullname ELSE u.user_fullname END) as opponent_name,gt.beginner_id,IFNULL(gt.joined_player_ids,"") as joined_player_ids');
        $this->db->from('ct_game_tictactoe gt');
        $this->db->join('ct_users u','u.users_id=gt.sender_id','inner');
        $this->db->join('ct_users ur','ur.users_id=gt.receiver_id','inner');
        $this->db->where($where_cond);
        $game_data = $this->db->get()->row_array();

        if(!empty($game_data)) {

            $exploded_data = (!empty($game_data['joined_player_ids'])) ? explode(',',$game_data['joined_player_ids']) : array();

            if(!in_array($user_id, $exploded_data)) {
                $exploded_data[] = $user_id;
                $player_count = count($exploded_data);
                $update_data = $this->db->where('game_tictactoe_id',$game_id)->update('ct_game_tictactoe',array('joined_player_ids'=>implode(',',$exploded_data)));

                if($player_count == 1) {
                    if($game_data['beginner_id'] == $game_data['user_id']) {
                        $player1_id = $game_data['user_id'];
                        $player2_id = $game_data['opponent_id'];
                    }
                    else {
                        $player1_id = $game_data['opponent_id'];
                        $player2_id = $game_data['user_id'];            
                    }
                    // $already = $this->db->get_where('ct_tictactoe',array('game_id'=>$game_id))->num_rows();
                    // if($already == 0) {
                    $this->db->insert('ct_tictactoe',array('game_id'=>$game_id,'player1_id'=>$player1_id,'player2_id'=>$player2_id,'playing_user'=>"p1",'tictactoe_status'=>1,'tictactoe_updated_date'=>date('Y-m-d')));
                    // }
                }
                else if($player_count == 2) {
                    $this->db->where('game_id',$game_id)->update('ct_tictactoe',array('tictactoe_status'=>2,'tictactoe_updated_date'=>date('Y-m-d')));
                }
            }

            $model_data['status'] = "true";
            $model_data['data'] = $game_data;
        }
        else {
            $model_data['status'] = "false";
        }

        return $model_data;
    }

    public function user_game_update_status($game_id) {

        $model_data = $this->db->select('game_id,playing_user,sec0,sec1,sec2,sec3,sec4,sec5,sec6,sec7,sec8,tictactoe_status,tictactoe_updated_date')->get_where('ct_tictactoe',array('game_id'=>$game_id))->row_array();

        return $model_data;
    }

    public function user_update_game_status($data) {

        $check_game_expired = $this->db->get_where('ct_tictactoe',array('game_id'=>$data['game_id'],'tictactoe_status'=>2))->num_rows();
        if($check_game_expired == 1) {
            $next_player = ($data['player'] == "p2") ? "p1" : "p2" ;
            $this->db->where('game_id',$data['game_id'])->update('ct_tictactoe',array($data['sec']=>$data['player'],'playing_user'=>$next_player));
            $model_data['status'] = "true";
            $model_data['message'] = "Updated successfully";
        }
        else {
            $model_data['status'] = "false";
            $model_data['message'] = "Something went wrong. Please try again later";
        }
        
        return $model_data;
    }

    public function user_game_end($data) {

        $update_status = $this->db->where('game_id',$data['game_id'])->update('ct_tictactoe',array('tictactoe_status'=>3,'tictactoe_updated_date'=>date('Y-m-d H:i:s')));
        $player_det = $this->db->select('player1_id,player2_id')->get_where('ct_tictactoe',array('game_id'=>$data['game_id']))->row_array();

        if($data['player_name'] != "none") {
            if($data['player_name'] == "p1") {

                $winner_id = $player_det['player1_id'];
            }
            else {
                $winner_id = $player_det['player2_id'];
            }

            $update_status = $this->db->where('game_tictactoe_id',$data['game_id'])->set('sender_score', "CASE WHEN sender_id=$winner_id THEN 1 ELSE 0 END", FALSE)->set('receiver_score', "CASE WHEN receiver_id=$winner_id THEN 1 ELSE 0 END", FALSE)->update('ct_game_tictactoe',array('tictactoe_status'=>3,'tictactoe_updated_date'=>date('Y-m-d H:i:s'),'winner_id'=>$winner_id));
        }
        else {
            $update_status = $this->db->where('game_tictactoe_id',$data['game_id'])->update('ct_game_tictactoe',array('tictactoe_status'=>3,'tictactoe_updated_date'=>date('Y-m-d H:i:s'),'sender_score'=>0,'receiver_score'=>0));
        } 
        
        return TRUE;
    }

    public function get_game_status($game_id) {

        $model_data = $this->db->select('tictactoe_status')->get_where('ct_tictactoe',array('game_id'=>$game_id))->row_array();

        return $model_data['tictactoe_status'];
    } 
   
} // End tic tac toe model
