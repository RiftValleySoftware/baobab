<?php
/***************************************************************************************************************************/
/**
    CHAMELEON Object Abstraction Layer
    
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

require_once(CO_Config::db_class_dir().'/co_main_db_record.class.php');
CO_Config::require_extension_class('tco_collection.interface.php');

/***************************************************************************************************************************/
/**
This is a generic collection class.
 */
class CO_Collection extends CO_LL_Location {
    use tCO_Collection; // These are the built-in collection methods.
    
    /***********************************************************************************************************************/
    /***********************/
    /**
    Constructor (Initializer)
     */
	public function __construct(    $in_db_object = NULL,           ///< The database object for this instance.
	                                $in_db_result = NULL,           ///< The database row for this instance (associative array, with database keys).
	                                $in_owner_id = NULL,            ///< The ID of the object (in the database) that "owns" this instance.
                                    $in_tags_array = NULL           ///< The tags to be assigned to this object.
                                ) {
        
        $this->_container = Array();

        parent::__construct($in_db_object, $in_db_result, $in_owner_id, $in_tags_array);
        $this->class_description = "This is a 'Generic Collection' Class.";
    }

    /***********************/
    /**
    This function sets up this instance, according to the DB-formatted associative array passed in.
    
    \returns true, if the instance was able to set itself up to the provided array.
     */
    public function load_from_db(   $in_db_result   ///< This is an associative array, formatted as a database row response.
                                    ) {
        $ret = parent::load_from_db($in_db_result);
        
        if ($ret) {
            $count = 0;
            if (isset($this->context['children_ids']) && is_array($this->context['children_ids'])) {
                $count = count($this->context['children_ids']);
            }
        
            $this->class_description = "This is a 'Generic Collection' Class.";
            $this->instance_description = isset($this->name) && $this->name ? "$this->name" : "Generic Collection Object";
        }
    
        return $ret;
    }
    
    /***********************/
    /**
    We override this, because we want to see if we can possibly delete children objects.  
    \returns true, if the deletion was successful.
     */
    public function delete_from_db( $with_extreme_prejudice = false ///< If true (Default is false), then we will attempt to delete all contained children. Remember that this could cause problems if other collections can see the children!
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
        
        return parent::delete_from_db();
    }
};
