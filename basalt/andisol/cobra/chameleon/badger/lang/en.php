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
defined( 'LGV_LANG_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.
    
/***************************************************************************************************************************/
/**
 */
class CO_Lang {
    static  $pdo_error_name_failed_to_open_data_db = 'Failed to open the data storage database.';
    static  $pdo_error_desc_failed_to_open_data_db = 'There was an error while trying to access the main data storage database.';

    static  $pdo_error_name_failed_to_open_security_db = 'Failed to open the security database.';
    static  $pdo_error_desc_failed_to_open_security_db = 'There was an error while trying to access the security database.';

    static  $pdo_error_name_invalid_login = 'Invalid Login.';
    static  $pdo_error_desc_invalid_login = 'The login or password provided was not valid.';
    
    static  $pdo_error_name_illegal_write_attempt = 'Illegal Database Write Attempt.';
    static  $pdo_error_desc_illegal_write_attempt = 'There was an attempt to write to a record for which the user does not have write permission.';
    
    static  $pdo_error_name_illegal_delete_attempt = 'Illegal Database delete Attempt.';
    static  $pdo_error_desc_illegal_delete_attempt = 'There was an attempt to delete a record for which the user does not have write permission.';
    
    static  $pdo_error_name_failed_delete_attempt = 'Failed Database delete Attempt.';
    static  $pdo_error_desc_failed_delete_attempt = 'There was a failure during an attempt to delete a record.';

    static  $db_error_name_class_file_not_found = 'Class file was not found.';
    static  $db_error_desc_class_file_not_found = 'The file for the class being instantiated was not found.';
    static  $db_error_name_class_not_created = 'Class was not created.';
    static  $db_error_desc_class_not_created = 'The attempt to instantiate the class failed.';
    
    static  $db_error_name_user_not_authorized = 'User Not Authorized';
    static  $db_error_desc_user_not_authorized = 'The user is not authorized to perform the requested operation.';
    
    static  $access_error_name_user_not_authorized = 'User Not Authorized';
    static  $access_error_desc_user_not_authorized = 'The user is not authorized to perform the requested operation.';
    static  $access_error_name_class_file_not_found = 'Class file was not found.';
    static  $access_error_desc_class_file_not_found = 'The file for the class being instantiated was not found.';
    static  $access_error_name_class_not_created = 'Class was not created.';
    static  $access_error_desc_class_not_created = 'The attempt to instantiate the class failed.';
    
    static  $login_error_name_api_key_invalid = 'API Key Invalid';
    static  $login_error_desc_api_key_invalid = 'The API key is either invalid, or has expired. You need to log in again, and acquire a new API key.';
    static  $login_error_name_api_key_mismatch = 'API Key Mismatch';
    static  $login_error_desc_api_key_mismatch = 'The API key does not match the API key for this instance.';
    
    static  $login_error_name_attempt_to_delete_god = 'Attempt To Delete \'God\' Login';
    static  $login_error_desc_attempt_to_delete_god = 'You cannot delete the \'God\' login!';
}
?>