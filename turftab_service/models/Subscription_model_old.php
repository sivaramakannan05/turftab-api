<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_model extends CI_Model {

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

    /* =============       Subscription plan list        ============== */
    public function subscription_plan_list($data) {
       
       $model_data = $this->db->select('subscriptions_id,subscriptions_name,subscriptions_description   ,subscriptions_validity_period,subscriptions_cost,subscriptions_created_date')->order_by('subscriptions_id desc')->get_where('ct_subscriptions',array('subscriptions_status'=>1))->result_array();

        return $model_data;
    }

    /* =============       Subscription activation for user        ============== */
    public function subscription_user_activation($data) {


        if($data['payment_status'] == "true") {

            $refund = 0;
            $user_subscription_data = $this->db->get_where('ct_subscription_users',array('users_id'=>$data['users_id'],'subscription_users_status'=>1))->num_rows();

            if($user_subscription_data == 0) {

                $subscription_data = $this->db->get_where('ct_subscriptions',array('subscriptions_id'=>$data['subscriptions_id']))->row_array();

                if(!empty($subscription_data)) {

                    $num_of_days = $subscription_data['subscriptions_validity_period']-1;
                    $start_date = date('Y-m-d H:i:s');
                    $end_date = date('Y-m-d H:i:s', strtotime('+'.$num_of_days.' days'));

                    $insert_data = array('users_id'=>$data['users_id'],'subscriptions_id'=>$data['subscriptions_id'],'users_paid_amount'=>$subscription_data['subscriptions_cost'],'subscription_users_status'=>1,'subscription_start_date'=>$start_date,'subscription_end_date'=>$end_date);
                    $insert_user_subscription = $this->db->insert('ct_subscription_users',$insert_data);
                    $model_data['status'] = "true";
                    $model_data['message'] = "Subscription activated successfully";
                }
                else {
                    $refund = 1;
                    $refund_message = "Subscription id not exist";
                    $model_data['status'] = "false";
                    $model_data['message'] = "Something went wrong.We will contact you soon.";
                    $model_data['refund_message'] = "Subscription id not exist.";
                }
            }
            else {

                $refund = 1;
                $refund_message = "Already subscribed";
                $model_data['status'] = "false";
                $model_data['message'] = "Already subscribed";
                $model_data['refund_message'] = "Already subscribed.";
            }

            $payment_insert_data = array( 
                                    'payment_name' => "In-app purchase",
                                    'subscriptions_id' => $data['subscriptions_id'],
                                    'payment_cost' => $data['payment_cost'],
                                    'payment_id'  => $data['payment_id'],
                                    'payment_status_code'  => $data['payment_status_code'],
                                    'payment_status_message'  => $data['payment_status_message'],
                                    'payment_date'  => $data['payment_date'],
                                    'payment_status'  => 1
                                    );
            if($refund == 1) {
                $payment_insert_data['refund_status'] = 1;
                $payment_insert_data['refund_message'] = $refund_message;
            }
            else {
                $payment_insert_data['refund_status'] = 2;   
            }
            $payment_insert = $this->db->insert('ct_payment_history',$payment_insert_data);
        }
        else {

            $payment_insert_data = array( 
                                        'payment_name' => "In-app purchase",
                                        'subscriptions_id' => $data['subscriptions_id'],
                                        'payment_cost' => $data['payment_cost'],
                                        'payment_id'  => $data['payment_id'],
                                        'payment_status_code'  => $data['payment_status_code'],
                                        'payment_status_message'  => $data['payment_status_message'],
                                        'payment_date'  => $data['payment_date'],
                                        'payment_status'  => 2,
                                        'refund_status'  => 2
                                    );
            $payment_insert = $this->db->insert('ct_payment_history',$payment_insert_data);
            $model_data['status'] = "false";
            $model_data['message'] = "Transaction failed. We will contact you soon";
        }

        return $model_data;
    }




} // End subscription model
