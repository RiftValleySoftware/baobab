<?php
/***************************************************************************************************************************/
/**
    ANDISOL Object Model Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC.
    
    This code is proprietary and confidential code, 
    It is NOT to be reused or combined into any application,
    unless done so, specifically under written license from Little Green Viper Software Development LLC.

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_ANDISOL_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

define('__ANDISOL_VERSION__', '1.0.0.2007');

if (!defined('LGV_ACCESS_CATCHER')) {
    define('LGV_ACCESS_CATCHER', 1);
}

require_once(CO_Config::cobra_main_class_dir().'/co_cobra.class.php');

if ( !defined('LGV_LANG_CATCHER') ) {
    define('LGV_LANG_CATCHER', 1);
}

require_once(CO_Config::andisol_lang_class_dir().'/common.inc.php');

/****************************************************************************************************************************/
/**
This class is the principal Model layer interface for the Rift Valley Platform. You instantiate an instance of this class, and
it, in turn, will create an instance of CHAMELEON/BADGER, and, possibly, an instance of COBRA. That establishes a connection
to the lower level data storage and security infrastructure.

You are to use this class for ALL access to the lower level functionality.
 */
class CO_Andisol {
    protected $_chameleon_instance = NULL;  ///< This is the CHAMELEON instance.
    protected $_cobra_instance = NULL;      ///< This is the COBRA instance.
    
    var $version;                           ///< The version indicator.
    var $error;                             ///< Any errors that occured are kept here.
    
    /************************************************************************************************************************/    
    /*#################################################### INTERNAL METHODS ################################################*/
    /************************************************************************************************************************/    
    
    /***********************/
    /**
    This creates an uninitialized object, based upon the passed-in class.
    
    \returns a new instance of the class.
     */
    protected function _create_db_object(   $in_classname,                  ///< REQUIRED: A classname to use.
                                            $in_read_security_id = 1,       ///< OPTIONAL: An initial read security ID. If not specified, 1 (open to all logged-in users) will be specified.
                                            $in_write_security_id = NULL    ///< OPTIONAL: An initial write security ID. If not specified, the current user's integer login ID will be used as the write security token.
                                        ) {
        $ret = NULL;
        if ($this->logged_in()) {
            $instance = $this->get_chameleon_instance()->make_new_blank_record($in_classname);

            if (isset($instance) && ($instance instanceof $in_classname)) {
                // If we did not get a specific read security ID, then we assume 1.
                if (!isset($in_read_security_id)) {
                    $in_read_security_id = 1;
                }
            
                // If we did not get a specific write security ID, then we assume the logged-in user ID.
                if (!isset($in_write_security_id) || !intval($in_write_security_id)) {
                    $in_write_security_id = $this->get_chameleon_instance()->get_login_id();
                }
                
                if ($instance->set_read_security_id($in_read_security_id)) {
                    if ($instance->set_write_security_id($in_write_security_id)) {
                        $ret = $instance;
                    } elseif (isset($instance) && ($instance instanceof A_CO_DB_Table_Base)) {
                        if ($instance->error) {
                            $this->error = $instance->error;
                        }
        
                        $instance->delete_from_db();
                    }
                } elseif (isset($instance) && ($instance instanceof A_CO_DB_Table_Base)) {
                    if ($instance->error) {
                        $this->error = $instance->error;
                    }
    
                    $instance->delete_from_db();
                }
            } elseif (isset($instance) && ($instance instanceof A_CO_DB_Table_Base)) {
                if ($instance->error) {
                    $this->error = $instance->error;
                }
                
                $instance->delete_from_db();
            }
        } else {
            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_name_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This creates a new security token.
    Only managers or God can do this.
    
    \returns an integer, with the new token. NULL, if this did not work.
     */
    protected function _make_security_token() {
        $ret = NULL;
        
        if ($this->manager()) {
            $ret = $this->get_cobra_instance()->make_security_token();
            if (0 == $ret) {
                $ret = NULL;
            }
        } else {
            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_name_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
        
    /************************************************************************************************************************/    
    /***********************/
    /**
    The constructor.
    
    Default for parameters is NULL, but not supplying them will result in a non-logged-in instance of ANDISOL.
     */
	public function __construct(    $in_login_string_id = NULL, ///< OPTIONAL: The String login ID
                                    $in_hashed_password = NULL, ///< OPTIONAL: The password, crypt-hashed
                                    $in_raw_password = NULL,    ///< The password, cleartext.
                                    $in_api_key = NULL          ///< An API key, for REST.
	                            ) {
        $this->class_description = 'The main model interface class.';
	    $this->version = __ANDISOL_VERSION__;
	    $this->error = NULL;
	    
	    // The first thing we do, is set up any login instance, as well as any possible COBRA instance.
        $chameleon_instance = new CO_Chameleon($in_login_string_id, $in_hashed_password, $in_raw_password, $in_api_key);
        if (isset($chameleon_instance) && ($chameleon_instance instanceof CO_Chameleon)) {
            if ($chameleon_instance->valid) {
                $this->_chameleon_instance = $chameleon_instance;
                
                $login_item = $chameleon_instance->get_login_item();
                
                // COBRA requires a manager (or God).
                if (isset($login_item) && ($chameleon_instance->god_mode() || ($login_item instanceof CO_Login_Manager))) {
                    $cobra_instance = CO_Cobra::make_cobra($chameleon_instance);
        
                    if (isset($cobra_instance) && ($cobra_instance instanceof CO_Cobra)) {
                        $this->_cobra_instance = $cobra_instance;
                    } elseif (isset($cobra_instance) && ($cobra_instance->error instanceof LGV_Error)) {
                        $this->error = $cobra_instance->error;
                    }
                }
            } elseif (isset($chameleon_instance) && ($chameleon_instance->error instanceof LGV_Error)) {
                $this->error = $chameleon_instance->error;
            }
        }
        
        // At this point, we have (or have not) logged in, and any infrastructure for logged-in operations is in place.
    }
    
    /************************************************************************************************************************/    
    /*############################################### COMPONENT ACCESS METHODS #############################################*/
    /************************************************************************************************************************/    

    /***********************/
    /**
    \returns The COBRA instance. It will be NULL if the current login is not a manager or God.
     */
    public function get_cobra_instance() {
        return $this->_cobra_instance;
    }
    
    /***********************/
    /**
    \returns The CHAMELEON instance.
     */
    public function get_chameleon_instance() {
        return $this->_chameleon_instance;
    }
    
    /************************************************************************************************************************/    
    /*############################################# BASIC LOGIN STATUS QUERIES #############################################*/
    /************************************************************************************************************************/    

    /***********************/
    /**
    \returns true, if we have an active database connection (as represented by an active CHAMELEON instance).
     */
    public function valid() {
        return (NULL != $this->get_chameleon_instance()) && ($this->get_chameleon_instance() instanceof CO_Chameleon);
    }
    
    /***********************/
    /**
    \returns true, if we have actually logged into the CHAMELEON instance.
     */
    public function logged_in() {
        return $this->valid() && ($this->get_chameleon_instance()->get_login_item() instanceof CO_Security_Login);
    }
    
    /***********************/
    /**
    \returns true, if we are logged in as a COBRA Login Manager or as God.
     */
    public function manager() {
        return $this->logged_in() && ($this->get_cobra_instance() instanceof CO_Cobra);
    }
    
    /***********************/
    /**
    \returns true, if we are logged in as the "God" admin ID.
     */
    public function god() {
        return $this->manager() && $this->get_chameleon_instance()->god_mode();
    }
    
    /***********************/
    /**
    \returns The current login Item. NULL if no login.
     */
    public function current_login() {
        return $this->get_login_item();
    }
    
    /***********************/
    /**
    \returns The current user Item. NULL, if no user for the current login.
     */
    public function current_user() {
        return $this->get_user_from_login();
    }
    
    /***********************/
    /**
    Tests a token, to see if the current user has it.
    
    \returns true, if the current user has the given token.
     */
    public function i_have_this_token(  $in_token_to_test   ///< The token we are checking out
                                    ) {
        return $this->get_chameleon_instance()->i_have_this_token($in_token_to_test);
    }
    
    /************************************************************************************************************************/    
    /*################################################# USER ACCESS METHODS ################################################*/
    /************************************************************************************************************************/    

    /***********************/
    /**
    This returns the actual security DB login item for the requested user (or the current logged-in user).
    
    The response is subject to standard security vetting, so there is a possibility that nothing will be returned, when there is an existing login at that ID.
    
    \returns the instance for the requested user. NULL, if not logged in.
     */
    public function get_login_item( $in_login_integer_id = NULL ///< OPTIONAL: The integer login ID to check (Default is NULL). If not-NULL, then the ID of a login instance. It must be one that the current user can see. If NULL, then the current user.
                                    ) {
        $ret = NULL;
        
        if ($this->logged_in()) {
            $ret = $this->get_chameleon_instance()->get_login_item($in_login_integer_id);
            $this->error = $this->get_chameleon_instance()->error;
        }
        
        return $ret;
    }

    /***********************/
    /**
    This returns the actual security DB login item for the requested user (or the current logged-in user).
    
    The response is subject to standard security vetting, so there is a possibility that nothing will be returned, when there is an existing login at that ID.
    
    \returns the instance for the requested user. NULL, if not logged in.
     */
    public function get_login_item_by_login_string( $in_login_string_id ///< REQUIRED: The string login ID to check. It must be one that the current user can see.
                                                    ) {
        $ret = NULL;
        
        if ($this->logged_in()) {
            $ret = $this->get_chameleon_instance()->get_login_item_by_login_string($in_login_string_id);
        
            $this->error = $this->get_chameleon_instance()->error;
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    \returns the user collection object for a given login string. If there is no login given, then the current login is assumed. This is subject to security restrictions.
     */
    public function get_user_from_login_string( $in_login_string_id ///< REQUIRED: The string login ID that is associated with the user collection.   
                                                ) {
        $ret = NULL;
        
        $login_item = $this->get_login_item_by_login_string($in_login_string_id);
        
        if (isset($login_item) && ($login_item instanceof CO_Security_Login)) {
            $ret = $this->get_user_from_login($login_item->id());
        }
        
        return $ret;
    }

    /***********************/
    /**
    Test an item to see which logins can access it.
    
    This is security-limited.
    
    \returns an array of instances of CO_Security_Login (Security Database login) items that can read/see the given item. If the read ID is 0 (open), then the function simply returns true. If nothing can see the item, then false is returned.
     */
    public function who_can_see(    $in_test_target ///< REQUIRED: This is an instance of a subclass of A_CO_DB_Table_Base (General Database Record).
                                ) {
        $ret = Array();
        
        if ($this->manager()) { // Don't even bother unless we're a manager.
            $ret = $this->get_cobra_instance()->who_can_see($in_test_target);
        
            $this->error = $this->get_cobra_instance()->error;
        } else {
            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_name_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This returns the access class for the given ID for the Data Database.
    
    This is "security safe," so that means that if the user does not have rights to the row, they will get NULL.
    
    \returns a string, containing the access_class data column. NULL, if no response (like the ID does not exist, or the user does not have read rights to it).
     */
    public function get_data_access_class_by_id( $in_id  ///< This is the ID of the record to fetch.
                                                ) {
        $ret = NULL;
        
        if ($this->get_chameleon_instance()->can_i_see_this_data_record($in_id)) {
            $ret = $this->get_chameleon_instance()->get_data_access_class_by_id($in_id);
            $this->error = $this->get_chameleon_instance()->error;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This returns the access class for the given ID for the Security Database.
    
    This is "security safe," so that means that if the user does not have rights to the row, they will get NULL.
    
    \returns a string, containing the access_class data column. NULL, if no response (like the ID does not exist, or the user does not have read rights to it).
     */
    public function get_security_access_class_by_id(    $in_id  ///< This is the ID of the record to fetch.
                                                    ) {
        $ret = $this->$this->get_chameleon_instance()->get_security_access_class_by_id($in_id);
        $this->error = $this->get_chameleon_instance()->error;
        
        return $ret;
    }
    
    /************************************************************************************************************************/    
    /*################################################## DATA SEARCH METHODS ###############################################*/
    /************************************************************************************************************************/    

    /***********************/
    /**
    This is a "generic" data search. It can be called from external user contexts, and allows a fairly generalized search of the "data" datasource.
    Sorting will be done for the "owner" and "location" values. "owner" will be sorted by the ID of the returned records, and "location" will be by distance from the center.
    
    String searches are always case-insensitive.
    
    All parameters are optional, but calling this "blank" will return the entire data databse (that is visible to the user).
                                                                            
    Here are a few examples:
    
    \code{.php}
            // Search for records with long/lat set within a 10Km radius circle (centered on Tysons Corner, VA):
            $returned_array = $andisol_instance->generic_search(    // The first parameter is an associative array of main search keys and values:
                                                                    Array(  // This is the requested location:
                                                                            'location' => Array('longitude' => -77.2311,
                                                                                                'latitude' => 38.9187,
                                                                                                'radius' => 10
                                                                                                )
                                                                        )
                                                                );
    \endcode
    
    \code{.php}
            // Search for records with an access_class of CO_User_Collection:
            $returned_array = $andisol_instance->generic_search(    // The first parameter is an associative array of main search keys and values:
                                                                    Array(  // This is the requested class:
                                                                            'access_class' => 'CO_User_Collection'
                                                                        )
                                                                );
    \endcode
    
    \code{.php}
            // Search for records with an access_class of CO_User_Collection, but this time, specify a wildcard, so you also get subclasses:
            $returned_array = $andisol_instance->generic_search(    // The first parameter is an associative array of main search keys and values:
                                                                    Array(  // This is the requested class:
                                                                            'access_class' => '%_User_Collection',
                                                                            'use_like' => 1
                                                                        )
                                                                );
    \endcode
    
    \code{.php}
            // Search for records with an object_name of "Back to Basics", and a class of CO_US_Place_Collection.
            $returned_array = $andisol_instance->generic_search(    // The first parameter is an associative array of main search keys and values:
                                                                    Array(  // This is the requested class:
                                                                            'access_class' => 'CO_US_Place_Collection',
                                                                            'Back to Basics'
                                                                        )
                                                                );
    \endcode
    
    \code{.php}
            // Search for records with an object_name of "Back to Basics", and a class of CO_US_Place_Collection. However, this time, we make it an OR search.
            $returned_array = $andisol_instance->generic_search(    // The first parameter is an associative array of main search keys and values:
                                                                    Array(  // This is the requested class:
                                                                            'access_class' => 'CO_US_Place_Collection',
                                                                            'Back to Basics'
                                                                        ),
                                                                    true
                                                                );
    \endcode
    
    \code{.php}
            // Search for records with an object_name of "Back to Basics", and a class of CO_US_Place_Collection. However, this time, we make it an OR search, and only the second page, with pages of ten.
            $returned_array = $andisol_instance->generic_search(    // The first parameter is an associative array of main search keys and values:
                                                                    Array(  // This is the requested class:
                                                                            'access_class' => 'CO_US_Place_Collection',
                                                                            'Back to Basics'
                                                                        ),
                                                                    true,
                                                                    10,
                                                                    1
                                                                );
    \endcode
    
    \returns an array of instances (or integers, if $ids_only is true) that match the search parameters. If $count_only is true, then it will be a single integer, with the count of responses to the search (if a page, then this count will only be the number of items on that page).
     */
    public function generic_search( $in_search_parameters = NULL,   /**<    OPTIONAL: This is an associative array of terms to define the search. The keys should be:
                                                                            - 'id'
                                                                                This should be accompanied by an array of one or more integers, representing specific item IDs.
                                                                            - 'access_class'
                                                                                This should be accompanied by an array, containing one or more PHP class names.
                                                                            - 'name'
                                                                                This will contain a case-insensitive array of strings to check against the object_name column.
                                                                            - 'owner'
                                                                                This should be accompanied by an array of one or more integers, representing specific item IDs for "owner" objects.
                                                                            - 'tags'
                                                                                This should be accompanied by an array (up to 10 elements) of one or more case-insensitive strings, representing specific tag values.
                                                                                The position in the array denotes which tag to match, so unchecked tags should still be in the array, but empty. You don't match empty tags.
                                                                                You can specify an array for the values, which allows you to do an OR search for the values.
                                                                            - 'location'
                                                                                This is only relevant if we are searching for subclasses (or instances) of CO_LL_Location
                                                                                This requires that the parameter be a 3-element associative array of floating-point numbers:
                                                                                    - 'longitude'
                                                                                        This is the search center location longitude, in degrees.
                                                                                    - 'latitude'
                                                                                        This is the search center location latitude, in degrees.
                                                                                    - 'radius'
                                                                                        This is the search radius, in Kilometers.
    
                                                                            You can specify an array for any one of the values, which allows you to do an OR search for those values ($or_search does not apply. It is only for the combination of main values).
                                                                            If you add an element called 'use_like' ('use_like' => 1) to the end of 'access_class', 'name' or one of the 'tags', then you can use SQL-style "wildcards" (%) in your matches.
                                                                            If you have 'use_like', and put just a single wildcard in quotes ('%'), then you are saying "not-empty."
                                                                            NOTE: Although this is an optional parameter, failing to provide anything could return the entire readable database.
                                                                    */
                                    $or_search = false,             ///< OPTIONAL: If true, then the search is very wide (OR), as opposed to narrow (AND), by default. If you specify a location, then that will always be AND, but the other fields can be OR. Tags will always be searched as OR.
                                    $page_size = 0,                 ///< OPTIONAL: If specified with a 1-based integer, this denotes the size of a "page" of results. NOTE: This is only applicable to MySQL or Postgres, and will be ignored if the DB is not MySQL or Postgres. Default is 0.
                                    $initial_page = 0,              ///< OPTIONAL: This is ignored unless $page_size is greater than 0. In that case, this 0-based index will specify which page of results to return. Values beyond the maximum number of pages will result in no returned values.
                                    $and_writeable = false,         ///< OPTIONAL: If true, then we only want records we can modify.
                                    $count_only = false,            ///< OPTIONAL: If true (default is false), then only a single integer will be returned, with the count of items that fit the search.
                                    $ids_only = false               ///< OPTIONAL: If true (default is false), then the return array will consist only of integers (the object IDs). If $count_only is true, this is ignored.
                                    ) {
        $ret = $this->get_chameleon_instance()->generic_search($in_search_parameters, $or_search, $page_size, $initial_page, $and_writeable, $count_only, $ids_only);
        
        $this->error = $this->get_chameleon_instance()->error;
        
        return $ret;
    }
    
    /***********************/
    /**
    This is a search, based on a location and radius around that location.
    Only objects that have a longitude and latitude that fall within the radius will be returned.
    All visible classes and instances will be returned. Only location and security filtering are applied.
    
    \returns an array of instances (or integers, if $ids_only is true) that fit within the location center and radius. If $count_only is true, then it will be a single integer, with the count of responses to the search (if a page, then this count will only be the number of items on that page).
     */
    public function location_search(    $in_longitude_degrees,  ///< REQUIRED: The latitude of the center, in degrees.
                                        $in_latitude_degrees,   ///< REQUIRED: The logitude of the center, in degrees.
                                        $in_radius_kilometers,  ///< REQUIRED: The search radius, in Kilomters.
                                        $page_size = 0,         ///< OPTIONAL: If specified with a 1-based integer, this denotes the size of a "page" of results. NOTE: This is only applicable to MySQL or Postgres, and will be ignored if the DB is not MySQL or Postgres. Default is 0.
                                        $initial_page = 0,      ///< OPTIONAL: This is ignored unless $page_size is greater than 0. If so, then this 0-based index will specify which page of results to return. Values beyond the maximum number of pages will result in no returned values.
                                        $and_writeable = false, ///< OPTIONAL: If true, then we only want records we can modify.
                                        $count_only = false,    ///< OPTIONAL: If true (default is false), then only a single integer will be returned, with the count of items that fit the search.
                                        $ids_only = false       ///< OPTIONAL: If true (default is false), then the return array will consist only of integers (the object IDs). If $count_only is true, this is ignored.
                                    ) {
        $ret = $this->generic_search(Array('location' => Array('longitude' => $in_longitude_degrees, 'latitude' => $in_latitude_degrees, 'radius' => $in_radius_kilometers)), false, $page_size, $initial_page, $and_writeable, $count_only, $ids_only);
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of instances of all the users (not logins) that are visible to the current login. It should be noted that this can return standalone users.
     */
    public function get_all_users(  $and_write = false  ///< OPTIONAL: If true (Default is false), then we only want ones we have write access to.
                                    ) {
        $ret = Array();
        
        $temp = $this->generic_search(Array('access_class' => Array('%_User_Collection', 'use_like' => 1)), false, 0, 0, $and_write);
        
        if (isset($temp) && is_array($temp) && count($temp)) {
            // We make sure that we don't return the God user, if there is one (unless we are God).
            foreach ($temp as $user) {
                $login_instance = $user->get_login_instance();
                if ($this->god() || !$user->is_god()) {
                    array_push($ret, $user);
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of instances of all the users (not logins) that are visible to the current login, and that have logins, themselves. It should be noted that this will not return standalone users.
     */
    public function get_all_login_users(    $and_write = false  ///< OPTIONAL: If true (Default is false), then we only want ones we have write access to.
                                        ) {
        $ret = Array();
        
        $temp = $this->generic_search(Array('access_class' => Array('%_User_Collection', 'use_like' => 1), 'tags' => Array('%', 'use_like' => 1)), false, 0, 0, $and_write);
        
        if (isset($temp) && is_array($temp) && count($temp)) {
            // We make sure that we don't return the God user, if there is one (unless we are God).
            foreach ($temp as $user) {
                $login_instance = $user->get_login_instance();
                if (isset($login_instance) && ($login_instance instanceof CO_Security_Login)) {
                    if ($this->god() || !$user->is_god()) {
                        array_push($ret, $user);
                    }
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of instances of all the users (not logins) that are visible to the current login. It should be noted that this can return standalone users.
     */
    public function get_all_standalone_users(   $and_write = false  ///< OPTIONAL: If true (Default is false), then we only want ones we have write access to.
                                            ) {
        $ret = Array();
        
        $temp = $this->generic_search(Array('access_class' => Array('%_User_Collection', 'use_like' => 1), 'tags' => Array('')), false, 0, 0, $and_write);
        
        if (isset($temp) && is_array($temp) && count($temp)) {
            // We make sure that we don't return the God user, if there is one (unless we are God).
            foreach ($temp as $user) {
                $login_instance = $user->get_login_instance();
                if ($this->god() || !$user->is_god()) {
                    array_push($ret, $user);
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of instances (or integers, if $ids_only is true) that match the requested tag values. If $count_only is true, then it will be a single integer, with the count of responses to the search (if a page, then this count will only be the number of items on that page).
     */
    public function tag_search( $in_tags_associative_array, /**< REQUIRED:  This is an associative array, with the keys being "0" through "9". Each element will have a requested value for that element.
                                                                            Leaving an element out will remove it as a search factor. Adding it, but leaving it NULL or blank, means that this tag MUST be null or blank.
                                                                            If you add an element called 'use_like' ('use_like' => 1) to the array, then you can use SQL-style "wildcards" (%) in your matches.
                                                                            Unless $in_or_search is set to true, the search will be an AND search; meaning that ALL the tag values must match, in order to result in a record being returned.
                                                            */
                                $in_or_search = false,      ///< OPTIONAL: If true (Default is false), then the search will be an "OR" search (any of the values).
                                $page_size = 0,             ///< OPTIONAL: If specified with a 1-based integer, this denotes the size of a "page" of results. NOTE: This is only applicable to MySQL or Postgres, and will be ignored if the DB is not MySQL or Postgres. Default is 0.
                                $initial_page = 0,          ///< OPTIONAL: This is ignored unless $page_size is greater than 0. If so, then this 0-based index will specify which page of results to return. Values beyond the maximum number of pages will result in no returned values.
                                $and_writeable = false,     ///< OPTIONAL: If true, then we only want records we can modify.
                                $count_only = false,        ///< OPTIONAL: If true (default is false), then only a single integer will be returned, with the count of items that fit the search.
                                $ids_only = false           ///< OPTIONAL: If true (default is false), then the return array will consist only of integers (the object IDs). If $count_only is true, this is ignored.
                            ) {
        $tags_array = Array();
        $ret = Array();
        
        return $ret;
    }
        
    /************************************************************************************************************************/    
    /*################################################ USER MANAGEMENT METHODS #############################################*/
    /************************************************************************************************************************/
        
    /***********************/
    /**
    This is a special function for returning the user for a login, with the possibility of creating one, if one was not already in place.
    In order to potentially create a user, the current login must be a manager, $in_make_user_if_necessary must be true, and the user must not already exist (even if the current login cannot see that user).
     
    \returns the user collection object for a given login. If there is no login given, then the current login is assumed. This is subject to security restrictions.
     */
    public function get_user_from_login(    $in_login_integer_id = NULL,        ///< OPTIONAL: The integer login ID that is associated with the user collection. If NULL (Default), then the current login is used.
                                            $in_make_user_if_necessary = false  ///< OPTIONAL: If true (Default is false), then the user will be created if it does not already exist. Ignored, if we are not a Login Manager.
                                        ) {
        $ret = NULL;
        
        if ($in_make_user_if_necessary && $this->manager()) {   // See if we are a manager, and they want to maybe create a new user.
            $ret = $this->get_cobra_instance()->get_user_from_login($in_login_integer_id, $in_make_user_if_necessary);
        
            $this->error = isset($this->get_cobra_instance()->error) ? $this->get_cobra_instance()->error : NULL;
        } else {
            $ret = $this->get_chameleon_instance()->get_user_from_login($in_login_integer_id);
        
            $this->error = isset($this->get_chameleon_instance()->error) ? $this->get_chameleon_instance()->error : NULL;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Creates a new "standalone" user that has no associated login instance.
    
    \returns the new user record.
     */
    public function make_standalone_user() {
        $ret = NULL;
        
        if ($this->manager()) { // Don't even bother unless we're a manager.
            $ret = $login_item = $this->get_cobra_instance()->make_standalone_user();
            
            if (isset($ret) && ($ret instanceof CO_User_Collection)) {
                $generic_name = sprintf(CO_ANDISOL_Lang::$andisol_new_unnamed_user_name_format, $ret->id());
                $ret->set_name($generic_name);
            }
        } else {
            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_name_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This method can only be called if the user is logged in as a Login Manager (or God).
    This creates a new login and user collection.
    Upon successful completion, both a new login, and a user collection, based upon that login, now exist.
    If there was an error, the user and login are deleted. It should be noted that, if the login was created, it is not really deleted, and is, instead, turned into a security token object.
    
    \returns a string, with the login password as cleartext (If an acceptable-length password is supplied in $in_password, that that is returned). If the operation failed, then NULL is returned.
     */
    public function create_new_user(    $in_login_string_id,            ///< REQUIRED: The string login ID. It must be unique in the Security DB.
                                        $in_password = NULL,            ///< OPTIONAL: A new password. It must be at least as long as the minimum password length. If not supplied, an auto-generated password is created and returned as the function return. If too short, then an auto-generated password is created.
                                        $in_display_name = NULL,        ///< OPTIONAL: A string, representing the basic "display name" to be associated with the login and user collection. If not supplied, the $in_login_string_id is used.
                                        $in_security_tokens = NULL,     ///< Any additional security tokens to apply to the new login. These must be a subset of the security tokens available to the logged-in manager. The God admin can set any tokens they want.
                                        $in_read_security_id = NULL,    ///< An optional read security ID. If not supplied, then ID 1 (logged-in users) is set. The write security ID is always set to the ID of the login.
                                        $is_manager = false             ///< If true (default is false), then the new user will be a CO_Login_Manager object.
                                    ) {
        $ret = NULL;
        
        if ($in_login_string_id) {
            if ($this->manager()) { // Don't even bother unless we're a manager.
                $login_item = NULL;
                
                // See if we need to auto-generate a password.
                if (!$in_password || (strlen($in_password) < CO_Config::$min_pw_len)) {
                    $in_password = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*~_-=+;:,.!?"), 0, CO_Config::$min_pw_len);
                }
            
                if ($is_manager) {  // See if they want to create a manager, or a standard login.
                    $login_item = $this->get_cobra_instance()->create_new_manager_login($in_login_string_id, $in_password, $in_security_tokens);
                } else {
                    $login_item = $this->get_cobra_instance()->create_new_standard_login($in_login_string_id, $in_password, $in_security_tokens);
                }
                // Make sure we got what we expect.
                if ($login_item instanceof CO_Security_Login) {
                    // Next, set the display name.
                    $display_name = $in_display_name;
                    if (!$display_name) {
                        $display_name = $in_login_string_id;
                    }
                    
                    // Set the display name.
                    if ($login_item->set_name($display_name)) {
                        // Assuming all that went well, now we create the user item.
                        $id = $login_item->id();
                        $user_item = $this->get_cobra_instance()->get_user_from_login($id, true);
                        
                        if (isset($in_read_security_id) && intval($in_read_security_id)) {
                            if (!$user_item->set_read_security_id(intval($in_read_security_id))) {
                                $user_item->delete_from_db();
                                $login_item->delete_from_db();
                                $user_item = NULL;
                                $login_item = NULL;
                                $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_name_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_desc_login_instance_failed_to_initialize);
                            } elseif (!$login_item->set_read_security_id($login_item->id())) {
                                $user_item->delete_from_db();
                                $login_item->delete_from_db();
                                $user_item = NULL;
                                $login_item = NULL;
                                $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_name_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_desc_login_instance_failed_to_initialize);
                            }
                        } else {    // By default, users are any-login-readable, and logins are login-user-only readable, and neither can be written except by the login.
                            if (!$user_item->set_read_security_id(1)) {
                                $user_item->delete_from_db();
                                $login_item->delete_from_db();
                                $user_item = NULL;
                                $login_item = NULL;
                                $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_name_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_desc_login_instance_failed_to_initialize);
                            } elseif (!$login_item->set_read_security_id($login_item->id())) {
                                $user_item->delete_from_db();
                                $login_item->delete_from_db();
                                $user_item = NULL;
                                $login_item = NULL;
                                $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_name_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_desc_login_instance_failed_to_initialize);
                            }
                        }
                        
                        if ($user_item instanceof CO_User_Collection) {
                            if ($user_item->set_name($display_name)) {
                                if ($login_item->set_password_from_cleartext($in_password)) {
                                    $ret = $in_password;
                                } else {
                                    $user_item->delete_from_db();
                                    $login_item->delete_from_db();
                                    $user_item = NULL;
                                    $login_item = NULL;
                                    $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_login_instance_failed_to_initialize,
                                                                    CO_ANDISOL_Lang::$andisol_error_name_login_instance_failed_to_initialize,
                                                                    CO_ANDISOL_Lang::$andisol_error_desc_login_instance_failed_to_initialize);
                                }
                            } else {
                                $user_item->delete_from_db();
                                $login_item->delete_from_db();
                                $user_item = NULL;
                                $login_item = NULL;
                                $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_name_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_desc_login_instance_failed_to_initialize);
                            }
                        } else {
                            $this->error = $this->get_cobra_instance()->error;
                    
                            // Just in case something was created.
                            if (isset($user_item) && ($user_item instanceof A_CO_DB_Table_Base)) {
                                $user_item->delete_from_db();
                            }
                            
                            $user_item = NULL;
                            
                            $login_item->delete_from_db();
                            $login_item = NULL;
                            if (!$this->error) {
                                $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_name_login_instance_failed_to_initialize,
                                                                CO_ANDISOL_Lang::$andisol_error_desc_login_instance_failed_to_initialize);
                            }
                        }
                    } else {
                        $this->error = $this->get_cobra_instance()->error;
                        $login_item->delete_from_db();
                        $login_item = NULL;
                        if (!$this->error) {
                            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_login_instance_failed_to_initialize,
                                                            CO_ANDISOL_Lang::$andisol_error_name_login_instance_failed_to_initialize,
                                                            CO_ANDISOL_Lang::$andisol_error_desc_login_instance_failed_to_initialize);
                        }
                    }
                    
                } else {
                    $this->error = $this->get_cobra_instance()->error;
                    
                    // Just in case something was created.
                    if (isset($login_item) && ($login_item instanceof A_CO_DB_Table_Base)) {
                        $login_item->delete_from_db();
                    }
                    
                    $login_item = NULL;
                    
                    if (!$this->error) {
                        $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_login_instance_failed_to_initialize,
                                                        CO_ANDISOL_Lang::$andisol_error_name_login_instance_failed_to_initialize,
                                                        CO_ANDISOL_Lang::$andisol_error_desc_login_instance_failed_to_initialize);
                    }
                }
            } else {
                $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_authorized,
                                                CO_ANDISOL_Lang::$andisol_error_name_user_not_authorized,
                                                CO_ANDISOL_Lang::$andisol_error_desc_user_not_authorized);
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This method can only be called if the user is logged in as a Login Manager (or God).
    This will delete both the login and the user collection for the given login ID.
    It should be noted that deleting a collection does not delete the data associated with that collection, unless $with_extreme_prejudice is true, and even then, only the records this manager can see will be deleted.
    
    \returns true, if the operation was successful.
     */
    public function delete_user(    $in_login_string_id,            ///< REQUIRED: The string login ID of the user to delete.
                                    $with_extreme_prejudice = false ///< OPTIONAL: If true (Default is false), then the manager will delete as many of the user data points as possible (It may not be possible for the manager to delete all data, unless the manager is God).
                                ) {
        $ret = false;
        
        if ($in_login_string_id) {
            if ($this->manager()) { // Don't even bother unless we're a manager.
                // First, fetch the login item.
                $login_item = $this->get_cobra_instance()->get_login_instance($in_login_string_id);
                if ($login_item) {
                    // Next, get the user item.
                    $user_item = $this->get_cobra_instance()->get_user_from_login($login_item->id());
                    if ($user_item) {
                        // We have to have both the login and the user. Now, we make sure that we have write perms on both.
                        if ($login_item->user_can_write() && $user_item->user_can_write()) {
                            if ($user_item->delete_from_db($with_extreme_prejudice, true)) {
                                $ret = true;
                            } else {
                                $this->error = $user_item->error;
                                if (!$this->error) {
                                    $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_deleted,
                                                                    CO_ANDISOL_Lang::$andisol_error_name_user_not_deleted,
                                                                    CO_ANDISOL_Lang::$andisol_error_desc_user_not_deleted);
                                }
                            }
                        } else {
                            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_authorized,
                                                            CO_ANDISOL_Lang::$andisol_error_name_user_not_authorized,
                                                            CO_ANDISOL_Lang::$andisol_error_desc_user_not_authorized);
                        }
                    } else {
                        $this->error = $this->get_cobra_instance()->error;
                        if (!$this->error) {
                            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_instance_unavailable,
                                                            CO_ANDISOL_Lang::$andisol_error_name_user_instance_unavailable,
                                                            CO_ANDISOL_Lang::$andisol_error_desc_user_instance_unavailable);
                        }
                    }
                } else {
                    $this->error = $this->get_cobra_instance()->error;
                    if (!$this->error) {
                        $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_login_instance_unavailable,
                                                        CO_ANDISOL_Lang::$andisol_error_name_login_instance_unavailable,
                                                        CO_ANDISOL_Lang::$andisol_error_desc_login_instance_unavailable);
                    }
                }
            } else {
                $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_authorized,
                                                CO_ANDISOL_Lang::$andisol_error_name_user_not_authorized,
                                                CO_ANDISOL_Lang::$andisol_error_desc_user_not_authorized);
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of instances of all the logins that are visible to the current login (or a supplied login, if in "God" mode). The user must be a manager.
     */
    public function get_all_logins( $and_write = false,         ///< OPTIONAL: If true (Default is false), then we only want ones we have write access to.
                                    $in_login_string_id = NULL, ///< OPTIONAL: This is ignored, unless this is the God login. If We are logged in as God, then we can select a login via its string login ID, and see what logins are available to it. This trumps the integer ID.
                                    $in_login_integer_id = NULL ///< OPTIONAL: This is ignored, unless this is the God login and $in_login_string_id is not specified. If We are logged in as God, then we can select a login via its integer login ID, and see what logins are available to it.
                                    ) {
        $ret = Array();
        
        if ($this->manager()) { // Don't even bother unless we're a manager.
            $ret = $this->get_cobra_instance()->get_all_logins($and_write, $in_login_string_id, $in_login_integer_id);
        
            $this->error = $this->get_cobra_instance()->error;
        } else {
            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_name_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This creates a new security token.
    Only managers or God can do this.
    
    \returns an integer, with the new token. NULL, if this did not work.
     */
    public function make_security_token() {
        return $this->_make_security_token();
    }
        
    /************************************************************************************************************************/    
    /*################################################## DATA ACCESS METHODS ###############################################*/
    /************************************************************************************************************************/
        
    /***********************/
    /**
    This method queries the "data" databse for multiple records, given a list of IDs.
    
    The records will not be returned if the user does not have read permission for them.
    
    \returns an array of instances, fetched an initialized from the database.
     */
    public function get_multiple_data_records_by_id(    $in_id_array    ///< REQUIRED: An array of integers, with the item IDs.
                                                    ) {
        $ret = $this->get_chameleon_instance()->get_multiple_data_records_by_id($in_id_array);
        
        $this->error = $this->get_chameleon_instance()->error;

        return $ret;
    }

    /***********************/
    /**
    This is a "security-safe" method for fetching a single record from the "data" database, by its ID.
    
    \returns a single new instance, initialized from the database.
     */
    public function get_single_data_record_by_id(   $in_id  ///< REQUIRED: The ID of the record to fetch.
                                                ) {
        $ret = $this->get_chameleon_instance()->get_single_data_record_by_id($in_id);
        
        $this->error = $this->get_chameleon_instance()->error;
    
        return $ret;
    }
    
    /************************************************************************************************************************/    
    /*############################################### DATA MODIFICATION METHODS ############################################*/
    /************************************************************************************************************************/    
    
    /************************************************************************************************************************/    
    /*                                                     GENERIC METHODS                                                  */
    /************************************************************************************************************************/    
    
    /***********************/
    /**
    This creates an uninitialized object, based upon the passed-in class.
    
    \returns a new instance of the class.
     */
    public function create_general_data_item(   $in_read_security_id = 1,           ///< OPTIONAL: An initial read security ID. If not specified, 1 (open to all logged-in users) will be specified.
                                                $in_write_security_id = NULL,       ///< OPTIONAL: An initial write security ID. If not specified, the current user's integer login ID will be used as the write security token.
                                                $in_classname = 'CO_Main_DB_Record' ///< OPTIONAL: This is the name of the class we want to create. It's optional, but leaving it out will give only the most basic data record.
                                            ) {
        return $this->_create_db_object($in_classname, $in_read_security_id, $in_write_security_id);
    }
    
    /***********************/
    /**
    This creates new generic collection object.
    
    \returns a new instance of the class.
     */
    public function create_collection(  $in_initial_item_ids = [],      ///< OPTIONAL: An array of integers, containing the data database IDs of existing items that are to be added to the collection. Default is empty.
                                        $in_read_security_id = 1,       ///< OPTIONAL: An initial read security ID. If not specified, 1 (open to all logged-in users) will be specified.
                                        $in_write_security_id = NULL,   ///< OPTIONAL: An initial write security ID. If not specified, the current user's integer login ID will be used as the write security token.
                                        $in_classname = 'CO_Collection' ///< OPTIONAL: This is the name of the class we want to create. It's optional, but leaving it out will give only the most basic collection record.
                                    ) {
        $ret = $this->create_general_data_item($in_read_security_id, $in_write_security_id, $in_classname);
        
        if (isset($in_initial_item_ids) && is_array($in_initial_item_ids) && count($in_initial_item_ids) && isset($ret) && ($ret instanceof CO_Collection)) {
            $elements = $this->get_chameleon_instance()->get_multiple_data_records_by_id($in_initial_item_ids);
            $this->error = $this->get_chameleon_instance()->error;
            
            if (isset($elements) && is_array($elements) && count($elements) && !isset($this->error)) {
                if (!$ret->appendElements($elements)) {
                    $this->error = $ret->error;
                    $ret->delete_from_db();
                    $ret = NULL;
                }
            } else {
                $ret->delete_from_db();
                $ret = NULL;
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Deletes a data item, given its integer ID.
    
    \returns true, if successful.
     */
    public function delete_item_by_id(  $in_item_id_integer ///< REQUIRED: This is the key that we are deleting. It must be a string.
                                    ) {
        $ret = false;
        
        if ($this->logged_in()) {
            $ret = $this->get_chameleon_instance()->delete_data_record($in_item_id_integer);
            $this->error = $this->get_chameleon_instance()->error;
        } else {
            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_name_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
    
    /************************************************************************************************************************/    
    /*                                                    KEY/VALUE METHODS                                                 */
    /************************************************************************************************************************/    
    
    /***********************/
    /**
    \returns the value for a given key. It is dependent on the class passed in. NULL, if no value or instance for the key.
     */
    public function get_value_for_key(  $in_key,                        ///< REQUIRED: This is the key that we are searching for. It must be a string.
                                        $in_classname = 'CO_KeyValue_CO_Collection'   ///< OPTIONAL: This is the class to search for the key. The default is the base class.
                                    ) {
        $ret = NULL;
        
        if ($this->valid()) {
            $ret = $this->get_chameleon_instance()->get_value_for_key($in_key, $in_classname);
            $this->error = $this->get_chameleon_instance()->error;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns true, if the key is unique in the DB for the given class (regardless of whether or not we can see it).
     */
    public function key_is_unique(  $in_key,                                    ///< The key to test (a string).
                                    $in_classname = 'CO_KeyValue_CO_Collection' ///< This is the class to search for the key. The default is the base class.
                                    ) {
        return $this->get_chameleon_instance()->key_is_unique($in_key, $in_classname);
    }
    
    /***********************/
    /**
    \returns the object that stores the given key. NULL, if no value or instance for the key.
     */
    public function get_object_for_key( $in_key ///< REQUIRED: This is the key that we are searching for. It must be a string.
                                        ) {
        $ret = NULL;
        
        if ($this->valid()) {
            $ret = $this->get_chameleon_instance()->get_object_for_key($in_key);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This deletes a key (and its associated data).
    
    \returns true, if successful.
     */
    public function delete_key( $in_key,                        ///< REQUIRED: This is the key that we are deleting. It must be a string.
                                $in_classname = 'CO_KeyValue_CO_Collection'   ///< OPTIONAL: This is the class to search for the key. The default is the base class.
                                ) {
        return $this->set_value_for_key($in_key, NULL, $in_classname);
    }
    
    /***********************/
    /**
    This sets a value to a key, creating the record, if need be. Passing in NULL will delete the key (if we have write access).
    We need to have a login for it to work at all. If the value already exists, then we need to have write access to it, or we will fail.
    This will only work if we are logged in.
    
    \returns true, if successful.
     */
    public function set_value_for_key(  $in_key,                        ///< REQUIRED: This is the key that we are setting. It must be a string.
                                        $in_value,                      ///< REQUIRED: The value to set. If NULL, then we will delete the key.
                                        $in_classname = 'CO_KeyValue_CO_Collection'   ///< OPTIONAL: This is the class to use for the key. The default is the base class.
                                    ) {
        $ret = NULL;
        
        if ($this->logged_in()) {
            $ret = $this->get_chameleon_instance()->set_value_for_key($in_key, $in_value, $in_classname);
            $this->error = $this->get_chameleon_instance()->error;
        } else {
            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_name_user_not_authorized,
                                            CO_ANDISOL_Lang::$andisol_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
    
    /************************************************************************************************************************/    
    /*                                                     LOCATION METHODS                                                 */
    /************************************************************************************************************************/    
    
    /***********************/
    /**
    This creates an initialized basic location object, based upon the passed-in class.
    
    \returns a new instance of the class.
     */
    public function create_ll_location( $in_longitude_degrees,              ///< REQUIRED: The longitude, in degrees.
                                        $in_latitude_degrees,               ///< REQUIRED: The latitude, in degrees.
                                        $in_fuzz_factor = NULL,             /**< OPTIONAL: If there is a "fuzz factor" to be applied, it should be sent in as a distance in Kilometers.
                                                                                           This creates a square, double the fuzz factor to a side, which is filled with a random value whenever the long/lat is queried.
                                                                                           This is used when we don't want an exact location being returned. It is used to do things like preserve privacy.
                                                                                           The "fuzzing" is done at an extremely low level, and only God, or IDs with write permission, can "see clearly."
                                                                                           If you have the ability to "see" the exact location, then you can call special functions.
                                                                                           Read permissions are not sufficient to "see clearly." You need to have write permissions on the object.
                                                                                           You can also set a single security token that is allowed to see 
                                                                                           If NULL (default), or 0.0, no "fuzz factor" is applied, so the location is exact.
                                                                            */
                                        $in_see_clearly_id = NULL,          ///< OPTIONAL: Ignored, if $in_fuzz_factor is not supplied. If $in_fuzz_factor is supplied, then this can be an ID, in addition to the write ID, that has permission to see the exact location. Default is NULL.
                                        $in_read_security_id = 1,           ///< OPTIONAL: An initial read security ID. If not specified, 1 (open to all logged-in users) will be specified.
                                        $in_write_security_id = NULL,       ///< OPTIONAL: An initial write security ID. If not specified, the current user's integer login ID will be used as the write security token.
                                        $in_classname = 'CO_LL_Location'    ///< OPTIONAL: A classname to use, besides the lowest-level class. If NULL, then the CO_LL_Location class is used.
                                        ) {
        $ret = NULL;
        
        $instance = $this->create_general_data_item($in_read_security_id, $in_write_security_id, $in_classname);
        
        // First, make sure we're in the right ballpark.
        if (isset($instance) && ($instance instanceof CO_LL_Location)) {
            if ($instance->set_longitude($in_longitude_degrees)) {
                if ($instance->set_latitude($in_latitude_degrees)) {
                    if (isset($in_fuzz_factor) && (0.0 < floatval($in_fuzz_factor))) {
                        if ($instance->set_fuzz_factor($in_fuzz_factor)) {
                            if (isset($in_see_clearly_id) && (0 < intval($in_see_clearly_id))) {
                                if ($instance->set_can_see_through_the_fuzz($in_see_clearly_id)) {
                                    $ret = $instance;
                                } else {
                                    if ($instance->error) {
                                        $this->error = $instance->error;
                                    }
            
                                    $instance->delete_from_db();
                                }
                            } else {
                                $ret = $instance;
                            }
                        } else {
                            if ($instance->error) {
                                $this->error = $instance->error;
                            }
            
                            $instance->delete_from_db();
                        }
                    } else {
                        $ret = $instance;
                    }
                } else {
                    if ($instance->error) {
                        $this->error = $instance->error;
                    }
            
                    $instance->delete_from_db();
                }
            } else {
                if ($instance->error) {
                    $this->error = $instance->error;
                }
            
                $instance->delete_from_db();
            }
        } else {
            if (isset($instance) && ($instance instanceof A_CO_DB_Table_Base)) {
                if ($instance->error) {
                    $this->error = $instance->error;
                }
            
                $instance->delete_from_db();
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This creates an initialized basic place location object, based upon the passed-in class.
    Although all parameters are optional, you need to specify at last enough information to specify a place.
    
    \returns a new instance of the class.
     */
    public function create_place(   $auto_resolve = true,               ///< OPTIONAL: If false (Default is true), then we will not try to "fill in the blanks" with any missing information.
                                    $in_venue = NULL,                   ///< OPTIONAL: The venue (place/building/establishment name).
                                    $in_street_address = NULL,          ///< OPTIONAL: Ignored if $in_fuzz_factor is nonzero. The street address (including number).
                                    $in_municipality = NULL,            ///< OPTIONAL: Ignored if $in_fuzz_factor is nonzero. The town/city.
                                    $in_county = NULL,                  ///< OPTIONAL: Ignored if $in_fuzz_factor is nonzero. The county/sub-province.
                                    $in_province = NULL,                ///< OPTIONAL: Ignored if $in_fuzz_factor is nonzero. The state/province/prefecture.
                                    $in_postal_code = NULL,             ///< OPTIONAL: Ignored if $in_fuzz_factor is nonzero. The ZIP/postal code.
                                    $in_nation = NULL,                  ///< OPTIONAL: Ignored if $in_fuzz_factor is nonzero. The nation.
                                    $in_extra_info = NULL,              ///< OPTIONAL: Additional (casual text) address/location/venue information.
                                    $in_longitude_degrees = NULL,       ///< OPTIONAL: The longitude, in degrees.
                                    $in_latitude_degrees = NULL,        ///< OPTIONAL: The latitude, in degrees.
                                    $in_fuzz_factor = NULL,             /**< OPTIONAL: If there is a "fuzz factor" to be applied, it should be sent in as a distance in Kilometers.
                                                                                       This creates a square, double the fuzz factor to a side, which is filled with a random value whenever the long/lat is queried.
                                                                                       This is used when we don't want an exact location being returned. It is used to do things like preserve privacy.
                                                                                       The "fuzzing" is done at an extremely low level, and only God, or IDs with write permission, can "see clearly."
                                                                                       If you have the ability to "see" the exact location, then you can call special functions.
                                                                                       Read permissions are not sufficient to "see clearly." You need to have write permissions on the object.
                                                                                       You can also set a single security token that is allowed to see 
                                                                                       If NULL (default), or 0.0, no "fuzz factor" is applied, so the location is exact.
                                                                        */
                                    $in_see_clearly_id = NULL,          ///< OPTIONAL: Ignored, if $in_fuzz_factor is not supplied. If $in_fuzz_factor is supplied, then this can be an ID, in addition to the write ID, that has permission to see the exact location. Default is NULL.
                                    $in_read_security_id = 1,           ///< OPTIONAL: An initial read security ID. If not specified, 1 (open to all logged-in users) will be specified.
                                    $in_write_security_id = NULL,       ///< OPTIONAL: An initial write security ID. If not specified, the current user's integer login ID will be used as the write security token.
                                    $in_classname = 'CO_Place'          ///< OPTIONAL: A classname to use, besides the lowest-level class. If NULL, then the CO_Place class is used.
                                ) {
        $ret = NULL;
        
        // We can create the special US version of the place, if we know we are US (Only if we are using the generic base class).
        if (((strtoupper($in_nation) == 'US') || (strtoupper($in_nation) == 'USA')) && ($in_classname == 'CO_Place')) {
            $in_classname ='CO_US_Place';
        }
        
        // If we know we are US, then we don't need to specify a nation.
        if ($in_classname == 'CO_US_Place') {
            $in_nation = NULL;
        }
        
        // We have to have at least enough basic information to denote a place.
        if((isset($in_longitude_degrees) && isset($in_longitude_degrees))   ||
            isset($in_venue) ||
            isset($in_street_address) ||
            isset($in_municipality) ||
            isset($in_county) ||
            isset($in_province) ||
            isset($in_postal_code) ||
            isset($in_nation)) {
            $instance = $this->create_general_data_item($in_read_security_id, $in_write_security_id, $in_classname);
    
            // First, make sure we're in the right ballpark.
            if (isset($instance) && ($instance instanceof CO_Place)) {
                $long_lat_explicitly_set = false;   // We use this to figure whether or not to do an initial lookup.
                $address_explicitly_set = false;    // We use this to figure whether or not to do an initial geocode.
        
                // If a long/lat was provided, we start by setting that to our object.
                if(isset($in_longitude_degrees) && isset($in_longitude_degrees)) {
                    if ($instance->set_longitude($in_longitude_degrees)) {
                        if ($instance->set_latitude($in_latitude_degrees)) {
                            $long_lat_explicitly_set = true;    // This means we won't be needing a lookup.
                        } else {
                            if ($instance->error) {
                                $this->error = $instance->error;
                            }

                            $instance->delete_from_db();
                            $instance = NULL;
                        }
                    } else {
                        if ($instance->error) {
                            $this->error = $instance->error;
                        }

                        $instance->delete_from_db();
                        $instance = NULL;
                    }
                }
                
                // Next, see if a venue name was provided.
                if(isset($instance) && isset($in_venue)) {
                    if ($instance->set_tag(0, $in_venue)) {
                        $address_explicitly_set = true;
                    } else {
                        if ($instance->error) {
                            $this->error = $instance->error;
                        }

                        $instance->delete_from_db();
                        $instance = NULL;
                    }
                }
            
                // Next, see if extra info was provided.
                if(isset($instance) && isset($in_extra_info)) {
                    if (!$instance->set_tag(2, $in_extra_info)) {
                        if ($instance->error) {
                            $this->error = $instance->error;
                        }

                        $instance->delete_from_db();
                        $instance = NULL;
                    }
                }

                // We only allow a specific address to be entered if this is a "non-fuzzed" location. This is a security measure.
                if (isset($instance) && (!isset($in_fuzz_factor) || (0.0 == floatval($in_fuzz_factor)))) {
                    // Next, see if a street address was provided.
                    if(isset($instance) && isset($in_street_address)) {
                        if ($instance->set_tag(1, $in_street_address)) {
                            $address_explicitly_set = true;
                        } else {
                            if ($instance->error) {
                                $this->error = $instance->error;
                            }

                            $instance->delete_from_db();
                            $instance = NULL;
                        }
                    }
            
                    // Next, see if a town was provided.
                    if(isset($instance) && isset($in_municipality)) {
                        if ($instance->set_tag(3, $in_municipality)) {
                            $address_explicitly_set = true;
                        } else {
                            if ($instance->error) {
                                $this->error = $instance->error;
                            }

                            $instance->delete_from_db();
                            $instance = NULL;
                        }
                    }
            
                    // Next, see if a county was provided.
                    if(isset($instance) && isset($in_county)) {
                        if ($instance->set_tag(4, $in_county)) {
                            $address_explicitly_set = true;
                        } else {
                            if ($instance->error) {
                                $this->error = $instance->error;
                            }

                            $instance->delete_from_db();
                            $instance = NULL;
                        }
                    }
            
                    // Next, see if a state was provided.
                    if(isset($instance) && isset($in_province)) {
                        if ($instance->set_tag(5, $in_province)) {
                            $address_explicitly_set = true;
                        } else {
                            if ($instance->error) {
                                $this->error = $instance->error;
                            }

                            $instance->delete_from_db();
                            $instance = NULL;
                        }
                    }
            
                    // Next, see if a ZIP code was provided.
                    if(isset($instance) && isset($in_postal_code)) {
                        if ($instance->set_tag(6, $in_postal_code)) {
                            $address_explicitly_set = true;
                        } else {
                            if ($instance->error) {
                                $this->error = $instance->error;
                            }

                            $instance->delete_from_db();
                            $instance = NULL;
                        }
                    }
            
                    // Next, see if a nation was provided.
                    if(isset($instance) && isset($in_nation)) {
                        if ($instance->set_tag(7, $in_nation)) {
                            $address_explicitly_set = true;
                        } else {
                            if ($instance->error) {
                                $this->error = $instance->error;
                            }

                            $instance->delete_from_db();
                            $instance = NULL;
                        }
                    }
                } elseif (isset($instance)) {
                    $auto_resolve = false;  // We do not do an auto-lookup if we are "fuzzy."
        
                    if ($instance->set_fuzz_factor($in_fuzz_factor)) {
                        if (isset($in_see_clearly_id) && (0 < intval($in_see_clearly_id))) {
                            if (!$instance->set_can_see_through_the_fuzz($in_see_clearly_id)) {
                                if ($instance->error) {
                                    $this->error = $instance->error;
                                }

                                $instance->delete_from_db();
                                $instance = NULL;
                            }
                        }
                    } else {
                        if ($instance->error) {
                            $this->error = $instance->error;
                        }
                
                        $instance->delete_from_db();
                        $instance = NULL;
                    }
                }

                // OK. If we are here, and still have a valid instance, then we can "set it in stone," and see if we need to do a geocode.
                if (isset($instance)) {
                    $instance->set_address_elements($instance->tags(), true);

                    // If we did not explicitly set a long/lat, and have a Google API key (assumed valid), then let's try a geocode.
                    if ($auto_resolve && !$long_lat_explicitly_set && CO_Config::$google_api_key) {  // If we can do a lookup, and need to, then lets's give that a go.
                        $long_lat = $instance->lookup_address();
                
                        if (isset($long_lat) && is_array($long_lat) && (1 < count($long_lat))) {
                            if ($instance->set_longitude($long_lat['longitude'])) {
                                if ($instance->set_latitude($long_lat['latitude'])) {
                                    $ret = $instance;   // Now we're ready for our close-up, Mr. DeMille...
                                } else {
                                    if ($instance->error) {
                                        $this->error = $instance->error;
                                    }

                                    $instance->delete_from_db();
                                    $instance = NULL;
                                }
                            } else {
                                if ($instance->error) {
                                    $this->error = $instance->error;
                                }

                                $instance->delete_from_db();
                                $instance = NULL;
                            }
                        } else {
                            if ($instance->error) {
                                $this->error = $instance->error;
                            }
                    
                            $instance->delete_from_db();
                            $instance = NULL;
                        }
                    } else {    // Otherwise, we simply send the current result back.
                        $ret = $instance;
                    }
            
                    // If we did not explicitly set an address, and have a Google API key (assumed valid), then let's try a geocode.
                    if ($ret && $auto_resolve && !$address_explicitly_set && CO_Config::$google_api_key) {  // If we can do a lookup, and need to, then lets's give that a go.
                        $ret = NULL;    // Not so fast, Skippy.
                        $address = $instance->geocode_long_lat();
                        if (isset($address) && is_array($address) && (0 < count($address))) {
                            for ($i = 0; $i < 8; $i++) {
                                eval("\$key = CO_CHAMELEON_Lang::\$chameleon_co_place_tag_$i;");
                        
                                if (isset($address[$key]) && trim($address[$key])) { // Is there a venue?
                                    if (!$instance->set_tag($i, trim($address[$key]))) {
                                        if ($instance->error) {
                                            $this->error = $instance->error;
                                        }

                                        $instance->delete_from_db();
                                        $instance = NULL;
                                        break;
                                    }
                                }
                            }
                    
                            // OK. Now we can do it.
                            if (isset($instance)) {
                                // This sets the object up to what we just sent in.
                                $instance->set_address_elements($instance->tags(), true);
                                $ret = $instance;
                            }
                        } else {
                            if ($instance->error) {
                                $this->error = $instance->error;
                            }
                    
                            $instance->delete_from_db();
                            $instance = NULL;
                        }
                    }
                } else {
                    $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_location_failed_to_initialize,
                                                    CO_ANDISOL_Lang::$andisol_error_name_location_failed_to_initialize,
                                                    CO_ANDISOL_Lang::$andisol_error_desc_location_failed_to_initialize);
                }
            } else {
                $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_location_failed_to_initialize,
                                                CO_ANDISOL_Lang::$andisol_error_name_location_failed_to_initialize,
                                                CO_ANDISOL_Lang::$andisol_error_desc_location_failed_to_initialize);
            }
        } else {
            $this->error = new LGV_Error(   CO_ANDISOL_Lang_Common::$andisol_error_code_insufficient_location_information,
                                            CO_ANDISOL_Lang::$andisol_error_name_insufficient_location_information,
                                            CO_ANDISOL_Lang::$andisol_error_desc_insufficient_location_information);
        }
                
        return $ret;
    }
    
    /***********************/
    /**
    This creates an initialized place location object, based upon the passed-in class.
    However, this method will take just a long/lat, like creating a long/lat class, and do a geocode to set the address fields.
    
    \returns a new instance of the class.
     */
    public function create_ll_place($in_longitude_degrees,          ///< REQUIRED: The longitude, in degrees.
                                    $in_latitude_degrees,           ///< REQUIRED: The latitude, in degrees.
                                    $in_fuzz_factor = NULL,         /**< OPTIONAL: If there is a "fuzz factor" to be applied, it should be sent in as a distance in Kilometers.
                                                                                   This creates a square, double the fuzz factor to a side, which is filled with a random value whenever the long/lat is queried.
                                                                                   This is used when we don't want an exact location being returned. It is used to do things like preserve privacy.
                                                                                   The "fuzzing" is done at an extremely low level, and only God, or IDs with write permission, can "see clearly."
                                                                                   If you have the ability to "see" the exact location, then you can call special functions.
                                                                                   Read permissions are not sufficient to "see clearly." You need to have write permissions on the object.
                                                                                   You can also set a single security token that is allowed to see 
                                                                                   If NULL (default), or 0.0, no "fuzz factor" is applied, so the location is exact.
                                                                    */
                                    $in_see_clearly_id = NULL,      ///< OPTIONAL: Ignored, if $in_fuzz_factor is not supplied. If $in_fuzz_factor is supplied, then this can be an ID, in addition to the write ID, that has permission to see the exact location. Default is NULL.
                                    $in_read_security_id = 1,       ///< OPTIONAL: An initial read security ID. If not specified, 1 (open to all logged-in users) will be specified.
                                    $in_write_security_id = NULL,   ///< OPTIONAL: An initial write security ID. If not specified, the current user's integer login ID will be used as the write security token.
                                    $in_classname = 'CO_Place'      ///< OPTIONAL: A classname to use, besides the lowest-level class. If NULL, then the CO_Place class is used.
                                    ) {
        return $this->create_place(true, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $in_longitude_degrees, $in_latitude_degrees, $in_fuzz_factor, $in_see_clearly_id, $in_read_security_id, $in_write_security_id, $in_classname);
    }
    
    /***********************/
    /**
    This creates an initialized basic US place location COLLECTION object. This does not accept a classname. Use the regular function if you want to get fancy.
    However, this method will take just a long/lat, like creating a long/lat class, and do a geocode to set the address fields.
    
    \returns a new instance of the class.
     */
    public function create_ll_us_place( $in_longitude_degrees,          ///< REQUIRED: The longitude, in degrees.
                                        $in_latitude_degrees,           ///< REQUIRED: The latitude, in degrees.
                                        $in_fuzz_factor = NULL,         /**< OPTIONAL: If there is a "fuzz factor" to be applied, it should be sent in as a distance in Kilometers.
                                                                                       This creates a square, double the fuzz factor to a side, which is filled with a random value whenever the long/lat is queried.
                                                                                       This is used when we don't want an exact location being returned. It is used to do things like preserve privacy.
                                                                                       The "fuzzing" is done at an extremely low level, and only God, or IDs with write permission, can "see clearly."
                                                                                       If you have the ability to "see" the exact location, then you can call special functions.
                                                                                       Read permissions are not sufficient to "see clearly." You need to have write permissions on the object.
                                                                                       You can also set a single security token that is allowed to see 
                                                                                       If NULL (default), or 0.0, no "fuzz factor" is applied, so the location is exact.
                                                                        */
                                        $in_see_clearly_id = NULL,      ///< OPTIONAL: Ignored, if $in_fuzz_factor is not supplied. If $in_fuzz_factor is supplied, then this can be an ID, in addition to the write ID, that has permission to see the exact location. Default is NULL.
                                        $in_read_security_id = 1,       ///< OPTIONAL: An initial read security ID. If not specified, 1 (open to all logged-in users) will be specified.
                                        $in_write_security_id = NULL    ///< OPTIONAL: An initial write security ID. If not specified, the current user's integer login ID will be used as the write security token.
                                        ) {
        return $this->create_place(true, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $in_longitude_degrees, $in_latitude_degrees, $in_fuzz_factor, $in_see_clearly_id, $in_read_security_id, $in_write_security_id, 'CO_US_Place');
    }
    
    /***********************/
    /**
    This creates an initialized basic place location COLLECTION object. This does not accept a classname. Use the regular function if you want to get fancy.
    Although all parameters are optional, you need to specify at last enough information to specify a place.
    
    \returns a new instance of the class.
     */
    public function create_place_collection($auto_resolve = true,           ///< OPTIONAL: If false (Default is true), then we will not try to "fill in the blanks" with any missing information.
                                            $in_venue = NULL,               ///< OPTIONAL: The venue (place/building/establishment name).
                                            $in_street_address = NULL,      ///< OPTIONAL: The street address (including number).
                                            $in_municipality = NULL,        ///< OPTIONAL: The town/city.
                                            $in_county = NULL,              ///< OPTIONAL: The county/sub-province.
                                            $in_province = NULL,            ///< OPTIONAL: The state/province/prefecture.
                                            $in_postal_code = NULL,         ///< OPTIONAL: The ZIP/postal code.
                                            $in_nation = NULL,              ///< OPTIONAL: The nation.
                                            $in_extra_info = NULL,          ///< OPTIONAL: Additional (casual text) address/location/venue information.
                                            $in_longitude_degrees = NULL,   ///< OPTIONAL: The longitude, in degrees.
                                            $in_latitude_degrees = NULL,    ///< OPTIONAL: The latitude, in degrees.
                                            $in_fuzz_factor = NULL,         /**< OPTIONAL: If there is a "fuzz factor" to be applied, it should be sent in as a distance in Kilometers.
                                                                                           This creates a square, double the fuzz factor to a side, which is filled with a random value whenever the long/lat is queried.
                                                                                           This is used when we don't want an exact location being returned. It is used to do things like preserve privacy.
                                                                                           The "fuzzing" is done at an extremely low level, and only God, or IDs with write permission, can "see clearly."
                                                                                           If you have the ability to "see" the exact location, then you can call special functions.
                                                                                           Read permissions are not sufficient to "see clearly." You need to have write permissions on the object.
                                                                                           You can also set a single security token that is allowed to see 
                                                                                           If NULL (default), or 0.0, no "fuzz factor" is applied, so the location is exact.
                                                                            */
                                            $in_see_clearly_id = NULL,      ///< OPTIONAL: Ignored, if $in_fuzz_factor is not supplied. If $in_fuzz_factor is supplied, then this can be an ID, in addition to the write ID, that has permission to see the exact location. Default is NULL.
                                            $in_read_security_id = 1,       ///< OPTIONAL: An initial read security ID. If not specified, 1 (open to all logged-in users) will be specified.
                                            $in_write_security_id = NULL    ///< OPTIONAL: An initial write security ID. If not specified, the current user's integer login ID will be used as the write security token.
                                            ) {
        $class = 'CO_Place_Collection';
        
        // We can create the special US version of the place, if we know we are US.
        if ((strtoupper($in_nation) == 'US') || (strtoupper($in_nation) == 'USA')) {
            $class ='CO_US_Place_Collection';
            $in_nation = NULL;
        }
        
        return $this->create_place($auto_resolve, $in_venue, $in_street_address, $in_municipality, $in_county, $in_province, $in_postal_code, NULL, NULL, $in_longitude_degrees, $in_latitude_degrees, $in_fuzz_factor, $in_see_clearly_id, $in_read_security_id, $in_write_security_id, $class);
    }
};
