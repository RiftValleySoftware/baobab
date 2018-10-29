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

global $g_lang_override;    // This allows us to override the configured language at initiation time.

if (isset($g_lang_override) && $g_lang_override && file_exists(dirname(__FILE__).'/'.$lang.'.php')) {
    $lang = $g_lang_override;
} else {
    $lang = CO_Config::$lang;
}

$lang_file = CO_Config::badger_lang_class_dir().'/'.$lang.'.php';
$lang_common_file = CO_Config::badger_lang_class_dir().'/common.inc.php';

require_once(dirname(__FILE__).'/'.$lang.'.php');
require_once($lang_file);
require_once($lang_common_file);

/***************************************************************************************************************************/
/**
 */
class CO_CHAMELEON_Lang_Common {
    /// These apply to the CO_Place class. Only the first seven tags are used for US location information.
    static  $chameleon_co_place_tag_0 = 'venue';
    static  $chameleon_co_place_tag_1 = 'street_address';
    static  $chameleon_co_place_tag_2 = 'extra_information';
    static  $chameleon_co_place_tag_3 = 'town';
    static  $chameleon_co_place_tag_4 = 'county';
    static  $chameleon_co_place_tag_5 = 'state';
    static  $chameleon_co_place_tag_6 = 'postal_code';
    static  $chameleon_co_place_tag_7 = 'nation';

    static  $co_place_error_code_failed_to_geocode = 1000;
    static  $co_place_error_code_failed_to_lookup = 1001;
    static  $co_collection_error_code_item_not_valid = 1100;
    static  $co_collection_error_code_user_not_authorized = 1101;
    static  $co_owner_error_code_user_not_authorized = 1200;
    static  $co_key_value_error_code_user_not_authorized = 1300;
    static  $co_key_value_error_code_instance_failed_to_initialize = 1301;

    static  $user_error_code_user_not_authorized = 1400;
    static  $user_error_code_invalid_id = 1401;
    static  $user_error_code_invalid_class = 1402;
}
?>