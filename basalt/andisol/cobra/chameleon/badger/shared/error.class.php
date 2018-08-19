<?php
/***************************************************************************************************************************/
/**
    Badger Hardened Baseline Database Component
    
    © Copyright 2018, Little Green Viper Software Development LLC.
    
    This code is proprietary and confidential code, 
    It is NOT to be reused or combined into any application,
    unless done so, specifically under written license from Little Green Viper Software Development LLC.

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_ERROR_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

/***************************************************************************************************************************/
/**
    \brief This class provides a general error report, with file, method and error information.
 */
class LGV_Error {
    var $error_code;
    var $error_name;
    var $error_description;
    var $error_file;
    var $error_line;
    var $error_detailed_description;

    /***********************************************************************************************************************/
    /***********************/
    /**
     */
	public function __construct(
                                $error_code = 0,
                                $error_name = NULL,
                                $error_description = NULL,
                                $error_file = NULL,
                                $error_line = NULL,
                                $error_detailed_description = NULL
	                            ) {
	    $this->error_code = $error_code;
	    $this->error_name = $error_name;
	    $this->error_description = $error_description;
	    $this->error_file = $error_file;
	    $this->error_line = $error_line;
	    $this->error_detailed_description = $error_detailed_description;
	}
};
