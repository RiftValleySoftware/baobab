<?php
/***************************************************************************************************************************/
/**
    Badger Hardened Baseline Database Component
    
    © Copyright 2018, Little Green Viper Software Development LLC/The Great Rift Valley Software Company
    
    LICENSE:
    
    FOR OPEN-SOURCE (COMMERCIAL OR FREE):
    This code is released as open source under the GNU Plublic License (GPL), Version 3.
    You may use, modify or republish this code, as long as you do so under the terms of the GPL, which requires that you also
    publish all modificanions, derivative products and license notices, along with this code.
    
    UNDER SPECIAL LICENSE, DIRECTLY FROM LITTLE GREEN VIPER OR THE GREAT RIFT VALLEY SOFTWARE COMPANY:
    It is NOT to be reused or combined into any application, nor is it to be redistributed, republished or sublicensed,
    unless done so, specifically WITH SPECIFIC, WRITTEN PERMISSION from Little Green Viper Software Development LLC,
    or The Great Rift Valley Software Company.

    Little Green Viper Software Development: https://littlegreenviper.com
    The Great Rift Valley Software Company: https://riftvalleysoftware.com

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_DBF_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

if ( !defined('LGV_SDBN_CATCHER') ) {
    define('LGV_SDBN_CATCHER', 1);
}

require_once(CO_Config::db_class_dir().'/co_security_node.class.php');

/***************************************************************************************************************************/
/**
This is the specialized class for the generic security ID.
 */
class CO_Security_ID extends CO_Security_Node {
    protected $_special_first_time_security_exemption;

    /***********************************************************************************************************************/
    /***********************/
    /**
    Constructor.
     */
	public function __construct(    $in_db_object = NULL,   ///< This is the database instance that "owns" this record.
	                                $in_db_result = NULL    ///< This is a database-format associative array that is used to initialize this instance.
                                ) {
        parent::__construct($in_db_object, $in_db_result);
        $this->class_description = 'This is a security class for IDs.';
        $this->instance_description = isset($this->name) && $this->name ? "$this->name ($this->_id)" : "Unnamed ID Node ($this->_id)";
        $this->_special_first_time_security_exemption = true;
        $this->read_security_id = $this->id();  // These are always the case, regardless of what anyone else says.
        $this->write_security_id = -1;
    }

    /***********************/
    /**
    This function sets up this instance, according to the DB-formatted associative array passed in.
    
    \returns true, if the instance was able to set itself up to the provided array.
     */
    public function load_from_db($in_db_result) {
        $ret = parent::load_from_db($in_db_result);
        
        if ($ret) {
            $this->read_security_id = $this->id();  // These are always the case, regardless of what anyone else says.
            $this->write_security_id = -1;
            $this->class_description = 'This is a security class for IDs.';
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
    
    /***********************/
    /**
    This is an overload, because we also want to make sure that only cleared manager objects get to see this (or God, of course).
    \returns true, if the current logged-in user has read permission on this record.
     */
    public function user_can_read() {
        $ret = parent::user_can_read();
        
        if ($ret) {
            $ret = false;
            // We make double-damn sure that only cleared managers can see this.
            $item = $this->get_access_object()->get_login_item();
            if ($this->get_access_object()->god_mode() || (isset($item) && ($item instanceof CO_Login_Manager))) {
                $exemption = in_array($this->id(), $item->ids());
            
                $ret = ($this->get_access_object()->god_mode() || $exemption);
            }
        }
        
        return $ret;
    }
};
