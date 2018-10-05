<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/core/MY_API_Controller.php';

class Users extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model("api/Users_model");
        $this->load->library("encrypt_decrypt");
    }

    /**
     * User registration API
     *
     */
    public function signup_post() {
        try {
            $request_data = json_decode($this->input->raw_input_stream, true);

            $request_data['mobile_number'] = $this->encrypt_decrypt->decrypt($request_data['mobile_number']);
            $request_data['username'] = $this->encrypt_decrypt->decrypt($request_data['username']);
            $request_data['password'] = $this->encrypt_decrypt->decrypt($request_data['password']);

            $this->form_validation->set_data($request_data);

            //Set validations
            $this->form_validation->set_rules("usertype_id", "User Type", "trim|required|is_natural_no_zero");
            $this->form_validation->set_rules("first_name", "First Name", "trim|required|max_length[" . MAX_100 . "]");
            $this->form_validation->set_rules("last_name", "Last Name", "trim|required|max_length[" . MAX_100 . "]");
            $this->form_validation->set_rules("email", "Email", "trim|required|max_length[" . MAX_100 . "]|valid_email|is_unique[core_users.email]");
            $this->form_validation->set_rules("mobile_number", "Mobile", "trim|required|max_length[" . MAX_13 . "]");
            $this->form_validation->set_rules("username", "Username", "trim|required|max_length[" . MAX_100 . "]|is_unique[core_users.username]");
            $this->form_validation->set_rules("password", "Password", "trim|required|password_checker|max_length[" . MAX_100 . "]");
            $this->form_validation->set_error_delimiters('', '');

            //Check form validation response : if false then gives error message else insert/update data
            if ($this->form_validation->run() === TRUE) {
                $data = array(
                    "usertype_id" => $request_data["usertype_id"],
                    "first_name" => $request_data["first_name"],
                    "last_name" => $request_data["last_name"],
                    "email" => $request_data["email"],
                    "mobile_number" => $request_data["mobile_number"],
                    "username" => $request_data["username"],
                    "password" => md5($request_data["password"]),
                    "status" => "0",
                    "otp" => $this->generate_otp()
                );

                $this->db->trans_start();
                $user_id = $this->Users_model->insert_data("core_users", $data);
                if ($user_id) {
                    unset($data['password']);
                    $request_data['user_id'] = $_POST['user_id'] = $user_id;
                    $data['id'] = "$user_id";
                    $data['access_token'] = $this->generate_jwt_token($request_data);
                }
                $db_error = $this->db->error();
                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    $this->_request_error(lang("internal_error"), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    write_err_log($db_error);
                } else {
                    $to_email = $request_data["email"];
                    $to_user_name = $request_data["first_name"] . ' ' . $request_data["last_name"];

                    //SMS and MAIL send for OTP (SMS REMAINING)
                    $this->send_otp($user_id, $to_email, $to_user_name, $data['otp']);

                    $data['mobile_number'] = $this->encrypt_decrypt->encrypt($data['mobile_number']);
                    $data['username'] = $this->encrypt_decrypt->encrypt($data['username']);

                    $this->_request_success(lang("user_signup_success"), $data);
                }
            } else {
                $errors = implode(",", $this->form_validation->error_array());
                $this->_request_error($errors);
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

    public function send_otp($user_id, $to_email, $to_user_name, $otp) {
        try {
            //start :: send otp by email
            $emailtemplate_data = $this->Users_model->get_emailtemplate_by_type(OTP_TEMPLATE);
            $message = array();

            $smessage = str_replace('"$NAME"', $to_user_name, $emailtemplate_data['content']);
            $message = str_replace('"$OTP"', $otp, $smessage);
            $message = str_replace('"$FROMEMAIL"', "<a href='mailto:" . FROM_MAIL . "'>" . FROM_MAIL . "</a>", $message);
            $message = str_replace('"$SITE_NAME"', SITE_TITLE, $message);
            $message = str_replace('"$LOGO"', EMAIL_LOGO, $message);
            $message = str_replace('"$YEAR"', date('Y'), $message);

//            $email_logs_id = $this->insert_email_logs($emailtemplate_data['subject'], $message, $to_email, $to_user_name);
            //Load email library 
            $this->load->library('email');
            $this->email->from(FROM_MAIL, FROM_NAME);
            $this->email->to($to_email);

            $this->email->subject($emailtemplate_data['subject']);
            $this->email->message($message);

            if ($this->email->send()) {
                $data = array(
                    'otp' => $otp,
                    'status' => 0,
                );
                $where_arr = array('id' => $user_id, 'deleted_by' => 0, 'usertype_id' => API_MOU_USERTYPE_ID);
                $updated = $this->Users_model->update_data("core_users", $data, $where_arr);
                return $updated;
            } else {
                return false;
            }
            //end :: send otp by email
            //start :: send otp by sms
            //
            //sms code is remaining
            //
            //end :: send otp by sms
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

    /**
     * User signin API
     *
     */
    public function signin_post() {
        try {
            $request_data = json_decode($this->input->raw_input_stream, true);
            //brijal = 0d52d9126c18c8d0c534ffe1ae9d8008
            //123456 = 6f2ffe310b88464af63f7ff0bac0cb0f
            $request_data['username'] = $this->encrypt_decrypt->decrypt($request_data['username']);
            $request_data['password'] = $this->encrypt_decrypt->decrypt($request_data['password']);

            $this->form_validation->set_data($request_data);

            //Set validations
            $this->form_validation->set_rules("username", "Username", "trim|required");
            $this->form_validation->set_rules("password", "Password", "trim|required|max_length[" . MAX_50 . "]");
            $this->form_validation->set_error_delimiters('', '');

            //Check form validation response : if false then gives error message else insert/update data
            if ($this->form_validation->run() === TRUE) {
                $user_name = $request_data['username'];
                $password = $request_data['password'];
                $device_type = isset($request_data['device_type']) ? $request_data['device_type'] : "";
                $device_token = isset($request_data['device_token']) ? $request_data['device_token'] : "";

                $data = $this->Users_model->get_user_detail($user_name, $password);

                if ($data) {
                    $request_data['user_id'] = $data['id'];
                    $data['access_token'] = $this->generate_jwt_token($request_data);

                    if ($data['status'] == 0 && $data['otp'] != null) {
                        $data['is_verified_otp'] = false;

                        // $user_id = $data['id'];
                        // $to_user_name = $data['first_name'] . ' ' . $data['last_name'];
                        // $to_email = $data['email'];
                        // $data['otp'] = $this->generate_otp();
                        // $this->send_otp($user_id, $to_email, $to_user_name, $data['otp']);

                        $data['username'] = $this->encrypt_decrypt->encrypt($data['username']);

                        $this->_request_error(lang("user_anauthorized"), REST_Controller::HTTP_UNAUTHORIZED, $data);
                    } else {
                        $data['is_verified_otp'] = true;
                        unset($data['otp']);
                        $data['device_type'] = $device_type;
                        $data['device_token'] = $device_token;
                        $request_data['user_id'] = $_POST['user_id'] = $data['id'];
                        $data['username'] = $this->encrypt_decrypt->encrypt($data['username']);

                        $this->_request_success(lang("user_signin_success"), $data);
                    }
                } else {
                    $this->_request_error(lang("invalid_username"), REST_Controller::HTTP_UNAUTHORIZED);
                }
            } else {
                $errors = implode(",", $this->form_validation->error_array());
                $this->_request_error($errors);
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

    /**
     * User signin API
     *
     */
    public function verify_otp_post() {
        try {
            $request_data = json_decode($this->input->raw_input_stream, true);

            $request_data['username'] = $this->encrypt_decrypt->decrypt($request_data['username']);
            $request_data['password'] = $this->encrypt_decrypt->decrypt($request_data['password']);

            $this->form_validation->set_data($request_data);

            //Set validations
            $this->form_validation->set_rules("user_id", "User ID", "trim|required|max_length[" . MAX_10 . "]");
            $this->form_validation->set_rules("otp", "OTP", "trim|required|max_length[" . MAX_10 . "]");
            $this->form_validation->set_rules("username", "Username", "trim|required|max_length[" . MAX_100 . "]");
            $this->form_validation->set_rules("password", "Password", "trim|required|max_length[" . MAX_50 . "]");
            $this->form_validation->set_error_delimiters('', '');

            //Check form validation response : if false then gives error message else insert/update data
            if ($this->form_validation->run() === TRUE) {
                $user_id = $request_data['user_id'];
                $otp = $request_data['otp'];
                $username = $request_data['username'];
                $password = $request_data['password'];

                $user_data = $this->Users_model->user_verify_otp($user_id, $otp);
                if (!empty($user_data)) {
                    //update status as active
                    $update_data = array(
                        'otp' => null,
                        'status' => 1,
                    );
                    $where_arr = array('id' => $user_id, 'deleted_by' => 0, 'usertype_id' => API_MOU_USERTYPE_ID);
                    $this->Users_model->update_data("core_users", $update_data, $where_arr);

                    $to_user_name = $user_data['name'];
                    $to_email = $user_data['email'];
                    $this->send_login_credential_mail($to_email, $to_user_name, $username, $password);

                    $data['access_token'] = $this->generate_jwt_token($request_data);
                    $this->_request_success(lang("otp_verify_success"), $data);
                } else {
                    $this->_request_error(lang("otp_verify_fail"), REST_Controller::HTTP_UNAUTHORIZED);
                }
            } else {
                $errors = implode(",", $this->form_validation->error_array());
                $this->_request_error($errors);
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

    public function send_login_credential_mail($to_email, $to_user_name, $username, $password) {
        $emailtemplate_data = $this->Users_model->get_emailtemplate_by_type(API_LOGIN_TEMPLATE);
        $message = array();

        $smessage = str_replace('"$NAME"', "<b>" . $to_user_name . "</b>", $emailtemplate_data['content']);
        $message = str_replace('"$PASSWORD"', "<b>" . $password . "</b>", $smessage);
        $message = str_replace('"$USERNAME"', "<b>" . $username . "</b>", $message);
        $message = str_replace('"$FROMEMAIL"', "<a href='mailto:" . FROM_MAIL . "'>" . FROM_MAIL . "</a>", $message);
        $message = str_replace('"$SITE_NAME"', SITE_TITLE, $message);
        $message = str_replace('"$LOGO"', EMAIL_LOGO, $message);
        $message = str_replace('"$YEAR"', date('Y'), $message);

        $subject = $emailtemplate_data['subject'];
        $email_logs_id = $this->insert_email_logs($subject, $message, $to_email, $to_user_name);
        return $email_logs_id;
    }

    /**
     * Forgot Password API
     *
     */
    public function forgot_password_post() {
        try {
            $request_data = json_decode($this->input->raw_input_stream, true);
            $this->form_validation->set_data($request_data);
            //Set validations

            $this->form_validation->set_rules("email", "Email", "trim|required|max_length[" . MAX_100 . "]|valid_email");
            $this->form_validation->set_error_delimiters('', '');
            //Check form validation response : if false then gives error message else insert/update data
            if ($this->form_validation->run() === TRUE) {
                $email = $request_data['email'];

                $user_data = $this->Users_model->get_user_detail_by_email($email);

                if ($user_data) {
                    // Make a small string (code) to assign to the user // to indicate they've requested a change of // password
                    $code = mt_rand('5000', '200000');
                    $data = array(
                        'forgot_pwd_code' => $code,
                    );
                    // Update okay, send email
                    $url = base_url("reset-password/" . $code);
                    $emailtemplate_data = $this->Users_model->get_emailtemplate_by_type("forgot_password_api");
                    $message = str_replace('"$NAME"', $user_data['first_name'], $emailtemplate_data['content']);
                    $message = str_replace('"$URL"', $url, $message);
                    $message = str_replace('"$SITE_NAME"', SITE_TITLE, $message);
                    $message = str_replace('"$LOGO"', EMAIL_LOGO, $message);
                    $message = str_replace('"$YEAR"', date('Y'), $message);
                    //send mail
                    $this->load->library('email');
                    $this->email->from(FROM_MAIL, FROM_NAME);
                    $this->email->to($email);

                    $this->email->subject($emailtemplate_data['subject']);
                    $this->email->message($message);

                    if ($this->email->send()) {
                        if ($this->Users_model->set_forgot_password_code($data, $email)) {
                            $this->_request_success(lang("forgot_pwd_success"), $data);
                        } else {
                            $this->_request_error(lang("forgot_pwd_fail"), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                        }
                    } else {
                        $this->_request_error(lang("forgot_pwd_fail"), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    }
                } else {
                    $this->_request_error(lang("invalid_email"), REST_Controller::HTTP_UNAUTHORIZED);
                }
            } else {
                $errors = implode(",", $this->form_validation->error_array());
                $this->_request_error($errors);
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

    /**
     * change Password API
     *
     */
    public function change_password_post() {
        try {
            $request_data = json_decode($this->input->raw_input_stream, true);

            $request_data['old_password'] = $this->encrypt_decrypt->decrypt($request_data['old_password']);
            $request_data['new_password'] = $this->encrypt_decrypt->decrypt($request_data['new_password']);

            $this->form_validation->set_data($request_data);

            //Set validations
            $this->form_validation->set_rules("user_id", "User Id", "trim|required");
            $this->form_validation->set_rules('old_password', 'Old Password', 'required|trim|callback_check_old_password|max_length[' . MAX_100 . ']');
            $this->form_validation->set_rules('new_password', 'New Password', 'required|trim|password_checker|max_length[' . MAX_100 . ']');
            $this->form_validation->set_error_delimiters('', '');

            //Check form validation response : if false then gives error message else insert/update data
            if ($this->form_validation->run() === TRUE) {
                $data_array = array(
                    'password' => md5($request_data['new_password']),
                );
                $where_arr = array("id" => $request_data['user_id']);
                $last_id = $this->Users_model->update_data("core_users", $data_array, $where_arr);
                if ($last_id) {
                    $this->_request_success(lang("change_password_success"));
                } else {
                    $this->_request_error(lang("change_password_fail"), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                $errors = implode(",", $this->form_validation->error_array());
                $this->_request_error($errors);
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

    /**
     * Resend OTP API
     *
     */
    public function resend_otp_post() {
        try {
            $request_data = json_decode($this->input->raw_input_stream, true);
            $this->form_validation->set_data($request_data);
            //Set validations
            $this->form_validation->set_rules("user_id", "User ID", "trim|required|is_natural_no_zero");
            $this->form_validation->set_error_delimiters('', '');

            //Check form validation response : if false then gives error message else insert/update data
            if ($this->form_validation->run() === TRUE) {
                $user_id = $request_data['user_id'];
                $where_arr = array('id' => $user_id, 'deleted_by' => 0, 'usertype_id' => MOU_USERTYPE_ID);
                $user_data = $this->Users_model->single_data('core_users', 'id,email,concat(first_name," ",last_name)as name', $where_arr, '', true);

                $to_email = $user_data['email'];
                $to_user_name = $user_data['name'];
                $data['otp'] = $this->generate_otp();
                $this->send_otp($user_id, $to_email, $to_user_name, $data['otp']);
                $data['access_token'] = $this->generate_jwt_token($request_data);
                $this->_request_success(lang("resend_otp_success"), $data);
            } else {
                $errors = implode(",", $this->form_validation->error_array());
                $this->_request_error($errors);
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

    /**
     * Update User Profile API
     *
     */
    public function user_profile_post() {
        try {
            $request_data = json_decode($this->input->raw_input_stream, true);

            $request_data['mobile_number'] = $this->encrypt_decrypt->decrypt($request_data['mobile_number']);

            $this->form_validation->set_data($request_data);

            //Set validations
            $this->form_validation->set_rules("user_id", "User ID", "trim|required|is_natural_no_zero");
            $_POST['id'] = $request_data['user_id'];
            $this->form_validation->set_rules("mobile_number", "Mobile No", "trim|required|max_length[" . MAX_13 . "]");//|is_unique[core_users.mobile_number]
            $this->form_validation->set_rules("first_name", "First Name", "trim|required|max_length[" . MAX_100 . "]");
            $this->form_validation->set_rules("last_name", "Last Name", "trim|required|max_length[" . MAX_100 . "]");
            $this->form_validation->set_rules("email", "Email", "trim|required|max_length[" . MAX_100 . "]|valid_email|is_unique[core_users.email]");
            $this->form_validation->set_error_delimiters('', '');

            //Check form validation response : if false then gives error message else insert/update data
            if ($this->form_validation->run() === TRUE) {
                $data = array(
                    'mobile_number' => $request_data['mobile_number'],
                    'first_name' => $request_data['first_name'],
                    'last_name' => $request_data['last_name'],
                    'email' => $request_data['email'],
                );

                $where_arr = array('id' => $request_data['user_id'], 'deleted_by' => 0, 'status' => 1, 'usertype_id' => MOU_USERTYPE_ID);
                $updated = $this->Users_model->update_data('core_users', $data, $where_arr);

                $data = $this->Users_model->get_user_detail_by_id($request_data);

                $db_error = $this->db->error();
                if ($updated) {

                    $data['mobile_number'] = $this->encrypt_decrypt->encrypt($data['mobile_number']);
                    $data['username'] = $this->encrypt_decrypt->encrypt($data['username']);

                    $this->_request_success(lang("profile_update_success"), $data);
                } else {
                    $this->_request_error(lang("profile_update_fail"), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    write_err_log($db_error);
                }
            } else {
                $errors = implode(",", $this->form_validation->error_array());
                $this->_request_error($errors);
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

    /**
     * Get user profile data
     *
     */
    public function user_basic_detail_post() {
        try {
            $request_data = json_decode($this->input->raw_input_stream, true);
            $user_detail = $this->Users_model->get_user_detail_by_id($request_data);
            if (!empty($user_detail)) {
                $this->_request_success(lang("record_found"), $user_detail);
            } else {
                $this->_request_success(lang("record_not_found"), $user_detail);
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

    /**
     * Check old password
     *
     * @access    public
     * @param    old password
     * @return    true / false
     */
    function check_old_password() {
        $request_data = json_decode($this->input->raw_input_stream, true);
        $old_password = md5($this->encrypt_decrypt->decrypt($request_data['old_password']));

        $data = $this->Users_model->single_data('core_users', 'password', array('id' => $request_data['user_id']), '', false);
        if ($old_password != $data->password) {
            $this->form_validation->set_message('check_old_password', 'Invalid old password!.');
            return false;
        } else {
            return true;
        }
    }

    /**
     * Sign out API
     *
     */
    public function sign_out_post() {
        try {
            $request_data = json_decode($this->input->raw_input_stream, true);
            $this->form_validation->set_data($request_data);
            //Set validations

            $this->form_validation->set_rules("user_id", "User Id", "trim|required");
            $this->form_validation->set_error_delimiters('', '');
            //Check form validation response : if false then gives error message else insert/update data
            if ($this->form_validation->run() === TRUE) {
                $data_array = array(
                    'access_token' => null,
                );
                $where_arr = array("id" => $request_data['user_id']);
                $this->db->trans_start();
                $this->Users_model->update_data("users", $data_array, $where_arr);

                $token_data_array = array(
                    'device_type' => null,
                    'token_id' => null,
                    'status' => 0,
                );
                $this->Users_model->update_data("user_device_token", $token_data_array, ["user_id" => $request_data['user_id']]);

                $this->headers = $headers = getallheaders();
                $access_token = "";
                if (isset($headers['access_token'])) {
                    $access_token = $headers['access_token'];
                }

                $db_error = $this->db->error();
                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    $this->_request_error(lang("internal_error"), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    write_err_log($db_error);
                } else {
                    $this->_request_success(lang("sign_out_success"));
                }
            } else {
                $errors = implode(",", $this->form_validation->error_array());
                $this->_request_error($errors);
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

    // Function to get the client ip address
    function get_user_ip_server() {
        $ipaddress = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (!empty($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (!empty($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (!empty($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (!empty($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = $_SERVER['REMOTE_ADDR'];

        return $ipaddress;
    }

    // Get IP Details
    function ip_details($ip) {
        $json = file_get_contents("http://ipinfo.io/{$ip}/geo");
        $details = json_decode($json, true);
        return $details;
    }

    /**
     * Get APP Version API
     *
     */
    public function app_version_post() {
        try {
            $request_data = json_decode($this->input->raw_input_stream, true);
            $this->form_validation->set_data($request_data);

            //Set validations
            $this->form_validation->set_rules("device_type", "Device Type", "trim|required|in_list[A,I]");
            $this->form_validation->set_error_delimiters('', '');

            if ($this->form_validation->run() === TRUE) {

                $device_type = $request_data['device_type'];

                if ($device_type == "A") {
                    $app_version = $this->config->item('android_version');
                } elseif ($device_type == "I") {
                    $app_version = $this->config->item('ios_version');
                }

                $this->_request_success(lang("record_found"), $app_version);

            } else {
                $errors = implode(",", $this->form_validation->error_array());
                $this->_request_error($errors);
            }
        } catch (Exception $e) {
            log_message("Error", $e->getMessage());
            $this->_request_error($e->getMessage());
        }
    }

}
