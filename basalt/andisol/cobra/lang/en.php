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
class CO_COBRA_Lang {
    static  $cobra_error_name_invalid_chameleon = 'Invalid CHAMELEON Instance';
    static  $cobra_error_desc_invalid_chameleon = 'COBRA cannot be initialized with the given CHAMELEON instance.';
    static  $cobra_error_name_user_not_authorized = 'Current User Not Authorized';
    static  $cobra_error_desc_user_not_authorized_instance = 'The current user is not authorized to instantiate COBRA.';
    static  $cobra_error_desc_user_not_authorized = 'The current user is not authorized to create user objects.';
    static  $cobra_error_name_instance_failed_to_initialize = 'User Not Initialized';
    static  $cobra_error_desc_instance_failed_to_initialize = 'The user object failed to initialize properly.';
    static  $cobra_error_name_user_already_exists = 'User Already Exists';
    static  $cobra_error_desc_user_already_exists = 'The specified user already exists.';
    static  $cobra_error_name_login_unavailable = 'The Login Is Unavailable';
    static  $cobra_error_desc_login_unavailable = 'The reqested login is unavailable to this user.';
    static  $cobra_error_name_login_error = 'Login Error';
    static  $cobra_error_desc_login_error = 'There was an unspecified error with this login.';
    static  $cobra_error_name_token_instance_failed_to_initialize = 'Token Not Initialized';
    static  $cobra_error_desc_token_instance_failed_to_initialize = 'The security token object failed to initialize properly.';
    static  $cobra_error_name_token_id_not_set = 'Token ID Not Set';
    static  $cobra_error_desc_token_id_not_set = 'The security token object was not created, because the ID could not be set.';
    static  $cobra_error_name_password_too_short = 'Password Too Short';
    static  $cobra_error_desc_password_too_short = 'The password is too short.';
}
?>