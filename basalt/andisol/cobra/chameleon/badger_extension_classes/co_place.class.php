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
defined( 'LGV_DBF_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

require_once(CO_Config::db_classes_class_dir().'/co_ll_location.class.php');

if ( !defined('LGV_CHAMELEON_UTILS_CATCHER') ) {
    define('LGV_CHAMELEON_UTILS_CATCHER', 1);
}

$utils_file = CO_Config::chameleon_main_class_dir().'/co_chameleon_utils.class.php';
require_once($utils_file);

function test_is_assoc(array $arr) {
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

/***************************************************************************************************************************/
/**
This is a specialization of the location class. It adds support for US addresses, and uses the first eight tags for this.
 */
class CO_Place extends CO_LL_Location {
    var $address_elements = Array();        ///< These are the address elements we use for creating lookup addresses.
    var $google_geocode_uri_prefix = NULL;  ///< This is the Geocode URI for the Google Geocode.
    var $google_lookup_uri_prefix = NULL;   ///< This is the lookup URI for the Google reverse Geocode.
    var $region_bias = NULL;                ///< This can be set by subclasses in order to set a region bias.
    
    /***********************************************************************************************************************/
    /***********************/
    /**
    This fetches string labels to be used as keys for the fixed tags.
    
    \returns an array of strings, which will correspond to the first eight tags.
     */
	protected function _get_address_element_labels() {
	    return Array(
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_0,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_1,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_2,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_3,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_4,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_5,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_6,
                        CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_7
                    );
	}
	
    /***********************************************************************************************************************/
    /***********************/
    /**
    Constructor (Initializer)
     */
	public function __construct(    $in_db_object = NULL,   ///< The database object for this instance.
	                                $in_db_result = NULL,   ///< The database row for this instance (associative array, with database keys).
	                                $in_owner_id = NULL,    ///< The ID of the object (in the database) that "owns" this instance.
                                    $in_tags_array = NULL,  /**< An array of up to 10 strings, with address information in the first 8. Order is important:
                                                                - 0: Venue
                                                                - 1: Street Address
                                                                - 2: Extra Information
                                                                - 3: Town
                                                                - 4: County
                                                                - 5: State
                                                                - 6: ZIP Code
                                                                - 7: Nation
                                                              
                                                                Associative keys are not used. The array should be in that exact order.
	                                                        */
	                                $in_longitude = NULL,   ///< An initial longitude value.
	                                $in_latitude = NULL     ///< An initial latitude value.
                                ) {
        
        parent::__construct($in_db_object, $in_db_result, $in_owner_id, $in_tags_array, $in_longitude, $in_latitude);
        
        $this->class_description = "This is a 'Place' Class for General Addresses.";
        $this->instance_description = isset($this->name) && $this->name ? "$this->name ($this->_longitude, $this->_latitude)" : "($this->_longitude, $this->_latitude)";
        
        $this->set_address_elements($this->tags(), true);
        
        $bias = (NULL != $this->region_bias) ? 'region='.$this->region_bias.'&' : '';
        
        $this->google_geocode_uri_prefix = 'https://maps.googleapis.com/maps/api/geocode/json?'.$bias.'key='.CO_Config::$google_api_key.'&address=';
        $this->google_lookup_uri_prefix = 'https://maps.googleapis.com/maps/api/geocode/json?'.$bias.'key='.CO_Config::$google_api_key.'&latlng=';
    }
    
    /***********************/
    /**
    This sets the address_elements property, as per the provided array of strings. This can also update the tags.
    
    \returns true, if $dont_save was false, and the tags were successfully saved.
     */
	public function set_address_elements (  $in_tags,   /**< An array of up to 8 strings, with the new address information. Order is important:
                                                            - 0: Venue
                                                            - 1: Street Address
                                                            - 2: Extra Information
                                                            - 3: Town
                                                            - 4: County
                                                            - 5: State
                                                            - 6: ZIP Code
                                                            - 7: Nation
                                                              
                                                            Associative keys are not used. The array should be in that exact order.
	                                                    */
	                                        $dont_save = false  ///< If true, then the DB update will not be called.
                                ) {
        $ret = false;
        
        $this->address_elements = Array();
        $labels = $this->_get_address_element_labels();
        
        for ($i = 0; $i < count($labels); $i++) {
            $tag_value = '';
            
            if (test_is_assoc($in_tags)) {
                $tag_value = isset($in_tags[$labels[$i]]) ? $in_tags[$labels[$i]] : '';
            } else {
                $tag_value = isset($in_tags[$i]) ? $in_tags[$i] : '';
            }
            
            $this->set_address_element($i, $tag_value, true);
        }
        
        if (!$dont_save) {
            $ret = $this->update_db();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the indexed address_element property, as per the provided string. This can also update the tag.
    
    \returns true, if $dont_save was false, and the tags were successfully saved.
     */
    public function set_address_element(    $in_index,          ///< The 0-based index of the value to set.
                                            $in_value,          ///< The value to set to the address element string.
	                                        $dont_save = false  ///< If true, then the DB update will not be called.
	                                    ) {
	    $ret = false;
        
        $in_index = intval($in_index);
        $labels = $this->_get_address_element_labels();
        
        if ((0 <= $in_index) && ($in_index < count($labels))) {
            $key = $labels[$in_index];

            $in_value = strval($in_value);
        
            $this->address_elements[$key] = $in_value;
            $this->_tags[$in_index] = $in_value;
            
            if (!$dont_save) {
                $ret = $this->update_db();
            }
        }
	    
	    return $ret;
	}
    
    /***********************/
    /**
    This sets the address_element property, as per the provided string, and indexed by the associative key. This can also update the tag.
    
    \returns true, if $dont_save was false, and the tags were successfully saved.
     */
    public function set_address_element_by_key( $in_key,            ///< The string, with the element key.
                                                $in_value,          ///< The value to set to the address element string.
	                                            $dont_save = false  ///< If true, then the DB update will not be called.
	                                            ) {
	    $ret = false;
        
        $in_index = intval($in_index);
        $labels = $this->_get_address_element_labels();
        
        for ($i = 0; $i < count($labels); $i++) {
            if ($labels[$i] == $in_key) {
                $ret = $this->set_address_element($i, $in_value, $dont_save);
                break;
            }
        }
	    
	    return $ret;
	}
	
    /***********************/
    /**
    \returns the indexed address element, or NULL.
     */
	public function get_address_element_by_index(   $in_index   ///< The 0-based index we're looking for.
	                                            ) {
	    $ret = NULL;
	    
        $labels = $this->_get_address_element_labels();
        
        if ((0 <= $in_index) && ($in_index < count($labels))) {
            $key = $labels[$in_index];
            $ret = $this->address_elements[$key];
        }
	    
	    return $ret;
	}
	
    /***********************/
    /**
    This will do a reverse geocode, using the Google Geocode API, of the object's address, and will return the long/lat (in an associative array).
    This requires that CO_Config::$google_api_key be set to a valid API key with the Google Geocode service enabled.
    
    \returns 
     */
    public function lookup_address() {
        $uri = $this->google_geocode_uri_prefix.urlencode($this->get_readable_address(false));
        $http_status = '';
        $error_catcher = '';
        
        $resulting_json = json_decode(CO_Chameleon_Utils::call_curl($uri, false, $http_status, $error_catcher));
        if (isset($resulting_json) && $resulting_json &&isset($resulting_json->results) && is_array($resulting_json->results) && count($resulting_json->results)) {
            if (isset($resulting_json->results[0]->geometry) && isset($resulting_json->results[0]->geometry->location) && isset($resulting_json->results[0]->geometry->location->lng) && isset($resulting_json->results[0]->geometry->location->lat)) {
                return Array( 'longitude' => floatval($resulting_json->results[0]->geometry->location->lng), 'latitude' => floatval($resulting_json->results[0]->geometry->location->lat));
            }
        }
            
        $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_place_error_code_failed_to_lookup,
                                        CO_CHAMELEON_Lang::$co_place_error_name_failed_to_lookup,
                                        CO_CHAMELEON_Lang::$co_place_error_desc_failed_to_lookup);

        return NULL;
    }
	
    /***********************/
    /**
    This will do a geocode, using the Google Geocode API, of the object's long/lat, and will return the address (in an associative array).
    This requires that CO_Config::$google_api_key be set to a valid API key with the Google Geocode service enabled.
    
    \returns 
     */
    public function geocode_long_lat() {
        $uri = $this->google_lookup_uri_prefix . urlencode($this->raw_latitude()) . ',' . urlencode($this->raw_longitude());
        $http_status = '';
        $error_catcher = '';

        $resulting_json = json_decode(CO_Chameleon_Utils::call_curl($uri, false, $http_status, $error_catcher));
        if (isset($resulting_json) && $resulting_json &&isset($resulting_json->results) && is_array($resulting_json->results) && count($resulting_json->results)) {
            if (isset($resulting_json->results[0]->address_components) && is_array($resulting_json->results[0]->address_components) && count($resulting_json->results[0]->address_components)) {
                $address_components = $resulting_json->results[0]->address_components;
                
                $labels = $this->_get_address_element_labels();
                $ret = Array($labels[0] => '', $labels[1] => '', $labels[3] => '', $labels[4] => '', $labels[5] => '', $labels[6] => '');
                
                if (isset($labels[7])) {
                    $ret[$labels[7]] = '';
                }
                
                foreach ($address_components as $component) {
                    $int_key = $component->types[0];
                    
                    switch ($int_key) {
                        case 'premise':
                            if ($ret[$labels[0]]) {
                                $ret[$labels[0]] = ' '.$ret[$labels[0]];
                            }
                            $ret[$labels[0]] = strval($component->long_name).$ret[$labels[0]];
                        break;
                        case 'street_number':
                            if ($ret[$labels[1]]) {
                                $ret[$labels[1]] = ' '.$ret[$labels[1]];
                            }
                            $ret[$labels[1]] = strval($component->short_name).$ret[$labels[1]];
                        break;
                        case 'route':
                            if ($ret[$labels[1]]) {
                                $ret[$labels[1]] .= ' ';
                            }
                            $ret[$labels[1]] .= strval($component->long_name);
                        break;
                        case 'locality':
                            $ret[$labels[3]] = strval($component->long_name);
                        break;
                        case 'administrative_area_level_1':
                            $ret[$labels[5]] = strval($component->short_name);
                        break;
                        case 'administrative_area_level_2':
                            $ret[$labels[4]] = strval($component->short_name);
                        break;
                        case 'postal_code':
                            if ($ret[$labels[6]]) {
                                $ret[$labels[6]] = '-'.$ret[$labels[6]];
                            }
                            $ret[$labels[6]] = strval($component->short_name).$ret[$labels[6]];
                        break;
                        case 'postal_code_suffix':
                            if ($ret[$labels[6]]) {
                                $ret[$labels[6]] .= '-';
                            }
                            $ret[$labels[6]] .= strval($component->short_name);
                        break;
                        case 'country':
                            if (isset($labels[7])) {
                                $ret[$labels[7]] = strval($component->short_name);
                            }
                        break;
                    }
                }
                
                return $ret;
            }
        }
            
        $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_place_error_code_failed_to_geocode,
                                        CO_CHAMELEON_Lang::$co_place_error_name_failed_to_geocode,
                                        CO_CHAMELEON_Lang::$co_place_error_desc_failed_to_geocode);

        return NULL;
    }
    
    /***********************/
    /**
    \returns the address, in a "readable" format.
     */
    public function get_readable_address(   $with_venue = true  ///< If false, then only the street address/town/state/nation will be displayed. That makes this better for geocoding. Default is true.
                                        ) {
        $ret = '';
        
        $tag_key_array = $this->_get_address_element_labels();
        
        if (isset($tag_key_array) && is_array($tag_key_array) && count($tag_key_array)) {
            if ($with_venue && isset($tag_key_array[0]) && isset($this->address_elements[$tag_key_array[0]])) {
                $ret = $this->address_elements[$tag_key_array[0]];
            }
        
            if ($with_venue && isset($tag_key_array[2]) && isset($this->address_elements[$tag_key_array[2]]) && $this->address_elements[$tag_key_array[2]]) {
                $open_paren = false;
            
                if ($ret) {
                    $ret .= ' (';
                    $open_paren = true;
                }
            
                $ret .= $this->address_elements[$tag_key_array[2]];
            
                if ($open_paren) {
                    $ret .= ')';
                }
            }
        
            if (isset($tag_key_array[1]) && isset($this->address_elements[$tag_key_array[1]]) && $this->address_elements[$tag_key_array[1]]) {
                if ($ret) {
                    $ret .= ', ';
                }
            
                $ret .= $this->address_elements[$tag_key_array[1]];
            }
        
            if (isset($tag_key_array[3]) && isset($this->address_elements[$tag_key_array[3]]) && $this->address_elements[$tag_key_array[3]]) {
                if ($ret) {
                    $ret .= ', ';
                }
            
                $ret .= $this->address_elements[$tag_key_array[3]];
            }
        
            if (isset($tag_key_array[5]) && isset($this->address_elements[$tag_key_array[5]]) && $this->address_elements[$tag_key_array[5]]) {
                if ($ret) {
                    $ret .= ', ';
                }
            
                $ret .= $this->address_elements[$tag_key_array[5]];
            }
        
            if (isset($tag_key_array[6]) && isset($this->address_elements[$tag_key_array[6]]) && $this->address_elements[$tag_key_array[6]]) {
                if ($ret) {
                    $ret .= ' ';
                }
            
                $ret .= $this->address_elements[$tag_key_array[6]];
            }
        
            if (isset($tag_key_array[7]) && isset($this->address_elements[$tag_key_array[7]]) && $this->address_elements[$tag_key_array[7]]) {
                if ($ret) {
                    $ret .= ' ';
                }
            
                $ret .= $this->address_elements[$tag_key_array[7]];
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This updates the tags (and saves them to the DB) as per our internal address_elements property.
    
    \returns true, if successful.
     */
	public function set_tags_from_address_elements() {
	    $new_tags = $this->tags;
        $labels = $this->_get_address_element_labels();
	    
        for ($i = 0; $i < count($labels); $i++) {
            $key = $labels[$i];
            $new_tags[$key] = $this->address_elements[$key];
        }
        
        return $this->set_tags($new_tags);
	}
    
    /***********************/
    /**
    \returns the address elements, in an associative array.
     */
	public function get_address_elements() {
	    $ret = [];
        $labels = $this->_get_address_element_labels();
	    
        for ($i = 0; $i < count($labels); $i++) {
            $key = $labels[$i];
            if (isset($this->address_elements[$key]) && trim($this->address_elements[$key])) {
                $ret[$key] = trim($this->address_elements[$key]);
            }
        }
        
        return $ret;
	}
};
