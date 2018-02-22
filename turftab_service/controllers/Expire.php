<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Expire extends CI_Controller {

	/* Constructor */
	public function __construct()
    {
        parent::__construct();
        // $this->load->library('form_validation');
		$this->load->model('expire_model');
    }

	public function index()
	{
		echo "welcome";
	}

	/* ============         Expire events          =========== */
	public function user_expire() {

		$current_date = date('Y-m-d H:i:s');
		$expire_events = $this->expire_model->user_expire_events($current_date);
	}
	
	
} // End expire controller

