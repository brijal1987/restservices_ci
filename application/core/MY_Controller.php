<?php

if (!defined("BASEPATH"))
    exit("No direct script access allowed");
ob_clean();

class MY_Controller extends CI_Controller {

    //Set the class variable.
    var $template = array();
    var $data = array();

    public function __construct() {
        parent::__construct();


        $this->load->library('encryption');

        $this->encryption->initialize(
            array(
                'cipher' => 'aes-128',
                'mode' => 'ctr',
                'key' => $this->config->item('encryption_key')
            )
        );
        $this->load->model('My_Model');
    }

    /**
     * layout : Front Layout management
     *
     * @access    public
     * @param    NA
     * @return    NA
     */
    public function layout() {
        // making temlate and send data to view.
        $this->template["header"] = $this->load->view("layout/header", $this->data, true);
        //$this->template["left"] = $this->load->view("layout/left", $this->data, true);
        $this->template["middle"] = $this->load->view($this->middle, $this->data, true);
        $this->template["footer"] = $this->load->view("layout/footer", $this->data, true);
        //Load view
        $this->load->view("layout/index", $this->template);
    }

    /**
     * layout : admin Layout management
     *
     * @access    public
     * @param    NA
     * @return    NA
     */
    public function admin_layout() {
        if (empty($this->session->username)) {
            redirect('home');
        }

        // making temlate and send data to view.
        $this->template["header"] = $this->load->view(ADMIN . "layout/header", $this->data, true);
        $this->template["left"] = $this->load->view(ADMIN . "layout/left", $this->data, true);
        $this->template["middle"] = $this->load->view(ADMIN . $this->middle, $this->data, true);
        $this->template["footer"] = $this->load->view(ADMIN . "layout/footer", $this->data, true);

        //Load view
        $this->load->view("admin/layout/index", $this->template);
    }

    /**
     * _valid_request
     *
     * @access    public
     * @param    req_method get or post, check_ajax_req
     * @return    boolean
     */
    protected function _valid_request($req_method = "post", $check_ajax_req = true) {

        if ($check_ajax_req) {
            return ($this->input->is_ajax_request() && $this->input->method() == $req_method);
        } else {
            return ($this->input->method() == $req_method);
        }
    }

    /**
     * Method to check admin user already logged in or not
     * if Already Logged In then redirect to admin dashboard
     * @access    protected
     * @param    sesson data
     * @return    boolean or redirect
     */
    protected function checkAdminLogged() {

        if (!$this->session->userdata()) {
            return false;
            //Redirect based on action
            redirect(base_url());
        } else {
            return true;
        }
    }

    /**
     * Upload file
     * @param  array $file
     * @param  string $field_name
     * @param  string $upload_directory
     * @param  string $allowed_types
     * @param  integer $max_size
     * @param  array $config_vars
     * @return array
     */
    public function upload_file($file, $field_name, $upload_directory, $allowed_types, $max_size, $config_vars = array()) {
        $error = array();

        if (($file[$field_name]["error"] == 0 && $upload_directory != "")) {
            //Initialise variable
            $return_array = array();

            //if directory not exists then create new directory
            if (!is_dir($upload_directory)) {
                mkdir($upload_directory, DIR_FILE_WRITE_MODE, true);
                if (defined("FORBIDDEN_DIRECTORY") && file_exists(FORBIDDEN_DIRECTORY)) {
                    rename(FORBIDDEN_DIRECTORY, $upload_directory . "/index.html");
                }
            }

            //set values in config array
            $config = array(
                "upload_path" => $upload_directory,
                "allowed_types" => $allowed_types,
                "overwrite" => TRUE,
                "max_size" => $max_size,
                "encrypt_name" => TRUE,
            );

            if (!empty($config_vars)) {
                $config = array_merge($config, $config_vars);
            }

            //set config array value in upload library
            $this->load->library("upload", $config);

            //Initialise config array
            $this->upload->initialize($config);

            //upload image from specific path
            if (!$this->upload->do_upload($field_name)) {
                $error = array("image" => $this->upload->display_errors());
                $return_array["error"] = $error;
                $return_array["image"] = "";
            } else {
                $data = array("upload_data" => $this->upload->data());
                $return_array["image"] = $data["upload_data"]["file_name"];
                $return_array["error"] = "";
            }

            return $return_array;
        }
    }

    /**
     * Delete Common for all module
     * @access  public
     * @param  string $table_name
     * @param  string $ids_arr
     * @param  string $success_msg
     * @param  array $where_arr
     * @param  string $table_index_field
     * @param  boolean $is_return
     * @return array/NA
     */
    public function delete_by_ids($table_name = "", $ids_arr = "", $success_msg = "", $where_arr = array(), $table_index_field = "id", $is_return = false) {

        //Initialise variable
        $output = array("status" => 0, "msg" => "We could not delete record(s).");

        if (!empty($table_name) && !empty($table_index_field) && !empty($ids_arr)) {
            //Set data array
            $data_arr = array("deleted_by" => $this->session->id, "deleted_on" => CURRENT_DATETIME);

            if (!empty($where_arr)) {
                $this->db->where($where_arr);
            }

            $this->db->where_in($table_index_field, $ids_arr)->update($table_name, $data_arr);
            //If delete successfull
            if ($this->db->affected_rows()) {
                $output["status"] = 1;
                $output["msg"] = ($success_msg) ? $success_msg : lang("delete_success");
            }
        }
        $output[CSRF_TOKEN_MP] = $this->security->get_csrf_hash();

        if ($is_return) {
            return $output;
        }

        //output to json format        
        echo json_encode($output);
        exit;
    }

    /**
     * Delete By reference common for all modules
     * @param  string $table_name
     * @param  array $ids_arr
     * @param  array $tables_arr
     * @param  string $where_arr
     * @param  string $table_index_field
     * @param  boolean $is_return
     * @return array/NA
     */
    public function delete_by_ids_ref($table_name = "", $ids_arr = array(), $tables_arr = array(), $where_arr = "", $table_index_field = "id", $is_return = false) {
        //Initialise variable
        $ids = array();
        $output = array("status" => 0, "msg" => lang("delete_with_ref_fail"), "ids" => array());

        if ($this->_valid_request() && !empty($table_name) && !empty($ids_arr)) {
            if (!empty($tables_arr) && count($tables_arr) > 0) {
                //checkReference return to array 
                $ids = $this->checkReference($tables_arr, $ids_arr);
                $delete_ids = $ids['no_ref'];
            } else {
                $delete_ids = $ids_arr;
            }

            $output["ids"] = $delete_ids;
            if (!empty($delete_ids)) {
                //Set data array
                $data_arr = array("deleted_by" => $this->session->id, "deleted_on" => CURRENT_DATETIME);

                if (!empty($where_arr)) {
                    $this->db->where($where_arr);
                }

                $this->db->where_in(!empty($table_index_field) ? $table_index_field : "id", $delete_ids)->update($table_name, $data_arr);

                //If delete successfull
                if ($this->db->affected_rows()) {
                    $output["status"] = 1;
                    $output["msg"] = "Total " . count($delete_ids) . " record(s) deleted successfully, other record(s) may has reference or do not have delete rights so could not delete.";
                }
            }
        }

        if (empty($ids_arr)) {
            $output["msg"] = "You do not have delete rights for selected records.";
        }

        $output[CSRF_TOKEN_MP] = $this->security->get_csrf_hash();

        if ($is_return) {
            return $output;
        }

        //output to json format        
        echo json_encode($output);
        exit;
    }

    /**
     * Check refrance form table
     *
     * @access
     * @param
     * @return
     */
    public function checkReference($tables, $ids) {

        //Initailize variable
        $find_id = array();
        $not_find_id = array();

        if (is_array($ids)) {
            $ids_arr = $ids;
        } else {
            $ids_arr = array($ids);
        }

        foreach ($ids_arr as $id) {

            $querys = array();
            foreach ($tables as $key => $val) {
                $this->db->select('count(*) as cnt');
                $this->db->from($key);
                if (isset($val['fkey']) && $val['fkey']) {
                    $this->db->where($val['fkey'], $id);
                }
                if (isset($val['whr']) && $val['whr']) {
                    $this->db->where($val['whr']);
                }
                $querys [] = $this->db->get_compiled_select();
            }

            $union_quries = implode(" UNION ", $querys);

            $this->db->select('sum(a.cnt) as cnt');
            $this->db->from("($union_quries) as a");
            if ($row = $this->db->get()->row()) {

                if ($row->cnt) {
                    $find_id[] = $id;
                } else {
                    $not_find_id[] = $id;
                }
            }
        }

        return array("find_ref" => $find_id, "no_ref" => $not_find_id);
    }

    /**
     * set pagination config
     *
     * @access public
     * @param section_url, per_page
     * @return Array
     */
    public function set_pagination_config($section_url, $per_page) {

        $config = array();
        $config["base_url"] = base_url($section_url);
        $config['use_page_numbers'] = TRUE;
        $config["per_page"] = $per_page;
        $config["back_to_pagination"] = '<li><a href="' . $config["base_url"] . '">1</a></li>';

        //$config['full_tag_open'] = "<ul class='newpaginationlist'>";
        //$config['full_tag_close'] = '</ul>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="current"><a href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['prev_link'] = '<i class="fa fa-angle-left" aria-hidden="true"></i>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_link'] = '<i class="fa fa-angle-right" aria-hidden="true"></i>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';

        return $config;
    }

    public function get_IP_address() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress;
    }

    /**
     * get states from country
     *
     * @access public
     * @param POST Array
     * @return json
     */
    public function get_states() {
        $strHtml = '';
        $output = array("status" => 0, "msg" => lang("data_not_fetch"), CSRF_TOKEN_MP => $this->security->get_csrf_hash());
        $intCountryId = $this->input->post('country_id');
        $intStateId = $this->input->post('state_id');

        if (!empty($intCountryId)) {
            $arrState = $this->My_Model->list_data("core_states", "id,state_name", array("deleted_by" => 0, "country_id" => $intCountryId), "state_name asc", TRUE);
            $strHtml = '<option value="">Select State</option>';
            if (!empty($arrState)) {
                foreach ($arrState as $intK => $strVal) {
                    $strHtml .= '<option value="' . $strVal['id'] . '" ' . ($intStateId == $strVal['id'] ? 'selected' : '') . '>' . $strVal['state_name'] . '</option>';
                }
            }
            $output = array("status" => 1, "states" => $strHtml, "msg" => lang("data_fetch"), CSRF_TOKEN_MP => $this->security->get_csrf_hash());
        }
        echo json_encode($output);
        exit;
    }

    function get_city_from_states() {
        $strHtml = '';
        $output = array("status" => 0, "msg" => lang("data_not_fetch"), CSRF_TOKEN_MP => $this->security->get_csrf_hash());
        $intStateId = $this->input->post('state_id');
        $intCityId = $this->input->post('city_id');

        if (!empty($intStateId)) {
            $arrCity = $this->My_Model->list_data("core_cities", "id,name", array("deleted_by" => 0, "state_id" => $intStateId), "name asc", TRUE);
            $strHtml = '<option value="">Select City</option>';
            if (!empty($arrCity)) {
                foreach ($arrCity as $intK => $strVal) {
                    $strHtml .= '<option value="' . $strVal['id'] . '" ' . ($intCityId == $strVal['id'] ? 'selected' : '') . '>' . $strVal['name'] . '</option>';
                }
            }
            $output = array("status" => 1, "cities" => $strHtml, "msg" => lang("data_fetch"), CSRF_TOKEN_MP => $this->security->get_csrf_hash());
        }
        echo json_encode($output);
        exit;
    }

    /**
     * get states from country
     *
     * @access public
     * @param POST Array
     * @return json
     */
    public function get_specie_scientific_names_by_classification_id() {
        $strHtml = '';
        $output = array("status" => 0, "msg" => lang("data_not_fetch"), CSRF_TOKEN_MP => $this->security->get_csrf_hash());
        $intClassificationId = $this->input->post('classification_id');
        $intFishSpecieId = $this->input->post('spi_sci_name');
        $strHtml = '<option value="">Select Species Scientific Name</option>';
        if (!empty($intClassificationId)) {
            $arrFishSpecie = $this->My_Model->list_data("core_fish_species", "id, spi_sci_name, spi_com_name", array("deleted_by" => 0, "classification_id" => $intClassificationId), "spi_sci_name asc", TRUE);
            if (!empty($arrFishSpecie)) {
                foreach ($arrFishSpecie as $intK => $strVal) {
                    $strHtml .= '<option data-id="' . $strVal['id'] . '" data-specie-common-name="' . $strVal['spi_com_name'] . '" value="' . $strVal['spi_sci_name'] . '" ' . ($intFishSpecieId == $strVal['id'] ? 'selected' : '') . '>' . $strVal['spi_sci_name'] . '</option>';
                }
            }
        }
        $output = array("status" => 1, "spi_sci_name" => $strHtml, "msg" => lang("data_fetch"), CSRF_TOKEN_MP => $this->security->get_csrf_hash());
        echo json_encode($output);
        exit;
    }

    /**
     * get states from country
     *
     * @access public
     * @param POST Array
     * @return json
     */
    public function get_products_by_product_category() {
        $strHtml = '';
        $fish_specie_id = $this->input->post('fish_specie_id');
        $id = $this->input->post('id');
        $type = $this->input->post('type');

        $strHtml = '<option value="">Select Product</option>';
        $arrAssociatedProductsA = array();

        if (!empty($fish_specie_id)) {
            $searchArray = [];
            $searchArray['deleted_by'] = 0;
            if (isset($fish_specie_id) && $fish_specie_id != false) {
                $data['fish_specie_id'] = $fish_specie_id;
            }

            //Called for Product Yield
            if (isset($type) && $type == 'product_yield') {
                $product_category = $this->input->post('product_category');
                $associated_products = $this->input->post('associated_products');
                $arrAssociatedProductsA = array();
                if (isset($product_category) && $product_category != '') {
                    $data['product_category'] = $product_category;
                }
                if (isset($associated_products) && $associated_products != '') {
                    $arrAssociatedProductsA = explode("#$#", $associated_products);
                }
                if (isset($id) && $id != '') {
                    $data['id'] = $id;
                }
                if ($arrAssociatedProductsA == false) {
                    $data['arrAssociatedProducts'] = $this->My_Model->list_data("core_associated_yield_products", "associated_product_id", array("product_id" => $id, "deleted_by" => 0));

                    if (isset($data['arrAssociatedProducts']) && $data['arrAssociatedProducts'] != false) {
                        foreach ($data['arrAssociatedProducts'] as $key => $arrAssociatedProducts) {
                            $arrAssociatedProductsA[] = $arrAssociatedProducts->associated_product_id;
                        }
                    }
                }
            }
            $arrProducts = $this->My_Model->get_products($data);
            if (!empty($arrProducts)) {
                foreach ($arrProducts as $intK => $strVal) {
                    $selected = "";
                    if (isset($arrAssociatedProductsA) && in_array($strVal['id'], $arrAssociatedProductsA)) {
                        $selected = " selected='selected' ";
                    }
                    $strHtml .= '<option  ' . $selected . ' data-product-yield-id="' . $strVal['id'] . '" data-yield-percentage="' . $strVal['yield_percentage'] . '" data-yield-type-id="' . $strVal['yield_type_id'] . '" data-product-name="' . $strVal['product_name'] . '" data-category="' . ($strVal['product_category'] == 1 ? "Main Product" : "Other Product") . '" data-category-id="' . ($strVal['product_category']) . '" value="' . $strVal['id'] . '">' . $strVal['product_name'] . '</option>';
                }
            }
        }
        $output = array("status" => 1, "productlist" => $strHtml, "msg" => lang("data_fetch"), CSRF_TOKEN_MP => $this->security->get_csrf_hash());
        $output['arrAssociatedProductsA'] = $arrAssociatedProductsA;
        echo json_encode($output);
        exit;
    }

    function upload_editor_img() {
        $output = array("status" => 0, "msg" => "file not uploaded", CSRF_TOKEN_MP => $this->security->get_csrf_hash());
        if (!$_FILES['file']['error']) {
            $name = md5(uniqid(mt_rand()));
            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $filename = $name . '.' . $ext;
            $destination = './uploads/content/' . $filename; //change this directory
            $location = $_FILES["file"]["tmp_name"];
            move_uploaded_file($location, $destination);
            $strUrl = base_url("uploads/content/" . $filename);
            $output = array("status" => 1, "msg" => "file not uploaded", CSRF_TOKEN_MP => $this->security->get_csrf_hash(), "url" => $strUrl);
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($output));
    }

    /**
     * Method to check user page access and its detail rights
     * if user not have page access rights then redirect to dashboard
     * @access    private
     * @param    page_name ,access_right
     * @return    redirect
     */
    public function checkPageAccess($page_name, $access_right = 'view_right', $is_ajax = false, $bypass_sp = true) {
        if ($this->session->usertype_id == SUPER_ADMIN_USERTYPE_ID && $bypass_sp) {
            return true;
        }
        if (!array_key_exists($page_name, $this->session->access_rights)) {
            if ($is_ajax == true) {
                return false;
            } else {
                redirect('dashboard');
            }
        }
        if ($access_right != '') {
            if (array_key_exists($page_name, $this->session->access_rights) && !array_key_exists($access_right, $this->session->access_rights[$page_name])) {
                if ($is_ajax == true) {
                    return false;
                } else {
                    redirect('dashboard');
                }
            }
        }
        return true;
    }

    public function insert_email_logs($subject, $message) {
        $email_data_array = array(
            'from_email' => FROM_MAIL,
            'from_name' => FROM_NAME,
            'to_email' => $this->input->post("email"),
            'to_name' => $this->input->post("first_name") . ' ' . $this->input->post("last_name"),
            'cc_email' => NULL,
            'cc_name' => NULL,
            'bcc_email' => NULL,
            'bcc_name' => NULL,
            'subject' => $subject,
            'message' => $message,
            'mail_date' => CURRENT_DATETIME,
            'status' => '0',
        );
        $email_logs_id = $this->Users_model->insert_data("core_email_logs", $email_data_array);
        return $email_logs_id;
    }

    

    public function validate_vessel() {
        try {
            //Set validations
            $this->form_validation->set_rules("vessel_reg_no", "Vessel Registration Number", "trim|required");

            //Check form validation response : if false then gives error message else insert/update data
            if ($this->form_validation->run() === TRUE) {

                $output = array("status" => 1, "id" => "", "msg" => "", CSRF_TOKEN_MP => $this->security->get_csrf_hash());

                $response = vessel_is_valid($this->input->post('vessel_reg_no'));

                if (count($response) > 0) {
                    $response = $response[0];
                    if (!empty($response->ErrorMessage)) {
                        $output['status'] = 1;
                        $output['msg'] = $response->ErrorMessage;
                    } else {
                        if (isset($response->vessel_id)) {
                            $vessel = $this->PurchaseLog_model->single_data("core_vessels", "id,vessel_name,owner_name,vessel_regno,hull_length", array("id" => $response->vessel_id, "deleted_by" => 0), "", false);
                            $output['id'] = $vessel->id;
                            $output['vessel_name'] = $vessel->vessel_name;
                            $output['owner_name'] = $vessel->owner_name;
                            $output['vessel_regno'] = $vessel->vessel_regno;
                            $output['hull_length'] = $vessel->hull_length;
                        }
                        $output['status'] = 0;
                        $output['msg'] = lang("data_fetch");
                    }
                } else {
                    $output['status'] = 1;
                    $output['msg'] = NO_VALID_REG_EXIST;
                }
            } else {
                $output['status'] = 1;
            }
            $this->output->set_content_type('application/json')->set_output(json_encode($output));
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
        }
    }

    public function validate_vessel_server_side($vessel_reg_no = "") {
        try {

            $output['status'] = 1;
            $output['msg'] = "";

            if (!empty($vessel_reg_no)) {
                $request_data['vessel_reg_no'] = $vessel_reg_no;
                $this->form_validation->set_data($request_data);
            } else {
                $request_data['vessel_reg_no'] = $this->input->post('vessel_reg_no');
            }
            //Set validations
            $this->form_validation->set_rules("vessel_reg_no", "Vessel Registration Number", "trim|required");

            //Check form validation response
            if ($this->form_validation->run() === TRUE) {

                $response = vessel_is_valid($request_data['vessel_reg_no']);

                if (count($response) > 0) {
                    $response = $response[0];

                    if (!empty($response->ErrorMessage)) {
                        $output['form_errors'] = array("vessel_reg_no" => $response->ErrorMessage);
                    } else {
                        if (isset($response->vessel_id) && !empty($response->vessel_id)) {
                            $vessel = $this->PurchaseLog_model->single_data("core_vessels", "id", array("id" => $response->vessel_id, "deleted_by" => 0), "", false);
                            $_POST['vessel_id'] = $vessel->id;
                            $request_data["vessel_id"] = $vessel->id;
                        } else {
                            $vessel = $this->PurchaseLog_model->single_data("core_vessels", "id", array("vessel_regno" => $request_data['vessel_reg_no'], "deleted_by" => 0), "", false);
                            $_POST['vessel_id'] = $vessel->id;
                            $request_data["vessel_id"] = $vessel->id;
                        }
                        return true;
                    }
                } else {
                    $output['form_errors'] = array("vessel_reg_no" => NO_VALID_REG_EXIST);
                }
            } else {
                $output['form_errors'] = $this->form_validation->error_array();
            }
            return $output;
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
        }
    }

    public static function get_vessel_detail($vessel_data) {
        //Create CI instance
        $obj = &get_instance();
        $obj->load->model('PurchaseLog_model');

        $vessel = $obj->PurchaseLog_model->single_data("core_vessels", "id,vessel_regno,vessel_name,owner_name,boat_category,hull_length", array("vessel_regno" => trim($vessel_data[0]->vessel_regno), "deleted_by" => 0), "", false);

        if (count($vessel) == 0) {
            return $vessel_id = $obj->add_vessel($vessel_data);
        } else {
            $real_carft = array("vessel_name" => $vessel_data[0]->vessel_name,
                "owner_name" => $vessel_data[0]->owner_name,
                "boat_category" => $vessel_data[0]->boat_category,
                "hull_length" => $vessel_data[0]->hull_length);

            $db_data = array("vessel_name" => $vessel->vessel_name,
                "owner_name" => $vessel->owner_name,
                "boat_category" => $vessel->boat_category,
                "hull_length" => $vessel->hull_length);

            $diff_result = array_diff($real_carft, $db_data);
            if (count($diff_result) > 0) {
                $obj->PurchaseLog_model->update_data("core_vessels", $diff_result, array("vessel_regno" => $vessel_data[0]->vessel_regno));
            }
        }
    }

    public function add_vessel($vessel_data) {
        try {
            $data_array = array(
                "vessel_name" => isset($vessel_data[0]->vessel_name) ? $vessel_data[0]->vessel_name : NULL,
                "vessel_regno" => isset($vessel_data[0]->vessel_regno) ? $vessel_data[0]->vessel_regno : NULL,
                "date_of_regn" => isset($vessel_data[0]->date_of_regn) ? DDtoYYYY1($vessel_data[0]->date_of_regn) : NULL,
                "owner_name" => isset($vessel_data[0]->owner_name) ? $vessel_data[0]->owner_name : NULL,
                "gender" => isset($vessel_data[0]->gender) ? $vessel_data[0]->gender : NULL,
                "address" => isset($vessel_data[0]->address) ? $vessel_data[0]->address : NULL,
                "taluk" => isset($vessel_data[0]->taluk) ? $vessel_data[0]->taluk : NULL,
                "district" => isset($vessel_data[0]->district) ? $vessel_data[0]->district : NULL,
                "fuel" => isset($vessel_data[0]->fuel) ? $vessel_data[0]->fuel : NULL,
                "fuel_capacity" => isset($vessel_data[0]->fuel_capacity) ? $vessel_data[0]->fuel_capacity : NULL,
                "engine_capacity" => isset($vessel_data[0]->engine_capacity) ? $vessel_data[0]->engine_capacity : NULL,
                "place_of_registry" => isset($vessel_data[0]->place_of_registry) ? $vessel_data[0]->place_of_registry : NULL,
                "storage_capacity" => isset($vessel_data[0]->storage_capacity) ? $vessel_data[0]->storage_capacity : NULL,
                "tonnage_capacity" => isset($vessel_data[0]->tonnage_capacity) ? $vessel_data[0]->tonnage_capacity : NULL,
                "boat_type" => isset($vessel_data[0]->boat_type) ? $vessel_data[0]->boat_type : NULL,
                "boat_category" => isset($vessel_data[0]->boat_category) ? $vessel_data[0]->boat_category : NULL,
                "base_name" => isset($vessel_data[0]->base_name) ? $vessel_data[0]->base_name : NULL,
                "engine_no" => isset($vessel_data[0]->engine_no) ? $vessel_data[0]->engine_no : NULL,
                "engine_type" => isset($vessel_data[0]->engine_type) ? $vessel_data[0]->engine_type : NULL,
                "year_of_make" => isset($vessel_data[0]->year_of_make) ? $vessel_data[0]->year_of_make : NULL,
                "hull_length" => isset($vessel_data[0]->hull_length) ? $vessel_data[0]->hull_length : NULL,
                "hull_width" => isset($vessel_data[0]->hull_width) ? $vessel_data[0]->hull_width : NULL,
                "hull_depth" => isset($vessel_data[0]->hull_depth) ? $vessel_data[0]->hull_depth : NULL,
                "license_no" => isset($vessel_data[0]->license_no) ? $vessel_data[0]->license_no : NULL,
                "license_validity_from" => isset($vessel_data[0]->license_validity_from) ? DDtoYYYY1($vessel_data[0]->license_validity_from) : NULL,
                "license_validity_to" => isset($vessel_data[0]->license_validity_to) ? DDtoYYYY1($vessel_data[0]->license_validity_to) : NULL,
            );
            return $last_id = $this->My_Model->insert_data("core_vessels", $data_array);

        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
        }
    }

    /**
     * Method to compare_date
     * @access   public
     * @param    NA
     * @return   bool
     */
    public function compare_date() {
        $from_date = strtotime($this->input->post('from_date'));
        $to_date = strtotime($this->input->post('to_date'));

        if ($to_date >= $from_date)
            return TRUE;
        else {
            $this->form_validation->set_message('compare_date', '%s should be greater than from Date.');
            return FALSE;
        }
    }

    public function get_vessel_max_qty($vessel_id = "") {
        try {
            if (!empty($vessel_id)) {
                $vessel = $this->PurchaseLog_model->single_data("core_vessels", "(CASE WHEN boat_category = '" . MOTORIZED_MECHANICAL . "' THEN " . MOTORIZED_MECHANICAL_QTY . "
                                                                                         WHEN boat_category = '" . MOTORIZED_NON_MECHANICAL . "' THEN " . MOTORIZED_NON_MECHANICAL_QTY . "
                                                                                         WHEN boat_category = '" . NON_MOTORIZED . "' THEN " . NON_MOTORIZED_QTY . "
                                                                                         ELSE " . MOTORIZED_MECHANICAL_QTY . " 
                                                                                         END) as max_qty", array("id" => $vessel_id, "deleted_by" => 0), "", false);
                return $vessel->max_qty;
            } else {
                return MOTORIZED_MECHANICAL_QTY;
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
        }
    }
}
