<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Home extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Frontend default landing page
     * @access  public
     * @param NA
     * @return NA
     */
    public function index() {
        try {
            $data["page_title"] = "Welcome to Servies";

            #include page level js
            #Set page layout
            $this->middle = "home/index";
            $this->data = $data;
            $this->layout();
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
        }
    }

}
