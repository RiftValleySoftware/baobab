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

CO_Config::require_extension_class('co_place.class.php');
	
/***************************************************************************************************************************/
/**
This is a specialization of the location class. It adds support for US addresses, and uses the first seven tags for this.
 */
class CO_US_Place extends CO_Place {
    /***********************************************************************************************************************/
    /***********************/
    /**
    This fetches string labels to be used as keys for the fixed tags.
    
    \returns an array of strings, which will correspond to the first six tags.
     */
	protected function _get_address_element_labels() {
	    return Array(
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_0,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_1,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_2,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_3,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_4,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_5,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_6
                    );
	}
    
    /***********************************************************************************************************************/
    /***********************/
    /**
    Constructor (Initializer)
     */
	public function __construct(    $in_db_object = NULL,   ///< The database object for this instance.
	                                $in_db_result = NULL,   ///< The database row for this instance (associative array, with database keys).
	                                $in_owner_id = NULL,    ///< The ID of the object (in the database) that "owns" this instance.
                                    $in_tags_array = NULL,  /**< An array of up to 10 strings, with address information in the first 7. Order is important:
                                                                - 0: Venue
                                                                - 1: Street Address
                                                                - 2: Extra Information
                                                                - 3: Town
                                                                - 4: County
                                                                - 5: State
                                                                - 6: ZIP Code
                                                              
                                                                Associative keys are not used. The array should be in that exact order.
	                                                        */
	                                $in_longitude = NULL,   ///< An initial longitude value.
	                                $in_latitude = NULL     ///< An initial latitude value.
                                ) {
        
        $this->region_bias = 'us';

        parent::__construct($in_db_object, $in_db_result, $in_owner_id, $in_tags_array, $in_longitude, $in_latitude);
        
        $this->class_description = "This is a 'Place' Class for US Addresses.";
    }
    
    /***********************/
    /**
    \returns the address elements, in an associative array.
     */
	public function get_address_elements() {
	    $ret = parent::get_address_elements();
        $ret[CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_7] = 'USA';   // Fixed, for USA.
        
        return $ret;
	}
};
