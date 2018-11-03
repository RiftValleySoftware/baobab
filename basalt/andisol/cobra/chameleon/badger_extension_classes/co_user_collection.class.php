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
defined( 'LGV_DBF_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

CO_Config::require_extension_class('tco_collection.interface.php');
require_once(CO_Config::db_class_dir().'/co_main_db_record.class.php');

$lang = CO_Config::$lang;

global $g_lang_override;    // This allows us to override the configured language at initiation time.

if (isset($g_lang_override) && $g_lang_override && file_exists(CO_Config::lang_class_dir().'/'.$g_lang_override.'.php')) {
    $lang = $g_lang_override;
}

$lang_file = CO_Config::badger_lang_class_dir().'/'.$lang.'.php';
$lang_common_file = CO_Config::badger_lang_class_dir().'/common.inc.php';

if ( !defined('LGV_LANG_CATCHER') ) {
    define('LGV_LANG_CATCHER', 1);
}

require_once($lang_file);
require_once($lang_common_file);

/***************************************************************************************************************************/
/**
This is a container, meant to define a user.

A user is a collection, with various data items attached to it.

The first seven tags are reserved for the class:
    - Tag 0, which is the ID of an associated login. This can only be changed if the logged-in user has write permissions on this object, and at least read permissions on the login.
    - Tag 1, the user's surname (family name).
    - Tag 2, the user's middle name.
    - Tag 3, the user's given (first) name.
    - Tag 4, the user's prefix (Mr., Mrs., Dr., etc.).
    - Tag 5, the user's suffix (Ph.D., LCSW, Jr., III, etc.).
    - Tag 6, the user's nickname.

It also has a single link to a login (which can be NULL, if the user is not one that can log into the system).

As with other login-related classes, only login managers that have access to security tokens (which are also IDs of login and other security DB items) can set certain IDs as tokens.
 */
class CO_User_Collection extends CO_LL_Location {
    use tCO_Collection; // These are the built-in collection methods.

    private $_login_object = NULL;  ///< The Security DB COBRA login instance associated with this user.

    /***********************/
    /**
    \returns true, if the instance was able to set itself up to the internal login ID.
     */
    protected function _load_login() {
        $ret = false;
        // Tag 0 contains the ID of the user login (in the security DB) for this user.
        $login_id = isset($this->tags()[0]) ? intval($this->tags()[0]) : 0;
        
        if (0 < $login_id) {
            $my_login_object = $this->get_access_object()->get_single_security_record_by_id($login_id);
            
            if (isset($my_login_object) && ($my_login_object instanceof CO_Security_Login)) {
                $this->_login_object = $my_login_object;
                $ret = true;
            } elseif (!($my_login_object instanceof CO_Security_Login)) {
                $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$user_error_code_invalid_class,
                                                CO_CHAMELEON_Lang::$user_error_name_invalid_class,
                                                CO_CHAMELEON_Lang::$user_error_desc_invalid_class,
                                                __FILE__,
                                                __LINE__,
                                                __METHOD__
                                            );
            } else {
                $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$user_error_code_user_not_authorized,
                                                CO_CHAMELEON_Lang::$user_error_name_user_not_authorized,
                                                CO_CHAMELEON_Lang::$user_error_desc_user_not_authorized,
                                                __FILE__,
                                                __LINE__,
                                                __METHOD__
                                            );
            }
        } else {
            $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$user_error_code_invalid_id,
                                            CO_CHAMELEON_Lang::$user_error_name_invalid_id,
                                            CO_CHAMELEON_Lang::$user_error_desc_invalid_id,
                                            __FILE__,
                                            __LINE__,
                                            __METHOD__
                                        );
        }
        
        return $ret;
    }
    
    /***********************************************************************************************************************/
    /***********************/
    /**
    Constructor (Initializer)
     */
	public function __construct(    $in_db_object = NULL,   ///< The database object for this instance.
	                                $in_db_result = NULL,   ///< The database row for this instance (associative array, with database keys).
	                                $in_owner_id = NULL,    ///< The ID of the object (in the database) that "owns" this instance.
	                                $in_tags_array = NULL   ///< An array of strings, up to ten elements long, for the tags. Tag 0 MUST be a single integer (as a string), with the ID of the login object associated with this instance.
                                ) {
        
        $this->_container = Array();
        $this->_login_object = NULL;
        
        parent::__construct($in_db_object, $in_db_result, $in_owner_id, $in_tags_array);
        
        $this->class_description = "This is a 'Collection' Class for Users.";
    }
    
    /***********************/
    /**
    This function sets up this instance, according to the DB-formatted associative array passed in.
    
    \returns true, if the instance was able to set itself up to the provided array.
     */
    public function load_from_db(   $in_db_result   ///< This is an associative array, formatted as a database row response.
                                    ) {
        $ret = parent::load_from_db($in_db_result);
        
        $this->class_description = "This is a 'Collection' Class for Users.";
     
        $this->_set_up_container();
    }
    
    /***********************/
    /**
     Accessor for the login object.
     
     Note that this may return NULL, even if there is a login, as the current user may not have permission to see that login.
     
     \returns the login object associated with this user. It loads the object, if one is not in the cache.
     */
    public function get_login_instance() {
        if (!($this->_login_object instanceof CO_Security_Login)) {
            $this->_load_login();
        }
        
        return $this->_login_object;
    }
    
    /***********************/
    /**
    This is a "security-safe" way of testing for an associated login object. The user may have permission to view the user, but not the login, and they should not know what the login ID is, so this masks the ID.
    
     \returns true, if the object has a login (regardless of whether or not they can see that login).
     */
    public function has_login() {
        return (intval($this->tags()[0]) > 0);
    }
    
    /***********************/
    /**
    This is a "security-safe" way of testing for a login ID that the current user can't see. The user may have permission to view the user, but not the login, and they should not know what the login ID is, so this masks the ID.
    
     \returns true, if the object has a login ID, but the current user can't see that ID or object.
     */
    public function has_login_i_cant_see() {
        $this->get_login_instance();
        return (!($this->_login_object instanceof CO_Security_Login) && (isset($this->tags()[0]) && intval($this->tags()[0])));
    }
    
    /***********************/
    /**
    This is a "security-safe" way of testing for a God login ID. The user may have permission to view the user, but not the login, and they should not know what the login ID is, so this masks the ID.

    \returns true, if the "God" user.
     */
    public function is_god() {
        $tags = $this->tags();
        
        return isset($tags) && is_array($tags) && count($tags) && (0 < intval($tags[0])) && (intval($tags[0]) == CO_Config::god_mode_id());
    }
    
    /***********************/
    /**
    \returns true, if we are a manager.
     */
    public function is_manager() {
        if (!$this->is_god() && !isset($this->_login_object)) {
            $this->_load_login();
        }

        return $this->is_god() || (isset($this->_login_object) && ($this->_login_object instanceof CO_Login_Manager));
    }
    
    /***********************/
    /**
    Simple setter for the tags.
    
    \returns true, if successful.
     */
    public function set_tags(   $in_tags_array  ///< An array of strings, up to ten elements long, for the tags.
                            ) {
        $ret = false;
        
        if (isset($in_tags_array) && is_array($in_tags_array) && count($in_tags_array) && (11 > count($in_tags_array))) {
            // We cannot assign a user we don't have write permissions for
            $id_pool = $this->get_access_object()->get_security_ids();
            $tag0 = intval($in_tags_array[0]);
            if ($this->get_access_object()->god_mode() || ((isset($id_pool) && is_array($id_pool) && count($id_pool) && ((0 == $tag0) || in_array($tag0, $id_pool))))) {
                $ret = parent::set_tags($in_tags_array);
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Setter for one tag, by index.
    
    \returns true, if successful.
     */
    public function set_tag(    $in_tag_index,  ///< The index (0-based -0 through 9) of the tag to set.
                                $in_tag_value   ///< A string, with the tag value.
                            ) {
        $ret = false;
        
        $in_tag_index = intval($in_tag_index);
        
        if ((10 > $in_tag_index) && $this->user_can_write()) {
            // We cannot assign a user we don't have write permissions for
            $id_pool = $this->get_access_object()->get_security_ids();
            if ($this->get_access_object()->god_mode() || ((isset($id_pool) && is_array($id_pool) && count($id_pool) && ((0 <= $in_tag_index) || in_array(intval($in_tag_value), $id_pool))))) {
                $ret = parent::set_tag($in_tag_index, $in_tag_value);
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Simple getter for the surname tag (tag 1).
    
    \returns a string, with the surname.
     */
    public function get_surname() {
        return isset($this->_tags[1]) ? $this->_tags[1] : '';
    }
    
    /***********************/
    /**
    Simple setter for the surname tag (tag 1).
    
    \returns true, if successful.
     */
    public function set_surname(    $in_surname ///< A string, containing the user surname.
                                ) {
        return $this->set_tag(1, $in_surname);
    }
    
    /***********************/
    /**
    Simple getter for the middle name tag (tag 2).
    
    \returns a string, with the middle name.
     */
    public function get_middle_name() {
        return isset($this->_tags[2]) ? $this->_tags[2] : '';
    }
    
    /***********************/
    /**
    Simple setter for the surname tag (tag 2).
    
    \returns true, if successful.
     */
    public function set_middle_name(    $in_middle_name ///< A string, containing the user middle name.
                                ) {
        return $this->set_tag(2, $in_middle_name);
    }
    
    /***********************/
    /**
    Simple getter for the given (first) name tag (tag 3).
    
    \returns a string, with the given name.
     */
    public function get_given_name() {
        return isset($this->_tags[3]) ? $this->_tags[3] : '';
    }
    
    /***********************/
    /**
    Simple setter for the given (first) tag (tag 3).
    
    \returns true, if successful.
     */
    public function set_given_name( $in_given_name ///< A string, containing the user middle name.
                                    ) {
        return $this->set_tag(3, $in_given_name);
    }
    
    /***********************/
    /**
    Simple getter for the prefix tag (tag 4).
    
    \returns a string, with the prefix.
     */
    public function get_prefix() {
        return isset($this->_tags[4]) ? $this->_tags[4] : '';
    }
    
    /***********************/
    /**
    Simple setter for the prefix tag (tag 4).
    
    \returns true, if successful.
     */
    public function set_prefix( $in_prefix  ///< A string, containing the user prefix.
                                ) {
        return $this->set_tag(4, $in_prefix);
    }
    
    /***********************/
    /**
    Simple getter for the suffix tag (tag 5).
    
    \returns a string, with the suffix.
     */
    public function get_suffix() {
        return isset($this->_tags[5]) ? $this->_tags[5] : '';
    }
    
    /***********************/
    /**
    Simple setter for the suffix tag (tag 5).
    
    \returns true, if successful.
     */
    public function set_suffix( $in_suffix  ///< A string, containing the user suffix.
                                ) {
        return $this->set_tag(5, $in_suffix);
    }
    
    /***********************/
    /**
    Simple getter for the nickname tag (tag 6).
    
    \returns a string, with the nickname.
     */
    public function get_nickname() {
        return isset($this->_tags[6]) ? $this->_tags[6] : '';
    }
    
    /***********************/
    /**
    Simple setter for the nickname tag (tag 6).
    
    \returns true, if successful.
     */
    public function set_nickname(   $in_nickname    ///< A string, containing the user nickname.
                                ) {
        return $this->set_tag(6, $in_nickname);
    }
    
    /***********************/
    /**
    This sets the login ID, and has the object regenerate the new instance.
    
    This can only be done by a COBRA Login Manager that has write access to the user object and the login object.
    The manager does not have to have write access to the login object, but it does need read access to it.
    
    \returns true, if the operation suceeded.
     */
    public function set_login(  $in_login_id_integer    ///< The integer ID of the login object to be associated with this instance.
                                ) {
        $ret = parent::user_can_write();
        $in_login_id_integer = intval($in_login_id_integer);
        // Further check to make sure that the current login is a manager.
        if ($ret && ($this->get_access_object()->god_mode() || ($this->get_access_object()->get_login_item() instanceof CO_Login_Manager))) {
            $login_item = $this->get_access_object()->get_login_item($in_login_id_integer);
            
            if ((0 == $in_login_id_integer) || ($login_item instanceof CO_Security_Login)) {
                $tag0 = (0 == $in_login_id_integer) ? NULL : strval(intval($in_login_id_integer));
                
                $ret = $this->set_tag(0, $tag0);
                
                if ($ret) { // Make sure that we'll get a fresh login next time.
                    $this->_login_object = NULL;
                }
            }
        } else {
            $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$user_error_code_user_not_authorized,
                                            CO_CHAMELEON_Lang::$user_error_name_user_not_authorized,
                                            CO_CHAMELEON_Lang::$user_error_desc_user_not_authorized,
                                            __FILE__,
                                            __LINE__,
                                            __METHOD__
                                        );
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    We override this, because we see if we need to fetch our lang from the login object.
    
    \returns a string, with the language ID for this login.
     */
    public function get_lang() {
        $ret = parent::get_lang();
        
        if (!isset($this->context['lang'])) {
            $login_object = $this->get_login_instance();
            if (isset($login_object) && ($login_object instanceof CO_Security_Login)) {
                $ret = $login_object->get_lang();
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    We override this, because we want to see if they want us to delete associated objects.
    
    \returns true, if the deletion was successful.
     */
    public function delete_from_db( $with_extreme_prejudice = false,    ///< If true (Default is false), then we will attempt to delete all contained children. Remember that this could cause problems if other collections can see the children!
                                    $delete_login_object_too = false    ///< If true (Default is false), then we will attempt to delete any associated login object, as well.
                                    ) {
        if ($with_extreme_prejudice && $this->user_can_write()) {
            // We don't error-check this on purpose, as it's a given that there might be issues, here. This is a "due dilligence" thing.
            $user_items_to_delete = $this->children();
            
            foreach ($user_items_to_delete as $child) {
                if ($child->user_can_write()) {
                    $child->delete_from_db();
                }
            }
        }
        
        // Again, we won't return false if this fails, but we will fetch the error.
        if ($delete_login_object_too) {
            $login_object = $this->get_login_instance();
        
            if (isset($login_object) && $login_object->user_can_write()) {
                $login_object->delete_from_db();
                $this->error = $login_object->error;
            }
        }
        
        return parent::delete_from_db();
    }
};
