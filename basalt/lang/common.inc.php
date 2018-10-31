<?php
/***************************************************************************************************************************/
/**
    BASALT Extension Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC/The Great Rift Valley Software Company
    
    LICENSE:
    

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
class CO_Basalt_Lang_Common {
    static  $basalt_error_code_user_not_authorized = 3000;
}
?>