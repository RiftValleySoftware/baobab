<?php
/***************************************************************************************************************************/
/**
    BASALT Extension Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC/The Great Rift Valley Software Company
    
    LICENSE:
    

    Little Green Viper Software Development: https://littlegreenviper.com
*/
// This won't work unless the config file is active.
if (class_exists('CO_Config')) {
    if ( !defined('LGV_BASALT_CATCHER') ) {
        define('LGV_BASALT_CATCHER', 1);
    }
    
    // Include the BASALT class.
    require_once(CO_Config::main_class_dir().'/co_basalt.class.php');
    
    run_basalt();
}

function run_basalt() {
    $basalt_instance = new CO_Basalt();
}