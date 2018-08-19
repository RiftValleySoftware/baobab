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

CO_Config::require_extension_class('tco_collection.interface.php');
CO_Config::require_extension_class('co_place.class.php');

/***************************************************************************************************************************/
/**
This is a specialization of the US Place class. It acts as a collection, so it's a "place of places."
 */
class CO_Place_Collection extends CO_Place {
    use tCO_Collection; // These are the built-in collection methods.
    
    /***********************************************************************************************************************/
    /***********************/
    /**
    Constructor (Initializer)
     */
	public function __construct(    $in_db_object = NULL,   ///< The database object for this instance.
	                                $in_db_result = NULL,   ///< The database row for this instance (associative array, with database keys).
	                                $in_owner_id = NULL,    ///< The ID of the object (in the database) that "owns" this instance.
                                    $in_tags_array = NULL,  /**< An array of up to 10 strings, with address information in the first 8. Order is important:
                                                                - 0: Venue
                                                                - 1: Street Address
                                                                - 2: Extra Information
                                                                - 3: Town
                                                                - 4: County
                                                                - 5: State
                                                                - 6: ZIP Code
                                                                - 7: Nation
                                                              
                                                                Associative keys are not used. The array should be in that exact order.
	                                                        */
	                                $in_longitude = NULL,   ///< An initial longitude value.
	                                $in_latitude = NULL     ///< An initial latitude value.
                                ) {
        
        $this->_container = Array();

        parent::__construct($in_db_object, $in_db_result, $in_owner_id, $in_tags_array, $in_longitude, $in_latitude);
        $this->class_description = "This is a 'Place Collection' Class for Addresses.";
        
        $count = 0;
        if (isset($this->context['children_ids']) && is_array($this->context['children_ids'])) {
            $count = count($this->context['children_ids']);
        }
        
        $this->instance_description = isset($this->name) && $this->name ? "$this->name ($this->_longitude, $this->_latitude - $count children objects)" : "($this->_longitude, $this->_latitude - $count children objects)";
    }

    /***********************/
    /**
    This function sets up this instance, according to the DB-formatted associative array passed in.
    
    \returns true, if the instance was able to set itself up to the provided array.
     */
    public function load_from_db(   $in_db_result   ///< This is an associative array, formatted as a database row response.
                                    ) {
        $ret = parent::load_from_db($in_db_result);
        
        $count = 0;
        if (isset($this->context['children_ids']) && is_array($this->context['children_ids'])) {
            $count = count($this->context['children_ids']);
        }
        
        $this->class_description = "This is a 'Place Collection' Class for Addresses.";
        $this->instance_description = isset($this->name) && $this->name ? "$this->name ($this->_longitude, $this->_latitude - $count children objects)" : "($this->_longitude, $this->_latitude - $count children objects)";
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
