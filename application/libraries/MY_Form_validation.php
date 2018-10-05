<?php

/*
 * Server Side Custom Validation Library Class 
 * 
 */

class MY_Form_validation extends CI_Form_validation {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Check Unique record value in table
     * 
     * @param string 
     * @param string table field name.     
     * @return boolean
     */
    public function is_unique($str, $field) {
        $field_ar = explode('.', $field);

        if (!empty($_POST['id'])) {
            $this->CI->db->where('id !=', $_POST['id']);
        }
        $this->CI->db->where('deleted_by', 0);
        $query = $this->CI->db->get_where($field_ar[0], array($field_ar[1] => $str), 1, 0);
        if ($query->num_rows() === 0) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Check Unique record value in table
     *
     * @param JSON
     * @example {"table":"epics", "field":"title", "post_key":"epic_id", "whr_field":"id" , "whr_value" : 10, "is_delete" : false}
     * In josn table and fields are compalsory and other are used based on requirement
     * @return boolean
     */
    /*
      is_unique_dynamic[{"table":"epics", "field":"title", "post_key":"epic_id", "whr_field":"id"}]

     * Used fields : table, field, post_key, whr_field, whr_value, is_delete
     * table and field are complosary
     * If whr_field not pass then it will consider "id"
     * If post_key not pass then it will consider "id"
     * If whr_value pass then post_key will not consider
     * If is_delete pass with false value then delete condition will not apply
     * EX: select * from <table> where <whr_field> != <post_key> and <field> = "value" and deleted_by = 0
     */
    function is_unique_dynamic($str, $json_param) {
        if ($jObj = json_decode($json_param)) {
            if (isset($jObj->table) && isset($jObj->field)) {
                $is_delete = true;
                $where_field = isset($jObj->whr_field) ? $jObj->whr_field : "id";
                $where_value = isset($jObj->whr_value) ? $jObj->whr_value : "";

                //Check if value is blank then get data from post
                if (empty($where_value)) {
                    //Get post data
                    $post_key = isset($jObj->post_key) ? $jObj->whr_field : "id";
                    if ($post_key) {
                        $where_value = $this->CI->input->post($jObj->post_key);
                    }
                }

                $this->CI->db->where($where_field . ' !=', $where_value);

                if (isset($jObj->whr_array) && !empty($jObj->whr_array)) {

                    foreach ($jObj->whr_array as $key => $val) {
                        $this->CI->db->where($key, $val);
                    }
                }

                if (!(isset($jObj->is_delete) && $jObj->is_delete == false)) {
                    $this->CI->db->where('deleted_by', 0);
                }

                $query = $this->CI->db->get_where($jObj->table, array($jObj->field => $str), 1, 0);
                if ($query->num_rows() === 0) {
                    return TRUE;
                }
            }
        }
        //You can set error messgae from here but we set on application/language/english/form_validation_lang.php In form_validation_is_unique_dynamic
        if (!empty($jObj->error_msg)) {
            $this->CI->form_validation->set_message('is_unique_dynamic', $jObj->error_msg);
        } else {
            $this->CI->form_validation->set_message('is_unique_dynamic', 'The %s field must contain a unique value.');
        }
        return FALSE;
    }

    /**
     * valid with alpha number and space dash dote
     * 
     * @param string    
     * @return boolean
     */
    public function valid_pass($str) {
        //if (1 !== preg_match("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[$%#@_-]).{8,20}$/", $str)) {
        if (1 !== preg_match("/^(?=.*\d)(?=.*[A-Z]).{8,20}$/", $str)) {
            $this->CI->form_validation->set_message('valid_pass', lang('valid_password'));
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * valid with alpha number and space dash dote
     * 
     * @param string    
     * @return boolean
     */
    public function alphanum_space_dash_dote($str) {
        $this->CI->form_validation->set_message('alphanum_space_dash_dote', 'The %s may only contain only alphanumeric space and quote.');
        return (bool) preg_match('/^[0-9a-zA-Z-. ]+$/i', $str);
    }

    /**
     * valid with alpha and space
     * 
     * @param string    
     * @return boolean
     */
    public function alphanum_space_quote($str) {
        $this->CI->form_validation->set_message('alphanum_space_quote', 'The %s may only contain only alphanumeric space and quote.');
        return (bool) preg_match('/^[\'0-9a-zA-Z ]+$/i', $str);
    }

    /**
     * valid with alpha and space
     * 
     * @param string    
     * @return boolean
     */
    public function alphanum_space_quote_and($str) {
        $this->CI->form_validation->set_message('alphanum_space_quote_and', 'The %s may only contain only alphanumeric space, quote ,& ,(), . and -');
        return (bool) preg_match('/^[\'0-9a-zA-Z&.()\- ]+$/i', $str);
    }

    /**
     * valid with alpha and space
     * 
     * @param string    
     * @return boolean
     */
    public function alpha_space($str) {
        $this->CI->form_validation->set_message('alpha_space', 'The %s may only contain only alphabates and space.');
        return (bool) preg_match('/^[A-Za-z ]+$/i', $str);
    }

    /**
     * valid with mobile and phone number
     * 
     * @param string    
     * @return boolean
     */
    public function phone_mobile($str) {
        $this->CI->form_validation->set_message('phone_mobile', 'Please enter valid %s.');
        return (bool) preg_match('/^[0-9 +().-]+$/i', $str);
    }

    /**
     * valid with numeric space dash and dot
     * 
     * @param string    
     * @return boolean
     */
    public function alpha_num_dot_dash($str) {
        $this->CI->form_validation->set_message('alpha_num_dot_dash', 'The %s may only contain alpha numeric, dot and dash.');
        return (bool) preg_match('/^[A-Za-z0-9.-_ ]+$/i', $str);
    }

    /**
     * valid with salary format
     * 
     * @param string    
     * @return boolean
     */
    public function valid_decimal($str) {
        if ($str != "") {
            $this->CI->form_validation->set_message('valid_decimal', 'The %s may only contain numbers and single dot.');
            return (bool) preg_match('/^\d*\.?\d*$/', $str);
        } else {
            return true;
        }
    }
    
    /**
     * valid with salary format
     * 
     * @param string    
     * @return boolean
     */
    public function valid_decimal_two_dots($str) {
        if ($str != "") {
            $this->CI->form_validation->set_message('valid_decimal_two_dots', 'The %s may only contain numbers and allow two digit after decimal point.');
            return (bool) preg_match('/^[0-9]+(\.[0-9]{0,2})?$/', $str);
        } else {
            return true;
        }
    }
    
    /**
     * valid with salary format
     * 
     * @param string    
     * @return boolean
     */
    public function valid_decimal_three_dots($str) {
        if ($str != "") {
            $this->CI->form_validation->set_message('valid_decimal_three_dots', 'The %s may only contain numbers and allow three digit after decimal point.');
            return (bool) preg_match('/^[0-9]+(\.[0-9]{0,3})?$/', $str);
        } else {
            return true;
        }
    }

    /**
     * valid date format
     * 
     * @param string    
     * @return boolean
     */
    public function valid_date($str) {
        if ($str != "") {
            $this->CI->form_validation->set_message('valid_date', 'The %s is not valid date.');
            return (bool) preg_match('/(^(((0[1-9]|1[0-9]|2[0-8])[-](0[1-9]|1[012]))|((29|30|31)[-](0[13578]|1[02]))|((29|30)[-](0[4,6,9]|11)))[-](19|[2-9][0-9])\d\d$)|(^29[-]02[-](19|[2-9][0-9])(00|04|08|12|16|20|24|28|32|36|40|44|48|52|56|60|64|68|72|76|80|84|88|92|96)$)/', $str);
            //http://stackoverflow.com/questions/8937408/regular-expression-for-date-format-dd-mm-yyyy-in-javascript
        } else {
            return true;
        }
    }

    /**
     * valid month selected
     * 
     * @param string    
     * @return boolean
     */
    public function valid_month($str) {
        $this->CI->form_validation->set_message('valid_month', 'The %s is not valid month.');
        if (!in_array($str, array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12))) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * valid year selected
     * 
     * @param string    
     * @return boolean
     */
    public function valid_year($str) {
        $this->CI->form_validation->set_message('valid_year', 'The %s is not valid year.');
        $years = range(date('Y'), date('Y') + 10);
        if (!in_array($str, $years)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * valid youtube embed url
     *
     * @param string
     * @return boolean
     */
    public function youtube_embed_url($str) {
        if ($str != "") {
            $this->CI->form_validation->set_message('youtube_embed_url', 'The %s is not valid url.');
            return (bool) preg_match('/^http(s)?:\/\/www\.youtube\.com\/embed\/\S*$/', $str);
        } else {
            return true;
        }
    }

    /**
     * Validate URL
     *
     * @access    public
     * @param    string
     * @return    string
     */
    /* function valid_url($url) {
      $pattern = "/^((ht|f)tp(s?)\:\/\/|~/|/)?([w]{2}([\w\-]+\.)+([\w]{2,5}))(:[\d]{1,5})?/";
      if (!preg_match($pattern, $url)) {
      return FALSE;
      }

      return TRUE;
      } */

    /**
     * Callback function for validating phone number.
     * @access public
     * @param  string $phone_number
     * @return true/false
     */
    public function check_phone_number($phone_number) {
        // $regex = "/^(?=.*[0-9])[- +()0-9]+$/";
        // $regex = "/^([+]\d{2,3}[-\s]?|0[-\s]?)?\d{2,3}[-\s]?\d{3}[-\s]?\d{3,4}$/";
        // $regex = "/^([+]\d{2,3}[-\s]?|0[-\s]?)?(\d{2,3}[-\s]?\d{3}[-\s]?\d{3,4})(\/\d{3}){0,3}?$/";
        $regex = "/^(?=.{10,15})([+]\d{2,3}[-\s]?|0[-\s]?)?(\d{2,3}[-\s]?\d{3}[-\s]?\d{3,4})(\/\d{3}){0,3}?$/";
        preg_match($regex, $phone_number, $matches);

        if (empty($matches)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Callback function for google captcha.
     * @access public
     * @param  string $captcha
     * @return true/false
     */
    public function check_google_captcha($captcha) {

        $secretKey = CAPTCHA_SECRET_KEY;
        $ip = $_SERVER['REMOTE_ADDR'];
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $secretKey . "&response=" . $captcha . "&remoteip=" . $ip);

        $responseKeys = json_decode($response, true);
        if (intval($responseKeys["success"]) !== 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * valid purchase date
     *
     * @param string
     * @return boolean
     */
    public function valid_purchase_date($str, $json_param) {

        $this->CI->form_validation->set_message('valid_purchase_date', 'Please select valid purchase date.');

        if ($param = json_decode($json_param)) {
            if ($param->voyage_returndate != "") {
                $voyage_returndate = strtotime(format_date($param->voyage_returndate, "Y-m-d"));
                $purchase_date = strtotime(format_date($str, "Y-m-d"));
                $today_date = strtotime(date("Y-m-d"));
                if ($purchase_date <= $today_date && $purchase_date >= $voyage_returndate) {
                    return TRUE;
                } else {
                    return FALSE;
                }
            }
        }
        return FALSE;
    }

    /**
     * valid voyage start date
     *
     * @param string, json param
     * @return boolean
     */
    public function valid_voyage_start_date($str, $json_param) {

        $this->CI->form_validation->set_message('valid_voyage_start_date', 'Please select valid voyage start date.');

        $day_restrict_date = "";
        if ($param = json_decode($json_param)) {
            if ($param->voyage_returndate != "") {
                $voyage_startdate = strtotime(format_date($str, "Y-m-d"));
                $voyage_returndate = strtotime(format_date($param->voyage_returndate, "Y-m-d"));
                $today_date = strtotime(date("Y-m-d"));
                if ($voyage_startdate <= $today_date && $voyage_startdate <= $voyage_returndate) {
                    return TRUE;
                } else {
                    return FALSE;
                }
            }
        }
        return FALSE;
    }

    /**
     * valid voyage return date
     *
     * @param string, json param
     * @return boolean
     */
    public function valid_voyage_return_date($str, $json_param) {

        $this->CI->form_validation->set_message('valid_voyage_return_date', 'Please select valid voyage return date.');

        $day_restrict_date = "";
        if ($param = json_decode($json_param)) {
            if ($param->voyage_startdate != "") {
                $usertype_id = $param->usertype_id;
                if ($usertype_id == FO_USERTYPE_ID || $usertype_id == EXPORTER_USERTYPE_ID || $usertype_id == DEO_USERTYPE_ID || $usertype_id == MOU_USERTYPE_ID) {
                    if ($usertype_id == FO_USERTYPE_ID) {
                        $day_restrict = 30;
                    } else {
                        $day_restrict = 7;
                    }
                    $day_restrict_date = strtotime(date('Y-m-d', strtotime('-' . $day_restrict . ' days')));
                }
                $voyage_startdate = strtotime(format_date($param->voyage_startdate, "Y-m-d"));
                $voyage_returndate = strtotime(format_date($str, "Y-m-d"));
                $today_date = strtotime(date("Y-m-d"));
                if ($voyage_returndate <= $today_date && $voyage_startdate <= $voyage_returndate) {
                    if (!empty($day_restrict_date)) {
                        if ($voyage_returndate < $day_restrict_date) {
                            $this->CI->form_validation->set_message('valid_voyage_return_date', 'Voyage return date must not be greater than ' . $day_restrict . ' days from today.');
                            return FALSE;
                        }
                    }
                    return TRUE;
                } else {
                    return FALSE;
                }
            }
        }
        return FALSE;
    }


    /**
     * validate vessel
     *
     * @param string
     * @return boolean
     */
    public function validate_vessel($str, $is_registration_no = false) {
        $this->CI->load->model("My_Model");
        $this->CI->form_validation->set_message('validate_vessel', lang("invalid_vessel"));
        if ($is_registration_no) {
            $vessel_reg_no = $str;
        } else {
            $vessel_data = $this->CI->My_Model->single_data("core_vessels", "vessel_regno", ["id" => $str, "deleted_by" => 0]);

            if (!empty($vessel_data)) {
                $vessel_reg_no = $vessel_data['vessel_regno'];
            }
        }
        if (!empty($vessel_reg_no)) {
            $response = vessel_is_valid($vessel_data['vessel_regno']);
            return !empty($response[0]->ErrorMessage) ? false : true;
        }
        return false;
    }

    /**
     * valid vessel
     *
     * @param string
     * @return boolean
     */
    public function valid_vessel($str, $json_param) {
        $this->CI->load->model("My_Model");
        $this->CI->form_validation->set_message('valid_vessel', lang("invalid_vessel"));

        if ($param = json_decode($json_param)) {
            $vessel_reg_no = $param->vessel_reg_no;

            $response = vessel_is_valid($vessel_reg_no);
            if (count($response) == 0) {
                return false;
            } else if (!empty($response->ErrorMessage)) {
                return false;
            } else {
                if($response[0]->vessel_id != ""){
                    $_POST['vessel_id'] = $response[0]->vessel_id;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Callback function for validating password_checker.
     * @access public
     * @param  string passoword
     * @return true/false
     */
    public function password_checker($str) {
        $regex = "/^((?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,100})$/";
        $this->CI->form_validation->set_message('password_checker', 'Password must be minimum 8 Characters including numbers, special character, Upper and Lower case character.');
        return (bool) preg_match($regex, $str);
    }
}
