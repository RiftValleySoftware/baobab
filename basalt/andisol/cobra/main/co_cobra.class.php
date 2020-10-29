<?php
/***************************************************************************************************************************/
/**
    COBRA Security Administration Layer
    
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

define('__COBRA_VERSION__', '1.0.3.3000');

require_once(CO_Config::chameleon_main_class_dir().'/co_chameleon.class.php');

if ( !defined('LGV_LANG_CATCHER') ) {
    define('LGV_LANG_CATCHER', 1);
}

require_once(CO_Config::lang_class_dir().'/common.inc.php');

/***************************************************************************************************************************/
/**
This class implements a "login manager" functionality to The Rift Valley Platform.

This class can only be instantiated by the "God" login, or a login that is a CO_Login_Manager.

You use COBRA to manage other logins, or security tokens.
 */
class CO_Cobra {
    private $_chameleon_instance = NULL;    ///< This is the CHAMELEON instance that is associated with this COBRA instance.
    
    var $version;                           ///< The version indicator.
    
    /***********************/
    /**
    Factory Function.
    
    This vets the CHAMELEON instance, and makes sure that it's valid before returning a constructed COBRA.
    
    \returns an instance of CO_Cobra upon success. If there was an error set by COBRA (or CHAMELEON), or the vetting failed, that is returned instead of a COBRA instance.
     */
    static function make_cobra($in_chameleon_instance) {
        $ret = NULL;
        
	    // We must have a valid CHAMELEON instance that is logged in. The login user must be a COBRA Manager user (the standard logins cannot use COBRA).
	    if (isset($in_chameleon_instance)
	        && ($in_chameleon_instance instanceof CO_Chameleon)
	        && $in_chameleon_instance->security_db_available()
	        && ($in_chameleon_instance->god_mode() || ($in_chameleon_instance->get_login_item() instanceof CO_Login_Manager))) {
            $ret = new CO_Cobra($in_chameleon_instance);
        } elseif (isset($in_chameleon_instance) && ($in_chameleon_instance instanceof CO_Chameleon)) {
            $ret = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_user_not_authorized,
                                    CO_COBRA_Lang::$cobra_error_name_user_not_authorized,
                                    CO_COBRA_Lang::$cobra_error_desc_user_not_authorized_instance);
        } else {
            $ret = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_invalid_chameleon,
                                    CO_COBRA_Lang::$cobra_error_name_invalid_chameleon,
                                    CO_COBRA_Lang::$cobra_error_desc_invalid_chameleon);
        }
    
        return $ret;
    }
    
    /***********************************************************************************************************************/    
    /***********************/
    /**
    The constructor.
    
    We declare it private to prevent it being instantiated outside the factory.
     */
	private function __construct(    $in_chameleon_instance = NULL   ///< The CHAMELEON instance associated with this COBRA instance.
	                            ) {
	    $this->_chameleon_instance = $in_chameleon_instance;
	    $this->version = __COBRA_VERSION__;
    }
    
    /***********************/
    /**
    This is the internal function used to convert a login to (or from) a manager, in the security database.
    This can only be called from a login manager.
    
    \returns the converted instance.
     */
    protected function _convert_login(  $in_login_id,                   ///< The login ID as text. This must be for a login that can be managed by the current manager.
                                        $in_is_login_manager = false    ///< If true, then this is a "promotion" to a a manager. If false (default), then this is a "demotion" to a standard user.
                                        ) {
        $ret = NULL;
        $working_login = $this->_chameleon_instance->get_login_item_by_login_string($in_login_id);   // This is the login we are changing.
        $manager = $this->_chameleon_instance->get_login_item();                                    // This is our current login.
        // Make sure we are a login manager, first, and that we have write permission on the user.
        if ($working_login && $working_login->user_can_write() && ($manager instanceof CO_Login_Manager || $manager->is_god())) {
            // Now, make sure that we're actually making a change.
            if (($in_is_login_manager && !$working_login->is_manager()) || (!$in_is_login_manager && $working_login->is_manager())) {
                $className = $in_is_login_manager ? 'CO_Login_Manager' : 'CO_Cobra_Login';
                $user_object = $this->get_user_from_login($working_login->id());    // Get this, so we can modify it.
                
                // Create a new blank instance.
                $new_login_object = $this->_chameleon_instance->make_new_blank_record($className);
                
                if (isset($new_login_object) && ($new_login_object instanceof CO_Cobra_Login)) {
                    $original_login_id = $working_login->id();
                    $original_ids = $working_login->ids();
                    if (!isset($original_ids)) {
                        $original_ids = [];
                    }
                    
                    array_push($original_ids, $original_login_id);    // We will add this to the new login's IDs.
                    $original_ids = array_unique($original_ids);
                    
                    asort($original_ids);
                    
                    $new_login_object->login_id = $working_login->login_id;
                    $new_login_object->name = $working_login->name;
                    $new_login_object->context = $working_login->context;
                    $new_login_object->set_ids($original_ids);
                    // OK. At this point, we have a new login that is of the type that we want. It is different from the previous one, and has all the relevant data.
                    // Now, we will delete the old one, and then replace it with the new one.
                    if ($working_login->delete_from_db()) {
                        if ($new_login_object->update_db()) {
                            $new_id = $new_login_object->id();
                            if ($this->_chameleon_instance->get_login_item()->add_new_login_id($new_id)) {
                                $new_login_object->read_security_id = $new_id;
                                $new_login_object->write_security_id = $new_id;
                                if ($new_login_object->update_db()) {
                                    $user_object->write_security_id = $new_id;
                                    $user_object->set_login($new_id);
                                    if ($user_object->update_db()) {
                                        $ret = $new_login_object;   // If everything went well, to this point, we update the return.
                                    } else {
                                        $this->error = $user_object->error;
                                    }
                                } else {
                                    $this->error = $new_login_object->error;
                                }
                            } else {
                                $this->error = $this->_chameleon_instance->error;
                            }
                        } else {
                            $this->error = $new_login_object->error;
                        }
                    } else {
                        $this->error = $working_login->error;
                    }
                }                
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This is the internal function used to create a new login in the security database.
    This can only be called from a login manager.
    
    \returns the new CO_Cobra_Login instance (or CO_Login_Manager instance).
     */
    protected function _create_new_login(   $in_login_id,                   ///< The login ID as text. It needs to be unique, within the Security database, and this will fail, if it is not.
                                            $in_cleartext_password,         ///< The password to set (in cleartext). It will be stored as a hashed password.
                                            $in_security_token_ids = NULL,  ///< An array of integers. These are security token IDs for the login (default is NULL). If NULL, then no IDs will be set. These IDs must be selected from those available to the currently logged-in manager.
                                            $in_is_login_manager = false    ///< true, if we want a CO_Login_Manager instance, instead of a CO_Cobra_Login instance. Default is false.
                                        ) {
        $ret = NULL;
        
        if (isset($in_login_id) && !$this->_chameleon_instance->check_login_exists_by_login_string($in_login_id)) {
            $manager = $this->_chameleon_instance->get_login_item();
            if ($manager instanceof CO_Login_Manager || $manager->is_god()) { // Make sure we are a login manager, first.
                $use_these_ids = Array();
                // Next, see if they provided IDs. If so, we remove any that we don't own.
                if (isset($in_security_token_ids) && is_array($in_security_token_ids) && count($in_security_token_ids)) {
                    $my_ids = $this->get_security_ids();
                
                    foreach ($in_security_token_ids as $id) {
                        if (in_array($id, $my_ids)) {
                            array_push($use_these_ids, $id);
                        }
                    }
                    // At this point, only IDs that we have available are in the array.
                }
                
                $className = $in_is_login_manager ? 'CO_Login_Manager' : 'CO_Cobra_Login';
                
                $new_login_object = $this->_chameleon_instance->make_new_blank_record($className);
                
                if (isset($new_login_object) && ($new_login_object instanceof CO_Cobra_Login)) {
                    $new_login_object->login_id = $in_login_id;
                    if (strlen($in_cleartext_password) >= CO_Config::$min_pw_len) {
                        $new_login_object->context['hashed_password'] = password_hash($in_cleartext_password, PASSWORD_DEFAULT);
                        
                        if (!$new_login_object->update_db()) {
                            $this->error = $new_login_object->error;
                            $new_login_object->delete_from_db();
                            $new_login_object = NULL;
                        } else {
                            $new_id = $new_login_object->id();
                            if (method_exists($manager, 'add_new_login_id')) {
                                $manager->add_new_login_id($new_id);
                            }
                            if ($new_login_object->set_read_security_id($new_id)) {
                                if ($new_login_object->set_write_security_id($new_id)) {
                                    $new_ids = [];
                                    foreach ($use_these_ids as $id) {
                                        if (in_array($id, $my_ids) && ($id != $new_id)) {
                                            array_push($new_ids, $id);
                                        }
                                    }
                                    if ($new_login_object->set_ids($new_ids)) {
                                        $ret = $new_login_object;
                                    } else {
                                        $this->error = $new_login_object->error;
                                        $new_login_object->delete_from_db();
                                        $new_login_object = NULL;
                                    }
                                } else {
                                    $this->error = $new_login_object->error;
                                    $new_login_object->delete_from_db();
                                    $new_login_object = NULL;
                                }
                            } else {
                                $this->error = $new_login_object->error;
                                $new_login_object->delete_from_db();
                                $new_login_object = NULL;
                            }
                        }
                    } else {
                        $new_login_object->delete_from_db();
                        $new_login_object = NULL;
                        $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_password_too_short,
                                                        CO_COBRA_Lang::$cobra_error_name_password_too_short,
                                                        CO_COBRA_Lang::$cobra_error_desc_password_too_short);
                    }
                } else {
                    if (isset($this->_chameleon_instance->error)) {
                        $this->error = $this->_chameleon_instance->error;
                    } else {
                        $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_instance_failed_to_initialize,
                                                        CO_COBRA_Lang::$cobra_error_name_instance_failed_to_initialize,
                                                        CO_COBRA_Lang::$cobra_error_desc_instance_failed_to_initialize);
                    }
                }
                
            } else {
                $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_user_not_authorized,
                                                CO_COBRA_Lang::$cobra_error_name_user_not_authorized,
                                                CO_COBRA_Lang::$cobra_error_desc_user_not_authorized);
            }
        } elseif (isset($in_login_id)) {
            $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_user_already_exists,
                                            CO_COBRA_Lang::$cobra_error_name_user_already_exists,
                                            CO_COBRA_Lang::$cobra_error_desc_user_already_exists);
        } else {
            $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_login_error,
                                            CO_COBRA_Lang::$cobra_error_name_login_error,
                                            CO_COBRA_Lang::$cobra_error_desc_login_error);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of integers, with each one representing a special security token for editing security items.
     */
    public function get_security_ids() {
        return $this->_chameleon_instance->get_security_ids();
    }
    
    /***********************/
    /**
    Creates a new "standalone" user that has no associated login instance.
    
    \returns the new user record.
     */
    public function make_standalone_user() {
        $user = NULL;
        
        if ($this->_chameleon_instance->god_mode() || ($this->_chameleon_instance->get_login_item() instanceof CO_Login_Manager)) {     // We have to be a manager to create a user.
            $user = $this->_chameleon_instance->make_new_blank_record('CO_User_Collection');
        
            if ($user) {
                if (!isset($user->error)) {
                    $user->set_read_security_id(1); // Users default to 1 (only logged-in users can see).
                    $user->set_write_security_id($this->_chameleon_instance->get_login_item()->id()); // Make sure that only we can modify this record.
                    if (isset($user->error)) {
                        $this->error = $user->error;
                        $user->delete_from_db();
                        $user = NULL;
                    }
                } else {
                    $this->error = $user->error;
                    $user->delete_from_db();
                    $user = NULL;
                }
            } else {
                $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_instance_failed_to_initialize,
                                                CO_COBRA_Lang::$cobra_error_name_instance_failed_to_initialize,
                                                CO_COBRA_Lang::$cobra_error_desc_instance_failed_to_initialize);
            }
        } elseif (!($this->_chameleon_instance->get_login_item() instanceof CO_Login_Manager)) {
            $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_user_not_authorized,
                                            CO_COBRA_Lang::$cobra_error_name_user_not_authorized,
                                            CO_COBRA_Lang::$cobra_error_desc_user_not_authorized);
        }
        
        return $user;
    }
    
    /***********************/
    /**
    This returns the login instance for the given ID string.
    
    This is scurity-vetted. The current login needs to be able to see the item.
    
    \returns the Login Item. NULL if unable to fetch the item.
     */
    public function get_login_instance( $in_login_id    ///< The string login ID that we are referencing.
                                        ) {
        $ret = NULL;
        
        if (isset($in_login_id) && $in_login_id) {
            $ret = $this->_chameleon_instance->get_login_item_by_login_string($in_login_id);
            
            $this->error = $this->_chameleon_instance->error;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This fetches a user from a given login ID.
    
    The user may be created, if the current login is a Login Manager, and the second parameter is set to true.
    
    \returns an instance of a user collection. If new, it will be blank.
     */
    public function get_user_from_login(    $in_login_id = NULL,                ///< The integer login ID that is associated with the user collection. If NULL, then the current login is used.
                                            $in_make_user_if_necessary = false  ///< If true (Default is false), then the user will be created if it does not already exist. Ignored, if we are not a Login Manager.
                                        ) {
        $user = $this->_chameleon_instance->get_user_from_login($in_login_id);   // First, see if it's already a thing.
        
        if (!$user && $in_make_user_if_necessary && ($this->_chameleon_instance->god_mode() || ($this->_chameleon_instance->get_login_item() instanceof CO_Login_Manager))) {   // If not, we will create a new one, based on the given login. We must be a manager.
            if (isset($in_login_id) && (0 < intval($in_login_id))) {    // See if they seek a different login.
                $login_id = intval($in_login_id);
            }
            // Assuming all is well, we need to create a new user. We have to be a login manager to do this.
            if (isset($in_login_id) && (0 < intval($in_login_id))) {
                $login_item = $this->_chameleon_instance->get_login_item($in_login_id);
                
                if (isset($login_item) && ($login_item instanceof CO_Security_Login)) {
                    if (!$this->_chameleon_instance->check_user_exists($in_login_id)) {
                        $user = $this->make_standalone_user();
                    
                        if ($user) {
                            $user->set_login($in_login_id); // We set the user's login instance to the login instance we're using as the basis.
                            if (isset($user->error)) {
                                $this->error = $user->error;
                                $user->delete_from_db();
                                $user = NULL;
                            } else {
                                $user->set_write_security_id($in_login_id); // Make sure the user can modify their own record.
                                if (isset($user->error)) {
                                    $this->error = $user->error;
                                    $user->delete_from_db();
                                    $user = NULL;
                                }
                            }
                        } else {
                            $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_instance_failed_to_initialize,
                                                            CO_COBRA_Lang::$cobra_error_name_instance_failed_to_initialize,
                                                            CO_COBRA_Lang::$cobra_error_desc_instance_failed_to_initialize);
                        }
                    } else {
                        $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_user_already_exists,
                                                        CO_COBRA_Lang::$cobra_error_name_user_already_exists,
                                                        CO_COBRA_Lang::$cobra_error_desc_user_already_exists);
                    }
                } else {
                    $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_login_unavailable,
                                                    CO_COBRA_Lang::$cobra_error_name_login_unavailable,
                                                    CO_COBRA_Lang::$cobra_error_desc_login_unavailable);
                }
            } else {
                $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_login_unavailable,
                                                CO_COBRA_Lang::$cobra_error_name_login_unavailable,
                                                CO_COBRA_Lang::$cobra_error_desc_login_unavailable);
            }
        } elseif (!($this->_chameleon_instance->get_login_item() instanceof CO_Login_Manager)) {
            $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_user_not_authorized,
                                            CO_COBRA_Lang::$cobra_error_name_user_not_authorized,
                                            CO_COBRA_Lang::$cobra_error_desc_user_not_authorized);
        }
        
        return $user;
    }
    
    /***********************/
    /**
    This is the public function used to create a new standard login in the security database.
    This can only be called from a login manager.
    
    \returns the new CO_Cobra_Login instance.
     */
    public function create_new_standard_login(  $in_login_id,                   ///< The login ID as text. It needs to be unique, within the Security database, and this will fail, if it is not.
                                                $in_cleartext_password,         ///< The password to set (in cleartext). It will be stored as a hashed password.
                                                $in_security_token_ids = NULL   ///< An array of integers. These are security token IDs for the login (default is NULL). If NULL, then no IDs will be set. These IDs must be selected from those available to the currently logged-in manager.
                                            ) {
        return $this->_create_new_login($in_login_id, $in_cleartext_password, $in_security_token_ids);
    }
    
    /***********************/
    /**
    This is the public function used to create a new login manager login in the security database.
    This can only be called from a login manager.
    
    \returns the new CO_Login_Manager instance.
     */
    public function create_new_manager_login(   $in_login_id,                   ///< The login ID as text. It needs to be unique, within the Security database, and this will fail, if it is not.
                                                $in_cleartext_password,         ///< The password to set (in cleartext). It will be stored as a hashed password.
                                                $in_security_token_ids = NULL   ///< An array of integers. These are security token IDs for the login (default is NULL). If NULL, then no IDs will be set. These IDs must be selected from those available to the currently logged-in manager.
                                            ) {
        return $this->_create_new_login($in_login_id, $in_cleartext_password, $in_security_token_ids, true);
    }
    
    /***********************/
    /**
    This is the public function used to convert a login to (or from) a manager, in the security database.
    This can only be called from a login manager.
    The user is not affected, and the login IDs (tokens) are also left "as is."
    
    \returns the converted instance.
     */
    public function convert_login(  $in_login_id,                   ///< The login ID as text. This must be for a login that can be managed by the current manager.
                                    $in_is_login_manager = false    ///< If true, then this is a "promotion" to a a manager. If false (default), then this is a "demotion" to a standard user.
                                ) {
        return $this->_convert_login($in_login_id, $in_is_login_manager);
    }
    
    /***********************/
    /**
    This deletes a login, given the login ID.
    When we delete a login, it actually gets changed into a security ID instance (to reserve the ID slot), however, the user object is actually removed.
    It should be noted that deleting a (user) collection does not delete everything in the collection; only the collection object, itself.
    
    \returns true, if the operation (or operations) succeeded.
     */
    public function delete_login(   $in_login_id,               ///< The login ID as text.
                                    $also_delete_user = false   ///< If true (Default is false), then we will also delete the user record associated with this login.
                                ) {
        $ret = false;
        
        $cobra_login_instance = $this->get_login_instance($in_login_id);
        
        if ($cobra_login_instance) {
            $cobra_user_instance = NULL;
            
            if ($also_delete_user) {
                $cobra_user_instance = $this->get_user_from_login($cobra_login_instance->id());
            }
            
            $ret = $cobra_login_instance->delete_from_db();
            
            if ($ret && $cobra_user_instance) {
                $ret = $cobra_user_instance->delete_from_db();
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of instances of all the logins that are visible to the current user (or a supplied user, if in "God" mode).
     */
    public function get_all_logins( $and_write = false,         ///< If true, then we only want ones we have write access to.
                                    $in_login_id = NULL,        ///< This is ignored, unless this is the God login. If We are logged in as God, then we can select a login via its string login ID, and see what logins are available to it.
                                    $in_login_integer_id = NULL ///< This is ignored, unless this is the God login and $in_login_id is not specified. If We are logged in as God, then we can select a login via its integer login ID, and see what logins are available to it.
                                    ) {
        if (!$this->_chameleon_instance->god_mode()) {  // Definitely won't look at this unless we are God.
            $in_login_id = NULL;
            $in_login_integer_id = 0;
        } else {
            $in_login_id = trim(strval($in_login_id));
        
            if (!$in_login_id) {    // String login ID trumps integer.
                $in_login_integer_id = intval($in_login_integer_id);
            } else {
                $item = $this->_chameleon_instance->get_login_item_by_login_string($in_login_id);
                $in_login_id = NULL;
                $in_login_integer_id = $item->id();
            }
        }
        
        // If both $in_login_id and $in_login_integer_id are unspecified, then we'll find every login we can see.
        // If they are specified (which means we're God), then we filter for only the 
        $id_list = Array();
        $results = $this->_chameleon_instance->get_all_login_objects($and_write);
        if (isset($results) && is_array($results) && count($results)) {
            foreach ($results as $result) {
                if (!$in_login_integer_id || ($in_login_integer_id == $result->id())) {
                    $id_list[] = $result->id();
                    foreach ($result->ids() as $id) {
                        if (($id != CO_Config::god_mode_id()) || (($id == CO_Config::god_mode_id()) && ($in_login_integer_id == CO_Config::god_mode_id()))) {
                            $id_list[] = $id;
                        }
                    }
                }
            }
        }
        
        $id_list = array_unique($id_list);
        $ret = Array();
        foreach ($id_list as $id) {
            $object = $this->_chameleon_instance->get_single_security_record_by_id($id);
            if ($object instanceof CO_Security_Login) {
                $ret[] = $object;
            }
        }
        if (1 < count($ret)) {
            // Sort the results by ID.
            usort($ret, function ($a, $b) {
                                            if ($a->id() == $b->id()) {
                                                return 0;
                                            }
                                        
                                            if ($a->id() < $b->id()) {
                                                return -1;
                                            }
                                        
                                            return 1;
                                            });
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Test an item to see which logins can access it.
    
    This is security-limited.
    
    \returns an array of instances of CO_Security_Login (Security Database login) items that can read/see the given item. If the read ID is 0 (open), then the function simply returns true. If nothing can see the item, then false is returned.
     */
    public function who_can_see(    $in_test_target ///< This is a subclass of A_CO_DB_Table_Base (General Database Record).
                                ) {
        $ret = false;
        
        if (isset($in_test_target) && ($in_test_target instanceof A_CO_DB_Table_Base)) {
            $id = intval($in_test_target->read_security_id);
            
            if (0 < $id) {
                $ret = $this->_chameleon_instance->get_all_login_objects_with_access($id);
                if (!isset($ret) || !is_array($ret) || !count($ret)) {
                    $ret = false;
                }
            } elseif (0 == $id) {
                $ret = true;
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Test an item to see which logins can modify it.
    
    This is security-limited.
    
    \returns an array of instances of CO_Security_Login (Security Database login) items that can modify the given item. If the write ID is 0 (open), then the function simply returns true. If nothing can modify the item, then false is returned.
     */
    public function who_can_modify( $in_test_target,            ///< This is a subclass of A_CO_DB_Table_Base (General Database Record).
                                    $non_managers_only = false  /**< Ignored if the target is not an instance (or subclass) of CO_Security_Login.
                                                                     If true (default is false), then only login manager objects will be returned.
                                                                     If you supply a login object as the target, this is a quick way to see if any non-manager objects can modify it.
                                                                     In reality, there should be no non-manager objects that can modify a login, besides the login, itself.
                                                                */
                                    ) {
        $ret = false;
        
        if (isset($in_test_target) && ($in_test_target instanceof A_CO_DB_Table_Base)) {
            $id = intval($in_test_target->write_security_id);
            
            if (0 < $id) {
                $ret = $this->_chameleon_instance->get_all_login_objects_with_access($id, true);
                // Check to see if any non-manager objects can modify the login (should never be).
                if (($in_test_target instanceof CO_Security_Login) && isset($ret) && is_array($ret) && $non_managers_only) {
                    $ret_temp = Array();
                    foreach ($ret as $login) {
                        if (!($login instanceof CO_Login_Manager)) {
                            $ret_temp[] = $login;
                        }
                    }
                    
                    if (count($ret_temp)) {
                        $ret = $ret_temp;
                    } else {
                        $ret = false;
                    }
                } elseif (!isset($ret) || !is_array($ret) || !count($ret)) {
                    $ret = false;
                }
            } elseif (0 == $id) {
                $ret = true;
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This simply generates a new security token instance.
    
    Security tokens are "the gift that keeps on giving." Once created, they can't easily be deleted. Only the God admin can delete them. They are permanent placeholders.
    
    \returns an integer, with the new ID, or 0, if the method failed (check error).
     */
    public function make_security_token() {
        $ret = 0;
        
        $manager = $this->_chameleon_instance->get_login_item();
        if ($this->_chameleon_instance->god_mode() || ($manager instanceof CO_Login_Manager)) {
            $new_token = $this->_chameleon_instance->make_new_blank_record('CO_Security_ID');
            if (isset($new_token) && ($new_token instanceof CO_Security_ID)) {
                $new_id = $new_token->id();
                if ($this->_chameleon_instance->god_mode() || $manager->add_new_login_id($new_id)) {  // We need to do the "special excemption" add of the ID to the manager.
                    $ret = $new_id;
                } else {    // We were unable to set the new token ID to the manager.
                    $new_token->delete_from_db();
                    $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_token_id_not_set,
                                                    CO_COBRA_Lang::$cobra_error_name_token_id_not_set,
                                                    CO_COBRA_Lang::$cobra_error_desc_token_id_not_set);
                }
            } else {    // Token object did not get instantiated.
                $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_token_instance_failed_to_initialize,
                                                CO_COBRA_Lang::$cobra_error_name_token_instance_failed_to_initialize,
                                                CO_COBRA_Lang::$cobra_error_desc_token_instance_failed_to_initialize);
            }
        } else {    // Should never happen, but what the hell...
            $this->error = new LGV_Error(   CO_COBRA_Lang_Common::$cobra_error_code_user_not_authorized,
                                            CO_COBRA_Lang::$cobra_error_name_user_not_authorized,
                                            CO_COBRA_Lang::$cobra_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
};
