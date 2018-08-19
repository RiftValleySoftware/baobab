<?php
/***************************************************************************************************************************/
/**
    CHAMELEON Object Abstraction Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC.
    
    This code is proprietary and confidential code, 
    It is NOT to be reused or combined into any application,
    unless done so, specifically under written license from Little Green Viper Software Development LLC.

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_DBF_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

require_once(dirname(__FILE__).'/co_collection.class.php');

/***************************************************************************************************************************/
/**
    This is a simple key/value class. It uses the Tag 0 object as a "key" (So the key needs to be a string), and the payload
    to store the value (so the value can be fairly big).
    
    There can only be one.
    
    The uniqueness is determined by a combination of the access_class and tag0 fields. That means that a subclass of this class
    could have the same key as a parent, but two instances of the same class cannot have duplicate keys.
 */
class CO_KeyValue_CO_Collection extends CO_Collection {
    
    /***********************************************************************************************************************/
    /***********************/
    /**
    Constructor (Initializer)
     */
	public function __construct(    $in_db_object = NULL,   ///< The database object for this instance.
	                                $in_db_result = NULL,   ///< The database row for this instance (associative array, with database keys).
	                                $in_key = NULL,         ///< The key to be used for this instance (overrides anything in the DB record).
	                                $in_value = NULL        ///< The value to be used for this instance (overrides anything in the DB record).
                                ) {
        parent::__construct($in_db_object, $in_db_result);
        $this->class_description = "This is a class for doing \"key/value\" storage.";
        
        if (NULL != $in_key) {
            $this->set_key($in_key, $value);
        } elseif (NULL != $in_value) {
            $this->set_value($in_key, $value);
        }
    }

    /***********************/
    /**
    This function sets up this instance, according to the DB-formatted associative array passed in.
    
    \returns true, if the instance was able to set itself up to the provided array.
     */
    public function load_from_db(   $in_db_result   ///< This is an associative array, formatted as a database row response.
                                    ) {
        $ret = parent::load_from_db($in_db_result);
        
        if ($ret) {
            $this->class_description = "This is a class for doing \"key/value\" storage.";
            $this->instance_description = $this->name;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This method will set the key for this instance.
    It checks the database, as the key needs to be unique for the access class and tag 0.
    
    \returns true, if the key was successfully set and updated in the database.
     */
    function set_key(   $in_key,            ///< The key to be applied to this object.
                        $in_value = NULL    ///< We can associate a value.
                    ) {
        $ret = false;
        
        if ($this->user_can_write()) { //< Must have write access
            // We need to make sure that the key doesn't already exist, or if it does, that the object associated with it is us. Must be writeable.
            $instance_list = $this->get_access_object()->generic_search(Array('access_class' => get_class($this), 'tags' => Array($in_key)));
        
            if (!isset($instance_list) || !is_array($instance_list) || !count($instance_list) || (1 == count($instance_list) && ($instance_list[0] === $this))) {
                // If there is no change, then we don't do anything, and report a success.
                if (isset($instance_list) && is_array($instance_list) && (1 == count($instance_list) && ($instance_list[0] === $this))) {
                    $ret = true;
                } else {
                    $ret = $this->set_tag(0, $in_key);   // We assume that our claim to be writeable is true. If not, this will fail. Remember we are untrusting bastards.
                }
                
                if ($ret && (NULL != $in_value)) {
                    return $this->set_value($in_value);
                }
            }
        } else {
            $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_key_value_error_code_user_not_authorized,
                                            CO_CHAMELEON_Lang::$co_key_value_error_name_user_not_authorized,
                                            CO_CHAMELEON_Lang::$co_key_value_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns the instance key.
     */
    function get_key() {
        return $this->tags[0];
    }
    
    /***********************/
    /**
    \returns the payload for this instance.
     */
    function get_value() {
        return $this->get_payload();
    }
    
    /***********************/
    /**
    \returns the instance key and value, in a 2-element indexed array, with element 0 being the key, and element 1 being the value.
     */
    function get_key_value_array() {
        return Array($this->get_key(), $this->get_value());
    }
    
    /***********************/
    /**
    \returns the instance key and value, in a 1-element associative array, with the key being the key, and the value being the value.
     */
    function get_key_value_assoc() {
        return Array($this->get_key() => $this->get_value());
    }
    
    /***********************/
    /**
    \returns true, if the value was successfully set and updated in the database.
     */
    function set_value( $in_value
                        ) {
        $ret = false;
        
        if ($this->user_can_write()) { //< Must have write access
            $ret = $this->set_payload($in_value);
        } else {
            $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_key_value_error_code_user_not_authorized,
                                            CO_CHAMELEON_Lang::$co_key_value_error_name_user_not_authorized,
                                            CO_CHAMELEON_Lang::$co_key_value_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
};
