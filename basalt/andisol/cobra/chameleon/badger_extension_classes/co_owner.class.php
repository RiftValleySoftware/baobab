<?php
/***************************************************************************************************************************/
/**
    CHAMELEON Object Abstraction Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC.
    
    This code is proprietary and confidential code, 
    It is NOT to be reused or combined into any application,
    unless done so, specifically under written license from Little Green Viper Software Development LLC.

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_DBF_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

require_once(CO_Config::db_class_dir().'/a_co_db_table_base.class.php');
CO_Config::require_extension_class('tco_owner.interface.php');

/***************************************************************************************************************************/
/**
 */
class CO_Owner extends A_CO_DB_Table_Base {
    use tCO_Owner; // These are the built-in owner methods.
    
    /***********************************************************************************************************************/
    /***********************/
    /**
    Constructor (Initializer)
     */
	public function __construct(    $in_db_object = NULL,   ///< The database object for this instance.
	                                $in_db_result = NULL    ///< The database row for this instance (associative array, with database keys).
                                ) {
        parent::__construct($in_db_object, $in_db_result);
        $this->class_description = "This is an 'Owner' Class for general items.";
        $this->children_ids();  ///< Forces a cache load.
    }

    /***********************/
    /**
    This function sets up this instance, according to the DB-formatted associative array passed in.
    
    \returns true, if the instance was able to set itself up to the provided array.
     */
    public function load_from_db(   $in_db_result   ///< This is an associative array, formatted as a database row response.
                                    ) {
        $ret = parent::load_from_db($in_db_result);
        
        $this->class_description = "This is an 'Owner' Class for general items.";
        $this->instance_description = $this->name;
    }
};
