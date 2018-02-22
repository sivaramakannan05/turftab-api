<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_model extends CI_Model {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
    }

    /* =============       Subscription activation for user        ============== */
    public function subscription_user_activation($data) {
      
        if($data['payment_status'] == "success") {

            $refund = 0;
            $user_subscription_data = $this->db->get_where('ct_subscription_activation',array('users_id'=>$data['users_id'],'subscription_type'=>$data['subscription_type'],'subscription_status'=>1))->num_rows();

            if($user_subscription_data == 0) {

                $num_of_days = $data['subscription_duration']-1; // in days
                $start_date = date('Y-m-d H:i:s');
                $end_date = date('Y-m-d H:i:s', strtotime('+'.$num_of_days.' days'));

                $insert_data = array('users_id'=>$data['users_id'],'subscription_type'=>$data['subscription_type'],'subscription_cost'=>$data['subscription_cost'],'subscription_start_date'=>$start_date,'subscription_end_date'=>$end_date,'subscription_duration'=>$data['subscription_duration'],'subscription_status'=>1);
                $insert_user_subscription = $this->db->insert('ct_subscription_activation',$insert_data);
                if($insert_user_subscription) {
                    $model_data['status'] = "true";
                    $model_data['message'] = "Subscription activated successfully";
                }
                else {
                    $model_data['status'] = "false";
                    $model_data['message'] = "Error in insertion process";
                    $refund = 1;
                    $refund_message = "Error in insertion process";
                }
            }
            else {

                $refund = 1;
                $refund_message = "Already subscribed";
                $model_data['status'] = "false";
                $model_data['message'] = "You already have active subscription. Your cash will be refunded soon";
            }

            $payment_insert_data = array( 
                                    'payment_name' => "In-app purchase",
                                    'subscription_type' => $data['subscription_type'],
                                    'payment_cost' => $data['subscription_cost'],
                                    'payment_id'  => $data['payment_id'],
                                    'users_id'  => $data['users_id'],
                                    'payment_date'  => date('Y-m-d H:i:s'),
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
                                        'subscription_type' => $data['subscription_type'],
                                        'payment_cost' => $data['subscription_cost'],
                                        'payment_id'  => $data['payment_id'],
                                        'users_id'  => $data['users_id'],
                                        'payment_date'  => date('Y-m-d H:i:s'),
                                        'payment_status'  => 2,
                                        'refund_status'  => 2
                                    );
            $payment_insert = $this->db->insert('ct_payment_history',$payment_insert_data);
            $model_data['status'] = "false";
            $model_data['message'] = "Transaction failed. We will contact you soon";
        }

        return $model_data;
    }

    /* =============       User subscribed plan details       ============== */
    public function subscription_details($data) {

        $model_data = $this->db->select('*')->get_where('ct_subscription_activation',array('users_id'=>$data['users_id'],'subscription_type'=>$data['subscription_type'],'subscription_status'=>1))->row_array();
        return $model_data;
    }

} // End subscription model
