<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Custom_function {

    /**
     * Set user language
     *
     * @access	public
     * @param	language
     * @return	NA
     */
    public function set_language($language) {
        $ci = &get_instance();
        $ci->load->helper('language');
        $lang_data = unserialize(LANGUAGE);                
        $siteLang = $lang_data[$language];
        if ($siteLang) {
            $ci->config->set_item('language',$siteLang);
            $ci->lang->load('message', $siteLang);
            $ci->lang->load('form_validation', $siteLang);
            
        } else {
            $ci->config->set_item('language','english');
            $ci->lang->load('message', 'english');
            $ci->lang->load('form_validation', $siteLang);
            
        }
    }
    
    /**
     * Send mail
     *
     * @access	public
     * @param	language
     * @return	NA
     */
     public function send_mail($msg,$email,$template,$subject){
         
        $ci =&get_instance();   
        $ci->config->load('email');
        $ci->email->from(EMAIL_FROM, EMAIL_FROM_NAME);
        $ci->email->to($email);
        $ci->email->subject($subject);
        $body = $ci->load->view($template, $msg, TRUE);
        $ci->email->message($body);

        //Send mail 
        $ci->email->send();
     }

    /**
     * Check access token
     *
     * @access	public
     * @param	language,access_token
     * @return	JSON Array
     */
    public function check_access_token($is_json=0) {
        $retarr = array();                
        $_POST = ($is_json <= 0) ? json_decode(file_get_contents('php://input'), true) : $_POST;
        $ci =&get_instance();                
        $ci->custom_function->set_language($ci->input->post('language'));
        $access_token = $ci->input->post('access_token');
        if (!empty($access_token)) {            
            $stored_access_token = get_cookie('access_token');
            if ($access_token == $stored_access_token) {
                return TRUE;
            } else {
               return FALSE;
            }
        }
    }
    /**
     * Get user id from access token
     *
     * @access	public
     * @param	access_token
     * @return	user_id
     */
    public function get_user_id_from_access_token($is_json=0){
        $user_id=0;
        $ci =&get_instance();
        $_POST = ($is_json <= 0) ? json_decode(file_get_contents('php://input'), true) : $_POST;
        $encrypt_key = $ci->config->item('encryption_key');
        $decrypt_access_token = $ci->encrypt->decode($ci->input->post('access_token'), $encrypt_key);
        $result = explode("|", $decrypt_access_token);
        $user_id =$result[0];
        return $user_id;
    }

}
