<?php
/***************************************************************************************************************************/
/**
    BASALT Extension Layer
    
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
defined( 'LGV_BASALT_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

if (!defined('LGV_CHAMELEON_UTILS_CATCHER')) {
    define('LGV_CHAMELEON_UTILS_CATCHER', 1);
}

require_once(CO_Config::chameleon_main_class_dir().'/co_chameleon_utils.class.php');

/****************************************************************************************************************************/
/**
This is a REST plugin that allows access to places (locations).
 */
class CO_places_Basalt_Plugin extends A_CO_Basalt_Plugin {
    /***********************/
    /**
    This static protected method will allow us to do a Google lookup of an address, and return a long/lat.
    
    \returns an associative array of floats ("longitude" and "latitude"). NULL, if lookup failed.
     */
    static protected function _lookup_address(  $in_address_string,     ///< The address to look up, in a single string (Google will do its best to parse the string).
                                                $in_region_bias = NULL  ///< Any region bias (like "us" or "sv"). Max. 3 characters.
                                                ) {
        if (isset(CO_Config::$allow_address_lookup) && CO_Config::$allow_address_lookup && CO_Config::$google_api_key) {
            $in_address_string = urlencode($in_address_string);
            $in_region_bias = urlencode(strtolower(trim(substr($in_region_bias, 0, 3))));
            $bias = (NULL != $in_region_bias) ? 'region='.$in_region_bias.'&' : '';
            $http_status = '';
            $error_catcher = '';
        
            $uri = 'https://maps.googleapis.com/maps/api/geocode/json?'.$bias.'key='.CO_Config::$google_api_key.'&address='.$in_address_string;
        
            $resulting_json = json_decode(CO_Chameleon_Utils::call_curl($uri, false, $http_status, $error_catcher));
            if (isset($resulting_json) && $resulting_json &&isset($resulting_json->results) && is_array($resulting_json->results) && count($resulting_json->results)) {
                if (isset($resulting_json->results[0]->geometry) && isset($resulting_json->results[0]->geometry->location) && isset($resulting_json->results[0]->geometry->location->lng) && isset($resulting_json->results[0]->geometry->location->lat)) {
                    return Array( 'longitude' => floatval($resulting_json->results[0]->geometry->location->lng), 'latitude' => floatval($resulting_json->results[0]->geometry->location->lat));
                }
            }
        }
        
        return NULL;
    }
    
    /***********************/
    /**
    This returns a fairly short summary of the place.
    
    \returns an associative array of strings and integers.
     */
    protected function _get_short_description(  $in_object  ///< REQUIRED: The user or login object to extract information from.
                                            ) {
        $ret = parent::_get_short_description($in_object);
        
        $address = trim($in_object->get_readable_address());
        
        if (isset($address) && $address) {
            $ret['address'] = $address;
        }
        
        return $ret;
    }

    /***********************/
    /**
    This returns a more comprehensive description of the place.
    
    \returns an associative array of strings and integers.
     */
    protected function _get_long_place_description( $in_place_object,           ///< REQUIRED: The object to display.
                                                    $in_show_parents = false    ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                                    ) {
        $ret = parent::_get_long_description($in_place_object, $in_show_parents);
        
        $address_elements = $in_place_object->get_address_elements();
        
        if (isset($address_elements) && is_array($address_elements) && (0 < count($address_elements))) {
            $ret['address_elements'] = $address_elements;
        }
        
        if (isset($in_place_object->tags()[8]) && trim($in_place_object->tags()[8])) {
            $ret['tag8'] = trim($in_place_object->tags()[8]);
        }
        
        if (isset($in_place_object->tags()[9]) && trim($in_place_object->tags()[9])) {
            $ret['tag9'] = trim($in_place_object->tags()[9]);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns XML, containing the schema for this plugin's responses. The schema needs to be comprehensive.
     */
    protected function _get_xsd() {
        return $this->_process_xsd(dirname(__FILE__).'/schema.xsd');
    }
        
    /***********************/
    /**
    Parses the query parameters and cleans them for the database.
    
    \returns an associative array of the parameters, parsed for submission to the database.
     */
    protected function _process_parameters( $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                            $in_query               ///< REQUIRED: The query string to be parsed.
                                        ) {
        $ret = parent::_process_parameters($in_andisol_instance, $in_query);
        
        if (isset($in_query) && is_array($in_query)) {
            // See if we are to geocode, reverse geocode, or do nothing (This depends on the Google API Key being enabled).
            if (isset($in_query['geocode'])) {
                $ret['geocode'] = 1;
            } elseif (isset($in_query['reverse-geocode'])) {
                $ret['geocode'] = -1;
            } else {
                $ret['geocode'] = 0;
            }
            
            if (isset($in_query['address_venue'])) {
                $ret['address_venue'] = trim($in_query['address_venue']);
            }
            
            if (isset($in_query['address_street_address'])) {
                $ret['address_street_address'] = trim($in_query['address_street_address']);
            }
            
            if (isset($in_query['address_extra_information'])) {
                $ret['address_extra_information'] = trim($in_query['address_extra_information']);
            }
            
            if (isset($in_query['address_town'])) {
                $ret['address_town'] = trim($in_query['address_town']);
            }
            
            if (isset($in_query['address_county'])) {
                $ret['address_county'] = trim($in_query['address_county']);
            }
            
            if (isset($in_query['address_state'])) {
                $ret['address_state'] = trim($in_query['address_state']);
            }
            
            if (isset($in_query['address_postal_code'])) {
                $ret['address_postal_code'] = trim($in_query['address_postal_code']);
            }
            
            if (isset($in_query['address_nation'])) {
                $ret['address_nation'] = trim($in_query['address_nation']);
            }
            
            // Next, look for the last two tags (the only ones we're allowed to change).
            if (isset($in_query['tag8'])) {
                $ret['tag8'] = trim(strval($in_query['tag8']));
            }
            
            if (isset($in_query['tag9'])) {
                $ret['tag9'] = trim(strval($in_query['tag9']));
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Handles the DELETE operation.
    
    \returns an associative array, with the "raw" response.
     */
    protected function _process_place_delete(   $in_andisol_instance,       ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                $in_object_list = [],       ///< OPTIONAL: This function is worthless without at least one object. This will be an array of place objects, holding the places to delete.
                                                $in_path = [],              ///< OPTIONAL: The REST path, as an array of strings.
                                                $in_query = [],             ///< OPTIONAL: The query parameters, as an associative array.
                                                $in_show_parents = false    ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                            ) {
        $ret = [];
        
        if ($in_andisol_instance->logged_in()) {    // Must be logged in to DELETE.
            if (isset($in_object_list) && is_array($in_object_list) && (0 < count($in_object_list))) {
                foreach ($in_object_list as $place) {
                    $to_be_deleted = $this->_get_long_place_description($place, $in_show_parents);
                    if ($place->user_can_write() && $place->delete_from_db()) {
                        $ret['deleted_places'][] = $to_be_deleted;
                    }
                }
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            exit();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Handles the POST operation (new).
    
    \returns an associative array, with the "raw" response.
     */
    protected function _process_place_post( $in_andisol_instance,       ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                            $in_path = [],              ///< OPTIONAL: The REST path, as an array of strings.
                                            $in_query = []              ///< OPTIONAL: The query parameters, as an associative array.
                                            ) {
        $ret = [];
        
        if ($in_andisol_instance->logged_in()) {    // Must be logged in to POST.
            $new_record = $in_andisol_instance->create_general_data_item(0, NULL, 'CO_Place_Collection');
            
            if ($new_record instanceof CO_Place_Collection) {
                if (isset($in_query) && is_array($in_query) && count($in_query)) {
                    $temp = $this->_process_place_put($in_andisol_instance, [$new_record], $in_path, $in_query);
                    if (isset($temp) && is_array($temp) && count($temp)) {
                        $ret['new_place'] = $temp['changed_places'][0]['after'];
                    } else {
                        $new_record-delete_from_db();
                        header('HTTP/1.1 400 Resource Not Created');
                        exit();
                    }
                } else {
                    $ret['new_place'] = $this->_get_long_place_description($new_record);
                }
            } else {
                header('HTTP/1.1 400 Resource Not Created');
                exit();
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            exit();
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    Handle the PUT operation (modify).
    
    \returns an associative array, with the "raw" response.
     */
    protected function _process_place_put(  $in_andisol_instance,       ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                            $in_object_list = [],       ///< OPTIONAL: This function is worthless without at least one object. This will be an array of place objects, holding the places to modify.
                                            $in_path = [],              ///< OPTIONAL: The REST path, as an array of strings.
                                            $in_query = [],             ///< OPTIONAL: The query parameters, as an associative array.
                                            $in_show_parents = false    ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                        ) {
        if ($in_andisol_instance->logged_in()) {    // Must be logged in to PUT.
            $ret = ['changed_places' => []];
            $fuzz_factor = isset($in_query) && is_array($in_query) && isset($in_query['fuzz_factor']) ? floatval($in_query['fuzz_factor']) : 0; // Set any fuzz factor.
        
            $parameters = $this->_process_parameters($in_andisol_instance, $in_query);
            if (isset($parameters) && is_array($parameters) && count($parameters) && isset($in_object_list) && is_array($in_object_list) && count($in_object_list)) {
                /*
                What we are doing here, is using the "batch mode" for each record object to set the changes in place without doing a DB update.
                We generate a change report, but don't add the report to the final report yet, as the change isn't "set" yet.
                After we make all the changes, we cycle through the records, clearing the "batch mode" for each record object, which sends it to the DB.
                If the clear works, then we set it into the final report.
                This makes the process work much better in a multiuser environment, where other clients could be querying the DB.
                */
                $change_reports = [];   // We will keep our interin reports here.
                
                foreach ($in_object_list as $place) {
                    if ($place->user_can_write()) { // Belt and suspenders. Make sure we can write.
                        $place->set_batch_mode();
                        // Take a "before" snapshot.
                        $changed_place = ['before' => $this->_get_long_place_description($place, $in_show_parents)];
                        $result = true;
                    
                        if ($result && isset($parameters['name'])) {
                            $result = $place->set_name($parameters['name']);
                        }
             
                        if ($result && isset($parameters['write_token'])) {
                            $result = $place->set_write_security_id($parameters['write_token']);
                        }
             
                        if ($result && isset($parameters['lang'])) {
                            $result = $place->set_lang($parameters['lang']);
                        }
                        
                        if (isset($parameters['longitude'])) {
                            $result = $place->set_longitude($parameters['longitude']);
                        }
                
                        if ($result && isset($parameters['latitude'])) {
                            $result = $place->set_latitude($parameters['latitude']);
                        }
                    
                        if ($result && isset($parameters['fuzz_factor'])) {
                            $result = $place->set_fuzz_factor($parameters['fuzz_factor']);
                        }
                    
                        if ($result && isset($parameters['address_venue'])) {
                            $result = $place->set_address_element(0, $parameters['address_venue']);
                        }
                    
                        if ($result && isset($parameters['address_street_address'])) {
                            $result = $place->set_address_element(1, $parameters['address_street_address']);
                        }
                    
                        if ($result && isset($parameters['address_extra_information'])) {
                            $result = $place->set_address_element(2, $parameters['address_extra_information']);
                        }
                    
                        if ($result && isset($parameters['address_town'])) {
                            $result = $place->set_address_element(3, $parameters['address_town']);
                        }
                    
                        if ($result && isset($parameters['address_county'])) {
                            $result = $place->set_address_element(4, $parameters['address_county']);
                        }
                    
                        if ($result && isset($parameters['address_state'])) {
                            $result = $place->set_address_element(5, $parameters['address_state']);
                        }
                    
                        if ($result && isset($parameters['address_postal_code'])) {
                            $result = $place->set_address_element(6, $parameters['address_postal_code']);
                        }
                        
                        if ($result && isset($parameters['can_see_through_the_fuzz'])) {
                            $result = $place->set_can_see_through_the_fuzz($parameters['can_see_through_the_fuzz']);
                        }
                        
                        if ($result && isset($parameters['address_nation'])) {  // This might fail, if it's a nation-specific one, so we don't test for the result.
                            $test = $place->set_address_element(7, $parameters['address_nation']);
                            if (!$test) {   // If so, we add a note to the change record.
                                $changed_place['nation_not_changed'] = true;
                            }
                        }
                        
                        // Geocode requires that address information already be set. Geolocation requires that the long/lat already be set.
                        if ($result && isset($parameters['geocode']) && (0 != intval($parameters['geocode']))) {
                            if (1 == intval($parameters['geocode'])) {
                                $long_lat = $place->lookup_address();
                                
                                if (isset($long_lat) && is_array($long_lat) && 1 < count($long_lat)) {
                                    $result = $place->set_longitude(floatval($long_lat['longitude']));
                                    if ($result) {
                                        $result = $place->set_latitude(floatval($long_lat['latitude']));
                                    }
                                }
                            } else {
                                $address_elements = $place->geocode_long_lat();
                                if (isset($address_elements) && is_array($address_elements) && count($address_elements)) {
                                    $result = $place->set_address_elements($address_elements);
                                }
                            }
                        }
                    
                        if ($result && isset($parameters['tag8'])) {
                            $result = $place->set_tag(8, $parameters['tag8']);
                        }
                    
                        if ($result && isset($parameters['tag9'])) {
                            $result = $place->set_tag(9, $parameters['tag9']);
                        }
                    
                        if ($result && isset($parameters['remove_payload'])) {
                            $result = $place->set_payload(NULL);
                        } elseif ($result && isset($parameters['payload'])) {
                            $result = $place->set_payload($parameters['payload']);
                        }
                    
                        // We have previously split into "add" and "remove" lists (dictated by the sign of the integer).
                        if ($result && isset($parameters['child_ids'])) {
                            $add = $parameters['child_ids']['add'];
                            $remove = $parameters['child_ids']['remove'];
                    
                            foreach ($remove as $id) {
                                if ($id != $place->id()) {
                                    $child = $in_andisol_instance->get_single_data_record_by_id($id);
                                    if (isset($child)) {
                                        $result = $place->deleteThisElement($child);
                                    }
                        
                                    if (!$result) {
                                        break;
                                    }
                                }
                            }
                        
                            if ($result) {
                                foreach ($add as $id) {
                                    if ($id != $place->id()) {
                                        $child = $in_andisol_instance->get_single_data_record_by_id($id);
                                        if (isset($child)) {
                                            $result = $place->appendElement($child);
                                        
                                            if (!$result) {
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        // We do this last, so we have the option of doing a "lock" (which isn't necessary in "batch mode").
                        if ($result && isset($parameters['read_token'])) {
                            $result = $place->set_read_security_id($parameters['read_token']);
                        }
                    
                        if ($result) {  // Assuming all went well to this point, we take an "after" snapshot, and save the object and interim report in our "final clear" list.
                            $changed_place['after'] = $this->_get_long_place_description($place, $in_show_parents);
                            $change_reports[] = ['object' => $place, 'report' => $changed_place];
                        }
                    }
                }
                
                // Here's where we actually set each record into the DB, and generate the full final report.
                if ($result && count($change_reports)) {
                    $ret['changed_places'] = [];
                    foreach ($change_reports as $value) {
                        $result = $value['object']->clear_batch_mode();
                        if ($result) {  // We only report the ones that work.
                            $ret['changed_places'][] = $value['report'];
                        } else {
                            break;
                        }
                    }
                }
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            exit();
        }
        return $ret;
    }
    
    /***********************/
    /**
    Handles the GET operation (list records).
    
    \returns an associative array, with the "raw" response.
     */
    protected function _process_place_get(  $in_andisol_instance,           ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                            $in_object_list = [],           ///< OPTIONAL: This function is worthless without at least one object. This will be an array of place objects, holding the places to examine.
                                            $in_show_details = false,       ///< OPTIONAL: If true (default is false), then the resulting record will be returned in "detailed" format.
                                            $in_show_parents = false,       ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                            $in_search_count_only = false,  ///< OPTIONAL: If true, then we are only looking for a single integer count.
                                            $in_search_ids_only = false,    ///< OPTIONAL: If true, then we are going to return just an array of int (the IDs of the resources).
                                            $in_path = [],                  ///< OPTIONAL: The REST path, as an array of strings.
                                            $in_query = []                  ///< OPTIONAL: The query parameters, as an associative array.
                                        ) {
        $ret = [];
    
        if ($in_search_count_only) {
            $ret['count'] = intval($in_object_list);
        } elseif (isset($in_object_list) && is_array($in_object_list) && (0 < count($in_object_list))) {
            if ($in_search_ids_only) {
                $ret['ids'] = $in_object_list;
            } else {
                foreach ($in_object_list as $place) {
                    if ($in_show_details) {
                        $ret[] = $this->_get_long_place_description($place, $in_show_parents);
                    } else {
                        $ret[] = $this->_get_short_description($place);
                    }
                }
            }
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    \returns a string, with our plugin name.
     */
    public function plugin_name() {
        return 'places';
    }
    
    /***********************/
    /**
    This returns an array of classnames, handled by this plugin.
    
    \returns an array of string, with the names of the classes handled by this plugin.
     */
    static public function classes_managed() {
        return ['CO_Place_Collection', 'CO_US_Place_Collection', 'CO_Place', 'CO_US_Place', 'CO_LL_Location'];
    }
        
    /***********************/
    /**
    This runs our plugin command.
    
    \returns the HTTP response string, as either JSON or XML.
     */
    public function process_command(    $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                        $in_http_method,        ///< REQUIRED: 'GET', 'POST', 'PUT' or 'DELETE'
                                        $in_response_type,      ///< REQUIRED: Either 'json' or 'xml' -the response type.
                                        $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings.
                                        $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                    ) {
        $ret = [];
        
        if ('POST' == $in_http_method) {    // We handle POST directly.
            $ret = $this->_process_place_post($in_andisol_instance, $in_path, $in_query);
        } else {
            $show_parents = isset($in_query) && is_array($in_query) && isset($in_query['show_parents']);    // Show all places in detail, as well as the parents (applies only to GET or DELETE).
            $show_details = $show_parents || (isset($in_query) && is_array($in_query) && isset($in_query['show_details']));    // Show all places in detail (applies only to GET).
            $writeable = isset($in_query) && is_array($in_query) && isset($in_query['writeable']);          // Show/list only places this user can modify.
            $search_count_only = isset($in_query) && is_array($in_query) && isset($in_query['search_count_only']);  // Ignored for discrete IDs. If true, then a simple "count" result is returned as an integer.
            $search_ids_only = isset($in_query) && is_array($in_query) && isset($in_query['search_ids_only']);      // Ignored for discrete IDs. If true, then the response will be an array of integers, denoting resource IDs.
            $search_page_size = isset($in_query) && is_array($in_query) && isset($in_query['search_page_size']) ? abs(intval($in_query['search_page_size'])) : 0;           // Ignored for discrete IDs. This is the size of a page of results (1-based result count. 0 is no page size).
            $search_page_number = isset($in_query) && is_array($in_query) && isset($in_query['search_page_number']) ? abs(intval($in_query['search_page_number'])) : 0;  // Ignored for discrete IDs, or if search_page_size is 0. The page we are interested in (0-based. 0 is the first page).
            $search_name = isset($in_query) && is_array($in_query) && isset($in_query['search_name']) ? trim($in_query['search_name']) : NULL;          // Search in the object name.
            
            // For the default (no place ID), we simply act on a list of all available places (or filtered by some search criteria).
            if (0 == count($in_path)) {
                $radius = isset($in_query) && is_array($in_query) && isset($in_query['search_radius']) && (0.0 < floatval($in_query['search_radius'])) ? floatval($in_query['search_radius']) : NULL;
                $longitude = isset($in_query) && is_array($in_query) && isset($in_query['search_longitude']) ? floatval($in_query['search_longitude']) : NULL;
                $latitude = isset($in_query) && is_array($in_query) && isset($in_query['search_latitude']) ? floatval($in_query['search_latitude']) : NULL;
                $search_region_bias = isset($in_query) && is_array($in_query) && isset($in_query['search_region_bias']) ? strtolower(trim($search_region_bias)) : CO_Config::$default_region_bias;  // This is a region bias for an address lookup. Ignored if search_address is not specified.
                $search_address = isset($in_query) && is_array($in_query) && isset($in_query['search_address']) && trim($in_query['search_address']) ? trim($in_query['search_address']) : NULL;
                
                $tags = [];
                $tags_temp = [];
                
                // This mess allows us to use the field names set up in the CHAMELEON class as search query bases.
                for ($count = 0; $count < 8; $count++) {
                    $eval_line = '$parameter_name = CO_CHAMELEON_Lang_Common::$chameleon_co_place_tag_'.$count.';';
                    eval($eval_line);
                    $parameter_name = 'search_'.$parameter_name;
                    if (isset($in_query) && is_array($in_query) && isset($in_query[$parameter_name])) {
                        $parameter_value = strval(trim($in_query[$parameter_name]));
                        $tags_temp[] = $parameter_value;
                    } else {
                        $tags_temp[] = NULL;
                    }
                }
                
                // These two tags are available for whatever we want them for.
                if (isset($in_query) && is_array($in_query) && isset($in_query['search_tag8'])) {
                    $tags_temp[] = trim($in_query['search_tag8']);
                } else {
                    $tags_temp[] = NULL;
                }
                
                if (isset($in_query) && is_array($in_query) && isset($in_query['search_tag9'])) {
                    $tags_temp[] = trim($in_query['search_tag9']);
                } else {
                    $tags_temp[] = NULL;
                }
                
                // See if we will even be looking at our tags.
                if(array_reduce($tags_temp, function($prev, $current) { return $prev || (NULL !== $current) ? true : $prev; }, false)) {
                    $tags = $tags_temp;
                }
                
                $address = NULL;
                
                // Long/lat trumps an address.
                // If we have an address, and no long/lat, we see if we can do a lookup.
                if (isset(CO_Config::$allow_address_lookup) && CO_Config::$allow_address_lookup && CO_Config::$google_api_key) {
                    $address = isset($in_query) && is_array($in_query) && isset($in_query['search_address']) && trim($in_query['search_address']) ? trim($in_query['search_address']) : NULL;
                    if (isset($search_address) && $search_address && !(isset($longitude) && isset($latitude))) {
                        if (CO_Config::$allow_general_address_lookup || $in_andisol_instance->logged_in()) {
                            $result = self::_lookup_address($search_address, $search_region_bias);
                    
                            if ($result && is_array($result) && (1 < count($result))) {
                                $longitude = $result['longitude'];
                                $latitude = $result['latitude'];
                            }
                        } else {
                            header('HTTP/1.1 400 Improper Distance Search (Login Required)');
                            exit();
                        }
                    }
                } elseif ($search_address) {
                    header('HTTP/1.1 400 Incomplete Distance Search');
                    exit();
                }
                
                $location_search = NULL;
                
                // We make sure that we puke if they give us a bad distance search.
                if (isset($radius) && isset($longitude) && isset($latitude)) {
                    $location_search = Array('radius' => $radius, 'longitude' => $longitude, 'latitude' => $latitude);
                } elseif (isset($radius) || isset($longitude) || isset($latitude)) {
                    header('HTTP/1.1 400 Incomplete Distance Search');
                    exit();
                }
                
                $class_search = Array('%_Place_Collection', 'use_like' => 1);
                $search_array['access_class'] = $class_search;
                
                // Now, I had initially considered doing a cool recursive-descent parser in the directories for a value search, but realized that could be a security vector. So instead, I am implementing a rather primitive, AND-connected query-based lookup.
                // You can put SQL wildcards ('%') into the values, and specifying multiple values will act as an AND search.
                if (count($tags)) {
                    $tags['use_like'] = 1;
                    $search_array['tags'] = $tags;
                }
                
                if (isset($location_search)) {
                    $search_array['location'] = $location_search;
                    if (isset($search_address) && $search_address && !(isset($longitude) && isset($latitude))) {
                        $search_array['location']['address'] = $search_address;
                    }
                }
                
                if (isset($search_name)) {
                    $search_array['name'] = Array($search_name, 'use_like' => 1);
                }

                $placelist = $in_andisol_instance->generic_search($search_array, false, $search_page_size, $search_page_number, $writeable, $search_count_only, $search_ids_only);
                
                if ('GET' == $in_http_method) {
                    $ret = $this->_process_place_get($in_andisol_instance, $placelist, $show_details, $show_parents, $search_count_only, $search_ids_only, $in_path, $in_query);
                    $ret = Array('results' => $ret);
                } elseif ('PUT' == $in_http_method) {
                    $ret = $this->_process_place_put($in_andisol_instance, $placelist, $in_path, $in_query, $show_parents);
                } elseif ('DELETE' == $in_http_method) {
                    $ret = $this->_process_place_delete($in_andisol_instance, $placelist, $in_path, $in_query, $show_parents);
                }
                
                if ($location_search && !$search_count_only) {
                    $ret['search_location'] = $location_search;
                }
            } else {
                $first_directory = $in_path[0];    // Get the first directory.
        
                // This tests to see if we only got one single digit as our "command."
                $single_place_id = (ctype_digit($first_directory) && (1 < intval($first_directory))) ? intval($first_directory) : NULL;    // This will be for if we are looking only one single place.
        
                // The first thing that we'll do, is look for a list of place IDs. If that is the case, we split them into an array of int.
        
                $place_id_list = explode(',', $first_directory);
        
                // If we do, indeed, have a list, we will force them to be ints, and cycle through them.
                if ($single_place_id || (1 < count($place_id_list))) {
                    $place_id_list = ($single_place_id ? [$single_place_id] : array_unique(array_map('intval', $place_id_list)));
                    $placelist = [];
                
                    foreach ($place_id_list as $id) {
                        if (0 < $id) {
                            $place = $in_andisol_instance->get_single_data_record_by_id($id);
                            if (isset($place) && ($place instanceof CO_Place)) {
                                $placelist[] = $place;
                            }
                        }
                    }
                    
                    if ('GET' == $in_http_method) {
                        $ret = $this->_process_place_get($in_andisol_instance, $placelist, $show_details, $show_parents, $search_count_only, $search_ids_only, $in_path, $in_query);
                        $ret = Array('results' => $ret);
                    } elseif ('PUT' == $in_http_method) {
                        $ret = $this->_process_place_put($in_andisol_instance, $placelist, $in_path, $in_query, $show_parents);
                    } elseif ('DELETE' == $in_http_method) {
                        $ret = $this->_process_place_delete($in_andisol_instance, $placelist, $in_path, $in_query, $show_parents);
                    }
                }
            }
        }
        
        return $this->_condition_response($in_response_type, $ret);
    }
}