<?php
/***************************************************************************************************************************/
/**
    BLUE DRAGON PHP SDK
    
    © Copyright 2018, Little Green Viper Software Development LLC.
    
    This code is proprietary and confidential code, 
    It is NOT to be reused or combined into any application,
    unless done so, specifically under written license from Little Green Viper Software Development LLC.

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_CONFIG_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

/***************************************************************************************************************************/
/**
This file contains the implementation-dependent configuration settings.
 */
define('_MAIN_DB_TYPE_', 'mysql');

define('_SECURITY_DB_TYPE_', 'mysql');

class CO_Config {
    use tCO_Basalt_Config; // These are the built-in config methods.
    static private $_my_base_dir;
    static private $_god_mode_id = 2;               ///< God Login Security DB ID. This is private, so it can't be programmatically changed.
    static private $_god_mode_password = 'CoreysGoryStory'; ///< Plaintext password for the God Mode ID login. This overrides anything in the ID row.
    static private $_login_validation_callback = NULL;
                                                    /**<    This is a special callback for validating REST logins (BASALT). For most functions in the global scope, this will simply be the function name,
                                                            or as an array (with element 0 being the object, itself, and element 1 being the name of the function).
                                                            If this will be an object method, then it should be an array, with element 0 as the object, and element 1 a string, containing the function name.
                                                            The function signature will be:
                                                                function login_validation_callback (    $in_login_id,  ///< REQUIRED: The login ID provided.
                                                                                                        $in_password,   ///< REQUIRED: The password (in cleartext), provided.
                                                                                                        $in_server_vars ///< REQUIRED: The $_SERVER array, at the time of the call.
                                                                                                    );
                                                            The function will return a boolean, true, if the login is allowed to proceed normally, and false, if the login is to be aborted.
                                                            If false is returned, the REST login will terminate with a 403 Forbidden response.
                                                            It should be noted that there may be security, legal, ethical and resource ramifications for logging.
                                                            It is up to the implementor to ensure compliance with all constraints.
                                                    */
    
    /// These are special callbacks for logging. Read carefully. The first logs the bottom of the stack, the second, the top.
    static private $_low_level_log_handler_function = NULL;
                                                     /**<   WARNING: DANGER WILL ROBINSON DANGER
                                                            This is a special "callback caller" for logging Database calls (PDO). The callback must be defined in the CO_Config::$_low_level_log_handler_function static variable,
                                                            either as a function (global scope), or as an array (with element 0 being the object, itself, and element 1 being the name of the function).
                                                            For most functions in the global scope, this will simply be the function name.
                                                            If this will be an object method, then it should be an array, with element 0 as the object, and element 1 a string, containing the function name.
                                                            The function signature will be:
                                                                function log_callback(  $in_id,     ///< REQUIRED: The numeric login ID of the currently logged-in user..
                                                                                        $in_sql,    ///< REQUIRED: The SQL being sent to the PDO prepared query.
                                                                                        $in_params  ///< REQUIRED: Any parameters that are being sent in the prepared query.
                                                                                    );
                                                            There is no function return.
                                                            The function will take care of logging the SQL query in whatever fashion the user desires.
                                                            THIS SHOULD BE DEBUG ONLY!!! There are so many security implications in leaving this on, that I can't even begin to count. Also, performance will SUCK.
                                                            It should be noted that there may be legal, ethical and resource ramifications for logging.
                                                            It is up to the implementor to ensure compliance with all constraints.
                                                    */

    static private $_log_handler_function =  NULL;
                                                    /**<    This is a special callback for logging REST calls (BASALT). For most functions in the global scope, this will simply be the function name,
                                                            or as an array (with element 0 being the object, itself, and element 1 being the name of the function).
                                                            If this will be an object method, then it should be an array, with element 0 as the object, and element 1 a string, containing the function name.
                                                            The function signature will be:
                                                                function log_callback ( $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance at the time of the call.
                                                                                        $in_server_vars         ///< REQUIRED: The $_SERVER array, at the time of the call.
                                                                                        );
                                                            There is no function return.
                                                            The function will take care of logging the REST connection in whatever fashion the user desires.
                                                            This will assume a successful ANDISOL instantiation, and is not designed to replace the traditional server logs.
                                                            It should be noted that there may be security, legal, ethical and resource ramifications for logging.
                                                            It is up to the implementor to ensure compliance with all constraints.
                                                    */
                                                    
    static private $_server_secret = 'Supercalifragilisticexpialidocious';  ///< This is a random string of characters that must be presented in the authentication header, along with the temporary API key.
    
    static $enable_bulk_upload = true;              ///< If true (default is false), then the "God" admin is allowed to use the baseline 'bulk-upload' POST command.
    static $lang = 'en';                            ///< The default language for the server.
    static $min_pw_len = 8;                         ///< The minimum password length.
    static $session_timeout_in_seconds = 3600;      ///< One-hour API key timeout.
    static $god_session_timeout_in_seconds  = 600;  ///< API key session timeout for the "God Mode" login, in seconds (integer value). Default is 10 minutes.
    static $api_key_includes_ip_address = true;     ///< If true (default is false), then the API key will include the user's IP address in the generation.
    static $block_logins_for_valid_api_key = true;  ///< If this is true, then users cannot log in if there is an active API key in place for that user (forces the user to log out, first).
    static $ssl_requirement_level  = CO_CONFIG_HTTPS_OFF;   /** This is the level of SSL/TLS required for transactions with the server. The possible values are:
                                                                - CO_CONFIG_HTTPS_OFF (0)               ///< This means that SSL is not required for ANY transacation. It is recommended this level be selected for testing only.
                                                                - CO_CONFIG_HTTPS_LOGIN_ONLY (1)        ///< SSL is only required for the initial 'login' call.
                                                                - CO_CONFIG_HTTPS_LOGGED_IN_ONLY (2)    ///< SSL is required for the login call, as well as all calls that include an authentication header.
                                                                - CO_CONFIG_HTTPS_ALL (3)               ///< SSL is required for all calls (Default).
                                                            */
    
    static $data_db_name = 'littlegr_badger_data';
    static $data_db_host = 'db';
    static $data_db_type = _MAIN_DB_TYPE_;
    static $data_db_login = 'littlegr_badg';
    static $data_db_password = 'pnpbxI1aU0L(';

    static $sec_db_name = 'littlegr_badger_security';
    static $sec_db_host = 'db';
    static $sec_db_type = _SECURITY_DB_TYPE_;
    static $sec_db_login = 'littlegr_badg';
    static $sec_db_password = 'pnpbxI1aU0L(';

    /**
    This is the Google API key. It's required for CHAMELEON to do address lookups and other geocoding tasks.
    CHAMELEON requires this to have at least the Google Geocoding API enabled.
    */
    static $google_api_key = 'AIzaSyAPCtPBLI24J6qSpkpjngXAJtp8bhzKzK8';
    
    /**
    This flag, if set to true (default is false), will allow REST users to send in an address as part of a location search.
    This is ignored, if there is no $google_api_key. It should be noted that each address lookup does count against the API key quota, so that should be considered
    before enabling this functionality.
    
    If enabled, REST users will be able to send in a 'search_address_lookup=' (instead of 'search_longitude=' and 'search_latitude=') query parameter, as well as a 'search_radius=' parameter.
    */
    static $allow_address_lookup = true;    
    static $allow_general_address_lookup = false;   ///< If true (default is false), then just anyone (login not required) can do an address lookup. If false, then only logged-in users can do an address lookup. Ignored if $allow_address_lookup is false.
    static $default_region_bias = '';               ///< A default server Region bias.
    
    /***********************/
    /**
    \returns the POSIX path to the main BASALT directory.
     */
    static function set_base_dir(   $in_base_dir
                                ) {
        self::$_my_base_dir = $in_base_dir;
    }
    
    /***********************/
    /**
    \returns the POSIX path to the main BASALT directory.
     */
    static function base_dir() {
        return self::$_my_base_dir;
    }
    
    /***********************/
    /**
    \returns the POSIX path to the RVP Additional Plugins directory.
     */
    static function extension_dir() {
        return dirname(dirname(dirname(dirname(__FILE__)))).'/rvp_plugins';
    }
}

function global_scope_basalt_login_validation_function($in_login_id, $in_password, $in_server_vars) {
    if (preg_match('|TEST\-SCRAG\-BASALT\-LOGIN|', $_SERVER['QUERY_STRING'])) {
        return false;
    }
    
    return true;
}

function global_scope_basalt_logging_function($in_andisol_instance, $in_server_vars) {
    $log_display = $in_server_vars;
    $id = (NULL !== $in_andisol_instance->get_login_item()) ? $in_andisol_instance->get_login_item()->id() : '';
    $login_id = (NULL !== $in_andisol_instance->get_login_item()) ? $in_andisol_instance->get_login_item()->login_id : '';
    $id_entry = '' != $id ? "$id:$login_id" : '-';
    $date_entry = date('\[d\/M\/Y:H:m:s O\]');
    $request_entry = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'];
    $log_entry = $_SERVER['REMOTE_ADDR']. ' - '.$id_entry.' '.$date_entry.' "'.$request_entry.'"';
    $log_file = fopen(dirname(__FILE__).'/test.log', 'a');
    fwrite($log_file, $log_entry."\n");
    fclose($log_file);
}

function global_scope_badger_low_level_logging_function($id, $in_sql, $in_params) {
    $id_entry = '' != $id ? "$id" : '-';
    $date_entry = date('\[d\/M\/Y:H:m:s O\]');
    $request_entry = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'];
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $bt_array = [];
    $bt_trace = '';
    $indent = '';
    foreach ($backtrace as $frame) {
        $bt_array[] = $frame['function'].' ('.$frame['file'].':'.$frame['line'].')';
        $bt_trace .= $indent.$bt_array[count($bt_array) - 1]."\n";
        $indent .= "\t";
    }
    
    if (!isset($in_params) || !$in_params || !is_array($in_params)) {
        $in_params = [];
    }
    
    $log_entry = $id_entry.' "SQL:'.$in_sql.'" "PARAMS:\''.implode('\',\'', $in_params).'\'" "BACKTRACE:'."\n$bt_trace".'"'."\n";
    $log_file = fopen(dirname(__FILE__).'/test.log', 'a');
    fwrite($log_file, $log_entry."\n");
    fclose($log_file);
}
