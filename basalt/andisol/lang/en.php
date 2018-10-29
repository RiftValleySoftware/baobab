<?php
/***************************************************************************************************************************/
/**
    COBRA Security Administration Layer
    
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
class CO_ANDISOL_Lang {
    static  $andisol_error_name_user_not_authorized = 'User Not Authorized';
    static  $andisol_error_desc_user_not_authorized = 'This user is not Authorized to Perform This Operation';
    static  $andisol_error_name_login_instance_failed_to_initialize = 'Login Failed to Initialize';
    static  $andisol_error_desc_login_instance_failed_to_initialize = 'The server was unable to create the requested login resource.';
    static  $andisol_error_name_login_instance_unavailable = 'Login Not Available';
    static  $andisol_error_desc_login_instance_unavailable = 'The requested login item was not available.';
    static  $andisol_error_name_user_instance_unavailable = 'User Not Available';
    static  $andisol_error_desc_user_instance_unavailable = 'The requested user item was not available.';
    static  $andisol_error_name_user_not_deleted = 'User Not Deleted';
    static  $andisol_error_desc_user_not_deleted = 'The user was not deleted by ANDISOL.';
    static  $andisol_error_name_login_not_deleted = 'Login Not Deleted';
    static  $andisol_error_desc_login_not_deleted = 'The login was not deleted by ANDISOL.';
    static  $andisol_error_name_insufficient_location_information = 'Insufficient Location Information';
    static  $andisol_error_desc_insufficient_location_information = 'The location creator needs more infomation to create the location.';
    static  $andisol_error_name_location_failed_to_initialize = 'Location Object Failed to Initialize';
    static  $andisol_error_desc_location_failed_to_initialize = 'The location object was not created.';
    static  $andisol_new_unnamed_user_name_format = 'New User %d';
}
?>