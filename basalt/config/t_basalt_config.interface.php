<?php
/***************************************************************************************************************************/
/**
    BASALT Extension Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC.
    
    This code is proprietary and confidential code, 
    It is NOT to be reused or combined into any application,
    unless done so, specifically under written license from Little Green Viper Software Development LLC.

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_CONFIG_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

/***************************************************************************************************************************/
/**
This file contains the implementation-dependent configuration settings.
 */
require_once(dirname(dirname(__FILE__)).'/andisol/cobra/chameleon/badger/install-assets/t_config.interface.php');

trait tCO_Basalt_Config {
    use tCO_Config; // These are the built-in config methods.
    /***********************/
    /**
    This is a special callback for validating REST logins (BASALT). For most functions in the global scope, this will simply be the function name,
    or as an array (with element 0 being the object, itself, and element 1 being the name of the function).
    If this will be an object method, then it should be an array, with element 0 as the object, and element 1 a string, containing the function name.
    The function signature will be:
        function login_validation_callback (    $in_login_id,  ///< REQUIRED: The login ID provided.
                                                $in_password,   ///< REQUIRED: The password (in cleartext), provided.
                                                $in_server_vars ///< REQUIRED: The $_SERVER array, at the time of the call.
                                            );
    The function will return a boolean, true, if the login is allowed to proceed normally, and false, if the login is to be aborted.
    If false is returned, the REST login will terminate with a 403 Forbidden response.
    It should be noted that there may be security, legal, ethical and resource ramifications for logging.
    It is up to the implementor to ensure compliance with all constraints.
    */
    static function call_login_validator_function(  $in_login_id,   ///< REQUIRED: The login ID provided.
                                                    $in_password,   ///< REQUIRED: The password (in cleartext), provided.
                                                    $in_server_vars ///< REQUIRED: The $_SERVER array, at the time of the call.
                                                ) {
        $login_validator = self::$_login_validation_callback;
        
        if (isset($login_validator) && is_array($login_validator) && (1 < count($login_validator)) && is_object($login_validator[0]) && method_exists($login_validator[0], $login_validator[1])) {
            return $login_validator[0]->$login_validator[1]($in_login_id, $in_password, $in_server_vars);
        } elseif (isset($log_handler) && function_exists($log_handler)) {
            return $login_validator($in_login_id, $in_password, $in_server_vars);
        }
        
        return true;
    }
    
    /***********************/
    /**
    This is a special "callback caller" for logging REST calls (BASALT). The callback must be defined in the CO_Config::$_log_handler_function static variable,
    either as a function (global scope), or as an array (with element 0 being the object, itself, and element 1 being the name of the function).
    For most functions in the global scope, this will simply be the function name.
    If this will be an object method, then it should be an array, with element 0 as the object, and element 1 a string, containing the function name.
    The function signature will be:
        function log_callback ( $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance at the time of the call.
                                $in_server_vars         ///< REQUIRED: The $_SERVER array, at the time of the call.
                                );
    There is no function return.
    The function will take care of logging the REST connection in whatever fashion the user desires.
    This will assume a successful ANDISOL instantiation, and is not designed to replace the traditional server logs.
    It should be noted that there may be legal, ethical and resource ramifications for logging.
    It is up to the implementor to ensure compliance with all constraints.
    */
    static function call_log_handler_function(  $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance at the time of the call.
                                                $in_server_vars         ///< REQUIRED: The $_SERVER array, at the time of the call.
                                                ) {   
        $log_handler = isset(self::$_log_handler_function) ? self::$_log_handler_function : NULL;
        
        if (isset($log_handler) && is_array($log_handler) && (1 < count($log_handler)) && is_object($log_handler[0]) && method_exists($log_handler[0], $log_handler[1])) {
            $log_handler[0]->$log_handler[1]($in_andisol_instance, $in_server_vars);
        } elseif (isset($log_handler) && function_exists($log_handler)) {
            $log_handler($in_andisol_instance, $in_server_vars);
        }
    }
    
    /***********************/
    /**
    \returns an array of strings, with each being the absolute POSIX path to a plugin directory.
     */
    static function plugin_dirs() {
        $baseline_plugins = dirname(dirname(__FILE__)).'/plugins';  // We at least have the built-in plugins.
        
        $ret = Array();
        
        // First, scan the baseline directory.
        // Iterate through that directory, and get each plugin directory.
        foreach (new DirectoryIterator($baseline_plugins) as $fileInfo) {
            if (!$fileInfo->isDot() && $fileInfo->isDir()) {
                $ret[] = strtolower(trim($fileInfo->getPathname()));
            }
        }
        
        // Next, see if we have any user-defined plugin directories.
        $plugin_dir = CO_Config::extension_dir();
        
        if (isset($plugin_dir) && is_dir($plugin_dir)) {
            // Iterate through that directory, and get each plugin directory.
            foreach (new DirectoryIterator($plugin_dir) as $fileInfo) {
                if (!$fileInfo->isDot() && $fileInfo->isDir()) {
                    $ret[] = strtolower(trim($fileInfo->getPathname()));
                }
            }
        }
        
        sort($ret);
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of strings, with each being the absolute POSIX path to a plugin directory.
     */
    static function plugin_names() {
        $plugin_dirs = self::plugin_dirs();
        
        return array_map(function($i){ return strtolower(trim(basename($i))); }, $plugin_dirs);
    }
    
    /***********************/
    /**
    \returns either the POSIX path to the ANDISOL Extension dir, or an array, which will include any extensions defined by plugins.
     */
    static function db_classes_extension_class_dir() {
        $ret = self::andisol_db_classes_extension_class_dir();
        
        $plugin_dirs = self::plugin_dirs();
        
        // Next, we check our plugin directories for extension classes.
        if (isset($plugin_dirs) && is_array($plugin_dirs) && count($plugin_dirs)) {
            $temp_array = is_array($ret) ? $ret : Array($ret);  // Start with whatever we already have.
            
            foreach ($plugin_dirs as $dir) {
                $extension_dir = "$dir/badger_extension_classes";
                
                if (file_exists($extension_dir) && is_dir($extension_dir)) {
                    $temp_array[] = $extension_dir;
                }
            }
            
            // If we only have one, then we don't need to return an array.
            if (1 == count($temp_array)) {
                $ret = $temp_array[0];
            } else {
                $ret = $temp_array;
            }
        }
        
        return $ret;
    }
}
