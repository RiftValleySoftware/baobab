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
defined( 'LGV_BASALT_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

/****************************************************************************************************************************/
/**
 */
abstract class A_CO_Basalt_Plugin {
    static protected $_s_cached_list = NULL;  ///< This will contain caches of our handler list.
    
    /***********************/
    /**
    This returns any handler for the presented class.
    
    \returns a string, with the plugin name that handles the given class.
     */
    static protected function _get_handler( $in_classname   ///< REQUIRED: The name of the class we are querying for a handler.
                                            ) {
        // First time through, we build up a cached list of handlers.
        if (!isset(self::$_s_cached_list) || !is_array(self::$_s_cached_list) || !count(self::$_s_cached_list)) {
            self::$_s_cached_list = [];
            $plugin_dirs = CO_Config::plugin_dirs();
        
            foreach ($plugin_dirs as $plugin_dir) {
                if (isset($plugin_dir) && is_dir($plugin_dir)) {
                    $plugin_name = basename($plugin_dir);
                    $plugin_classname = 'CO_'.$plugin_name.'_Basalt_Plugin';
                    $plugin_filename = strtolower($plugin_classname).'.class.php';
                    $plugin_file = $plugin_dir.'/'.$plugin_filename;
                    include_once($plugin_file);
                    $class_list = $plugin_classname::classes_managed();
                    self::$_s_cached_list[$plugin_classname] = $class_list;
                }
            }
        }
        
        foreach (self::$_s_cached_list as $plugin_name => $class_list) {
            if (in_array($in_classname, $class_list)) {
                $plugin_name = substr($plugin_name, 3, -14);
                return $plugin_name;
            }
        }
        
        return NULL;
    }

    /***********************/
    /**
    \returns the server base URI, including any custom port and/or SSL prefix.
    */
    protected static function _server_url() {
        $port = intval ( $_SERVER['SERVER_PORT'] );
    
        // IIS puts "off" in the HTTPS field, so we need to test for that.
        $https = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'] && (($_SERVER['HTTPS'] !== 'off') || ($port == 443)))) ? true : false;
    
        $url_path = $_SERVER['SERVER_NAME'];
    
        // See if we need to add an explicit port to the URI.
        if (!$https && ($port != 80)) {
            $url_path .= ":$port";
        } elseif ($https && ($port != 443)) {
            $url_path .= ":$port";
        }
        
        return 'http'.($https ? 's' : '').'://'.$url_path.$_SERVER['SCRIPT_NAME'];
    }

    /***********************/
    /**
    \returns the input array, converted to XML.
     */
    protected static function _array2xml(	$in_array   ///< REQUIRED: The input associative array
                                        ) {
        $output = '';
        $index = 0;
        
        foreach ($in_array as $name => $value) {
            $plurality = is_int($name);
            $name = $plurality ? 'value' : htmlspecialchars(trim($name));
                
            if ($value) {
                if ($plurality) {
                    $output .= '<'.$name.' sequence_index="'.strval ( $index++ ).'">';
                } else {
                    $output .= '<'.$name.'>';
                }
                
                if (is_array($value)) {
                    $output .= self::_array2xml($value);
                } else {
                    $output .= htmlspecialchars(strval($value));
                }
                
                $output .= '</'.$name.'>';
            }
        }
    
        return $output;
    }
    
    /***********************/
    /**
    This returns a fairly short summary of the given object.
    
    \returns an associative array of strings and integers.
     */
    protected function _get_short_description(  $in_object  ///< REQUIRED: The object to parse.
                                            ) {
        $ret = Array('id' => $in_object->id());
        
        $name = $in_object->name;
        
        if (isset($name) && trim($name)) {
            $ret ['name'] = $name;
        }
        
        $lang = $in_object->get_lang();

        if (isset($lang) && trim($lang)) {
            $ret ['lang'] = $lang;
        }
        
        // Just to make sure that we're a valid long/lat object
        if (method_exists($in_object, 'longitude')) {
            $latitude = $in_object->latitude();
            $longitude = $in_object->longitude();
        
            if (isset($longitude) && is_float($longitude) && isset($latitude) && is_float($latitude)) {
                $ret['coords'] = sprintf("%f,%f", $latitude, $longitude);
            }
        
            if (isset($in_object->distance)) {
                $ret['distance_in_km'] = floatval($in_object->distance);
            }
        }

        return $ret;
    }
    
    /***********************/
    /**
    This returns a fairly short summary of the given object.
    
    \returns an associative array of strings and integers.
     */
    protected function _get_long_description(   $in_object,                 ///< REQUIRED: The object to parse.
                                                $in_show_parents = false    ///< OPTIONAL: (Default is false). If true, then the parents will be shown. This can be a time-consuming operation, so it needs to be explicitly requested.
                                            ) {
        $ret = $this->_get_short_description($in_object);
        
        // We only return tokens that we already know about. It's entirely possible for a record to have read or write token that is not in our set.
        $my_ids = $in_object->get_access_object()->get_security_ids();
        $read_item = intval($in_object->read_security_id);
        $write_item = intval($in_object->write_security_id);
        
        // Checking for the availability of the Security DB is a quick login check. If not logged in, the DB will not be available.
        if ((((2 > $read_item) || $in_object->get_access_object()->god_mode() || in_array($read_item, $my_ids)) && count($my_ids)) && $in_object->get_access_object()->security_db_available()) {
            $ret['read_token'] = $read_item;
        }
        
        if (((2 > $read_item) || $in_object->get_access_object()->god_mode() || in_array($write_item, $my_ids) && count($my_ids)) && $in_object->get_access_object()->security_db_available()) {
            $ret['write_token'] = $write_item;
        }
        
        if (isset($in_object->last_access)) {
            $ret['last_access'] = date('Y-m-d H:i:s', $in_object->last_access);
        }
        
        if ($in_object->user_can_write()) {
            $ret['writeable'] = true;
        }
        
        if (method_exists($in_object, 'owner_id')) {   // Cheap test to figure out if we can look at these things.
            if (0 < intval($in_object->owner_id())) {
                $ret['owner_id'] = intval($in_object->owner_id());
            }
        }
        
        if (method_exists($in_object, 'longitude') && isset($ret['coords'])) {   // Cheap test to figure out if we can look at these things.
            // We do this little dance, because we want to make sure that our internal long/lat match the ones assigned to coords. If we ask again, we'll get different results (if fuzzy). Otherwise, it's just a wee bit faster.
            $coords = array_map('floatval', explode(',', $ret['coords']));
            $latitude = $coords[0];
            $longitude = $coords[1];
            
            if (isset($longitude) && is_float($longitude) && isset($latitude) && is_float($latitude)) {
                $ret['latitude'] = floatval($latitude);
                $ret['longitude'] = floatval($longitude);
            }
        
            if ($in_object->is_fuzzy()) {
                $ret['fuzzy'] = true;
            
                $cansee = intval($in_object->can_see_through_the_fuzz());
            
                if ($cansee) {
                    $ret['can_see_through_the_fuzz'] = $cansee;
                }
            
                // If this is a fuzzy location, but the logged-in user can see "the real," we show it to them.
                if ($in_object->i_can_see_clearly_now()) {
                    $ret['raw_latitude'] = floatval($in_object->raw_latitude());
                    $ret['raw_longitude'] = floatval($in_object->raw_longitude());
                    $ret['fuzz_factor'] = $in_object->fuzz_factor();
                }
            }
        }
        
        if (method_exists($in_object, 'children')) { 
            $child_objects = $this->_get_child_ids($in_object);
            if (0 < count($child_objects)) {
                $ret['children'] = $this->_get_child_handler_data($in_object);
            }
        }
    
        if ($in_show_parents && method_exists($in_object, 'who_are_my_parents')) {
            $parent_objects = $in_object->who_are_my_parents();
            if (isset($parent_objects) && is_array($parent_objects) && count($parent_objects)) {
                foreach ($parent_objects as $instance) {
                    $class_name = get_class($instance);
            
                    if ($class_name) {
                        $handler = self::_get_handler($class_name);
                        $ret['parents'][$handler][] = $instance->id();
                    }
                }
            }
        }
        
        if (method_exists($in_object, 'get_payload')) {
            $payload = $in_object->get_payload();
        
            if ($payload) {
                $temp_file = tempnam(sys_get_temp_dir(), 'RVP');  
                file_put_contents($temp_file , $payload);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);  
                $content_type = finfo_file($finfo, $temp_file);
                $ret['payload_type'] = $content_type.';base64';
                $ret['payload'] = base64_encode($payload);
            }
        }
        
        return $ret;
    }

    /***********************/
    /**
    This returns the appropriate XML header for our response.
    
    \returns a string, with the entire XML header (including the preamble).
     */
    protected function _get_xml_header() {
        $ret = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xsd_uri = self::_server_url().'/xsd/'.$this->plugin_name();
        $ret .= '<'.$this->plugin_name()." xsi:schemaLocation=\"".self::_server_url()." $xsd_uri\" xmlns=\"".self::_server_url()."\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">";
        
        return $ret;
    }
    
    /***********************/
    /**
    This returns the appropriate XML header for our schema file.
    
    \returns a string, with the entire XML header for the schema file (including the preamble).
     */
    protected function _get_xsd_header() {
        $ret = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xsd_uri = self::_server_url().'/xsd/'.$this->plugin_name();
        $ret .= "<xs:schema xmlns:xs='http://www.w3.org/2001/XMLSchema' xmlns:b='".self::_server_url()."' elementFormDefault='qualified' targetNamespace='".self::_server_url()."'>";
        
        return $ret;
    }
    
    /***********************/
    /**
    This processes the schema for this plugin as XML XSD.
    
    \returns XML, containing the schema for this plugin's responses. The schema needs to be comprehensive.
     */
    protected function _process_xsd(    $in_schema_file_path    ///< REQUIRED: The file path (POSIX) to the schema file to process.
                                    ) {
        $ret = '';
        
        $schema_file = file_get_contents($in_schema_file_path);
        
        if ($schema_file) {
            $ret = $this->_get_xsd_header()."$schema_file</xs:schema>";
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This conditions our response.
    
    \returns the HTTP response string, as either JSON or XML.
     */
    protected function _condition_response( $in_response_type,                          ///< REQUIRED: 'json', 'xml' or 'xsd' -the response type.
                                            $in_response_as_associative_array = NULL    ///< OPTIONAL (but required fon non-XSD): The response to be converted to JSON or XML, as an associative array. If XSD, then this can be an empty array.
                                            ) {
        $ret = '';
        
        if ('xml' == $in_response_type) {
            $header = $this->_get_xml_header();
            $body = self::_array2xml($in_response_as_associative_array);
            $footer = '</'.$this->plugin_name().'>';
            $ret = "$header$body$footer";
        } elseif ('xsd' == $in_response_type) {
            $ret = $this->_get_xsd();
        } else {
            $ret = json_encode(Array($this->plugin_name() => $in_response_as_associative_array));
        }
        return $ret;
    }
    
    /***********************/
    /**
    This checks the provided object to see if it's a collection.
    If so, it queries the collection for its children IDs, and returns them as an array.
    
    \returns an empty array if no children (or the object is not a collection), or an array of integers (each being the Data database ID of a child object).
     */
    protected function _get_child_ids(  $in_object  ///< REQUIRED: This is the object we are testing.
                                    ) {
        $ret = [];
        
        if (method_exists($in_object, 'children') && (0 < $in_object->count())) {
            $ret = $in_object->children_ids();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This returns a list of handler plugins for each of the child IDs of a collection object.
    
    \returns an associative array, with the key being the handler, and each ID being part of a sub-array, under that key. NULL, if the object is not a collection, or has no children.
     */
    protected function _get_child_handler_data( $in_object  ///< REQUIRED: This is the object we are testing.
                                                ) {
        $ret = [];
        
        $id_list = $this->_get_child_ids($in_object);
        $access_object = $in_object->get_access_object();
        
        if (isset($access_object) && is_array($id_list) && count($id_list)) {
            foreach ($id_list as $id) {
                $class_name = $access_object->get_data_access_class_by_id($id);
                if ($class_name) {
                    $handler = self::_get_handler($class_name);
                    $ret[$handler][] = $id;
                }
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
        $ret = [];
        if (isset($in_query) && is_array($in_query)) {
            // See if they want to add new child data items to each user, or remove existing ones.
            // We indicate adding ones via positive integers (the item IDs), and removing via negative integers (minus the item ID).
            if (isset($in_query['child_ids']) && trim($in_query['child_ids'])) {
                $child_item_list = [];          // If we are adding new child items, their IDs go in this list.
                $delete_child_item_list = [];   // If we are removing items, we indicate that with negative IDs, and put those in a different list (absvaled).
            
                $child_id_list = array_map('intval', explode(',', trim($in_query['child_ids'])));
        
                // Child IDs are a CSV list of integers, with IDs of data records.
                if (isset($child_id_list) && is_array($child_id_list) && count($child_id_list)) {
                    // Check for ones we can't see (we don't need write permission, but we do need read permission).
                    foreach ($child_id_list as $id) {
                        if (0 < $id) {  // See if we are adding to the list
                            $item = $in_andisol_instance->get_single_data_record_by_id($id);
                            // If we got the item, then it exists, and we can see it. Add its ID to our list.
                            $child_item_list[] = $id;
                        } else {    // If we are removing it, we still need read permission, but it goes in a different list.
                            $item = $in_andisol_instance->get_single_data_record_by_id(-$id);
                            $delete_child_item_list[] = -$id;
                        }
                    }
            
                    // Make sure there's no repeats.
                    $child_item_list = array_unique($child_item_list);
                    $delete_child_item_list = array_unique($delete_child_item_list);
                
                    // Because we're anal.
                    sort($child_item_list);
                    sort($delete_child_item_list);
                
                    // At this point, we have a list of IDs that we want to add, and IDs that we want to remove, from the various (or single) users.
                }
            
                // If we have items we want to add, we add them to our TO DO list.
                if (isset($child_item_list) && is_array($child_item_list) && count($child_item_list)) {
                    if (!isset($ret['child_ids'])) {
                        $ret['child_ids'] = Array('add' => [], 'remove' => []);
                    }
                    $ret['child_ids']['add'] = $child_item_list;
                }
            
                // If we have items we want to remove, we add those to our TO DO list.
                if (isset($delete_child_item_list) && is_array($delete_child_item_list) && count($delete_child_item_list)) {
                    if (!isset($ret['child_ids'])) {
                        $ret['child_ids'] = Array('add' => [], 'remove' => []);
                    }
                    $ret['child_ids']['remove'] = $delete_child_item_list;
                }
            } elseif (isset($in_query['child_ids']) && !trim($in_query['child_ids'])) {
                $ret['child_ids'] = 'DELETE-ALL';   // Remove everything.
            }
            
            if (isset($in_query['name'])) {
                $ret['name'] = trim($in_query['name']);
            }    
            
            if (isset($in_query['read_token'])) {
                $token = intval($in_query['read_token']);
                if ($in_andisol_instance->i_have_this_token($token)) {
                    $ret['read_token'] = intval($in_query['read_token']);
                }
            }
            
            if (isset($in_query['write_token'])) {
                $token = intval($in_query['write_token']);
                if ($in_andisol_instance->i_have_this_token($token)) {
                    $ret['write_token'] = intval($in_query['write_token']);
                }
            }
            
            if (isset($in_query['longitude'])) {
                $ret['longitude'] = floatval($in_query['longitude']);
            }
            
            if (isset($in_query['latitude'])) {
                $ret['latitude'] = floatval($in_query['latitude']);
            }
            
            if (isset($in_query['fuzz_factor'])) {
                $ret['fuzz_factor'] = floatval($in_query['fuzz_factor']);
            }
            
            if (isset($in_query['can_see_through_the_fuzz'])) {
                $ret['can_see_through_the_fuzz'] = intval($in_query['can_see_through_the_fuzz']);
            }
            
            // Next, we see if we want to change/set the "owner" object asociated with this. You can remove an associated owner object by passing in NULL or 0, here.
            if (isset($in_query['owner_id'])) {
                $ret['owner_id'] = abs(intval(trim($in_query['owner_id'])));
            }
        
            // Next, look for the language.
            if (isset($in_query['lang'])) {
                $ret['lang'] = trim(strval($in_query['lang']));
            }
        
            // Next, we see if we the user is supplying a payload to be stored, or removing the existing one.
            if (isset($in_query['remove_payload'])) { // If they did not specify a payload, maybe they want one removed?
                $ret['remove_payload'] = true;
            } elseif (isset($in_query['payload'])) {
                // See if the payload is already base64.
                if (base64_encode(base64_decode($in_query['payload'])) == $in_query['payload']) {
                    $in_query['payload'] = base64_decode($in_query['payload']);
                }
                $ret['payload'] = $in_query['payload'];
            }
        }
        
        return $ret;
    }
    
    /************************************************************************************************************************/    
    /*#################################################### ABSTRACT METHODS ################################################*/
    /************************************************************************************************************************/
    /***********************/
    /**
    This returns the schema for this plugin as XML XSD.
    
    \returns XML, containing the schema for this plugin's responses. The schema needs to be comprehensive.
     */
    abstract protected function _get_xsd();
        
    /***********************/
    /**
    This returns an array of classnames, handled by this plugin.
    
    \returns an array of string, with the names of the classes handled by this plugin.
     */
    abstract static public function classes_managed();
        
    /***********************/
    /**
    This returns our plugin name.
    
    \returns a string, with our plugin name.
     */
    abstract public function plugin_name();
    
    /***********************/
    /**
    This runs our plugin command.
    
    \returns the HTTP response string, as either JSON or XML.
     */
    abstract public function process_command(   $in_andisol_instance,   ///< REQUIRED: The ANDISOL instance to use as the connection to the RVP databases.
                                                $in_http_method,        ///< REQUIRED: 'GET', 'POST', 'PUT' or 'DELETE'
                                                $in_response_type,      ///< REQUIRED: 'json', 'xml' or 'xsd' -the response type.
                                                $in_path = [],          ///< OPTIONAL: The REST path, as an array of strings. For the baseline, this should be exactly one element.
                                                $in_query = []          ///< OPTIONAL: The query parameters, as an associative array.
                                            );
}