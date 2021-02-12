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

require_once(CO_Config::db_classes_class_dir().'/co_security_login.class.php');
require_once(dirname(__FILE__).'/co_login_manager.class.php');

/***************************************************************************************************************************/
/**
 */
class CO_Cobra_Login extends CO_Security_Login {
    protected $_special_first_time_security_exemption;

    /***********************************************************************************************************************/    
    /***********************/
    /**
    The constructor.
     */
	public function __construct(    $in_login_id = NULL,            ///< The login ID
                                    $in_hashed_password = NULL,     ///< The password, crypt-hashed
                                    $in_raw_password = NULL         ///< The password, cleartext.
	                            ) {
        parent::__construct($in_login_id, $in_hashed_password, $in_raw_password);
        
        $this->_special_first_time_security_exemption = true;
        if (intval($this->id()) == intval(CO_Config::god_mode_id())) {
            // God Mode is always forced to use the config password.
            $this->context['hashed_password'] = bin2hex(openssl_random_pseudo_bytes(4));    // Just create a randomish junk password. It will never be used.
            $this->instance_description = 'GOD MODE: '.(isset($this->name) && $this->name ? "$this->name (".$this->login_id.")" : "Unnamed Standard Login Node (".$this->login_id.")");
        } else {
            $this->instance_description = isset($this->name) && $this->name ? "$this->name (".$this->login_id.")" : "Unnamed Standard Login Node (".$this->login_id.")";
        }
    }
    
    /***********************/
    /**
    This sets just the "personal" IDs for object. Unlike the one in the initial login class, this can be called by non-God admins.
    
    This only sets the IDs, if the member property is NULL (just created).
    
    This is not an atomic operation. If any of the given IDs are also in the regular ID list, they will be removed from the personal IDs.
     */
    public function set_personal_ids(   $in_personal_ids = []   ///< An Array of Integers, with the new personal IDs. This replaces any previous ones. If empty, then the IDs are removed.
                                    ) {
        $personal_ids_temp = array_unique(array_map('intval', $in_personal_ids));
        if (CO_Config::use_personal_tokens() && (NULL == $this->_personal_ids) && is_array($personal_ids_temp) && count($personal_ids_temp)) {
            $personal_ids = [];
            $my_ids = $this->_ids;
            // None of the ids can be in the regular IDs, and will be removed from the set, if so.
            // They also cannot be anyone else's personal ID, or anyone's login ID. Personal IDs can ONLY be regular (non-login) security objects.
            foreach($personal_ids_temp as $id) {
                // Make sure that we don't have this personal token in our regular ID array.
                if (($key = array_search($id, $my_ids)) !== false) {
                    unset($my_ids[$key]);
                }
                if (!$this->get_access_object()->is_this_a_login_id($id) && (!$this->get_access_object()->is_this_a_personal_id($id) || in_array($id, $this->_personal_ids))) {
                    array_push($personal_ids, $id);
                }
            }
            $this->_ids = $my_ids;
            sort($personal_ids);
            $this->_personal_ids = $personal_ids;
            
            $this->update_db();
        } else {    // If we are not NULL, then we kick the can down the road.
            parent::set_personal_ids($in_personal_ids);
        }
    }

    /***********************/
    /**
    This function sets up this instance, according to the DB-formatted associative array passed in.
    
    \returns true, if the instance was able to set itself up to the provided array.
     */
    public function load_from_db($in_db_result) {
        $ret = parent::load_from_db($in_db_result);
        
        if ($ret) {
            $this->class_description = 'This is a security class for standard logins.';
            if (intval($this->id()) == intval(CO_Config::god_mode_id())) {
                // God Mode is always forced to use the config password.
                $this->context['hashed_password'] = bin2hex(openssl_random_pseudo_bytes(4));    // Just create a randomish junk password. It will never be used.
                $this->instance_description = 'GOD MODE: '.(isset($this->name) && $this->name ? "$this->name (".$this->login_id.")" : "Unnamed Standard Login Node (".$this->login_id.")");
            } else {
                $this->instance_description = isset($this->name) && $this->name ? "$this->name (".$this->login_id.")" : "Unnamed Standard Login Node (".$this->login_id.")";
            }
        }
        
        return $ret;
    }

    /***********************/
    /**
    This weird little function allows a creator to once -and only once- add an ID to itself, as long as that ID is for this object.
    This is a "Heisenberg" query. Once it's called, the security exemption is gone.
    
    returns true, if the security exemption was on.
     */
    public function security_exemption() {
        $ret = $this->_special_first_time_security_exemption;
        $this->_special_first_time_security_exemption = false;
        
        return $ret;
    }
};
