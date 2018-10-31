<?php
/***************************************************************************************************************************/
/**
    BASALT Extension Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC/The Great Rift Valley Software Company
    
    LICENSE:
    

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_BASALT_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

/****************************************************************************************************************************/
/**
This is a basic REST plugin to handle storage and retrieval general data.
 */
class CO_things_Basalt_Plugin extends A_CO_Basalt_Plugin {
    /***********************/
    /**
    \returns XML, containing the schema for this plugin's responses. The schema needs to be comprehensive.
     */
    protected function _get_xsd() {
        return $this->_process_xsd(dirname(__FILE__).'/schema.xsd');
    }

    /***********************/
    /**
    This returns a more comprehensive description of the thing.
    
    \returns an associative array of strings and integers.
     */
    protected function _get_long_thing_description( $in_object,                 ///< REQUIRED: The object to display.
                                                    $in_show_parents = false    ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                                    ) {
        $ret = parent::_get_long_description($in_object, $in_show_parents);
        
        if (isset($in_object->tags()[0]) && trim($in_object->tags()[0])) {
            $ret['key'] = trim($in_object->tags()[0]);
        }
        
        if (isset($in_object->tags()[1]) && trim($in_object->tags()[1])) {
            $ret['description'] = trim($in_object->tags()[1]);
        }
        
        for ($tag = 2; $tag < 10; $tag++) {
            if (isset($in_object->tags()[$tag]) && (NULL != $in_object->tags()[$tag])) {
                $ret['tag'.$tag] = trim($in_object->tags()[$tag]);
            }
        }
        
        return $ret;
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
            if (isset($in_query['key'])) {
                $ret['key'] = trim(strval($in_query['key']));
            }
            
            if (isset($in_query['description'])) {
                $ret['description'] = trim(strval($in_query['description']));
            }
            
            for ($tag = 2; $tag < 10; $tag++) {
                if (isset($in_query['tag'.$tag])) {
                    $ret['tag'.$tag] = trim(strval($in_query['tag'.$tag]));
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Handles the POST operation (new).
    
    \returns an associative array, with the "raw" response.
     */
    protected function _process_thing_post( $in_andisol_instance,       ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                            $in_path = [],              ///< OPTIONAL: The REST path, as an array of strings.
                                            $in_query = []              ///< OPTIONAL: The query parameters, as an associative array.
                                            ) {
        $ret = [];
        // Extract any valid key from the resource specification. Commas not allowed.
        $thing_key = (isset($in_path) && is_array($in_path) && count($in_path) && (false === strpos(',', $in_path[0])) && !ctype_digit($in_path[0])) ? trim($in_path[0]) : NULL;
        
        $parameters = $this->_process_parameters($in_andisol_instance, $in_query);
        
        if ($thing_key) { // Not allowed to have any numerical value.
            $parameters['key'] = $thing_key;    // The resource specification always beats anything passed in.
        }
        
        if ($in_andisol_instance->logged_in() && isset($parameters['payload']) && isset($parameters['key']) && $parameters['key'] && (false === strpos($parameters['key'], ','))) {    // Must be logged in to POST, and we can't have commas in the key.
            if (!$in_andisol_instance->key_is_unique($parameters['key'])) {
                header('HTTP/1.1 400 Invalid Key');
                exit();
            }
            
            if ($in_andisol_instance->set_value_for_key($parameters['key'], $parameters['payload'])) {
                $new_record = $in_andisol_instance->get_object_for_key($parameters['key']);
                if ($new_record instanceof CO_KeyValue_CO_Collection) {
                    unset($parameters['payload']);
                    $result = $this->_process_thing_put($in_andisol_instance, [$new_record], $in_path, $in_query);
                    
                    $ret['new_thing'] = $result['changed_things'][0]['after'];
                } else {
                    header('HTTP/1.1 400 Resource Not Created');
                    exit();
                }
            } else {
                header('HTTP/1.1 400 Resource Not Created');
                exit();
            }
        } elseif ($in_andisol_instance->logged_in() && isset($parameters['key']) && !isset($parameters['payload'])) {
            header('HTTP/1.1 400 Invalid Payload');
            exit();
        } elseif ($in_andisol_instance->logged_in() && isset($parameters['key'])) {
            header('HTTP/1.1 400 Invalid Resource Key');
            exit();
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
    protected function _process_thing_get(  $in_andisol_instance,           ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                            $in_object_list = [],           ///< OPTIONAL: This function is worthless without at least one object. This will be an array of thing objects, holding the things to examine.
                                            $in_data_only = false,          ///< OPTIONAL: If true (default is false), then the resulting record will be returned in pure data format.
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
                foreach ($in_object_list as $thing) {
                    if ($in_show_details) {
                        $ret[] = $this->_get_long_thing_description($thing, $in_show_parents);
                    } elseif ($in_data_only) {
                        $payload = $thing->get_payload();
                        $temp_file = tempnam(sys_get_temp_dir(), 'RVP');  
                        file_put_contents($temp_file , $payload);
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);  
                        $content_type = finfo_file($finfo, $temp_file);
                        $payload_type = $content_type.';base64,';
                        $ret = $payload_type.base64_encode($payload);
                    } else {
                        $ret[] = $this->_get_short_description($thing);
                    }
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Handles the DELETE operation.
    
    \returns an associative array, with the "raw" response.
     */
    protected function _process_thing_delete(   $in_andisol_instance,       ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                $in_object_list = [],       ///< OPTIONAL: This function is worthless without at least one object. This will be an array of thing objects, holding the things to delete.
                                                $in_show_parents = false    ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                            ) {
        $ret = [];
        
        if ($in_andisol_instance->logged_in()) {    // Must be logged in to DELETE.
            if (isset($in_object_list) && is_array($in_object_list) && (0 < count($in_object_list))) {
                foreach ($in_object_list as $thing) {
                    $to_be_deleted = $this->_get_long_thing_description($thing, $in_show_parents);
                    if ($thing->user_can_write() && $thing->delete_from_db()) {
                        $ret['deleted_things'][] = $to_be_deleted;
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
    Handle the PUT operation (modify).
    
    \returns an associative array, with the "raw" response.
     */
    protected function _process_thing_put(  $in_andisol_instance,       ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                            $in_object_list = [],       ///< OPTIONAL: This function is worthless without at least one object. This will be an array of thing objects, holding the things to modify.
                                            $in_path = [],              ///< OPTIONAL: The REST path, as an array of strings.
                                            $in_query = [],             ///< OPTIONAL: The query parameters, as an associative array.
                                            $in_show_parents = false    ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                        ) {
        if ($in_andisol_instance->logged_in()) {    // Must be logged in to PUT.
            $ret = [];
            $fuzz_factor = isset($in_query) && is_array($in_query) && isset($in_query['fuzz_factor']) ? floatval($in_query['fuzz_factor']) : 0; // Set any fuzz factor.
        
            $parameters = $this->_process_parameters($in_andisol_instance, $in_query);
            if (isset($parameters) && is_array($parameters) && count($parameters) && isset($in_object_list) && is_array($in_object_list) && count($in_object_list)) {
                /*
                What we are doing here, is using the "batch mode" for each record object to set the changes in thing without doing a DB update.
                We generate a change report, but don't add the report to the final report yet, as the change isn't "set" yet.
                After we make all the changes, we cycle through the records, clearing the "batch mode" for each record object, which sends it to the DB.
                If the clear works, then we set it into the final report.
                This makes the process work much better in a multiuser environment, where other clients could be querying the DB.
                */
                $change_reports = [];   // We will keep our interin reports here.
                
                foreach ($in_object_list as $thing) {
                    if ($thing->user_can_write()) { // Belt and suspenders. Make sure we can write.
                        $thing->set_batch_mode();
                        // Take a "before" snapshot.
                        $changed_thing = ['before' => $this->_get_long_thing_description($thing, $in_show_parents)];
                        $result = true;
                        
                        // No commas allowed in the key.
                        if ($result && isset($parameters['key']) && (false === strpos(',', $parameters['key']))) {
                            $result = $thing->set_key($parameters['key']);
                            unset($parameters['key']);  // There can only be one...
                        }
                    
                        if ($result && isset($parameters['name'])) {
                            $result = $thing->set_name($parameters['name']);
                        }
             
                        if ($result && isset($parameters['write_token'])) {
                            $result = $thing->set_write_security_id($parameters['write_token']);
                        }
             
                        if ($result && isset($parameters['lang'])) {
                            $result = $thing->set_lang($parameters['lang']);
                        }
                    
                        if ($result && isset($parameters['longitude'])) {
                            $result = $thing->set_longitude($parameters['longitude']);
                        }
                    
                        if ($result && isset($parameters['latitude'])) {
                            $result = $thing->set_latitude($parameters['latitude']);
                        }
                    
                        if ($result && isset($parameters['fuzz_factor'])) {
                            $result = $thing->set_fuzz_factor($parameters['fuzz_factor']);
                        }
                        
                        if ($result && isset($parameters['can_see_through_the_fuzz'])) {
                            $result = $thing->set_can_see_through_the_fuzz($parameters['can_see_through_the_fuzz']);
                        }
                    
                        if ($result && isset($parameters['description'])) {
                            $result = $thing->set_tag(1, $parameters['description']);
                        }
                    
                        if ($result && isset($parameters['tag2'])) {
                            $result = $thing->set_tag(2, $parameters['tag2']);
                        }
                    
                        if ($result && isset($parameters['tag3'])) {
                            $result = $thing->set_tag(3, $parameters['tag3']);
                        }
                    
                        if ($result && isset($parameters['tag4'])) {
                            $result = $thing->set_tag(4, $parameters['tag4']);
                        }
                    
                        if ($result && isset($parameters['tag5'])) {
                            $result = $thing->set_tag(5, $parameters['tag5']);
                        }
                    
                        if ($result && isset($parameters['tag6'])) {
                            $result = $thing->set_tag(6, $parameters['tag6']);
                        }
                    
                        if ($result && isset($parameters['tag7'])) {
                            $result = $thing->set_tag(7, $parameters['tag7']);
                        }
                    
                        if ($result && isset($parameters['tag8'])) {
                            $result = $thing->set_tag(8, $parameters['tag8']);
                        }
                    
                        if ($result && isset($parameters['tag9'])) {
                            $result = $thing->set_tag(9, $parameters['tag9']);
                        }
                    
                        if ($result && isset($parameters['remove_payload'])) {
                            $result = $thing->set_payload(NULL);
                        } elseif ($result && isset($parameters['payload'])) {
                            $result = $thing->set_payload($parameters['payload']);
                        }
                        
                        // We have previously split into "add" and "remove" lists (dictated by the sign of the integer).
                        if ($result && isset($parameters['child_ids'])) {
                            $add = $parameters['child_ids']['add'];
                            $remove = $parameters['child_ids']['remove'];
                    
                            foreach ($remove as $id) {
                                if ($id != $thing->id()) {
                                    $child = $in_andisol_instance->get_single_data_record_by_id($id);
                                    if (isset($child)) {
                                        $result = $thing->deleteThisElement($child);
                                    }
                        
                                    if (!$result) {
                                        break;
                                    }
                                }
                            }
                        
                            if ($result) {
                                foreach ($add as $id) {
                                    if ($id != $thing->id()) {
                                        $child = $in_andisol_instance->get_single_data_record_by_id($id);
                                        if (isset($child)) {
                                            $result = $thing->appendElement($child);
                                        
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
                            $result = $thing->set_read_security_id($parameters['read_token']);
                        }
                    
                        if ($result) {  // Assuming all went well to this point, we take an "after" snapshot, and save the object and interim report in our "final clear" list.
                            $changed_thing['after'] = $this->_get_long_thing_description($thing, $in_show_parents);
                            $change_reports[] = ['object' => $thing, 'report' => $changed_thing];
                        }
                    }
                }
                
                // Here's where we actually set each record into the DB, and generate the full final report.
                if ($result && count($change_reports)) {
                    $ret['changed_things'] = [];
                    foreach ($change_reports as $value) {
                        $result = $value['object']->clear_batch_mode();
                        if ($result) {  // We only report the ones that work.
                            $ret['changed_things'][] = $value['report'];
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
    This returns an array of classnames, handled by this plugin.
    
    \returns an array of string, with the names of the classes handled by this plugin.
     */
    static public function classes_managed() {
        return ['CO_Collection', 'CO_KeyValue_CO_Collection'];
    }
        
    /***********************/
    /**
    \returns a string, with our plugin name.
     */
    public function plugin_name() {
        return 'things';
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
        $data_only = false; // Return only base64 data. If this is sent, the return type is ignored. Multiple responses are concatenated by spaces.
        
        if ('POST' == $in_http_method) {    // We handle POST directly.
            $ret = $this->_process_thing_post($in_andisol_instance, $in_path, $in_query);
        } else {
            $show_parents = isset($in_query) && is_array($in_query) && isset($in_query['show_parents']);    // Show all things in detail, as well as the parents (applies only to GET).
            $show_details = $show_parents || (isset($in_query) && is_array($in_query) && isset($in_query['show_details']));    // Show all things in detail (applies only to GET).
            $data_only = (isset($in_query) && is_array($in_query) && isset($in_query['data_only']));    
            $writeable = isset($in_query) && is_array($in_query) && isset($in_query['writeable']);          // Show/list only things this user can modify.
            $search_count_only = isset($in_query) && is_array($in_query) && isset($in_query['search_count_only']);  // Ignored for discrete IDs. If true, then a simple "count" result is returned as an integer.
            $search_ids_only = isset($in_query) && is_array($in_query) && isset($in_query['search_ids_only']);      // Ignored for discrete IDs. If true, then the response will be an array of integers, denoting resource IDs.
            $search_page_size = isset($in_query) && is_array($in_query) && isset($in_query['search_page_size']) ? abs(intval($in_query['search_page_size'])) : 0;           // Ignored for discrete IDs. This is the size of a page of results (1-based result count. 0 is no page size).
            $search_page_number = isset($in_query) && is_array($in_query) && isset($in_query['search_page_number']) ? abs(intval($in_query['search_page_number'])) : 0;  // Ignored for discrete IDs, or if search_page_size is 0. The page we are interested in (0-based. 0 is the first page).
            
            $thinglist = [];
            
            // For the default (no thing ID), we simply act on a list of all available things (or filtered by some search criteria).
            if (0 == count($in_path)) {
                $radius = isset($in_query) && is_array($in_query) && isset($in_query['search_radius']) && (0.0 < floatval($in_query['search_radius'])) ? floatval($in_query['search_radius']) : NULL;
                $longitude = isset($in_query) && is_array($in_query) && isset($in_query['search_longitude']) ? floatval($in_query['search_longitude']) : NULL;
                $latitude = isset($in_query) && is_array($in_query) && isset($in_query['search_latitude']) ? floatval($in_query['search_latitude']) : NULL;
                
                $location_search = NULL;
            
                if (isset($radius) && isset($longitude) && isset($latitude)) {
                    $location_search = Array('radius' => $radius, 'longitude' => $longitude, 'latitude' => $latitude);
                }
                
                $class_search = Array('%_KeyValue_CO_Collection', 'use_like' => 1);
            
                $search_array = ['access_class' => $class_search];
                
                if (isset($location_search)) {
                    $search_array['location'] = $location_search;
                }
                
                $search_name = isset($in_query) && is_array($in_query) && isset($in_query['search_name']) ? trim($in_query['search_name']) : NULL;          // Search in the object name.
                
                if (isset($search_name)) {
                    $search_array['name'] = Array($search_name, 'use_like' => 1);
                }
                
                $tags = [NULL];
                
                $in_query['search_tag1'] = isset($in_query) && is_array($in_query) && isset($in_query['search_description']) ? trim($in_query['search_description']) : NULL;
                $has_tag = false;
                
                for ($tag = 1; $tag < 10; $tag++) {
                    $tag_string = 'search_tag'.$tag;
                    $tag_value = isset($in_query) && is_array($in_query) && isset($in_query[$tag_string]) ? trim($in_query[$tag_string]) : NULL;
                    if ($tag_value !== NULL) {
                        $has_tag = true;
                    }
                    $tags[] = $tag_value;
                }
                
                // If there were any specified tags, we search by tag. Otherwise, we don't bother.
                if ($has_tag) {
                    $search_array['tags'] = $tags;
                    $search_array['tags']['use_like'] = 1;
                }

                $thinglist = $in_andisol_instance->generic_search($search_array, false, $search_page_size, $search_page_number, $writeable, $search_count_only, $search_ids_only);
            } else {
                $thing_id_list = array_unique(explode(',', $in_path[0]));
                
                if ($data_only) {   // We only do one at a time for data only.
                    $thing_id_list = [$thing_id_list[0]];
                }
                
                foreach ($thing_id_list as $id) {
                    $thing = NULL;
                    
                    if (ctype_digit($id) && (0 < intval($id))) {    // Numerical, we go by ID
                        $thing = $in_andisol_instance->get_single_data_record_by_id($id);
                    }
                    
                    if (!$thing) {
                        $id = urldecode($id);
                        $thing = $in_andisol_instance->get_object_for_key($id);
                    }
                    
                    if (isset($thing) && ($thing instanceof CO_Collection)) {
                        $thinglist[] = $thing;
                    }
                    
                    if ($data_only) {   // We only allow one thing for data only.
                        break;
                    }
                }
            }
            
            if ('GET' == $in_http_method) {
                $ret = $this->_process_thing_get($in_andisol_instance, $thinglist, $data_only, $show_details, $show_parents, $search_count_only, $search_ids_only, $in_path, $in_query);
            } elseif ('DELETE' == $in_http_method) {
                $ret = $this->_process_thing_delete($in_andisol_instance, $thinglist, $show_parents);
            } else {
                $ret = $this->_process_thing_put($in_andisol_instance, $thinglist, $in_path, $in_query, $show_parents);
            }
        }
                
        return $data_only ? $ret : $this->_condition_response($in_response_type, $ret);
    }
}