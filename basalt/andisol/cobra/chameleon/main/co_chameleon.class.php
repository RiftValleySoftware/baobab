<?php
/***************************************************************************************************************************/
/**
    CHAMELEON Object Abstraction Layer
    
    © Copyright 2018, The Great Rift Valley Software Company
    
    LICENSE:
    
    MIT License
    
    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
    files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
    modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
    IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
    CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

    The Great Rift Valley Software Company: https://riftvalleysoftware.com
*/
defined( 'LGV_ACCESS_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

define('__CHAMELEON_VERSION__', '1.0.6.3000');

require_once(CO_Config::badger_main_class_dir().'/co_access.class.php');

if ( !defined('LGV_LANG_CATCHER') ) {
    define('LGV_LANG_CATCHER', 1);
}

require_once(CO_Config::chameleon_lang_class_dir().'/common.inc.php');

/***************************************************************************************************************************/
/**
 */
class CO_Chameleon extends CO_Access {
    protected $_special_access_id;  ///< This is a special ephemeral ID that we use to allow a manager to create a login, and add that login to its pool.
    
    /***********************************************************************************************************************/    
    /***********************/
    /**
    The constructor.
     */
	public function __construct(    $in_login_id = NULL,        ///< The login ID
                                    $in_hashed_password = NULL, ///< The password, crypt-hashed
                                    $in_raw_password = NULL,    ///< The password, cleartext.
                                    $in_api_key = NULL          ///< An API key, for REST.
	                            ) {
        parent::__construct($in_login_id, $in_hashed_password, $in_raw_password, $in_api_key);
	    $this->version = __CHAMELEON_VERSION__;
    }
    
    /***********************/
    /**
    This is a special "God Mode-Only" method that dumps everything in both databases. It is used to generate a CSV backup file.
    
    The user must be logged in as "God."
    
    \returns an associative array ('security' => array, 'data' => array') of two sets of associative arrays, with the dump. NULL if the user is not authorized.
     */
    public function fetch_backup() {
        // Have to be "God," and the variable in the config needs to be set.
        if ($this->god_mode()) {
            $security_db_backup = $this->_security_db_object->get_db_backup();
            $data_db_backup = $this->_data_db_object->get_db_backup();
            
            return ['security' => $security_db_backup, 'data' => $data_db_backup];
        }
        
        return NULL;
    }
    
    /***********************/
    /**
    This fetches the list of security tokens the currently logged-in user has available.
    This will reload any non-God Mode IDs before fetching the IDs, in order to spike privilege escalation.
    If they have God Mode, then you're pretty much screwed, anyway.
    
    \returns an array of integers, with each one representing a security token. The first element will always be the ID of the user.
     */
    public function get_security_ids() {
        $ret = parent::get_security_ids();
        
        if (isset($this->_special_access_id)) {
            $id = $this->_special_access_id;
            unset($this->_special_access_id);
            $ret[] = intval($id);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This method chacks all the collections for the presence of the given element.
    If the collection is a direct parent of the element, it is returned.
    
    \returns an array of collection objects. If the element has no parents, then NULL is returned.
     */
    public function get_all_collections_for_element(    $in_element ///< The element we're looking for parents.
                                                    ) {
        $ret = NULL;
        
        if (isset($in_element) && $in_element && ($in_element->get_access_object() == $this)) {
            $collection_objects = $this->generic_search(Array('access_class' => Array('%_collection', 'use_like' => true)));

            if (isset($collection_objects) && is_array($collection_objects) && count($collection_objects)) {
                foreach ($collection_objects as $parent_object) {
                    if ($parent_object->areYouMyDaddy($in_element, false)) {
                        if (!$ret) {
                            $ret = Array();
                        }
                        array_push($ret, $parent_object);
                    }
                }
            }
        } else {    // If the item is invalid, we not only give a NULL, we also flag an error.
            $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_collection_error_code_item_not_valid,
                                            CO_CHAMELEON_Lang::$co_collection_error_name_item_not_valid,
                                            CO_CHAMELEON_Lang::$co_collection_error_desc_item_not_valid);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    You give a security ID, and you will get a count of all login objects that have that token in their list (or are of that ID).
    
    This is not restricted, and will count logins that we don't otherwise know about.
    
    It does not count the "God" admin, which always has access.
       
    \returns an integer, with the total count of logins with access to the ID. -1, if we are not allowed to see the token.
     */
    public function count_all_login_objects_with_access($in_security_token  ///< An integer, with the requested security token.
                                                        ) {
        return $this->_security_db_object->count_all_login_objects_with_access($in_security_token);
    }
    
    /***********************/
    /**
    You give a security ID, and you will get an array, with the login objects that have that token in their list (or are of that ID).
    
    This is security restricted, and will not return logins that we don't otherwise know about.
       
    \returns an array, with the login objects that have that token in their list (or are of that ID).
     */
    public function get_all_login_objects_with_access($in_security_token  ///< An integer, with the requested security token.
                                                        ) {
        return $this->_security_db_object->get_all_login_objects_with_access($in_security_token);
    }
    
    /***********************/
    /**
    You give a security ID, and you will get an array, with the user objects, associated with login objects that have that token in their list (or are of that ID).
    
    This is security restricted, and will not return users that we don't otherwise know about.
       
    \returns an array, with the user objects, associated with login objects that have that token in their list (or are of that ID).
     */
    public function get_all_user_objects_with_access($in_security_token  ///< An integer, with the requested security token.
                                                        ) {
        return $this->_security_db_object->get_all_user_objects_with_access($in_security_token);
    }
    
    /***********************/
    /**
    This returns the language string for the given user/login.
    
    If there is a user item associated with the login, then that gets first dibs. Login item gets dibs over default server.
    
    \returns a string, with the language ID for the login. If none, the the server default is returned.
     */
    public function get_lang(   $in_login_id = NULL ///< The integer login ID to check. If not-NULL, then the ID of a login instance. It must be one that the current user can see.
                            ) {
        $ret = parent::get_lang();
        
        $login_item = $this->get_login_item($in_login_id);
        
        if ($login_item) {
            $user_item = $this->get_user_from_login($login_item->login_id);
            if ($user_item) {
                $ret = $user_item->get_lang();
            }
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    This tests a login ID for the special "Heisenberg" one-time test.
    
    \returns true, if the item was a login, and had the flag set.
     */
    public function test_access(    $in_login_id    ///< The ID of the instance to test.
                                ) {
        $ret = false;
        
        // Yeah, this will crash if we're not in COBRA. Good.
        if ($this->security_db_available() && ($this->get_login_item() instanceof CO_Login_Manager)) {
            $item_to_test = $this->_security_db_object->get_initial_record_by_id($in_login_id);
            
            if (($item_to_test instanceof CO_Cobra_Login) || ($item_to_test instanceof CO_Security_ID)) {
                $ret = $item_to_test->security_exemption();
                if ($ret) {
                    $this->_special_access_id = $in_login_id;
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns the value for a given key. It is dependent on the class passed in. NULL, if no value or instance for the key.
     */
    public function get_value_for_key(  $in_key,                        ///< This is the key that we are searching for. It must be a string.
                                        $in_classname = 'CO_KeyValue_CO_Collection'   ///< This is the class to search for the key. The default is the base class.
                                    ) {
        $ret = NULL;
        $value_object_array = $this->generic_search(Array('access_class' => $in_classname, 'tags' => Array(strval($in_key))));
        
        if (isset($value_object_array) && is_array($value_object_array) && count($value_object_array)) {
            $value_object = $value_object_array[0]; // If the DB is messed up, we could get more than one. In that case, we only take the first one.
            if (isset($value_object) && ($value_object instanceof CO_KeyValue_CO_Collection)) {
                $ret = $value_object->get_value();
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns true, if the key is unique in the DB for the given class (regardless of whether or not we can see it).
     */
    public function key_is_unique(  $in_key,                                    ///< The key to test (a string).
                                    $in_classname = 'CO_KeyValue_CO_Collection' ///< This is the class to search for the key. The default is the base class.
                                    ) {
        return $this->_data_db_object->tag0_is_unique($in_key, $in_classname);
    }
    
    /***********************/
    /**
    \returns the object that stores the given key. NULL, if no value or instance for the key.
     */
    public function get_object_for_key( $in_key ///< This is the key that we are searching for. It must be a string.
                                        ) {
        $ret = NULL;
        $value_object_array = $this->generic_search(Array('access_class' => Array('%_KeyValue%', 'use_like' => 1), 'tags' => Array(strval($in_key))));
        
        if (isset($value_object_array) && is_array($value_object_array) && count($value_object_array)) {
            $ret = $value_object_array[0]; // If the DB is messed up, we could get more than one. In that case, we only take the first one.
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets a value to a key, creating the record, if need be. Passing in NULL will delete the key (if we have write access).
    We need to have a login for it to work at all. If the value already exists, then we need to have write access to it, or we will fail.
    
    \returns true, if successful.
     */
    public function set_value_for_key(  $in_key,                        ///< This is the key that we are setting. It must be a string.
                                        $in_value,                      ///< The value to set. If NULL, then we will delete the key.
                                        $in_classname = 'CO_KeyValue_CO_Collection'   ///< This is the class to use for the key. The default is the base class.
                                    ) {
        $ret = false;
        
        if ($this->security_db_available()) {   // Must be logged in.
            // First, we look for the object.
            $value_object = $this->generic_search(Array('access_class' => $in_classname, 'tags' => Array(strval($in_key))));
        
            if ((NULL != $in_value) && (!isset($value_object) || !$value_object)) {
                $value_object = $this->make_new_blank_record($in_classname);
                if (isset($value_object) && ($value_object instanceof CO_KeyValue_CO_Collection) && $value_object->user_can_write()) {
                    $ret = $value_object->set_name($in_key);
                    $ret = $value_object->set_key($in_key);
                    if (!$ret) {
                        $this->error = $value_object->error;
                        $value_object->delete_from_db();
                        $value_object = NULL;
                        if (!$this->error) {
                            $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_key_value_error_code_instance_failed_to_initialize,
                                                            CO_CHAMELEON_Lang::$co_key_value_error_name_instance_failed_to_initialize,
                                                            CO_CHAMELEON_Lang::$co_key_value_error_desc_instance_failed_to_initialize);
                        }
                    }
               }
            } elseif (isset($value_object) && is_array($value_object) && count($value_object)) {
                $value_object = $value_object[0];
            } else {
                $value_object = NULL;
                $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_key_value_error_code_instance_failed_to_initialize,
                                                CO_CHAMELEON_Lang::$co_key_value_error_name_instance_failed_to_initialize,
                                                CO_CHAMELEON_Lang::$co_key_value_error_desc_instance_failed_to_initialize);
            }

            if (isset($value_object) && ($value_object instanceof CO_KeyValue_CO_Collection) && $value_object->user_can_write()) {
                if (NULL == $in_value) {    // If we are deleting, we ask the object to go quietly into the great beyond.
                    $ret = $value_object->delete_from_db();
                } else {                    // Otherwise, we just set the value.
                    $ret = $value_object->set_tag(0, $in_key);
                    if ($ret) {
                        $ret = $value_object->set_value($in_value);
                    } else {
                        $this->error = $value_object->error;
                        
                        if (!$this->error) {
                            $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_key_value_error_code_instance_failed_to_initialize,
                                                            CO_CHAMELEON_Lang::$co_key_value_error_name_instance_failed_to_initialize,
                                                            CO_CHAMELEON_Lang::$co_key_value_error_desc_instance_failed_to_initialize);
                        }
                    }
                }
            } else {
                if (isset($value_object) && ($value_object instanceof CO_KeyValue_CO_Collection) && !$value_object->user_can_write()) {
                    $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_key_value_error_code_user_not_authorized,
                                                    CO_CHAMELEON_Lang::$co_key_value_error_name_user_not_authorized,
                                                    CO_CHAMELEON_Lang::$co_key_value_error_desc_user_not_authorized);
                } else {
                    $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_key_value_error_code_instance_failed_to_initialize,
                                                    CO_CHAMELEON_Lang::$co_key_value_error_name_instance_failed_to_initialize,
                                                    CO_CHAMELEON_Lang::$co_key_value_error_desc_instance_failed_to_initialize);
                }
            }
        } else {
            $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_key_value_error_code_user_not_authorized,
                                            CO_CHAMELEON_Lang::$co_key_value_error_name_user_not_authorized,
                                            CO_CHAMELEON_Lang::$co_key_value_error_desc_user_not_authorized);
        }
        
        return $ret;
    }

    /***********************/
    /**
    \returns a simple associative array, indexed by user (not login) IDs, with each element being the name (usually display name) of the user object. It reurns ALL users visible to the current login.
     */
    public function get_all_visible_users() {
        return $this->_data_db_object->get_all_visible_users();
    }
    
    /***********************/
    /**
    \returns the user collection object for a given login. If there is no login given, then the current login is assumed. This is subject to security restrictions.
     */
    public function get_user_from_login(    $in_login_id = NULL ///< The login ID that is associated with the user collection. If NULL, then the current login is used.
                                        ) {
        $ret = NULL;

        $login_id = $this->get_login_id();  // Default is the current login.
        
        if (isset($in_login_id) && (0 < intval($in_login_id))) {    // See if they seek a different login.
            $login_id = intval($in_login_id);
        }
        
        $tag0 = strval($login_id);
        
        $ret_temp = $this->generic_search(Array('access_class' => Array('%_user_collection', 'use_like' => true), 'tags' => Array($tag0)));
        
        if (isset($ret_temp) && is_array($ret_temp) && count($ret_temp)) {
            $ret = $ret_temp[0];    // We only get the first one. Multiple responses mean the DB is not so healthy.
        }
        
        return $ret;
    }
};
