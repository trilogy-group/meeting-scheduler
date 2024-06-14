<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sorry extends EA_Controller {

    public function index() {
        $this->load->view('appointments/sorry');
    }
}

