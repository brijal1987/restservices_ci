<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
  |--------------------------------------------------------------------------
  | Display Debug backtrace
  |--------------------------------------------------------------------------
  |
  | If set to TRUE, a backtrace will be displayed along with php errors. If
  | error_reporting is disabled, the backtrace will not display, regardless
  | of this setting
  |
 */
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);


/*
  |--------------------------------------------------------------------------
  | File and Directory Modes
  |--------------------------------------------------------------------------
  |
  | These prefs are used when checking and setting modes when working
  | with the file system.  The defaults are fine on servers with proper
  | security, but you may wish (or even need) to change the values in
  | certain environments (Apache running a separate process for each
  | user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
  | always be used to set the mode correctly.
  |
 */
defined('FILE_READ_MODE') OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE') OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE') OR define('DIR_WRITE_MODE', 0755);

/*
  |--------------------------------------------------------------------------
  | File Stream Modes
  |--------------------------------------------------------------------------
  |
  | These modes are used when working with fopen()/popen()
  |
 */
defined('FOPEN_READ') OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE') OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE') OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE') OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE') OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE') OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT') OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT') OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
  |--------------------------------------------------------------------------
  | Exit Status Codes
  |--------------------------------------------------------------------------
  |
  | Used to indicate the conditions under which the script is exit()ing.
  | While there is no universal standard for error codes, there are some
  | broad conventions.  Three such conventions are mentioned below, for
  | those who wish to make use of them.  The CodeIgniter defaults were
  | chosen for the least overlap with these conventions, while still
  | leaving room for others to be defined in future versions and user
  | applications.
  |
  | The three main conventions used for determining exit status codes
  | are as follows:
  |
  |    Standard C/C++ Library (stdlibc):
  |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
  |       (This link also contains other GNU-specific conventions)
  |    BSD sysexits.h:
  |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
  |    Bash scripting:
  |       http://tldp.org/LDP/abs/html/exitcodes.html
  |
 */
defined('CURRENT_DATETIME') OR define('CURRENT_DATETIME', date('Y-m-d H:i:s'));
defined('CURRENT_DATE') OR define('CURRENT_DATE', date('Y-m-d'));

defined('MAX_01') OR define('MAX_01', 1);
defined('MAX_02') OR define('MAX_02', 2);
defined('MAX_03') OR define('MAX_03', 3);
defined('MAX_04') OR define('MAX_04', 4);
defined('MAX_05') OR define('MAX_05', 5);
defined('MAX_06') OR define('MAX_06', 6);
defined('MAX_08') OR define('MAX_08', 8);
defined('MAX_10') OR define('MAX_10', 10);
defined('MAX_13') OR define('MAX_13', 13);
defined('MAX_15') OR define('MAX_15', 15);
defined('MAX_20') OR define('MAX_20', 20);
defined('MAX_30') OR define('MAX_30', 30);
defined('MAX_40') OR define('MAX_40', 40);
defined('MAX_50') OR define('MAX_50', 50);
defined('MAX_60') OR define('MAX_60', 60);
defined('MAX_100') OR define('MAX_100', 100);
defined('MAX_110') OR define('MAX_110', 110);
defined('MAX_150') OR define('MAX_150', 150);
defined('MAX_255') OR define('MAX_255', 255);
defined('MAX_500') OR define('MAX_500', 500);
defined('MAX_1000') OR define('MAX_1000', 1000);

defined('CSRF_TOKEN_MP') OR define('CSRF_TOKEN_MP', 'csrf_token_mp');
//STRAT :: API CONSTANTS
defined('API_LOG_PATH') OR define('API_LOG_PATH', 'D:/wamp64/www/services/');
defined('JWT_CONSUMER_KEY') OR define('JWT_CONSUMER_KEY', 'mpeda@dev'); // jwt token    
defined('SHA256_KEY') OR define('SHA256_KEY', '59A2CF8907DD9617F2B5A807BBFB57BB80719D560982206C8EBA12D4D5A079B1'); // jwt token
defined('SHA256_IV') OR define('SHA256_IV', '8217BC4EB7AD6A350EF84AE43B1151F0'); // jwt token
defined('SHA256_METHOD') OR define('SHA256_METHOD', 'AES-256-CBC'); // jwt token
defined('API_MOU_USERTYPE_ID') OR define('API_MOU_USERTYPE_ID', 6);
defined('OTP_TEMPLATE') OR define('OTP_TEMPLATE', "otp_credentials");
defined('API_LOGIN_TEMPLATE') OR define('API_LOGIN_TEMPLATE', "login_credentials");
defined('API_PAGE_LIMIT') OR define('API_PAGE_LIMIT', 20);
//END:: API CONSTANTS   

?>