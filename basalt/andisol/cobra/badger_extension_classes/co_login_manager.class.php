<?php
/***************************************************************************************************************************/
/**
    COBRA Security Administration Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC/The Great Rift Valley Software Company
    
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

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_ACCESS_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

require_once(dirname(__FILE__).'/co_cobra_login.class.php');

/***************************************************************************************************************************/
/**
 */
class CO_Login_Manager extends CO_Cobra_Login {
    protected   $_added_new_id; ///< This is a very temporary, ephemeral semaphore that we use to allow us to add an ID when we create a new object.
    
    /***********************************************************************************************************************/    
    /***********************/
    /**
    The constructor.
     */
	public function __construct(    $in_login_id = NULL,        ///< The login ID
                                    $in_hashed_password = NULL, ///< The password, crypt-hashed
                                    $in_raw_password = NULL     ///< The password, cleartext.
	                            ) {
        parent::__construct($in_login_id, $in_hashed_password, $in_raw_password);
        $this->_added_new_id = NULL;
        $this->class_description = 'This is a security class for login managers.';
        if (intval($this->id()) == intval(CO_Config::god_mode_id())) {
            // God Mode is always forced to use the config password.
            $this->context['hashed_password'] = bin2hex(openssl_random_pseudo_bytes(4));    // Just create a randomish junk password. It will never be used.
            $this->instance_description = 'GOD MODE: '.(isset($this->name) && $this->name ? "$this->name (".$this->login_id.")" : "Unnamed Login Manager Node (".$this->login_id.")");
        } else {
            $this->instance_description = isset($this->name) && $this->name ? "$this->name (".$this->login_id.")" : "Unnamed Login Manager Node (".$this->login_id.")";
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
            $this->class_description = 'This is a security class for login managers.';
            if (intval($this->id()) == intval(CO_Config::god_mode_id())) {
                // God Mode is always forced to use the config password.
                $this->context['hashed_password'] = bin2hex(openssl_random_pseudo_bytes(4));    // Just create a randomish junk password. It will never be used.
                $this->instance_description = 'GOD MODE: '.(isset($this->name) && $this->name ? "$this->name (".$this->login_id.")" : "Unnamed Login Manager Node (".$this->login_id.")");
            } else {
                $this->instance_description = isset($this->name) && $this->name ? "$this->name (".$this->login_id.")" : "Unnamed Login Manager Node (".$this->login_id.")";
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns true, as we are a manager.
     */
    public function is_manager() {
        return true;
    }
    
    /***********************/
    /**
    This allows us to add one single ID to our list.
    We set our ephemeral ID, then we add the ID, which should pass, just this once.
    
    \returns true, if the operation succeeded.
     */
    public function add_new_login_id(   $in_login_id    ///< The integer ID of the new login item.
                                    ) {
        $this->_added_new_id = intval($in_login_id);
        $ret = $this->add_id($in_login_id);
        unset($this->_added_new_id);
        return $ret;
    }
};
