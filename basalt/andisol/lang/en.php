<?php
/***************************************************************************************************************************/
/**
    COBRA Security Administration Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC.
    
    This code is proprietary and confidential code, 
    It is NOT to be reused or combined into any application,
    unless done so, specifically under written license from Little Green Viper Software Development LLC.

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