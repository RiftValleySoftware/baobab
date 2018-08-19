<?php
/***************************************************************************************************************************/
/**
    BASALT Extension Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC.
    
    This code is proprietary and confidential code, 
    It is NOT to be reused or combined into any application,
    unless done so, specifically under written license from Little Green Viper Software Development LLC.

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_BASALT_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

/****************************************************************************************************************************/
/**
This is a REST plugin that accesses and manages information about users and logins.
 */
class CO_people_Basalt_Plugin extends A_CO_Basalt_Plugin {
    /***********************/
    /**
    This returns a fairly short summary of the user or login.
    
    \returns an associative array of strings and integers.
     */
    protected function _get_short_description(  $in_object,                 ///< REQUIRED: The user or login object to extract information from.
                                                $in_additional_info = false ///< OPTIONAL: If true (default is false), then some extra information will be added to the basic ID and name.
                                            ) {
        $ret = parent::_get_short_description($in_object, $in_additional_info);
        
        if ($in_object instanceof CO_Security_Login) {
            $ret['login_id'] = $in_object->login_id;
        }
        
        return $ret;
    }

    /***********************/
    /**
    This returns a more comprehensive description of the login.
    
    \returns an associative array of strings and integers.
     */
    protected function _get_long_description( $in_login_object, ///< REQUIRED: The login object to extract information from.
                                              $ignored = false  ///< This is ignored for logins.
                                            ) {
        $ret = parent::_get_long_description($in_login_object);
        
        $user_item = $in_login_object->get_user_object();
        
        if ($in_login_object->id() == $in_login_object->get_access_object()->get_login_id()) {
            $ret['current_login'] = true;
        }
        
        if (isset($user_item) && ($user_item instanceof CO_User_Collection)) {
            $ret['user_object_id'] = $user_item->id();
        }
        
        $ret['login_id'] = $in_login_object->login_id;
        $ret['is_manager'] = $in_login_object->is_manager();
        $ret['is_main_admin'] = $in_login_object->is_god();
        $ret['security_tokens'] = $in_login_object->get_access_object()->god_mode() && $in_login_object->is_god() ? $in_login_object->get_access_object()->get_all_tokens() : $in_login_object->ids();
        
        $api_key = $in_login_object->get_api_key();
        $key_age = $in_login_object->get_api_key_age_in_seconds();

        if ($api_key) {
            // Most people can see whether or not the user has a current API key.
            $ret['current_api_key'] = true;
            // God can see the key, itself.
            if ($in_login_object->get_access_object()->god_mode()) {
                $ret['api_key'] = $api_key;
                //...and how old it is.
                if ( 0 <= $key_age) {
                    $ret['api_key_age_in_seconds'] = $key_age;
                }
            }
        }
        
        return $ret;
    }

    /***********************/
    /**
    This returns a more comprehensive description of the user.
    
    \returns an associative array of strings, integers and nested associative arrays.
     */
    protected function _get_long_user_description(  $in_user_object,                ///< REQUIRED: The user object to extract information from.
                                                    $in_with_login_info = false,    ///< OPTIONAL: Default is false. If true, then the login information is appended.
                                                    $in_show_parents = false        ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                                    ) {
        $ret = parent::_get_long_description($in_user_object, $in_show_parents);
        
        $test_string = $in_user_object->get_surname();
        if (isset($test_string) && trim($test_string)) {
            $ret['surname'] = $test_string;
        }
        
        $test_string = $in_user_object->get_middle_name();
        if (isset($test_string) && trim($test_string)) {
            $ret['middle_name'] = $test_string;
        }
        
        $test_string = $in_user_object->get_given_name();
        if (isset($test_string) && trim($test_string)) {
            $ret['given_name'] = $test_string;
        }
        
        $test_string = $in_user_object->get_prefix();
        if (isset($test_string) && trim($test_string)) {
            $ret['prefix'] = $test_string;
        }
        
        $test_string = $in_user_object->get_suffix();
        if (isset($test_string) && trim($test_string)) {
            $ret['suffix'] = $test_string;
        }
        
        $test_string = $in_user_object->get_nickname();
        if (isset($test_string) && trim($test_string)) {
            $ret['nickname'] = $test_string;
        }
        
        $tags = $in_user_object->tags();
        if (isset($tags) && is_array($tags) && count($tags)) {
            $test_string = $tags[7];
            if (isset($test_string) && trim($test_string)) {
                $ret['tag7'] = $test_string;
            }
            
            $test_string = $tags[8];
            if (isset($test_string) && trim($test_string)) {
                $ret['tag8'] = $test_string;
            }
            
            $test_string = $tags[9];
            if (isset($test_string) && trim($test_string)) {
                $ret['tag9'] = $test_string;
            }
        }
        
        $ret['is_manager'] = $in_user_object->is_manager();
        
        $ret['is_main_admin'] = $in_user_object->is_god();
        
        if ($in_with_login_info) {
            $login_instance = $in_user_object->get_login_instance();
            if (isset($login_instance) && ($login_instance instanceof CO_Security_Login)) {
                if ($login_instance->id() == $in_user_object->get_access_object()->get_login_id()) {
                    $ret['current_login'] = true;
                }
        
                $ret['associated_login'] = $this->_get_long_description($login_instance);
            }
        } else {
            $login_instance = $in_user_object->get_login_instance();
            if (isset($login_instance) && ($login_instance instanceof CO_Security_Login)) {
                $ret['associated_login_id'] = $login_instance->id();
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns XML, containing the schema for this plugin's responses. The schema needs to be comprehensive.
     */
    protected function _get_xsd() {
        return $this->_process_xsd(dirname(__FILE__).'/schema.xsd');
    }

    /***********************/
    /**
    This handles logins.
    
    \returns an array, with the resulting logins.
     */
    protected function _handle_logins(  $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                        $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings.
                                        $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                    ) {
        $ret = [];
        $show_details = isset($in_query) && is_array($in_query) && isset($in_query['show_details']);                // Flag that applies only for lists, forcing all people to be shown in detail.
        $logged_in = isset($in_query) && is_array($in_query) && isset($in_query['logged_in']) && $in_andisol_instance->manager();   // Flag that filters for only users that are logged in.
        $my_info = isset($in_path) && is_array($in_path) && (0 < count($in_path) && ('my_info' == $in_path[0]));    // This is a directory that specifies only our own user.
        $writeable = isset($in_query) && is_array($in_query) && isset($in_query['writeable']);                      // Show/list only logins this user can modify.
        
        if (isset($my_info) && $my_info) {  // If we are just asking after our own info, then we just send that back.
            if ($in_andisol_instance->logged_in()) {
                $login = $in_andisol_instance->current_login();
                if ($login instanceof CO_Security_Login) {
                    $ret['my_info'] = $this->_get_long_description($login);
                } else {
                    header('HTTP/1.1 400 No Login Available');
                    exit();
                }
            } else {
                header('HTTP/1.1 403 Forbidden');
                exit();
            }
        } elseif (isset($in_path) && is_array($in_path) && (0 < count($in_path))) {
            // See if they want the list of logins for people with logins, or particular people
            // Now, we see if they are a list of integer IDs or strings (login string IDs).
            $login_id_list = array_map('trim', explode(',', $in_path[0]));
            
            $is_numeric = array_reduce($login_id_list, function($carry, $item){ return $carry && ctype_digit($item); }, true);
            
            $login_id_list = $is_numeric ? array_map('intval', $login_id_list) : $login_id_list;
            
            foreach ($login_id_list as $id) {
                if (($is_numeric && (0 < $id)) || !$is_numeric) {
                    $login_instance = $is_numeric ? $in_andisol_instance->get_login_item($id) : $in_andisol_instance->get_login_item_by_login_string($id);
                    if (isset($login_instance) && ($login_instance instanceof CO_Security_Login) && (!$writeable || $login_instance->user_can_write())) {
                        if (!$logged_in || ($logged_in && $login_instance->get_api_key())) { // See if they are filtering for logins.
                            if ($show_details) {
                                $ret[] = $this->_get_long_description($login_instance);
                            } else {
                                $ret[] = $this->_get_short_description($login_instance);
                            }
                        }
                    }
                }
            }
        } else {    // They want the list of all of them.
            $login_id_list = $in_andisol_instance->get_all_login_users();
            if ($in_andisol_instance->get_cobra_instance()) {
                $login_id_list = $in_andisol_instance->get_cobra_instance()->get_all_logins();
                if (0 < count($login_id_list)) {
                    foreach ($login_id_list as $login_instance) {
                        if (isset($login_instance) && ($login_instance instanceof CO_Security_Login) && (!$writeable || $login_instance->user_can_write())) {
                            if (!$logged_in || ($logged_in && $login_instance->get_api_key())) { // See if they are filtering for logins.
                                if ($show_details) {
                                    $ret[] = $this->_get_long_description($login_instance);
                                } else {
                                    $ret[] = $this->_get_short_description($login_instance);
                                }
                            }
                        }
                    }
                }
            } else {
                header('HTTP/1.1 403 Forbidden');
                exit();
            }
        }
        
        return $ret;
    }

    /***********************/
    /**
    This handles the edit (POST, PUT and DELETE) functions for logins.
    
    \returns an array, with the results.
     */
    protected function _handle_edit_logins( $in_andisol_instance,       ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                            $in_http_method,            ///< REQUIRED: 'POST', 'PUT' or 'DELETE'
                                            $in_path = [],              ///< OPTIONAL: The REST path, as an array of strings.
                                            $in_query = [],             ///< OPTIONAL: The query parameters, as an associative array.
                                            $in_show_parents = false    ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                        ) {
        $ret = [];
        
        $logins_to_edit = [];
        
        $also_delete_user = false;
        
        if (isset($in_query) && is_array($in_query) && count($in_query) && isset($in_query['delete_user'])) {
            $also_delete_user = true;
        }
        
        $my_info = isset($in_path) && is_array($in_path) && (0 < count($in_path) && ('my_info' == $in_path[0]));
        
        if (isset($my_info) && $my_info) {  // If we are just asking after our own info, then we just use our own login.
            $logins_to_edit = [$in_andisol_instance->current_login()];
        } elseif (isset($in_path) && is_array($in_path) && (0 < count($in_path))) {
            // We see if they are a list of integer IDs or strings (login string IDs).
            $login_id_list = array_map('trim', explode(',', $in_path[0]));
            
            $is_numeric = array_reduce($login_id_list, function($carry, $item){ return $carry && ctype_digit($item); }, true);
            
            $login_id_list = $is_numeric ? array_map('intval', $login_id_list) : $login_id_list;
            
            foreach ($login_id_list as $id) {
                if (($is_numeric && (0 < $id)) || !$is_numeric) {
                    $login_instance = $is_numeric ? $in_andisol_instance->get_login_item($id) : $in_andisol_instance->get_login_item_by_login_string($id);
                    if (isset($login_instance) && ($login_instance instanceof CO_Security_Login) && $login_instance->user_can_write()) {
                        if (('DELETE' == $in_http_method) && $also_delete_user) {   // If we also want to delete the user, then we need to have write permission on the user, as well.
                            $user_instance = $login_instance->get_user_object();
                            if ($user_instance->user_can_write()) {
                                $logins_to_edit[] = $login_instance;
                            }
                        } else {
                            $logins_to_edit[] = $login_instance;
                        }
                    }
                }
            }
        } elseif ($in_andisol_instance->manager()) {  // Must have a COBRA instance, and be a manager
            $login_id_list = $in_andisol_instance->get_cobra_instance()->get_all_logins();
            if (0 < count($login_id_list)) {
                foreach ($login_id_list as $login_instance) {
                    if (isset($login_instance) && ($login_instance instanceof CO_Security_Login) && $login_instance->user_can_write()) {
                        if (('DELETE' == $in_http_method) && $also_delete_user) {   // If we also want to delete the user, then we need to have write permission on the user, as well.
                            $user_instance = $login_instance->get_user_object();
                            if ($user_instance->user_can_write()) {
                                $logins_to_edit[] = $login_instance;
                            }
                        } else {
                            $logins_to_edit[] = $login_instance;
                        }
                    }
                }
            }
        }
        
        if (('POST' == $in_http_method) && $in_andisol_instance->manager()) {
            $ret = $this->_handle_edit_logins_post($in_andisol_instance, $in_path, $in_query);
        } elseif (isset($logins_to_edit) && is_array($logins_to_edit) && count($logins_to_edit)) {
            if (('DELETE' == $in_http_method) && $in_andisol_instance->manager()) {
                if (!$also_delete_user) {
                    $in_show_parents = false;   // Doesn't count, unless we are deleting a user. Logins can't have parents.
                }
                $ret = $this->_handle_edit_logins_delete($in_andisol_instance, $logins_to_edit, $in_query, $also_delete_user, $in_show_parents);
            } elseif ('PUT' == $in_http_method) {   // Of course, there's always an exception. People can edit their own users.
                $ret = $this->_handle_edit_logins_put($in_andisol_instance, $logins_to_edit, $in_query);
            }
        }
        
        return $ret;
    }

    /***********************/
    /**
    This handles the create (POST) function for logins.
    
    \returns an array, with the results.
     */
    protected function _handle_edit_logins_post(    $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                    $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings.
                                                    $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                                ) {
        $ret = [];
        
        $lang = NULL;
        $name = NULL;
        $read_token = 0;
        
        if ($in_andisol_instance->manager()) {  // Must be a manager
            $is_manager = isset($in_query) && is_array($in_query) && isset($in_query['is_manager']);
            if (isset($in_query['is_manager'])) {
                unset($in_query['is_manager']);
            }
            
            $login_id = NULL;
            $login_string = (isset($in_query['login_string']) && trim($in_query['login_string'])) ? trim($in_query['login_string']) : NULL;
            
            // The first thing we do, is see if a login ID was supplied on the path.
            if (isset($in_path) && is_array($in_path) && (0 < count($in_path)) && trim($in_path[0])) {
                $login_id = trim($in_path[0]);
            } elseif (isset($in_query) && isset($in_query['login_id']) && trim($in_query['login_id'])) {    // How about as a query argument?
                $login_id = trim($in_query['login_id']);
            }
            
            if ($login_id) {    // 'login_string' is deprecated. 'login_id' trumps it.
                $login_string = $login_id;
            }
            
            if ($login_string) {    // Minimum is a login string.
                $params = $this->_build_login_mod_list($in_andisol_instance, $in_query);
        
                if (isset($params['lang']) && trim($params['lang'])) {
                    $lang = trim($params['lang']);
                }
        
                if (isset($params['name']) && trim($params['name'])) {
                    $name = trim($params['name']);
                }
        
                if (isset($params['read_token']) && intval($params['read_token'])) {
                    $read_token = intval($params['read_token']);
                }
        
                $result = true;
            
                $cobra_instance = $in_andisol_instance->get_cobra_instance();
            
                if (isset($cobra_instance) && ($cobra_instance instanceof CO_Cobra)) {
                    $new_login = NULL;
                
                    $password = isset($params['password']) ? $params['password'] : NULL;
                
                    if (!$password || (strlen($password) < CO_Config::$min_pw_len)) {
                        $password = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, CO_Config::$min_pw_len + 2);
                    }
                
                    $tokens = isset($params['tokens']) ? $params['tokens'] : NULL;
                
                    if ($is_manager) {
                        $new_login = $cobra_instance->create_new_manager_login($login_string, $password, $tokens);
                    } else {
                        $new_login = $cobra_instance->create_new_standard_login($login_string, $password, $tokens);
                    }
                
                    if ($new_login instanceof CO_Security_Login) {
                        if ($lang) {
                            $result = $new_login->set_lang($lang);
                        }
                        
                        $id = $new_login->id();
                        
                        if ($result && $name) {
                            $result = $new_login->set_name($name);
                        } elseif ($result) {
                            $result = $new_login->set_name($login_string);
                        }
            
                        if (!$result) {
                            header('HTTP/1.1 400 Error Creating Login');
                            exit();
                        }
                
                        $ret = Array('new_login' => $this->_get_long_description($new_login));
                        $ret['new_login']['password'] = $password;
                    }
                } else {
                    header('HTTP/1.1 403 Forbidden');
                    exit();
                }
            } else {
                header('HTTP/1.1 400 Login String Required');
                exit();
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            exit();
        }
        
        return $ret;
    }

    /***********************/
    /**
    This handles the delete (DELETE) function for logins.
    
    \returns an array, with the results.
     */
    protected function _handle_edit_logins_delete(  $in_andisol_instance,           ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                    $in_logins_to_edit,             ///< REQUIRED: An array of login objects to be affected.
                                                    $in_query = [],                 ///< OPTIONAL: The query parameters, as an associative array.
                                                    $in_also_delete_user = false,   ///< OPTIONAL: (Default is false). If true, then we also want the user to be deleted.
                                                    $in_show_parents = false        ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                                ) {
        $ret = [];
        
        if ($in_andisol_instance->manager()) { // Only managers can delete.
            $ret = Array ('deleted_logins' => []);
        
            if ($in_also_delete_user) {
                $ret['deleted_users'] = [];
            }
        
            foreach ($in_logins_to_edit as $login_object) {
                if ($login_object->id() != CO_Config::god_mode_id()) {  // Can't delete God.
                    if ($in_also_delete_user) { // Do we want to delete any associated user object?
                        $user_object = $login_object->get_user_object();
                        if (isset($user_object) && $user_object->user_can_write()) {
                            $desc = $this->_get_long_user_description($user_object);
                            if (!$user_object->delete_from_db()) {
                                header('HTTP/1.1 400 Unable to Delete User');
                                exit();
                            } else {
                                $ret['deleted_users'][] = $desc;
                            }
                        }
                    }
                
                    $desc = $this->_get_long_description($login_object);
                    if (!$login_object->delete_from_db()) {
                        header('HTTP/1.1 400 Unable to Delete Login');
                        exit();
                    } else {
                        $ret['deleted_logins'][] = $desc;
                    }
                } else {
                    header('HTTP/1.1 400 Cannot Delete Main Admin Login');
                    exit();
                }
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            exit();
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    This handles the edit (PUT) function for logins.
    
    \returns an array, with the results.
     */
    protected function _handle_edit_logins_put( $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                $in_logins_to_edit,     ///< REQUIRED: An array of login objects to be affected.
                                                $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                                ) {
        $ret = [];
        $params = $this->_build_login_mod_list($in_andisol_instance, $in_query);
        
        $lang = NULL;
        $name = NULL;
        $password = NULL;
        $login_string = NULL;
        $read_token = NULL;
        $write_token = NULL;
        $tokens = NULL;
        
        if (isset($params['lang']) && trim($params['lang'])) {
            $lang = trim($params['lang']);
        }
        
        if (isset($params['name']) && trim($params['name'])) {
            $name = trim($params['name']);
        }
        
        if (isset($params['password']) && trim($params['password'])) {
            $password = trim($params['password']);
        }
        
        if (isset($params['login_string']) && trim($params['login_string'])) {
            $login_string = trim($params['login_string']);
        }
        
        if (isset($params['read_token']) && $params['read_token']) {
            $read_token = intval($params['read_token']);
        }
        
        if (isset($params['write_token']) && $params['write_token']) {
            $write_token = intval($params['write_token']);
        }
        
        if (isset($params['tokens'])) {
            $tokens = array_filter(array_map('intval', $params['tokens']), function($i) { return intval($i) > 0; } );
        }
        
        foreach ($in_logins_to_edit as $login_instance) {
            $result = true;
            $login_report = Array('before' => $this->_get_long_description($login_instance));
            $login_changed = false;
            $changed_password = NULL;
            $can_edit = $login_instance->user_can_edit_ids();

            // We ignore attempts to set tokens that we don't own.
            if (isset($tokens) && $can_edit) {
                $new_tokens = [];
                
                foreach ($tokens as $token) {
                    $token = intval($token);
                    if ($in_andisol_instance->i_have_this_token(abs($token))) {
                        $new_tokens[] = $token;
                    }
                }
                
                $result = $login_instance->set_ids($new_tokens);
                
                if ($result) {
                    $login_changed = true;
                }
            }
                            
            // This is a rare and special occasion. The login change may fail, as it's possible to assign a login that already exists.
            // Additionally, this will only apply to the FIRST login encountered, as, by definition, login strings are unique.
            if ($login_string) {
                if ($in_andisol_instance->god()) {  // Only God can change login strings.
                    $original_login = $login_instance->login_id;
                    $login_instance->login_id = $login_string;
                    
                    $result = $login_instance->update_db(); // This will fail if the new login is not valid. It must be unique, globally.
                    
                    if ($result) {
                        $result = $login_instance->clear_api_key(); // Doing this invalidates any current logins.
                        $login_changed = true;
                        $login_string = NULL;
                    } else {
                        header('HTTP/1.1 400 Cannot Set New Login');
                        exit();
                    }
                }
            }
            
            if ($lang) {
                $result = $login_instance->set_lang($lang);
                if ($result) {
                    $login_changed = true;
                }
            }
            
            if ($result && $name) {
                $result = $login_instance->set_name($name);
                if ($result) {
                    $login_changed = true;
                }
            }
            
            if (($result && $password) && ($in_andisol_instance->manager() || ($login_instance == $in_andisol_instance->get_login_item()))) {
                $result = $login_instance->set_password_from_cleartext($password);
                if ($result) {
                    $result = $login_instance->clear_api_key(); // Doing this invalidates any current logins.
                    if ($result) {
                        $changed_password = $password;
                        $login_changed = true;
                    }
                }
            }
            
            if ($result && $read_token) {
                $result = $login_instance->set_read_security_id($read_token);
                if ($result) {
                    $login_changed = true;
                }
            }
            
            if ($result && $write_token) {
                $result = $login_instance->set_write_security_id($write_token);
                if ($result) {
                    $login_changed = true;
                }
            }
            
            if (!$result) {
                header('HTTP/1.1 400 Error Modifying Login');
                exit();
            }
            
            if ($login_changed) {
                $login_report['after'] = $this->_get_long_description($login_instance);
                if ($changed_password) {
                    $login_report['after']['password'] = $changed_password;
                }
        
                $ret['changed_logins'][] = $login_report;
            }
        }
        
        return $ret;
    }
            
    /***********************/
    /**
    This builds a list of the requested parameters for the login edit operation.
    
    \returns an associative array, with the requested commands, parsed, and ready for use.
     */
    protected function _build_login_mod_list(   $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                $in_query = NULL        ///< OPTIONAL: The query parameters, as an associative array, passed by reference. If left empty, this method is worthless.
                                                ) {
        $ret = [];   // We will build up an associative array of changes we want to make.
        
        if (isset($in_query) && is_array($in_query) && count($in_query)) {

            if (isset($in_query['tokens'])) {
                $ret['tokens'] = array_map('intval', explode(',', $in_query['tokens']));
            }
            
            // Next, we see if we want to change the password.
            if (isset($in_query['password']) && (strlen(trim($in_query['password'])) >= CO_Config::$min_pw_len)) {
                $ret['password'] = trim($in_query['password']);
            }
        
            // Next, we see if we want to change/set the login object asociated with this. You can remove an associated login object by passing in NULL or 0, here.
            if (isset($in_query['login_string']) && $in_andisol_instance->god()) {  // Only God can change login strings (unless we are creating a new user).
                $ret['login_string'] = trim($in_query['login_string']);
            }
            
            // Next, we see if we want to change the read security.
            if (isset($in_query['read_token'])) {
                $ret['read_token'] = intval($in_query['read_token']);
            }
        
            // Next, we see if we want to change the write security.
            if (isset($in_query['write_token'])) {
                $ret['write_token'] = intval($in_query['write_token']);
            }
        
            // Next, we see if we want to change the name.
            if (isset($in_query['name'])) {
                $ret['name'] = trim(strval($in_query['name']));
            }
        
            // Next, look for the language.
            if (isset($in_query['lang'])) {
                $ret['lang'] = trim(strval($in_query['lang']));
            }
        }
        
        return $ret;
    }

    /***********************/
    /**
    Dispatches edit functions.
    
    \returns an associative array, with the resulting data.
     */
    protected function _handle_edit_people( $in_andisol_instance,       ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                            $in_http_method,            ///< REQUIRED: 'POST', 'PUT' or 'DELETE'
                                            $in_path = [],              ///< OPTIONAL: The REST path, as an array of strings.
                                            $in_query = [],             ///< OPTIONAL: The query parameters, as an associative array.
                                            $in_show_parents = false    ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                        ) {
        $ret = NULL;
        
        $login_user = isset($in_query) && is_array($in_query) && isset($in_query['login_user']);    // Flag saying they are only looking for login people.
        
        // You need to be a manager for most of these. If you are not a manager, then you simply get an "incorrect method" response, as opposed to a "forbidden" response.
        if (('POST' == $in_http_method) && $in_andisol_instance->manager()) {
            $ret = $this->_handle_edit_people_post($in_andisol_instance, $login_user, $in_path, $in_query);
        } elseif (('DELETE' == $in_http_method) && $in_andisol_instance->manager()) {
            $ret = $this->_handle_edit_people_delete($in_andisol_instance, $login_user, $in_path, $in_query, $in_show_parents);
        } elseif ('PUT' == $in_http_method) {   // Of course, there's always an exception. People can edit their own users.
            $ret = $this->_handle_edit_people_put($in_andisol_instance, $login_user, $in_path, $in_query);
        } else {
            header('HTTP/1.1 400 Incorrect HTTP Request Method');   // Ah-Ah-Aaaahh! You didn't say the magic word!
            exit();
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    Handles the PUT (Modify User) implementation of the edit functionality.
    
    Also dispatches POST and DELETE calls.
    
    \returns an associative array, with the resulting data.
     */
    protected function _handle_edit_people_put( $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                $in_login_user,         ///< REQUIRED: True, if the user is associated with a login.
                                                $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings.
                                                $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                                ) {
        $ret = NULL;
        
        $user_object_list = [];
        
        $my_info = isset($in_path) && is_array($in_path) && (0 < count($in_path) && ('my_info' == $in_path[0]));
        
        if (isset($my_info) && $my_info) {  // If we are just asking after our own info, then we just use our own user.
            $user_object_list[] = $in_andisol_instance->current_user();
        } elseif (isset($in_path) && is_array($in_path) && (1 < count($in_path) && isset($in_path[0]) && ('login_ids' == $in_path[0]))) {    // See if they are looking for people associated with string login IDs.
            // Now, we see if they are a list of integer IDs or strings (login string IDs).
            $login_id_list = array_map('trim', explode(',', $in_path[1]));
        
            $is_numeric = array_reduce($login_id_list, function($carry, $item){ return $carry && ctype_digit($item); }, true);
        
            $login_id_list = $is_numeric ? array_map('intval', $login_id_list) : $login_id_list;
            $login_id_list = array_unique($login_id_list);
        
            foreach ($login_id_list as $login_id) {
                $login_instance = $is_numeric ? $in_andisol_instance->get_login_item($login_id) : $in_andisol_instance->get_login_item_by_login_string($login_id);
            
                if (isset($login_instance) && ($login_instance instanceof CO_Security_Login)) {
                    $id_string = $login_instance->login_id;
                    $user = $in_andisol_instance->get_user_from_login_string($id_string);
                    if ($user->user_can_write() && $user->has_login()) {
                        $user_object_list[] = $user;
                    }
                }
            }
        } elseif (isset($in_path) && is_array($in_path) && (0 < count($in_path))) { // See if they are looking for a list of individual discrete integer IDs.
            $user_nums = strtolower($in_path[0]);
    
            $single_user_id = (ctype_digit($user_nums) && (1 < intval($user_nums))) ? intval($user_nums) : NULL;    // This will be set if we are looking for only one single user.
            // The first thing that we'll do, is look for a list of user IDs. If that is the case, we split them into an array of int.
            $user_list = explode(',', $user_nums);
    
            // If we do, indeed, have a list, we will force them to be ints, and cycle through them.
            if ($single_user_id || (1 < count($user_list))) {
                $user_list = array_unique($single_user_id ? [$single_user_id] : array_map('intval', $user_list));
        
                foreach ($user_list as $id) {
                    if (0 < $id) {
                        $user = $in_andisol_instance->get_single_data_record_by_id($id);
                        if (isset($user) && ($user instanceof CO_User_Collection)) {
                            if (!$in_login_user || ($in_login_user && $user->has_login()) && $user->user_can_write()) {
                                $user_object_list[] = $user;
                            }
                        }
                    }
                }
            }
        } else {
            $userlist = $in_andisol_instance->get_all_users();
            if (0 < count($userlist)) {
                foreach ($userlist as $user) {
                    if (isset($user) && ($user instanceof CO_User_Collection)) {
                        if (!$in_login_user || ($in_login_user && $user->has_login()) && $user->user_can_write()) {
                            $user_object_list[] = $user;
                        }
                    }
                }
            }
        }
    
        // At this point, we have a list of writable user objects.
        // Now, if we are not a manager, then the only object we have the right to alter is our own.
        if (!$in_andisol_instance->manager()) {
            $temp = NULL;
            
            $current_user = $in_andisol_instance->current_user();
            
            foreach ($user_object_list as $user) {
                if ($user == $current_user) {
                    $temp = $user;
                    break;
                }
            }
            
            $user_object_list = [];
            
            if (isset($temp)) {
                $user_object_list = [$temp];
            }
        }


        // At this point, we have a fully-vetted list of users for modification, or none. If none, we react badly.
        if (0 == count($user_object_list)) {
            header('HTTP/1.1 403 No Editable Records'); // I don't think so. Homey don't play that game.
            exit();
        } else {
            $mod_list = $this->_build_user_mod_list($in_andisol_instance, 'PUT', $in_query);
            $ret = [];
            
            foreach ($user_object_list as $user) {
                $changed_password = NULL;
                $user_changed = false;
                if ($user->user_can_write()) {    // We have to be allowed to write to this user.
                    $user_report = Array('before' => $this->_get_long_user_description($user, $in_login_user));
                  
                    $user->set_batch_mode();
                    $result = false;
                
                    foreach ($mod_list as $key => $value) {
                        switch ($key) {
                            case 'child_ids':
                                if ('DELETE-ALL' == $value) { // This means remove everything.
                                    $result = $user->deleteAllChildren();
                                    $user_changed = true;
                                } else {
                                    $add = $value['add'];
                                    $remove = $value['remove'];
                                    $result = true;
                            
                                    foreach ($remove as $id) {
                                        if ($id != $user->id()) {
                                            $child = $in_andisol_instance->get_single_data_record_by_id($id);
                                            if (isset($child)) {
                                                $result = $user->deleteThisElement($child);
                                                $user_changed = true;
                                            }
                                
                                            if (!$result) {
                                                break;
                                            }
                                        }
                                    }
                            
                                    if ($result) {
                                        foreach ($add as $id) {
                                            if ($id != $user->id()) {
                                                $child = $in_andisol_instance->get_single_data_record_by_id($id);
                                                if (isset($child)) {
                                                    $result = $user->appendElement($child);
                                                    $user_changed = true;
                                                
                                                    if (!$result) {
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                                
                            case 'password':
                                if ($in_login_user) {
                                    $login_instance = $user->get_login_instance();
                                    
                                    // Only the user, themselves, or a manager with edit rights on the login, can change the password.
                                    if ($login_instance) {
                                        if (($in_andisol_instance->manager() || ($login_instance == $in_andisol_instance->current_login()) && $login_instance->user_can_write())) {
                                            $result = $login_instance->set_password_from_cleartext($value);
                                            if ($result) {
                                                $result = $login_instance->clear_api_key(); // Doing this invalidates any current logins.
                                                if ($result) {
                                                    $changed_password = $value;
                                                    $user_changed = true;
                                                }
                                            }
                                        } else {
                                            header('HTTP/1.1 403 Forbidden');
                                            exit();
                                        }
                                    } else {
                                        header('HTTP/1.1 400 No Login Item');
                                        exit();
                                    }
                                }
                            
                                break;
                                
                            case 'lang':
                                $result = $user->set_lang($value);
                            
                                if ($result && $in_login_user) {
                                    $login_instance = $user->get_login_instance();
                                
                                    if ($login_instance && $login_instance->user_can_write()) {
                                        $result = $login_instance->set_lang($value);
                                        $user_changed = true;
                                    }
                                }
                            
                                break;
                                
                            case 'write_token':
                                $result = $user->set_write_security_id($value);
                                $user_changed = true;
                                break;
                                
                            case 'read_token':
                                $result = $user->set_read_security_id($value);
                                $user_changed = true;
                                break;
                        
                            case 'longitude':
                                $result = $user->set_longitude($value);
                                $user_changed = true;
                                break;
                        
                            case 'latitude':
                                $result = $user->set_latitude($value);
                                $user_changed = true;
                                break;
                        
                            case 'fuzz_factor':
                                $result = $user->set_fuzz_factor($value);
                                $user_changed = true;
                                break;
                        
                            case 'can_see_through_the_fuzz':
                                $result = $user->set_can_see_through_the_fuzz($value);
                                $user_changed = true;
                                break;
                            
                            case 'tokens':
                                if ($in_login_user) {  // Can only do this, if the caller explicitly requested a login user.
                                    $login_instance = $user->get_login_instance();
                        
                                    if ($login_instance) {
                                        $result = $login_instance->set_ids($value);
                                        $user_changed = true;
                                    }
                                } else {
                                    header('HTTP/1.1 400 Improper Data Provided');
                                    exit();
                                }
                                break;
                            
                            case 'payload':
                                $result = $user->set_payload($value);
                                $user_changed = true;
                                break;
                            
                            case 'remove_payload':
                                $result = $user->set_payload(NULL);
                                $user_changed = true;
                                break;
                            
                            case 'name':
                                $result = $user->set_name($value);
                                $user_changed = true;
                                break;
                            
                            case 'surname':
                                $result = $user->set_surname($value);
                                $user_changed = true;
                                break;
                            
                            case 'middle_name':
                                $result = $user->set_middle_name($value);
                                $user_changed = true;
                                break;
                            
                            case 'given_name':
                                $result = $user->set_given_name($value);
                                $user_changed = true;
                                break;
                            
                            case 'prefix':
                                $result = $user->set_prefix($value);
                                $user_changed = true;
                                break;
                            
                            case 'suffix':
                                $result = $user->set_suffix($value);
                                $user_changed = true;
                                break;
                            
                            case 'nickname':
                                $result = $user->set_nickname($value);
                                $user_changed = true;
                                break;
                            
                            case 'tag7':
                                $result = $user->set_tag(7, $value);
                                $user_changed = true;
                                break;
                            
                            case 'tag8':
                                $result = $user->set_tag(8, $value);
                                $user_changed = true;
                                break;
                            
                            case 'tag9':
                                $result = $user->set_tag(9, $value);
                                $user_changed = true;
                                break;
                            
                            // Only God can associate a new login at this point.
                            case 'login_id':
                                if ($in_andisol_instance->god()) {
                                    $result = $user->set_login(intval($value));
                                    $user_changed = true;
                                }
                                break;
                        }
                    }
                
                    $result = $user->clear_batch_mode();
                    
                    if (!$result) {
                        break;
                    }
                    
                    if ($user_changed) {
                        $user_report['after'] = $this->_get_long_user_description($user, $in_login_user);
                        if ($changed_password) {
                            $user_report['after']['associated_login']['password'] = $changed_password;
                        }
                
                        $ret['changed_users'][] = $user_report;
                    }
                }
            }
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    Handles the DELETE (Delete User) implementation of the edit functionality.
    
    \returns an associative array, with the resulting data.
     */
    protected function _handle_edit_people_delete ( $in_andisol_instance,       ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                    $in_login_user,             ///< REQUIRED: True, if the user is associated with a login.
                                                    $in_path = [],              ///< OPTIONAL: The REST path, as an array of strings.
                                                    $in_query = [],             ///< OPTIONAL: The query parameters, as an associative array.
                                                    $in_show_parents = false    ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                                ) {
        $ret = NULL;
        
        // We build up a userlist.
        $user_object_list = [];
        
        if (isset($my_info) && $my_info) {  // If we are just asking after our own info, then we just use our own user.
            $user_object_list = [$in_andisol_instance->current_user()];
        } elseif (isset($in_path) && is_array($in_path) && (1 < count($in_path) && ('login_ids' == $in_path[0]))) {    // See if they are looking for people associated with string login IDs.
            // Now, we see if they are a list of integer IDs or strings (login string IDs).
            $login_id_list = array_map('trim', explode(',', $in_path[1]));
        
            $is_numeric = array_reduce($login_id_list, function($carry, $item){ return $carry && ctype_digit($item); }, true);
        
            $login_id_list = $is_numeric ? array_map('intval', $login_id_list) : $login_id_list;
        
            foreach ($login_id_list as $login_id) {
                $login_instance = $is_numeric ? $in_andisol_instance->get_login_item($login_id) : $in_andisol_instance->get_login_item_by_login_string($login_id);
            
                if (isset($login_instance) && ($login_instance instanceof CO_Security_Login)) {
                    $id_string = $login_instance->login_id;
                    $user = $in_andisol_instance->get_user_from_login_string($id_string);
                    if ($user->user_can_write() && $user->has_login()) {
                        $user_object_list[] = $user;
                    }
                }
            }
        } elseif (isset($in_path) && is_array($in_path) && (0 < count($in_path))) { // See if they are looking for a list of individual discrete integer IDs.
            $user_nums = strtolower($in_path[0]);
    
            $single_user_id = (ctype_digit($user_nums) && (1 < intval($user_nums))) ? intval($user_nums) : NULL;    // This will be set if we are looking for only one single user.
            // The first thing that we'll do, is look for a list of user IDs. If that is the case, we split them into an array of int.
            $user_list = explode(',', $user_nums);
    
            // If we do, indeed, have a list, we will force them to be ints, and cycle through them.
            if ($single_user_id || (1 < count($user_list))) {
                $user_list = ($single_user_id ? [$single_user_id] : array_map('intval', $user_list));
        
                foreach ($user_list as $id) {
                    if (0 < $id) {
                        $user = $in_andisol_instance->get_single_data_record_by_id($id);
                        if (isset($user) && ($user instanceof CO_User_Collection)) {
                            if (!$in_login_user || ($in_login_user && $user->has_login()) && $user->user_can_write()) {
                                $user_object_list[] = $user;
                            }
                        }
                    }
                }
            }
        } else {
            $userlist = $in_andisol_instance->get_all_users();
            if (0 < count($userlist)) {
                foreach ($userlist as $user) {
                    if (isset($user) && ($user instanceof CO_User_Collection)) {
                        if (!$in_login_user || ($in_login_user && $user->has_login()) && $user->user_can_write()) {
                            $user_object_list[] = $user;
                        }
                    }
                }
            }
        }
    
        // At this point, we have a list of writable user objects.
        // Now, if we are not a manager, then the only object we have the right to alter is our own.
        if (!$in_andisol_instance->manager()) {
            $temp = NULL;
            foreach ($user_object_list as $user) {
                if ($user == $in_andisol_instance->current_user()) {
                    $temp = $user;
                    break;
                }
            }
            
            $user_object_list = [];
            
            if (isset($temp)) {
                $user_object_list = [$temp];
            }
        }
        
        // At this point, we have a fully-vetted list of users for modification, or none. If none, we react badly.
        if (0 == count($user_object_list)) {
            header('HTTP/1.1 403 No Editable Records'); // I don't think so. Homey don't play that game.
            exit();
        } else {   // DELETE is fairly straightforward
            // We also can't delete ourselves, so we will remove any items that are us.
            $temp = [];
            foreach ($user_object_list as $user) {
                if ($user != $in_andisol_instance->current_user()) {
                    $temp[] = $user;
                }
            }
            
            $user_object_list = $temp;
            
            // We now have a list of items to delete. However, we also need to see if we have full rights to logins, if logins were also indicated.
            if ($in_login_user) {  // We can only delete user/login pairs for which we have write permissions on both.
                $temp = [];
                foreach ($user_object_list as $user) {
                    $login_item = $user->get_login_instance();
                    if ($login_item->user_can_write()) {
                        $temp[] = $user;
                    }
                }
            
                $user_object_list = $temp;
            }
            
            // See what we have left. If nothing, throw a hissy fit.
            if (0 == count($user_object_list)) {
                header('HTTP/1.1 403 No Editable Records'); // I don't think so. Homey don't play that game.
                exit();
            }
                
            $ret = Array ('deleted_users' => [], 'deleted_logins' => []);
            
            // Now, we have a full list of users that we have permission to delete.
            foreach ($user_object_list as $user) {
                $user_dump = $this->_get_long_user_description($user, $in_login_user, $in_show_parents);
                $login_dump = NULL;
                
                $ok = true;
                
                if ($in_login_user) {
                    $login_item = $user->get_login_instance();
                    $login_dump = $this->_get_long_description($login_item);
                    $ok = $login_item->delete_from_db();
                }
                
                if ($ok) {
                    $ok = $user->delete_from_db();
                }
                
                // We return a record of the deleted IDs.
                if ($ok) {
                    if (!isset($ret) || !is_array($ret)) {
                        $ret = ['deleted_users' => []];
                        if ($in_login_user) {
                            $ret['deleted_logins'] = [];
                        }
                    }
                    
                    $ret['deleted_users'][] = $user_dump;
                    
                    if ($login_dump) {
                        $ret['deleted_logins'][] = $login_dump;
                    }
                }
            }
        }
        // OK. We have successfully deleted the users (and maybe the logins, as well). We will return the dumps of the users and logins in the function return as associative arrays.
        
        return $ret;
    }
        
    /***********************/
    /**
    Handles the POST (Create User) implementation of the edit functionality.
    
    \returns an associative array, with the resulting data.
     */
    protected function _handle_edit_people_post(    $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                    $in_login_user,         ///< REQUIRED: True, if the user is associated with a login.
                                                    $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings.
                                                    $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                                ) {
        $ret = NULL;
        
        $login_id = (isset($in_query['login_id']) && trim($in_query['login_id'])) ? trim($in_query['login_id']) : NULL;
        $in_login_user = $in_login_user || (NULL != $login_id); // Supplying a login ID also means creating a new login.
        
        $ret = ['new_user'];
        $password = NULL;
        
        if (isset($in_query['login_id'])) {
            unset($in_query['login_id']);
        }
        
        $password = isset($in_query) && is_array($in_query) && isset($in_query['password']) && trim($in_query['password']) ? trim($in_query['password']) : NULL;
        if (isset($in_query['password'])) {
            unset($in_query['password']);
        }
        
        if (!$password || (strlen($password) < CO_Config::$min_pw_len)) {
            $password = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, CO_Config::$min_pw_len + 2);
        }
        
        $name = isset($in_query) && is_array($in_query) && isset($in_query['name']) && trim($in_query['name']) ? trim($in_query['name']) : NULL;
        if (isset($in_query['name'])) {
            unset($in_query['name']);
        }
        
        $is_manager = isset($in_query) && is_array($in_query) && isset($in_query['is_manager']);
        if (isset($in_query['is_manager'])) {
            unset($in_query['is_manager']);
        }
        
        $my_tokens = array_map('intval', $in_andisol_instance->get_login_item()->ids());
        array_shift($my_tokens); // we remove our own ID.
        
        $tokens = isset($in_query) && is_array($in_query) && isset($in_query['tokens']) && trim($in_query['tokens']) ? trim($in_query['tokens']) : NULL;
        if (isset($in_query['tokens'])) {
            unset($in_query['tokens']);
        }
        
        if (isset($tokens)) {
            $tokens_temp = array_map('intval', explode(',', $tokens));
            $tokens = [];
        
            if ($in_andisol_instance->god()) {  // God is on the TSA Pre-Check list.
                $tokens = $tokens_temp;
            } else {    // Otherwise, we need to make sure that we have only tokens that we own.
                // BADGER deals with this, but we trust no one.
                $tokens_temp = array_intersect($my_tokens, $tokens_temp);
                foreach ($tokens_temp as $token) {
                    if ((1 < $token) && ($token != $in_andisol_instance->get_login_item()->id())) {
                        $tokens[] = $token;
                    }
                }
            }
            
            $in_query['tokens'] = implode(',', $tokens);
        }                            
        
        $read_token = isset($in_query) && is_array($in_query) && isset($in_query['read_token']) && intval($in_query['read_token']) ? intval($in_query['read_token']) : 1;
        if (isset($in_query['read_token'])) {
            unset($in_query['read_token']);
        }
        
        if ((0 == $read_token) || (1 == $read_token) || in_array($read_token, $my_tokens)) {
            $in_query['read_token'] = $read_token;
        }
        
        $write_token = isset($in_query) && is_array($in_query) && isset($in_query['write_token']) && intval($in_query['write_token']) ? intval($in_query['write_token']) : 0;

        if (isset($in_query['write_token'])) {
            unset($in_query['write_token']);
        }
        
        if (isset($write_token) && in_array($write_token, $my_tokens)) {
            $in_query['write_token'] = $read_token;
        }
      
        $user = NULL;
        $settings_list = $this->_build_user_mod_list($in_andisol_instance, 'POST', $in_query);   // First, build up a list of the settings for the new user.

        if ($in_login_user) {  // Create a user/login pair.
            $password = $in_andisol_instance->create_new_user($login_id, $password, $name, $tokens, 0, $is_manager);
        
            if ($password) {
                $user = $in_andisol_instance->get_user_from_login_string($login_id);
            }
        } else {    // Standalone user (person).
            $user = $in_andisol_instance->make_standalone_user();
        }
        
        if (isset($user) && ($user instanceof CO_User_Collection)) {
            $in_path[] = $user->id();
            $result = $this->_handle_edit_people_put($in_andisol_instance, $in_login_user, $in_path, $in_query);
            
            $ret = Array('new_user' => $this->_get_long_user_description($user, true));
            
            if ($in_login_user && isset($password)) {
                $ret['new_user']['associated_login']['password'] = $password;
            }
        } else {
            header('HTTP/1.1 400 Failed to Create User');
            exit();
        }
        
        return $ret;
    }
            
    /***********************/
    /**
    This builds a list of the requested parameters for the user edit operation.
    
    \returns an associative array, with the requested commands, parsed, and ready for use.
     */
    protected function _build_user_mod_list(    $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                $in_http_method,        ///< REQUIRED: 'GET', 'POST', 'PUT' or 'DELETE'
                                                $in_query = NULL        ///< OPTIONAL: The query parameters, as an associative array. If left empty, this method is worthless.
                                                ) {
        // <rubs hands/> Now, let's get to work...
        // First, build up a list of the items that we want to change.
    
        $ret = parent::_process_parameters($in_andisol_instance, $in_query);
        
        if (isset($in_query) && is_array($in_query) && count($in_query)) {
            // See if they want to add new child data items to each user, or remove existing ones.
            // We indicate adding ones via positive integers (the item IDs), and removing via negative integers (minus the item ID).
            if (isset($in_query['tokens'])) {
                $tokens_temp = array_map('intval', explode(',', $in_query['tokens']));
                $tokens = [];
            
                if ($in_andisol_instance->god()) {  // God is on the TSA Pre-Check list.
                    $tokens = $tokens_temp;
                } else {    // Otherwise, we need to make sure that we have only tokens that we own.
                    // BADGER deals with this, but we trust no one.
                    $my_tokens = array_map('intval', $in_andisol_instance->get_login_item()->ids());
                    $tokens_temp = array_intersect($my_tokens, $tokens_temp);
                    foreach ($tokens_temp as $token) {
                        if ((1 < $token) && ($token != $in_andisol_instance->get_login_item()->id())) {
                            $tokens[] = $token;
                        }
                    }
                }
                
                $ret['tokens'] = $tokens;
            }                            
        
            // Next, we see if we want to change the password.
            if (isset($in_query['password']) && (strlen(trim($in_query['password'])) >= CO_Config::$min_pw_len)) {
                $ret['password'] = trim($in_query['password']);
            }
        
            // Next, we see if we want to change the surname.
            if (isset($in_query['surname'])) {
                $ret['surname'] = trim(strval($in_query['surname']));
            }
        
            // Next, we see if we want to change the middle name.
            if (isset($in_query['middle_name'])) {
                $ret['middle_name'] = trim(strval($in_query['middle_name']));
            }
        
            // Next, we see if we want to change the first name.
            if (isset($in_query['given_name'])) {
                $ret['given_name'] = trim(strval($in_query['given_name']));
            }
        
            // Next, we see if we want to change the prefix.
            if (isset($in_query['prefix'])) {
                $ret['prefix'] = trim(strval($in_query['prefix']));
            }
        
            // Next, we see if we want to change the suffix.
            if (isset($in_query['suffix'])) {
                $ret['suffix'] = trim(strval($in_query['suffix']));
            }
        
            // Next, we see if we want to change the nickname.
            if (isset($in_query['nickname'])) {
                $ret['nickname'] = trim(strval($in_query['nickname']));
            }
        
            // Next, we see if we want to change/set the login object asociated with this. You can remove an associated login object by passing in NULL or 0, here.
            if (isset($in_query['login_id']) && (('POST' == $in_http_method) || $in_andisol_instance->god())) {  // Only God can change login IDs (unless we are creating a new user).
                $ret['login_id'] = abs(intval(trim($in_query['login_id'])));
            }
                
            // Next, look for the last three tags (the only ones we're allowed to change).
            if (isset($in_query['tag7'])) {
                $ret['tag7'] = trim(strval($in_query['tag7']));
            }
                
            // Next, look for the last two tags (the only ones we're allowed to change).
            if (isset($in_query['tag8'])) {
                $ret['tag8'] = trim(strval($in_query['tag8']));
            }
        
            if (isset($in_query['tag9'])) {
                $ret['tag9'] = trim(strval($in_query['tag9']));
            }
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    This handles users.
    
    \returns an array, with the resulting people.
     */
    protected function _handle_people(  $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                        $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings.
                                        $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                    ) {
        $ret = [];
        $login_user = isset($in_query) && is_array($in_query) && isset($in_query['login_user']);                    // Flag saying they are only looking for login people.
        $show_parents = isset($in_query) && is_array($in_query) && isset($in_query['show_parents']);                // Show all places in detail, as well as the parents (applies only to GET).
        $show_details = $show_parents || (isset($in_query) && is_array($in_query) && isset($in_query['show_details']));             // Show all places in detail (applies only to GET).
        $logged_in = isset($in_query) && is_array($in_query) && isset($in_query['logged_in']) && $in_andisol_instance->manager();   // Flag that filters for only users that are logged in.
        $my_info = isset($in_path) && is_array($in_path) && (0 < count($in_path) && ('my_info' == $in_path[0]));    // Directory that specifies we are only looking for our own info.
        $writeable = isset($in_query) && is_array($in_query) && isset($in_query['writeable']);                      // Show/list only people this user can modify.
        
        if ($logged_in) {   // If we are looking for logged in users, then this should be true, Q.E.D.
            $login_user = true;
        }
        
        if ($login_user) {  // Same for login_user.
            if (!$in_andisol_instance->logged_in()) {   // You can't look for users by login, if you, yourself, are not logged in.
                return [];
            }
            $show_details = true;
        }
        
        if (isset($my_info) && $my_info) {  // If we are just asking after our own info, then we just send that back.
            if ($in_andisol_instance->logged_in()) {
                $user = $in_andisol_instance->current_user();
                if ($user instanceof CO_User_Collection) {
                    $ret['my_info'] = $this->_get_long_user_description($user, $login_user, $show_parents);
                } else {
                    header('HTTP/1.1 400 No Logged-In User');
                    exit();
                }
            } else {
                header('HTTP/1.1 403 Forbidden');
                exit();
            }
        } elseif (isset($in_path) && is_array($in_path) && (1 < count($in_path) && ('login_ids' == $in_path[0]))) {    // See if they are looking for people associated with string login IDs.
            // Now, we see if they are a list of integer IDs or strings (login string IDs).
            $login_id_list = array_map('trim', explode(',', $in_path[1]));
            
            $is_numeric = array_reduce($login_id_list, function($carry, $item){ return $carry && ctype_digit($item); }, true);
            
            $login_id_list = $is_numeric ? array_map('intval', $login_id_list) : $login_id_list;
            
            foreach ($login_id_list as $login_id) {
                $login_instance = $is_numeric ? $in_andisol_instance->get_login_item($login_id) : $in_andisol_instance->get_login_item_by_login_string($login_id);
                
                if (isset($login_instance) && ($login_instance instanceof CO_Security_Login)) {
                    if (!$logged_in || ($logged_in && $login_instance->get_api_key())) { // See if they are filtering for logins.
                        $id_string = $login_instance->login_id;
                        $user = $in_andisol_instance->get_user_from_login_string($id_string);
                        if (isset($user) && ($user instanceof CO_User_Collection) && (!$writeable || $user->user_can_write())) {
                            if ($show_details) {
                                $ret[] = $this->_get_long_user_description($user, $login_user, $show_parents);
                            } else {
                                $ret[] = $this->_get_short_description($user);
                            }
                        }
                    }
                }
            }
        } elseif (isset($in_path) && is_array($in_path) && (0 < count($in_path))) { // See if they are looking for a list of individual discrete integer IDs.
            $user_nums = strtolower($in_path[0]);
            
            $single_user_id = (ctype_digit($user_nums) && (1 < intval($user_nums))) ? intval($user_nums) : NULL;    // This will be for if we are looking only one single user.
            // The first thing that we'll do, is look for a list of user IDs. If that is the case, we split them into an array of int.
            $user_list = explode(',', $user_nums);
            
            // If we do, indeed, have a list, we will force them to be ints, and cycle through them.
            if ($single_user_id || (1 < count($user_list))) {
                $user_list = ($single_user_id ? [$single_user_id] : array_map('intval', $user_list));
                
                foreach ($user_list as $id) {
                    if (0 < $id) {
                        $user = $in_andisol_instance->get_single_data_record_by_id($id);
                        if (isset($user) && ($user instanceof CO_User_Collection) && (!$writeable || $user->user_can_write())) {
                            if (!$login_user || ($login_user && $user->has_login())) {
                                if ($logged_in) {   // If we are only looking for logged-in users, then we skip to the next one if this is not a logged-in user.
                                    $login_instance = $user->get_login_instance();
                                    
                                    if (!isset($login_instance) || !($login_instance instanceof CO_Security_Login) || !$login_instance->get_api_key()) {
                                        continue;
                                    }
                                }
                                if ($show_details) {
                                    $ret[] = $this->_get_long_user_description($user, $login_user, $show_parents);
                                } else {
                                    $ret[] = $this->_get_short_description($user);
                                }
                            }
                        }
                    }
                }
            }
        } else {    // They want the list of all of them (or a filtered list).
            $radius = isset($in_query) && is_array($in_query) && isset($in_query['search_radius']) && (0.0 < floatval($in_query['search_radius'])) ? floatval($in_query['search_radius']) : NULL;
            $longitude = isset($in_query) && is_array($in_query) && isset($in_query['search_longitude']) ? floatval($in_query['search_longitude']) : NULL;
            $latitude = isset($in_query) && is_array($in_query) && isset($in_query['search_latitude']) ? floatval($in_query['search_latitude']) : NULL;
            $search_page_size = isset($in_query) && is_array($in_query) && isset($in_query['search_page_size']) ? abs(intval($in_query['search_page_size'])) : 0;       // Ignored for discrete IDs. This is the size of a page of results (1-based result count. 0 is no page size).
            $search_page_number = isset($in_query) && is_array($in_query) && isset($in_query['search_page_number']) ? abs(intval($in_query['search_page_number'])) : 0; // Ignored for discrete IDs, or if search_page_size is 0. The page we are interested in (0-based. 0 is the first page).
            $search_name = isset($in_query) && is_array($in_query) && isset($in_query['search_name']) ? trim($in_query['search_name']) : NULL;          // Search in the object name.
            $search_surname = isset($in_query) && is_array($in_query) && isset($in_query['search_surname']) ? trim($in_query['search_surname']) : NULL; // Search in the surname.
            $search_middle_name = isset($in_query) && is_array($in_query) && isset($in_query['search_middle_name']) ? trim($in_query['search_middle_name']) : NULL; // Search in the middle name.
            $search_given_name = isset($in_query) && is_array($in_query) && isset($in_query['search_given_name']) ? trim($in_query['search_given_name']) : NULL; // Search in the first name.
            $search_nickname = isset($in_query) && is_array($in_query) && isset($in_query['search_nickname']) ? trim($in_query['search_nickname']) : NULL; // Search in the nickname.
            $search_prefix = isset($in_query) && is_array($in_query) && isset($in_query['search_prefix']) ? trim($in_query['search_prefix']) : NULL; // Search in the prefix.
            $search_suffix = isset($in_query) && is_array($in_query) && isset($in_query['search_suffix']) ? trim($in_query['search_suffix']) : NULL; // Search in the suffix.
            $search_tag7 = isset($in_query) && is_array($in_query) && isset($in_query['search_tag7']) ? trim($in_query['search_tag7']) : NULL; // Search in the tag.
            $search_tag8 = isset($in_query) && is_array($in_query) && isset($in_query['search_tag8']) ? trim($in_query['search_tag8']) : NULL; // Search in the tag.
            $search_tag9 = isset($in_query) && is_array($in_query) && isset($in_query['search_tag9']) ? trim($in_query['search_tag9']) : NULL; // Search in the tag.
            
            $location_search = NULL;
            $string_search =    ($search_name !== NULL)
                            ||  ($search_surname !== NULL)
                            ||  ($search_middle_name !== NULL)
                            ||  ($search_given_name !== NULL)
                            ||  ($search_nickname !== NULL)
                            ||  ($search_prefix !== NULL)
                            ||  ($search_suffix !== NULL)
                            ||  ($search_tag7 !== NULL)
                            ||  ($search_tag8 !== NULL)
                            ||  ($search_tag9 !== NULL);
            
            // We make sure that we puke if they give us a bad distance search.
            if (isset($radius) && isset($longitude) && isset($latitude)) {
                $location_search = Array('radius' => $radius, 'longitude' => $longitude, 'latitude' => $latitude);
            } elseif (isset($radius) || isset($longitude) || isset($latitude)) {
                header('HTTP/1.1 400 Incomplete Distance Search');
                exit();
            }
            
            $userlist = [];
            if ((isset($location_search) && is_array($location_search) && (3 == count($location_search))) || (0 < $search_page_size) || $string_search) {
                $class_search = Array('%_User_Collection', 'use_like' => 1);
                $search_array['access_class'] = $class_search;
                $search_array['location'] = $location_search;
                if (isset($search_name)) {
                    $search_array['name'] = Array($search_name, 'use_like' => 1);
                }
                
                $tags_array = [NULL];
                
                $tags_array[] = isset($search_surname) ? $search_surname : NULL;
                $tags_array[] = isset($search_middle_name) ? $search_middle_name : NULL;
                $tags_array[] = isset($search_given_name) ? $search_given_name : NULL;
                $tags_array[] = isset($search_nickname) ? $search_nickname : NULL;
                $tags_array[] = isset($search_prefix) ? $search_prefix : NULL;
                $tags_array[] = isset($search_suffix) ? $search_suffix : NULL;
                $tags_array[] = isset($search_tag7) ? $search_tag7 : NULL;
                $tags_array[] = isset($search_tag8) ? $search_tag8 : NULL;
                $tags_array[] = isset($search_tag9) ? $search_tag9 : NULL;
                
                $has_tags = false;
                foreach ($tags_array as $tag) {
                    if (NULL !== $tag) {
                        $has_tags = true;
                        break;
                    }
                }
                
                if ($has_tags) {
                    $tags_array['use_like'] = 1;
                    $search_array['tags'] = $tags_array;
                }

                $userlist = $in_andisol_instance->generic_search($search_array, false, $search_page_size, $search_page_number, $writeable);
            } else {
                $userlist = $in_andisol_instance->get_all_users();
            }
            
            if (0 < count($userlist)) {
                foreach ($userlist as $user) {
                    if (isset($user) && ($user instanceof CO_User_Collection) && (!$writeable || $user->user_can_write())) {
                        if (!$login_user || ($login_user && $user->has_login())) {
                            if ($logged_in) {   // If we are only looking for logged-in users, then we skip to the next one if this is not a logged-in user.
                                $login_instance = $user->get_login_instance();
                                
                                if (!isset($login_instance) || !($login_instance instanceof CO_Security_Login) || !$login_instance->get_api_key()) {
                                    continue;
                                }
                            }
                            if ($show_details) {
                                $ret[] = $this->_get_long_user_description($user, $login_user, $show_parents);
                            } else {
                                $ret[] = $this->_get_short_description($user);
                            }
                        }
                    }
                }
                
                if ($location_search) {
                    $ret['search_location'] = $location_search;
                }
            }
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    \returns a string, with our plugin name.
     */
    public function plugin_name() {
        return 'people';
    }
    
    /***********************/
    /**
    This returns an array of classnames, handled by this plugin.
    
    \returns an array of string, with the names of the classes handled by this plugin.
     */
    static public function classes_managed() {
        return ['CO_User_Collection', 'CO_Login_Manager', 'CO_Cobra_login', 'CO_Security_Login'];
    }
    
    /***********************/
    /**
    This runs our plugin command.
    
    \returns the HTTP response string, as either JSON or XML.
     */
    public function process_command(    $in_andisol_instance,       ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                        $in_http_method,            ///< REQUIRED: 'GET', 'POST', 'PUT' or 'DELETE'
                                        $in_response_type,          ///< REQUIRED: Either 'json' or 'xml' -the response type.
                                        $in_path = [],              ///< OPTIONAL: The REST path, as an array of strings.
                                        $in_query = []              ///< OPTIONAL: The query parameters, as an associative array.
                                    ) {
        $ret = [];
        $show_parents = isset($in_query) && is_array($in_query) && isset($in_query['show_parents']);    // Show all places in detail, as well as the parents (applies only to GET or DELETE).
        
        // For the default (no user ID), we simply return a list of commands. We also only allow GET to do this.
        if (0 == count($in_path)) {
            if ('GET' == $in_http_method) {
                $ret = ['people'];
                if ($in_andisol_instance->logged_in()) {
                    $ret[] = 'logins';
                }
            } else {
                header('HTTP/1.1 400 Incorrect HTTP Request Method');
                exit();
            }
        } else {
            $main_command = $in_path[0];    // Get the main command.
            array_shift($in_path);
            switch (strtolower($main_command)) {
                case 'people':
                    if ('GET' == $in_http_method) {
                        $ret['people'] = $this->_handle_people($in_andisol_instance, $in_path, $in_query);
                    } elseif ($in_andisol_instance->logged_in()) {  // Must be logged in to be non-GET.
                        $ret['people'] = $this->_handle_edit_people($in_andisol_instance, $in_http_method, $in_path, $in_query, $show_parents);
                    } else {
                        header('HTTP/1.1 400 Incorrect HTTP Request Method');
                        exit();
                    }
                    break;
                case 'logins':
                    if ('GET' == $in_http_method) {
                        $ret['logins'] = $this->_handle_logins($in_andisol_instance, $in_path, $in_query);
                    } else {
                        $ret['logins'] = $this->_handle_edit_logins($in_andisol_instance, $in_http_method, $in_path, $in_query, $show_parents);
                    }
                    break;
            }
        }
        
        return $this->_condition_response($in_response_type, $ret);
    }
}