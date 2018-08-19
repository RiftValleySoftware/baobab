<?php
define('LGV_CONFIG_CATCHER', true);
require_once (dirname(dirname(dirname(__FILE__))).'/baobab_config/s_config.class.php');
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
?>