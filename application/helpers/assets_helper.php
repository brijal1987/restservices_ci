<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

function assets_url() {
    return base_url();
}

function base64url_encode($data, $pad = null) {
   $data = str_replace(array('+', '/'), array('-', '_'), base64_encode($data));
//    if (!$pad) {
        $data = rtrim($data, '-');
        $data = rtrim($data, '=');
//    }
    return $data;
}

function base64url_decode($data) {
    return base64_decode(str_replace(array('-', '_'), array('+', '/'), $data));
}

?>