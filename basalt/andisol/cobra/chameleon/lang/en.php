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
defined( 'LGV_LANG_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.
    
/***************************************************************************************************************************/
/**
 */
class CO_CHAMELEON_Lang {
    static  $co_place_error_name_failed_to_geocode = 'Failed to determine an address from the longitude and latitude';
    static  $co_place_error_desc_failed_to_geocode = 'The Google Maps Geocoding API was unable to determine an address from the given longitude and latitude.';
    
    static  $co_place_error_name_failed_to_lookup = 'Failed to determine a longitude and latitude from the address';
    static  $co_place_error_desc_failed_to_lookup = 'The Google Maps Geocoding API was unable to determine a longitude and latitude from the given address information.';
    
    /// These apply to the *_Collection classes.
    static  $co_collection_error_name_item_not_valid = 'The Item Is not a Valid Database Item';
    static  $co_collection_error_desc_item_not_valid = 'The item with the given ID cannot be found in the database.';
    static  $co_collection_error_name_user_not_authorized = 'User Not Authorized';
    static  $co_collection_error_desc_user_not_authorized = 'The user is not authorized to modify this collection.';

    /// These apply to the Owner classes.
    static  $co_owner_error_name_user_not_authorized = 'User Not Authorized';
    static  $co_owner_error_desc_user_not_authorized = 'The user is not authorized to modify this value.';

    /// These apply to the KeyValue classes.
    static  $co_key_value_error_name_user_not_authorized = 'User Not Authorized';
    static  $co_key_value_error_desc_user_not_authorized = 'The user is not authorized to modify this value.';
    static  $co_key_value_error_name_instance_failed_to_initialize = 'Value Not Initialized';
    static  $co_key_value_error_desc_instance_failed_to_initialize = 'The value object failed to initialize properly.';

    // These apply to the user collection classes.
    static  $user_error_name_user_not_authorized = 'User Not Authorized';
    static  $user_error_desc_user_not_authorized = 'The user is not authorized to access the login for this user.';
    static  $user_error_name_invalid_id = 'User ID Invalid';
    static  $user_error_desc_invalid_id = 'The user ID in this instance is invalid.';
    static  $user_error_name_invalid_class = 'User Login Class Invalid';
    static  $user_error_desc_invalid_class = 'The login access class for this user is invalid.';
}
?>