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

global $g_lang_override;    // This allows us to override the configured language at initiation time.

if (isset($g_lang_override) && $g_lang_override && file_exists(dirname(__FILE__).'/'.$lang.'.php')) {
    $lang = $g_lang_override;
} else {
    $lang = CO_Config::$lang;
}

$lang_common_file = CO_Config::cobra_lang_class_dir().'/common.inc.php';

require_once(dirname(__FILE__).'/'.$lang.'.php');
require_once($lang_file);
require_once($lang_common_file);
    
/***************************************************************************************************************************/
/**
 */
class CO_ANDISOL_Lang_Common {
    static  $andisol_error_code_user_not_authorized = 2000;
    static  $andisol_error_code_login_instance_failed_to_initialize = 2001;
    static  $andisol_error_code_login_instance_unavailable = 2002;
    static  $andisol_error_code_user_instance_unavailable = 2003;
    static  $andisol_error_code_user_not_deleted = 2004;
    static  $andisol_error_code_login_not_deleted = 2005;
    static  $andisol_error_code_insufficient_location_information = 2006;
    static  $andisol_error_code_location_failed_to_initialize = 2007;
}
?>