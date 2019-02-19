<?php
define('LGV_CONFIG_CATCHER', true);

require_once(dirname(__FILE__).'/config/s_config.class.php');   ///< This is designed to reach down into the config directory.

if (method_exists('CO_Config', 'set_base_dir')) {
    CO_Config::set_base_dir(dirname(__FILE__).'/basalt');
}

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