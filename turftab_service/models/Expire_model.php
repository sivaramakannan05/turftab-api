<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Expire_model extends CI_Model {

    /* Constructor */
    public function __construct()
    {
        parent::__construct();
    }
    
    /* ===========     To expire the events if it got expire     ======= */
    public function user_expire_events($cur_date)
    {

        // To expire the events
        $where_cond = '(event_enddate < "'.$cur_date.'" AND event_status=1)';
        $this->db->where($where_cond)->update('ct_events',array('event_status'=>2));

        // To expire the subscription
        $where_cond = '(subscription_end_date < "'.$cur_date.'" AND subscription_status=1)';
        $this->db->where($where_cond)->update('ct_subscription_activation',array('subscription_status'=>2));
        return TRUE;
    }

} // End event model
