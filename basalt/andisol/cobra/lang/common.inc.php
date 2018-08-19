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

$lang_file = CO_Config::chameleon_lang_class_dir().'/'.$lang.'.php';
$lang_common_file = CO_Config::chameleon_lang_class_dir().'/common.inc.php';

require_once(dirname(__FILE__).'/'.$lang.'.php');
require_once($lang_file);
require_once($lang_common_file);

/***************************************************************************************************************************/
/**
 */
class CO_COBRA_Lang_Common {
    static  $cobra_error_code_user_not_authorized = 600;
    static  $cobra_error_code_instance_failed_to_initialize = 601;
    static  $cobra_error_code_invalid_chameleon = 602;
    static  $cobra_error_code_user_already_exists = 603;
    static  $cobra_error_code_login_unavailable = 604;
    static  $cobra_error_code_login_error = 605;
    static  $cobra_error_code_token_instance_failed_to_initialize = 606;
    static  $cobra_error_code_token_id_not_set = 607;
    static  $cobra_error_code_password_too_short = 608;
}
?>