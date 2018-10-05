<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

ini_set( 'always_populate_raw_post_data', '-1' );

require APPPATH . '/libraries/REST_Controller.php';

class MY_API_Controller extends REST_Controller {

    protected $_options = array();

    public function __construct() {
        parent::__construct();

        // Load the jwt.php configuration file
        $this->load->library("JWT");
        $this->lang->load("api");
        if (empty($options)) {
            $this->load->config('jwt', true);
            $options = $this->config->item('jwt');
        }
        $this->_options = $options;

        $this->headers = $headers = getallheaders();
        $access_token = "";
        if (isset($headers['access_token'])) {
            $access_token = $headers['access_token'];
        }
        $not_verify_token_mothod = ['signup', 'signin', 'forgot_password', 'harbour_list', 'catch_area_list', 'species_list', 'states_list', 'static_pages_url', 'validate_vessel', 'app_version'];

        if (!in_array($this->router->fetch_method(), $not_verify_token_mothod)) {
            if (!$this->check_access_token($access_token)) {
                $this->_request_error(lang("access_token_err"), REST_Controller::HTTP_NOT_ACCEPTABLE);
            }
        }

        $request_data = json_decode($this->input->raw_input_stream, true);
        if (!empty($request_data['user_id'])) {
            $_POST['user_id'] = $request_data['user_id'];
        }
    }

    // validate access token
    public function check_access_token($access_token) {
        try {
            if ($this->input->post("user_id")) {
                $request_data = $this->input->post();
            } else {
                $request_data = json_decode($this->input->raw_input_stream, true);
            }
            $where = ["id" => $request_data['user_id'], "access_token" => $access_token];
            $valid_token = $this->db->where($where)->get("users")->row();
            return (!empty($valid_token)) ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function _check_csrf() {
        redirect(current_url());
    }

    /*
     * Set response error
     * Param : Response message and error type
     * Return: Array
     */

    protected function _request_error($message, $error_type = REST_Controller::HTTP_UNPROCESSABLE_ENTITY, $results = []) {
        if (empty($results)) {
            $results = new stdClass();
        }
        $this->response(['statuscode' => $error_type, 'message' => $message, "result" => $results], $error_type);
    }

    /*
     * Set response error
     * Param : Response message and error type
     * Return: Array
     * Auther: Jitendra
     */

    protected function _request_success($message, $results = [], $code = REST_Controller::HTTP_OK) {
        $this->save_device_token();
        if (empty($results)) {
            $results = new stdClass();
        }
        $this->response(['statuscode' => $code, 'message' => $message, "result" => $results], $code);
    }

    /**
     * extended core function
     */
    protected function _log_request($authorized = false, $response = "", $response_header = "") {
        $method = $this->request->method;
        $uri = $this->uri->uri_string() . " " . $this->input->ip_address();
        $head_args = json_encode($this->_head_args);
        $request = json_encode($this->_post_args);
        $log = PHP_EOL . "[" . date('d-m-Y H:i:s') . "] INFO " . strtoupper($method) . " " . $uri . " " . $head_args . " app-request-start " . $request;
        $log .= " app-response-start " . $response_header . " " . $response . PHP_EOL;
        $file = date('d-m-Y');

        error_log($log, 3, API_LOG_PATH . "$file.log");
        return 1;
    }

    /**
     * extended core function
     */
    public function response($data = NULL, $http_code = NULL, $continue = FALSE) {
        // If the HTTP status is not NULL, then cast as an integer
        if ($http_code !== NULL) {
            // So as to be safe later on in the process
            $http_code = (int) $http_code;
        }

        // Set the output as NULL by default
        $output = NULL;

        // If data is NULL and no HTTP status code provided, then display, error and exit
        if ($data === NULL && $http_code === NULL) {
            $http_code = self::HTTP_NOT_FOUND;
        }

        // If data is not NULL and a HTTP status code provided, then continue
        elseif ($data !== NULL) {
            // If the format method exists, call and return the output in that format
            if (method_exists($this->format, 'to_' . $this->response->format)) {
                // Set the format header
                $this->output->set_content_type($this->_supported_formats[$this->response->format], strtolower($this->config->item('charset')));
                $output = $this->format->factory($data)->{'to_' . $this->response->format}();

                // An array must be parsed as a string, so as not to cause an array to string error
                // Json is the most appropriate form for such a datatype
                if ($this->response->format === 'array') {
                    $output = $this->format->factory($output)->{'to_json'}();
                }
            } else {
                // If an array or object, then parse as a json, so as to be a 'string'
                if (is_array($data) || is_object($data)) {
                    $data = $this->format->factory($data)->{'to_json'}();
                }

                // Format is not supported, so output the raw data as a string
                $output = $data;
            }
        }

        // If not greater than zero, then set the HTTP status code as 200 by default
        // Though perhaps 500 should be set instead, for the developer not passing a
        // correct HTTP status code
        $http_code > 0 || $http_code = self::HTTP_OK;

        $this->output->set_status_header($http_code);

        // JC: Log response code only if rest logging enabled
        if ($this->config->item('rest_enable_logging') === TRUE) {
            $this->_log_response_code($http_code);
        }

        // Output the data
        $this->output->set_output($output);
        $response_header = json_encode(["Content-type" => $this->output->get_content_type(), "http_status" => $http_code]);
        $this->_log_request(FALSE, $output, $response_header);
        if ($continue === FALSE) {
            // Display the data and exit execution
            $this->output->_display();
            exit;
        }

        // Otherwise dump the output automatically
    }

    public function save_device_token() {
        $post = json_decode($this->input->raw_input_stream, true);

        if (!empty($post["device_type"]) && $this->router->fetch_method() != "app_version") {
            $this->load->model("MY_API_Model");
            $user_id = isset($post['user_id']) ? $post['user_id'] : $_POST['user_id'];
            $where_arr = array(
                "user_id" => $user_id,
                //"status" => 1
            );
            $exist_token = $this->MY_API_Model->single_data("user_device_token", 'id', $where_arr);
            $data = [
                "user_id" => $user_id,
                "device_type" => $post["device_type"],
                "token_id" => $post["device_token"],
                "status" => 1,
            ];
            if (empty($exist_token)) {
                $this->MY_API_Model->insert_data("user_device_token", $data);
            } else {
                $this->MY_API_Model->update_data("user_device_token", $data, $where_arr);
            }
        }
    }

    public function generate_jwt_token($request_data) {
        $this->load->model("MY_API_Model");
        $jwt_data = $this->sha256_dec_enc("encrypt", json_encode(["user_id" => $request_data['user_id'], "secure_key" => mt_rand()]));
        $access_token = $this->jwt->encode($jwt_data, JWT_CONSUMER_KEY);
        $where_arr = array(
            "id" => $request_data['user_id']
        );
        $data = [
            "access_token" => $access_token
        ];
        $this->MY_API_Model->update_data("users", $data, $where_arr);
        return $access_token;
    }

    function sha256_dec_enc($action, $string) {
        $output = false;

        $encrypt_method = SHA256_METHOD;
        $secret_key = SHA256_KEY;
        $secret_iv = SHA256_IV;

        // hash
        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }

        return $output;
    }

    /**
     * Delete By reference common for all modules
     * @param  string  $table_name
     * @param  array   $ids_arr
     * @param  array   $tables_arr
     * @param  string  $where_arr
     * @param  string  $table_index_field
     * @param  boolean $is_return
     * @return array/NA
     */
    public function delete_by_ids_ref($table_name = "", $ids_arr = array(), $tables_arr = array(), $where_arr = "", $table_index_field = "id", $is_return = false) {
        //Initialise variable
        $ids = array();
        $output = array("status" => 0, "msg" => lang("delete_with_ref_fail"), "ids" => array());

        if (!empty($table_name) && !empty($ids_arr)) {
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
                $data_arr = array("deleted_by" => isset($_POST['user_id']) ? $_POST['user_id'] : 0, "deleted_on" => CURRENT_DATETIME);

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

    public function generate_otp() {
        return mt_rand(100000, 999999);
    }

}
