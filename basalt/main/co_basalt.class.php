<?php
/***************************************************************************************************************************/
/**
    BASALT Extension Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC/The Great Rift Valley Software Company
    
    LICENSE:
    

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_BASALT_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

define('__BASALT_VERSION__', '1.0.0.3003');

if (!defined('LGV_ACCESS_CATCHER')) {
    define('LGV_ACCESS_CATCHER', 1);
}

if ( !defined('LGV_ANDISOL_CATCHER') ) {
    define('LGV_ANDISOL_CATCHER', 1);
}

require_once(CO_Config::andisol_main_class_dir().'/co_andisol.class.php');
require_once(CO_Config::main_class_dir().'/a_co_basalt_plugin.class.php');

if ( !defined('LGV_LANG_CATCHER') ) {
    define('LGV_LANG_CATCHER', 1);
}

require_once(CO_Config::lang_class_dir().'/common.inc.php');

define('_PLUGIN_NAME_', 'baseline');

/****************************************************************************************************************************/
/**
 BASALT is the principal "interface" class for BAOBAB. It can be extended by writing simple PHP "plugins," and comes with
 four "default" ones: "people", "places" and "things". "baseline" is a "host" pseudo-plugin.
 */
class CO_Basalt extends A_CO_Basalt_Plugin {
    protected   $_andisol_instance; ///< This contains the instance of ANDISOL used by this instance.
    protected   $_path;             ///< This array will contain any path components that are received via GET, PUT, POST or DELETE.
    protected   $_vars;             ///< This associative array will contain any query variables that are received via GET, PUT, POST or DELETE.
    protected   $_request_type;     /**< This will contain the HTTP Request Type, in uppercase.
                                            It will be one of:
                                                - 'GET'
                                                - 'POST'
                                                - 'PUT'
                                                - 'DELETE'
                                    */
    protected   $_response_type;    ///< This is the reponse type. It is 'json', 'xml' or 'xsd'.
    protected   $_plugin_selector;  ///< This will be a lowercase string, denoting the plugin selected for the operation.
    
    var         $version;           ///< The version indicator.
    var         $error;             ///< Any errors that occured are kept here.

    /************************************************************************************************************************/    
    /*#################################################### INTERNAL METHODS ################################################*/
    /************************************************************************************************************************/
    
    /***********************/
    /**
    \returns CSV data, as an indexed array of rows, with each row being an associative array. No header.
     */
    protected static function _extract_csv_data(    $in_text_data   ///< REQUIRED: The text data to be parsed as new records for the databases.
                                                ) {
        $csv_array = [];
        $in_text_data = explode("\n", $in_text_data);
        if (isset($in_text_data) && is_array($in_text_data) && (1 < count($in_text_data))) {
            $keys = str_getcsv(array_shift($in_text_data));
            foreach ($in_text_data as $row) {
                $row_temp = str_getcsv($row);
                $row = [];
                foreach ($row_temp as $element) {
                    if (('"NULL"' == $element) || ('NULL' == $element) || ("'NULL'" == $element) || !trim($element)) {
                        $element = NULL;
                    }
                    
                    $row[] = $element;
                }
                if (count($row) == count($keys)) {
                    $row = array_combine($keys, $row);
                    $csv_array[] = $row;
                }
            }
        } else {
            header('HTTP/1.1 400 Invalid Bulk Data');
            exit();
        }
        
        return $csv_array;
    }
        
    /***********************/
    /**
    This static routine formats one line to a CSV string, then outputs it.
     */
    protected static function _output_one_line( $in_line,       ///< REQUIRED: The data line, as an associative array.
                                                $in_header_row  ///< Required: The header row, as an array of strings.
                                            ) {
        $empty_array = array_fill(0, count($in_header_row), 'NULL');
        $template = array_combine($in_header_row, $empty_array);
        
        // What we do, is go through each component of the line, format it for CSV, then add it to the "template" array.
        foreach ($in_line as $key => $value) {
            if (!trim($value) || ('api_key' == $key)) { // We don't back up API keys or empty strings.
                $value = 'NULL';
            } else {
                // Massage for proper CSV format.
                $needs_quotes = preg_match('|[\s",]|', $value);
                $value = str_replace("'", "''", $value);
                $value = str_replace('"', '""', $value);
                $value = str_replace('\\""', '""', $value);
                $value = str_replace('\\\\', '\\', $value);
                if ($needs_quotes) {
                    $value = "\"$value\"";
                }
            }
            
            $template[$key] = $value;
        }
        
        // Send it out.
        echo(implode(',', $template)."\n");
    }
    
    /***********************/
    /**
    This method goes through the passed-in REST query parameters and request paths, and sets up our local instance property with the decoded versions.
    At the end of this method, the internal $_path property will be an array, containing path components, and, if provided, the $_vars property will have any query parameters.
    If provided, the query array will be an associative array, with the key being the query element key, and the value being its value.
    If a query element is provided only as a key, then its value will be set to true.
     */
    protected function _process_basalt_parameters() {
        $paths = isset($_SERVER['PATH_INFO']) ? explode("/", substr($_SERVER['PATH_INFO'], 1)) : [];
        $query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : NULL;
        $path_final = [];
        $vars_final = [];
        $this->_path = [];
        $this->_vars = [];
        $this->_response_type = NULL;
        $this->_plugin_selector = NULL;
        
        $this->_request_type = strtoupper(trim($_SERVER['REQUEST_METHOD']));
        
        // Look to see if we are doing a login. In that case, we only grab a couple of things.
        if ((1 < count($paths)) || (isset($paths[0]) && (('login' == $paths[0]) || ('logout' == $paths[0])))) { // We need at least the response and plugin types. Login and Logout get special handling.
            $response_type = strtolower(trim($paths[0]));
            
            if ('login' == $response_type) {
                $query = explode('&', $query);
                $this->_path = Array('login');
                if (isset($query) && is_array($query) && (2 == count($query))) {
                    $vars_final = [];
                        
                    foreach ($query as $param) {
                        // Now, see if we have a bunch of parameters.
                        $key = trim($param);
                        $value = NULL;
        
                        $parts = explode('=', $param, 2);
                        if (1 < count($parts)) {
                            $key = trim($parts[0]);
                            $value = trim($parts[1]);
                        }
        
                        if ($key) {
                            if (!isset($value) || !$value) {
                                $value = true;
                            }
        
                            // remember that if we repeat the key, the first value is overwritten by the second (or third, or so on).
                            $vars_final[$key] = $value;
                        }
                    }
                    $this->_vars = $vars_final;
                } else {
                    header('HTTP/1.1 403 Unauthorized Login');
                    exit();
                }
            } elseif ('logout' == $response_type) { // We simply ignore anything else for logout.
                $this->_path = Array('logout');
            } else { // We handle the rest
                // Get the response type.
                if (('json' == $response_type) || ('xml' == $response_type) || ('xsd' == $response_type) || ('csv' == $response_type)) {
                    array_shift($paths);
                
                    $this->_response_type = $response_type;
                
                    $plugin_selector = strtolower(trim($paths[0]));
                
                    // Make sure that we are calling a valid plugin.
                    if (in_array($plugin_selector, $this->get_plugin_names())) {
                        $vars_final = [];
                        
                        $this->_plugin_selector = $plugin_selector;
                
                        array_shift($paths);
                
                        // We now trim the strings in the remaining paths, and make sure that we don't have any empties.
                        $this->_path = array_filter(array_map('trim', $paths), function($i){return '' != $i;});
        
                        // Next, we examine any query parameters.
                        $query = explode('&', $query);
        
                        if (isset($query) && is_array($query) && count($query)) {
                            foreach ($query as $param) {
                                // Now, see if we have a bunch of parameters.
                                $key = trim($param);
                                $value = NULL;
                
                                $parts = explode('=', $param, 2);
                                if (1 < count($parts)) {
                                    $key = trim($parts[0]);
                                    $value = urldecode(trim($parts[1]));
                                }
                
                                if ($key) {
                                    if (!isset($value)) {
                                        $value = true;
                                    }
                
                                    // remember that if we repeat the key, the first value is overwritten by the second (or third, or so on).
                                    $vars_final[$key] = $value;
                                }
                            }
                        }

                        $file_data = '';
                        
                        if (!isset($vars_final['remove_payload'])) { // If they did not specify a payload, maybe they want one removed?
                            // POST is handled differently from PUT. POST gets proper background handling, while PUT needs a very raw approach.
                            if ('POST' == $this->_request_type) {
                                if (isset($_FILES['payload']) && (!isset($_FILES['payload']['error']) || is_array($_FILES['payload']['error']))) {
                                    header('HTTP/1.1 400 '.print_r($_FILES['payload']['error'], true));
                                    exit();
                                } elseif (isset($_FILES['payload'])) {
                                    $file_data = file_get_contents($_FILES['payload']['tmp_name']);
                                }
                            } elseif ('PUT' == $this->_request_type) {
                                // See if they have sent any data to us via the standard HTTP channel (PUT).
                                $put_data = fopen('php://input', 'r');
                                if (isset($put_data) && $put_data) {
                                    while ($data = fread($put_data, 2048)) {    // Read it in 2K chunks.
                                        $file_data .= $data;
                                    }
                                    fclose($put_data);
                                }
                            }
                        
                            // This can only go to payload.
                            if (isset($file_data) && $file_data) {
                                $vars_final['payload'] = base64_decode($file_data);
                            } elseif (isset ($vars_final['payload'])) {
                                // See if the payload is already base64.
                                if (base64_encode(base64_decode($vars_final['payload'])) == $vars_final['payload']) {
                                    $vars_final['payload'] = base64_decode($vars_final['payload']);
                                }
                            }
                        }
                        
                        $this->_vars = $vars_final;
                    } else {
                        header('HTTP/1.1 400 Unsupported or Missing Plugin');
                        exit();
                    }
                } else {
                    header('HTTP/1.1 400 Improper Return Type');
                    exit();
                }
            }
        } else {
            header('HTTP/1.1 400 Missing Path Components');
            exit();
        }
    }
    
    /***********************/
    /**
    This runs our command.
    
    \returns the HTTP response string.
     */
    protected function _process_command() {
        $header = '';
        $result = '';
        
        if (isset($this->_andisol_instance) && ($this->_andisol_instance instanceof CO_Andisol) && $this->_andisol_instance->valid()) {
            if ('baseline' == $this->_plugin_selector) {
                if (('GET' == $this->_request_type) || ('POST' == $this->_request_type)) {
                    $result = $this->process_command($this->_andisol_instance, $this->_request_type, $this->_response_type, $this->_path, $this->_vars);
                } else {
                    $header = 'HTTP/1.1 400 Incorrect HTTP Request Method';
                    exit();
                }
            } else {
                $plugin_filename = 'co_'.$this->_plugin_selector.'_basalt_plugin.class.php';
                $plugin_classname = 'CO_'.$this->_plugin_selector.'_Basalt_Plugin';
                $plugin_dirs = CO_Config::plugin_dirs();
                $plugin_file = '';
            
                foreach ($plugin_dirs as $plugin_dir) {
                    if (isset($plugin_dir) && is_dir($plugin_dir)) {
                        // Iterate through that directory, and get each plugin directory.
                        foreach (new DirectoryIterator($plugin_dir) as $fileInfo) {
                            if ($plugin_filename == $fileInfo->getBasename()) {
                                $plugin_file = $fileInfo->getPathname();
                                break;
                            }
                        }
                    }
                }
            
                if ($plugin_file) {
                    require_once($plugin_file);
                    $plugin_instance = new $plugin_classname();
                    if ($plugin_instance instanceof A_CO_Basalt_Plugin) {
                        $result = $plugin_instance->process_command($this->_andisol_instance, $this->_request_type, $this->_response_type, $this->_path, $this->_vars);
                    } else {
                        header('HTTP/1.1 400 Unsupported or Missing Plugin');
                        exit();
                    }
                } else {
                    header('HTTP/1.1 400 Unsupported or Missing Plugin');
                    exit();
                }
            }
        
            switch ($this->_response_type) {
                case 'xsd':
                case 'xml':
                    $header .= 'Content-Type: text/xml';
                    break;
                
                case 'json':
                    $header .= 'Content-Type: application/json';
                    break;
                
                default:
                    $header = 'HTTP/1.1 400 Improper Return Type';
                    $result = '';
            }
        } else {
            if (isset($this->_andisol_instance) && ($this->_andisol_instance instanceof CO_Andisol)) {
                $this->error = $this->_andisol_instance->error;
                if (isset($this->error) && ($this->error->error_code == CO_Lang_Common::$login_error_code_api_key_mismatch) || ($this->error->error_code == CO_Lang_Common::$pdo_error_code_invalid_login)) {
                    $header = 'HTTP/1.1 401 Unauthorized API Key';
                } elseif (isset($this->error) && ($this->error->error_code == CO_Lang_Common::$login_error_code_api_key_invalid)) {
                    $header = 'HTTP/1.1 408 API Key Timeout';
                } else {
                    $header = 'HTTP/1.1 400 General Error';
                }
            } else {
                $header = 'HTTP/1.1 400 General Error';
            }
        }
        
        if ($header) {
            header($header);
        }
        
        $handler = null;
        
        if ( zlib_get_coding_type() === false )
            {
            $handler = "ob_gzhandler";
            }
        
        ob_start($handler);
        echo($result);
		ob_end_flush();
        exit();
    }
    
    /***********************/
    /**
    This runs our baseline token command.
    
    \returns the HTTP response intermediate state, as an associative array.
     */
    protected function _process_token_command(  $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases (ignored).
                                                $in_http_method,        ///< REQUIRED: 'GET' or 'POST' are the only allowed values.
                                                $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings. For the baseline, this should be exactly one element.
                                                $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                                ) {
        $ret = NULL;
        if ($in_andisol_instance->logged_in()) {    // We also have to be logged in to have any access to tokens.
            if (('GET' == $in_http_method) && (!isset($in_path) || !is_array($in_path) || !count($in_path))) {   // Do we just want a list of our tokens?
                $ret = ['tokens' => $in_andisol_instance->get_chameleon_instance()->get_available_tokens()];
            } elseif (('POST' == $in_http_method) && $in_andisol_instance->manager()) {  // If we are handling POST, then we ignore everything else, and create a new token. However, we need to be a manager to do this.
                $ret = ['tokens' => [$in_andisol_instance->make_security_token()]];
            } else {
                header('HTTP/1.1 403 Unauthorized Command');
                exit();
            }
        } else {
            header('HTTP/1.1 403 Unauthorized Command');
            exit();
        }
        return $ret;
    }
    
    /***********************/
    /**
    This runs our baseline serverinfo command.
    
    \returns the HTTP response intermediate state, as an associative array.
     */
    protected function _process_serverinfo_command( $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases (ignored).
                                                    $in_http_method,        ///< REQUIRED: 'GET' or 'POST' are the only allowed values.
                                                    $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings. For the baseline, this should be exactly one element.
                                                    $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                                    ) {
        $ret = NULL;
        if ($in_andisol_instance->god()) {    // We also have to be logged in as God to have any access to serverinfo.
            $ret = ['serverinfo' => []];
            $ret['serverinfo']['basalt_version'] = __BASALT_VERSION__;
            $ret['serverinfo']['andisol_version'] = __ANDISOL_VERSION__;
            $ret['serverinfo']['cobra_version'] = __COBRA_VERSION__;
            $ret['serverinfo']['chameleon_version'] = __CHAMELEON_VERSION__;
            $ret['serverinfo']['badger_version'] = __BADGER_VERSION__;
            $ret['serverinfo']['security_db_type'] = CO_Config::$sec_db_type;
            $ret['serverinfo']['data_db_type'] = CO_Config::$data_db_type;
            $ret['serverinfo']['lang'] = CO_Config::$lang;
            $ret['serverinfo']['min_pw_length'] = intval(CO_Config::$min_pw_len);
            $ret['serverinfo']['regular_timeout_in_seconds'] = intval(CO_Config::$session_timeout_in_seconds);
            $ret['serverinfo']['god_timeout_in_seconds'] = intval(CO_Config::$god_session_timeout_in_seconds);
            $ret['serverinfo']['block_repeated_logins'] = CO_Config::$block_logins_for_valid_api_key ? true : false;
            $ret['serverinfo']['block_differing_ip_address'] = CO_Config::$api_key_includes_ip_address ? true : false;
            $ret['serverinfo']['ssl_requirement_level'] = intval(CO_Config::$ssl_requirement_level);
            $ret['serverinfo']['google_api_key'] = CO_Config::$google_api_key;
            $ret['serverinfo']['allow_address_lookup'] = CO_Config::$allow_address_lookup ? true : false;
            $ret['serverinfo']['allow_general_address_lookup'] = CO_Config::$allow_general_address_lookup ? true : false;
            $ret['serverinfo']['default_region_bias'] = CO_Config::$default_region_bias;
        } else {
            header('HTTP/1.1 403 Unauthorized Command');
            exit();
        }
        return $ret;
    }
    
    /***********************/
    /**
    This runs our baseline command.
    
    \returns the HTTP response intermediate state, as an associative array.
     */
    protected function _process_baseline_command(   $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases (ignored).
                                                    $in_http_method,        ///< REQUIRED: 'GET' or 'POST' are the only allowed values.
                                                    $in_command,            ///< REQUIRED: The command to execute.
                                                    $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings. For the baseline, this should be exactly one element.
                                                    $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                                ) {
        $ret = [];
        
        // No command simply means list the plugins.
        if (('GET' == $in_http_method) && (!isset($in_command) || !$in_command)) {
            $ret = Array('plugins' => CO_Config::plugin_names());
            array_unshift($ret['plugins'], $this->plugin_name());
        } elseif (('POST' == $in_http_method) && $this->_andisol_instance->god() && ('bulk-loader' == $in_command) && CO_Config::$enable_bulk_upload) {  // This is the "bulk-loader." It is POST-only, you must be logged in as God, and the variable needs to be true in the config.
            $ret = $this->_baseline_bulk_loader();
        } elseif ('tokens' == $in_command) {   // If we are viewing or editing the tokens, then we deal with that here.
            $ret = $this->_process_token_command($in_andisol_instance, $in_http_method, $in_path, $in_query);
        } elseif (('serverinfo' == $in_command) && $in_andisol_instance->god()) {   // God can ask for information about the server.
            $ret = $this->_process_serverinfo_command($in_andisol_instance, $in_http_method, $in_path, $in_query);
        } elseif (('backup' == $in_command) && $in_andisol_instance->god() && ('GET' == $in_http_method)) {   // God can ask for a backup of the server (open wide).
            $ret = $this->_baseline_fetch_backup($in_andisol_instance);
        } elseif (('handlers' == $in_command) && isset($in_path[0]) && trim($in_path[0])) {
            $id_list = explode(',', trim($in_path[0]));
            $id_list = array_map('intval', $id_list);
            $handlers = [];
            foreach ($id_list as $id) {
                if (1 < $id) {
                    $class_name = $in_andisol_instance->get_data_access_class_by_id($id);
                    if ($class_name) {
                        $handler = self::_get_handler($class_name);
                        if ($handler) {
                            $ret[$handler][] = $id;
                        }
                    }
                }
            }
        } elseif (('visibility' == $in_command) && $in_andisol_instance->logged_in()) {
            if ('token' == trim($in_path[0])) {
                $token = intval(trim($in_path[1]));
                if ((0 <= $token) && $in_andisol_instance->get_chameleon_instance()->i_have_this_token($token)) {
                    $ids = $in_andisol_instance->get_chameleon_instance()->get_all_login_objects_with_access($token);
                    if (count($ids)) {
                        $ret['token']['token'] = $token;
                        $ret['token']['login_ids'] = array_map(function($item){ return intval($item->id()); }, $ids);
                    }
                } else {
                    header('HTTP/1.1 400 Invalid Token');
                    exit();
                }
            } else {
                $id = intval(trim($in_path[0]));
                // The ID must be generally valid, and we need to be able to see it.
                if ((1 < $id) && $in_andisol_instance->get_chameleon_instance()->can_i_see_this_data_record($id)) {
                    $record = $in_andisol_instance->get_single_data_record_by_id($id);
                
                    if ($record) {
                        $read_login_records = $in_andisol_instance->get_chameleon_instance()->get_all_login_objects_with_access($record->read_security_id);
                        $write_login_records = $in_andisol_instance->get_chameleon_instance()->get_all_login_objects_with_access($record->write_security_id);
                        $ret['id']['id'] = $id;
                        $ret['id']['writeable'] = $record->user_can_write();
                        $read_login_records = array_map(function($item){ return intval($item->id()); }, $read_login_records);
                        $write_login_records = array_map(function($item){ return intval($item->id()); }, $write_login_records);
                        
                        if (count($write_login_records)) {
                            foreach ($write_login_records as $id) {
                                if (!in_array($id, $read_login_records)) {
                                    $read_login_records[] = $id;
                                }
                            }
                            
                            sort($write_login_records);
                            sort($read_login_records);
                        }
                        
                        if (count($read_login_records)) {
                            $ret['id']['read_login_ids'] = $read_login_records;
                        }
                        
                        if (count($write_login_records)) {
                            $ret['id']['write_login_ids'] = $write_login_records;
                        }
                    }
                } else {
                    header('HTTP/1.1 400 Invalid ID');
                    exit();
                }
            }
        } elseif ('search' == $in_command) {
            // For a location search, all three of these need to be specified, and radius needs to be nonzero.
            $radius = isset($in_query) && is_array($in_query) && isset($in_query['search_radius']) && (0.0 < floatval($in_query['search_radius'])) ? floatval($in_query['search_radius']) : NULL;
            $longitude = isset($in_query) && is_array($in_query) && isset($in_query['search_longitude']) ? floatval($in_query['search_longitude']) : NULL;
            $latitude = isset($in_query) && is_array($in_query) && isset($in_query['search_latitude']) ? floatval($in_query['search_latitude']) : NULL;
            
            $search_page_size = isset($in_query) && is_array($in_query) && isset($in_query['search_page_size']) ? abs(intval($in_query['search_page_size'])) : 0;       // This is the size of a page of results (1-based result count. 0 is no page size).
            $search_page_number = isset($in_query) && is_array($in_query) && isset($in_query['search_page_number']) ? abs(intval($in_query['search_page_number'])) : 0; // Ignored if search_page_size is 0. The page we are interested in (0-based. 0 is the first page).
            $writeable = isset($in_query) && is_array($in_query) && isset($in_query['writeable']);                                                                      // Show/list only things this user can modify.
            $search_name = isset($in_query) && is_array($in_query) && isset($in_query['search_name']) ? trim($in_query['search_name']) : NULL;                          // Search in the object name.

            $search_array = [];
            
            if (isset($radius) && (0 < $radius) && isset($longitude) && isset($latitude)) {
                $location_search = Array('radius' => $radius, 'longitude' => $longitude, 'latitude' => $latitude);
                $search_array['location'] = $location_search;
            }
            
            if (isset($search_name)) {
                $search_array['name'] = Array($search_name, 'use_like' => 1);
            }
            
            $tags = [];
            
            $has_tag = false;
            
            for ($tag = 0; $tag < 10; $tag++) {
                $tag_string = 'search_tag'.$tag;
                $tag_value = isset($in_query) && is_array($in_query) && isset($in_query[$tag_string]) ? trim($in_query[$tag_string]) : NULL;
                if ($tag_value !== NULL) {
                    $has_tag = true;
                }
                $tags[] = $tag_value;
            }
            
            // If there were any specified tags, we search by tag. Otherwise, we don't bother.
            if ($has_tag) {
                $search_array['tags'] = $tags;
                $search_array['tags']['use_like'] = 1;
            }
            
            $object_list = $in_andisol_instance->generic_search($search_array, false, $search_page_size, $search_page_number, $writeable);
        
            if (isset($object_list) && is_array($object_list) && count($object_list)) {
                foreach ($object_list as $instance) {
                    $class_name = get_class($instance);
        
                    if ($class_name) {
                        $handler = self::_get_handler($class_name);
                        $ret[$handler][] = $instance->id();
                    }
                }
            }
        
            if (isset($location_search)) {
                $ret['search_location'] = $location_search;
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This is a special "God Only" method that fetches a backup of the entire set of databases as a CSV dump. It directly outputs CSV data, and bypasses the return type filtering.
     */
    protected function _baseline_fetch_backup(  $in_andisol_instance    ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                            ) {
        $ret = NULL;
        set_time_limit(3600);   // Give us plenty of time.
        
        // Have to be "God," and the variable in the config needs to be set.
        if ($in_andisol_instance->god()) {
            $backup = $in_andisol_instance->get_chameleon_instance()->fetch_backup();
            
            if (isset($backup) && is_array($backup) && (2 == count($backup)) && isset($backup['security']) && is_array($backup['security']) && count($backup['security']) && isset($backup['data']) && is_array($backup['data']) && count($backup['data'])) {
                $header_row = ['id','api_key','login_id','access_class','last_access','read_security_id','write_security_id','object_name','access_class_context','owner','longitude','latitude','tag0','tag1','tag2','tag3','tag4','tag5','tag6','tag7','tag8','tag9','ids','payload'];
                header('Content-Type: text/csv');
                echo(implode(',', $header_row)."\n");
                foreach ($backup['security'] as $line) {
                    self::_output_one_line($line, $header_row);
                }
                foreach ($backup['data'] as $line) {
                    self::_output_one_line($line, $header_row);
                }
                exit();
            } else {
                header('HTTP/1.1 500 Internal Server Error');
                exit();
            }
        } else {
            header('HTTP/1.1 403 Unauthorized User');
            exit();
        }
    }
    
    /***********************/
    /**
    This is a special processing routine that is used to facilitate bulk-loading a BAOBAB server.
    
    The caller must be logged in as a "God" admin, and they upload a CSV file. This file will have certain columns that will be used by this routine to instantiate new records.
    
    This is only of of two Baseline commands called via 'POST'.
    
    \returns the new records, in complete form.
     */
    protected function _baseline_bulk_loader() {
        $ret = NULL;
        set_time_limit(3600);   // Give us plenty of time.
        
        // Have to be "God," and the variable in the config needs to be set.
        if ($this->_andisol_instance->god() && CO_Config::$enable_bulk_upload) {
            if ('POST' == $this->_request_type) {   // We must be a POST. There is only the 'loader' command, no query parameters, and no resource path. Just a simple POST, authenticated as "God," and a 'payload' of a CSV file.
                if (isset($_FILES['payload']) && (!isset($_FILES['payload']['error']) || is_array($_FILES['payload']['error']))) {  // Look for any errors in the payload.
                    header('HTTP/1.1 400 '.print_r($_FILES['payload']['error'], true));
                    exit();
                } elseif (isset($_FILES['payload'])) {
                    $file_data = base64_decode(file_get_contents($_FILES['payload']['tmp_name'])); // We extract the CSV data. It should have been sent as Base64-encoded UTF-8 text, so we decode it first.
                    
                    $csv_data = self::_extract_csv_data($file_data);
                    if (isset($csv_data) && is_array($csv_data) && count($csv_data)) {
                        $ret = ['bulk_upload' => []];   // Prep a response.
                        $records = [];
                        $data_records = [];
                        $security_ids = [];
                        $data_ids = [];
                        
                        // This complex little dance, is so that we make sure that any tokens that used the old ID scheme are moved to the new scheme.
                        foreach ($csv_data as $row) {
                            $in_id = intval($row['id']);
                            $row_result = ['input_id' => $in_id];
                            $new_record = $this->_process_bulk_row($row);
                            // Here's where we track the old scheme. We make a record of each security node that we read.
                            if ($new_record instanceof CO_Security_Node) {
                                $security_ids[$in_id] = intval($new_record->id());  // This is a translation table for the IDs. We save all new security records; whether or not they are a login, as every record is a token.
                            } else {
                                $data_ids[$in_id] = intval($new_record->id());      // We do the same for data IDs.
                            }
                        
                            $records[] = $new_record; // Save the record.
                            $row_result['access_class'] = get_class($new_record);
                            $row_result['output_id'] = $new_record->id();
                            $ret['bulk_upload'][] = $row_result;
                        }
                        
                        // After we're done, we go back through the records, looking for ones with tokens. We then translate each set of tokens.
                        foreach ($records as $object) {
                            if (method_exists($object, 'ids')) {    // We look at the token properties of security IDs.
                                $ids = $object->ids();
                                if (isset($ids) && is_array($ids) && count($ids)) { // Look for tokens.
                                    $new_ids = [];
                                
                                    foreach ($ids as $id) {
                                        // This has the added benefit of removing any ones that don't apply to the dataset.
                                        if (isset($security_ids[$id]) && (1 < $security_ids[$id])) {
                                            $new_ids[] = $security_ids[$id];
                                        }
                                    }
                                
                                    // Replace the object's IDs with the new ones.
                                    $object->set_ids($new_ids);
                                }
                            }
                            
                            if (method_exists($object, 'owner_id')) {      // We look to see if there is an "owner" record column.
                                $id = $object->owner_id();   // If so, we translate that.
                                if (isset($data_ids[$id]) && (1 < $data_ids[$id])) {
                                    $object->set_owner_id(intval($data_ids[$id]));
                                }
                            }
                            
                            $context = $object->context;    // Get any context.

                            if (method_exists($object, 'children_ids')) {      // We look to see if there is an "owner" record column.
                                $old_ids = $object->children_ids(true);
                                if (isset($old_ids) && is_array($old_ids) && count($old_ids)) {
                                    $object->deleteAllChildren();
                                    $new_ids = [];
                                    foreach ($old_ids as $id) {
                                        if (isset($data_ids[$id]) && (1 < $data_ids[$id])) {
                                            $new_ids[] = intval($data_ids[$id]);
                                        }
                                    }
                                    
                                    if (isset($new_ids) && is_array($new_ids) && count($new_ids)) {
                                        if (!$object->set_children_ids($new_ids)) {
                                            header('HTTP/1.1 500 Internal Server Error');
                                            exit();
                                        }
                                    }
                                }
                            }

                            if ($object instanceof CO_User_Collection) {
                                $old_id = intval($object->tags()[0]);
                                $new_id = $security_ids[$old_id];
                                
                                if ($new_id) {
                                    $object->set_tag(0, $new_id);
                                }
                            }
                            
                            $read = $object->read_security_id;
                            
                            if (1 < $read) {
                                $read = $security_ids[$read];
                            }
                            
                            // All objects have read and write tokens that need to be translated.
                            $object->set_read_security_id($read);
                            if (0 < $object->write_security_id) {
                                $object->set_write_security_id($security_ids[$object->write_security_id]);
                            } else {
                                $object->set_write_security_id(-1);
                            }
                            
                            if (!$object->clear_batch_mode()) {
                                header('HTTP/1.1 500 Internal Server Error');
                                exit();
                            }
                        }
                    } else {
                        header('HTTP/1.1 400 Invalid Bulk Data');
                        exit();
                    }
                }
            } else {
                header('HTTP/1.1 400 Improper HTTP Method');
                exit();
            }
        } else {
            header('HTTP/1.1 403 Unauthorized User');
            exit();
        }
        return $ret;
    }
    
    /***********************/
    /**
     */
    protected function _process_bulk_row(   $in_row_data    ///< REQUIRED: The associative array that describes this row. It is in 
                                        ) {
        $access_class = $in_row_data['access_class'];
        
        // Make sure that we don't step on any logins.
        if ($this->_andisol_instance->get_chameleon_instance()->check_login_exists_by_login_string($in_row_data['login_id'])) {
            $in_row_data['login_id'] .= '-copy';
        }
        
        $new_record = $this->_andisol_instance->get_chameleon_instance()->make_new_blank_record($access_class);
        
        if (isset($new_record) && ($new_record instanceof $access_class)) {
            $in_row_data['id'] = $new_record->id();
            $new_record->set_batch_mode();
            $new_record->load_from_db($in_row_data);
            if (!$new_record->update_db()) {
                header('HTTP/1.1 500 Internal Server Error');
                exit();
            }
            return $new_record;
        } else {
            header('HTTP/1.1 400 Invalid Bulk Data');
            exit();
        }
    }
    
    /***********************/
    /**
    This returns the schema for this plugin as XML XSD.
    
    \returns XML, containing the schema for this plugin's responses. The schema needs to be comprehensive.
     */
    protected function _get_xsd() {
        return $this->_process_xsd(dirname(__FILE__).'/schema.xsd');
    }

    /************************************************************************************************************************/    
    /*##################################################### PUBLIC METHODS #################################################*/
    /************************************************************************************************************************/
    
    /***********************/
    /**
    Constructor
     */
    public function __construct() {
        $this->version = __BASALT_VERSION__;
        $this->error = NULL;
        $this->_andisol_instance = NULL;
        try {
            // IIS puts "off" in the HTTPS field, so we need to test for that.
            $https = ((!empty($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] !== 'off') || (intval($_SERVER['SERVER_PORT']) == 443)))) ? true : false;
            if ((CO_CONFIG_HTTPS_ALL > CO_Config::$ssl_requirement_level) || $https) {
                $this->_process_basalt_parameters();

                // If this is a login, we do nothing else. We simply handle the login.
                if ((1 == count($this->_path)) && ('login' == $this->_path[0])) {
                    if (isset($this->_vars) && isset($this->_vars['login_id']) && isset($this->_vars['password'])) {
                        // We have the option (default on) of requiring TLS/SSL for logging in, so we check for that now.
                        if ($https || (CO_CONFIG_HTTPS_OFF == CO_Config::$ssl_requirement_level)) {
                            $login_id = $this->_vars['login_id'];
                            $password = $this->_vars['password'];
                        
                            // See if we have our validator in place.
                            if (method_exists('CO_Config', 'call_login_validator_function')) {
                                if (!CO_Config::call_login_validator_function($login_id, $password, $_SERVER)) {
                                    header('HTTP/1.1 403 Unauthorized Login');
                                    exit();
                                }
                            }
                        
                            // We do a simple login. This will also generate an API key, which is the only response to this command.
                            $andisol_instance = new CO_Andisol($login_id, '', $password);
                    
                            if (isset($andisol_instance) && ($andisol_instance instanceof CO_Andisol) && $andisol_instance->logged_in()) {
                                if (method_exists('CO_Config', 'call_log_handler_function')) {
                                    CO_Config::call_log_handler_function($andisol_instance, $_SERVER);
                                }
                                $login_item = $andisol_instance->get_login_item();
                        
                                // If we are logging in, we shortcut the process, and simply return the API key.
                                if (isset($login_item) && ($login_item instanceof CO_Security_Login)) {
                                    $api_key = $login_item->get_api_key();
                                    // From now on, in order to access the login resources, you'll need to include the API key in the username/password fields.
                                    if (isset($api_key)) {
                                        echo($api_key);
                                    } else {
                                        header('HTTP/1.1 403 Unauthorized Login');
                                    }
                                } else {
                                    header('HTTP/1.1 403 Unauthorized Login');
                                }
                            } else {
                                header('HTTP/1.1 403 Unauthorized Login');
                            }
                        } else {
                            header('HTTP/1.1 401 SSL Connection Required');
                        }
                    } else {
                        header('HTTP/1.1 401 Credentials Required');
                    }
                
                    exit();
                } elseif ((1 == count($this->_path)) && ('logout' == $this->_path[0]))  {   // See if the user wants to log out a session.
                    $server_secret = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : NULL;
                    $api_key = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : NULL;
                    
                    if (!$server_secret || !$api_key) {
                        $auth = explode('&', $_SERVER['QUERY_STRING']);
                        foreach ($auth as $query) {
                            $exp = explode('=', $query);
                            if ('login_server_secret' == $exp[0]) {
                                $server_secret = trim($exp[1]);
                            } elseif ('login_api_key' == $exp[0]) {
                                $api_key = trim($exp[1]);
                            }
                        }
                    }
                
                    // See if an SSL connection is required.
                    if ($https || (CO_CONFIG_HTTPS_LOGGED_IN_ONLY > CO_Config::$ssl_requirement_level)) {
                        // If we don't have a valid API key/Server Secret pair, we scrag the process.
                        if(!(isset($api_key) && $api_key && ($server_secret == Co_Config::server_secret()))) {
                            header('HTTP/1.1 403 Cannot Logout Without Valid Credentials');
                        } else {
                            $andisol_instance = new CO_Andisol('', '', '', $api_key);
                
                            if (isset($andisol_instance) && ($andisol_instance instanceof CO_Andisol) && $andisol_instance->logged_in()) {
                                if (method_exists('CO_Config', 'call_log_handler_function')) {
                                    CO_Config::call_log_handler_function($andisol_instance, $_SERVER);
                                }
                            
                                $login_item = $andisol_instance->get_login_item();
                    
                                // We "log out" by clearing the API key.
                                if (isset($login_item) && ($login_item instanceof CO_Security_Login)) {
                                    if ($login_item->clear_api_key()) {
                                        header('HTTP/1.1 205 Logout Successful');
                                    } else {    // This will probably never happen, but belt and suspenders...
                                        header('HTTP/1.1 200 Logout Unneccessary');
                                    }
                                } else {    // This will probably never happen, but belt and suspenders...
                                    header('HTTP/1.1 500 Internal Server Error');
                                }
                            } else {
                                header('HTTP/1.1 403 Unauthorized Login');
                            }
                        }
                    } else {
                        header('HTTP/1.1 401 SSL Connection Required');
                    }
                
                    exit();
                } else {    // Handle the rest of the requests here.
                    // Look for authentication.

                    $server_secret = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : NULL;   // Supplied to the client by the Server Admin.
                    $api_key = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : NULL;             // Generated by the server for this session.
                    
                    if (!$server_secret || !$api_key) {
                        $auth = explode('&', $_SERVER['QUERY_STRING']);
                        foreach ($auth as $query) {
                            $exp = explode('=', $query);
                            if ('login_server_secret' == $exp[0]) {
                                $server_secret = trim($exp[1]);
                            } elseif ('login_api_key' == $exp[0]) {
                                $api_key = trim($exp[1]);
                            }
                        }
                    }
                    
                    // If we don't have a valid API key/Server Secret pair, we just forget about API keys.
                    if(!(isset($api_key) && $api_key && ($server_secret == Co_Config::server_secret()))) {
                        $api_key = NULL;
                    }

                    $https_requirement = true;
                
                    // Make sure we are HTTPS, or SSL is not required.
                    switch (CO_Config::$ssl_requirement_level) {
                        case CO_CONFIG_HTTPS_ALL:
                            // Yeah, it's required.
                            break;
                    
                        case CO_CONFIG_HTTPS_LOGGED_IN_ONLY:
                            // Only if we have an authentication header.
                            $https_requirement = (NULL != $api_key);
                            break;
                        
                        default:
                            // Not necessary if we are login only or off.
                            $https_requirement = false;
                            break;
                    }
                
                    if ($https || !$https_requirement) {
                        $andisol_instance = new CO_Andisol('', '', '', $api_key);
                        if (isset($andisol_instance) && ($andisol_instance instanceof CO_Andisol)) {
                            if (method_exists('CO_Config', 'call_log_handler_function')) {
                                CO_Config::call_log_handler_function($andisol_instance, $_SERVER);
                            }
                            $this->_andisol_instance = $andisol_instance;
                        } else {
                            header('HTTP/1.1 500 Internal Server Error');
                            exit();
                        }
                    } else {
                        header('HTTP/1.1 401 SSL Connection Required');
                        exit();
                    }
                }
            } else {
                header('HTTP/1.1 401 SSL Connection Required');
                exit();
            }
         // OK. By the time we get here, we are either logged in, or not logged in, and have a valid ANDISOL instance. We're ready to go. Put on our shades. We're on a mission for Glod.
            $this->_process_command();
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            exit();
        }
    }
    
    /***********************/
    /**
    \returns an array of strings, all lowercase, with the names of all the plugins used by BASALT.
     */
    public function get_plugin_names() {
        $ret = CO_Config::plugin_names();
        array_unshift($ret, $this->plugin_name());
        return $ret;
    }
    
    /***********************/
    /**
    \returns true, if we have an ANDISOL instance up and going.
     */
    public function valid() {
        return isset($this->_andisol_instance) ? $this->_andisol_instance->valid() : false;
    }
    
    /***********************/
    /**
    \returns true, if the current user is successfully logged into the system.
     */
    public function logged_in() {
        return isset($this->_andisol_instance) ? $this->_andisol_instance->logged_in() : false;
    }
        
    /***********************/
    /**
    \returns a string, with our plugin name.
     */
    public function plugin_name() {
        return _PLUGIN_NAME_;
    }
    
    /***********************/
    /**
    This returns an array of classnames, handled by this plugin.
    
    \returns an array of string, with the names of the classes handled by this plugin.
     */
    static public function classes_managed() {
        return [];
    }
    
    /***********************/
    /**
    This runs our baseline command.
    
    \returns the HTTP response string, as either JSON or XML.
     */
    public function process_command(    $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases (ignored).
                                        $in_http_method,        ///< REQUIRED: 'GET' or 'POST' are the only allowed values.
                                        $in_response_type,      ///< REQUIRED: 'json', 'csv', 'xml' or 'xsd' -the response type.
                                        $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings. For the baseline, this should be exactly one element.
                                        $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                    ) {
        $ret = NULL;
        
        if (is_array($in_path) && (3 >= count($in_path))) {
            $command = isset($in_path[0]) ? strtolower(trim(array_shift($in_path))) : [];
            // Backup needs God Admin, GET method, and CSV response format.
            if ((('csv' == $in_response_type) && ('backup' != $command)) || (('backup' == $command) && (!$in_andisol_instance->god() || ('GET' != $in_http_method) || ('csv' != $in_response_type)))) {
                header('HTTP/1.1 400 Improper Baseline Command');
                exit();
            }
            $ret = $this->_process_baseline_command($in_andisol_instance, $in_http_method, $command, $in_path, $in_query);
        } else {
            header('HTTP/1.1 400 Improper Baseline Command');
            exit();
        }
        
        return $this->_condition_response($in_response_type, $ret);
    }
};
