<?php
/***************************************************************************************************************************/
/**
    CHAMELEON Object Abstraction Layer
    
    © Copyright 2018, Little Green Viper Software Development LLC/The Great Rift Valley Software Company
    
    LICENSE:
    
    MIT License
    
    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
    files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
    modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
    IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
    CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'LGV_DBF_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

require_once(CO_Config::db_classes_class_dir().'/co_ll_location.class.php');

/***************************************************************************************************************************/
/**
This is a trait for the basic "owner" aggregator functionality.

Owners are VERY simple aggregates. They only allow a very rough "pre-filter" for searches. If you want to get fancier, use a collection (but you won't be able to handle as many children).
 */
trait tCO_Owner {
    var $my_owner_id = NULL;        ///< This is the ID We will use for the "owner." If left NULL, then the instance ID is used instead.
    protected $_cached_ids = NULL;  ///< This will contain our "owned" IDs after we load.
    
    /***********************/
    /**
    This method will change a given object to have this as its owner.
    
    \returns true, if the object's owner changed successfully.
     */
    public function adopt_child(    $in_object_to_own   ///< The instance that will be "owned" by this instance. The user must have write privileges on the onject.
                                ) {
        $ret = false;
        
        $my_owner_id = intval($this->my_owner_id) ? intval($this->my_owner_id) : $this->id();
        if ($in_object_to_own->owner_id() != $my_owner_id) {
            if ($in_object->user_can_write()) {
                $ret = $in_object->set_owner_id($my_owner_id);
            } else {
                $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_owner_error_code_user_not_authorized,
                                                CO_CHAMELEON_Lang::$co_owner_error_name_user_not_authorized,
                                                CO_CHAMELEON_Lang::$co_owner_error_desc_user_not_authorized);
            } 
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This counts the direct children of this collection, and returns that count.
        
    \returns the number of children.
     */
    public function count() {
        $children_ids = $this->children_ids();
        $my_count = isset($children_ids) && is_array($children_ids) ? count($children_ids) : 0;
                
        return $my_count;
    }
    
    /***********************/
    /**
    \returns an array of integers, with the "owned" object IDs.
     */
    public function children_ids() {
        if (!((isset($this->_cached_ids) && is_array($this->_cached_ids) && count($this->_cached_ids)))) {
            $my_owner_id = intval($this->my_owner_id) ? intval($this->my_owner_id) : $this->id();
            $this->_cached_ids = $this->get_access_object()->generic_search(Array('owner' => $my_owner_id), false, 0, 0, false, false, true);
        }
        
        return $this->_cached_ids;
    }
    
    /***********************/
    /**
    WARNING: Since the "owner" object is for big, fat datasets, this could blow up memory!
    
    \returns an array of instances, comprising all "owned" instances. This could recurse, inside the "child" objects.
     */
    public function children() {
        $children_ids = $this->children_ids();
        return $this->get_access_object()->get_multiple_data_records_by_id($children_ids);
    }
    
    /***********************/
    /**
    This is a "generic" data database search. It can be called from external user contexts, and allows a fairly generalized search of the "data" database.
    Sorting will be done for the "owner" and "location" values. "owner" will be sorted by the ID of the returned records, and "location" will be by distance from the center.
    This will ignore any "owner" parameters, and will only look for this specific owner.
    There is no "OR" search. It is always "AND."
    
    It is "security-safe."
    
    \returns an array of instances that match the search parameters. If $count_only is true, then it will be a single integer, with the count of responses to the search (if a page, then only the number of items on that page).
     */
    public function generic_search( $in_search_parameters = NULL,   /**< This is an associative array of terms to define the search. The keys should be:
                                                                        - 'id'
                                                                            This should be accompanied by an array of one or more integers, representing specific item IDs. These can only be ones "owned" by this instance.
                                                                        - 'access_class'
                                                                            This should be accompanied by an array, containing one or more PHP class names.
                                                                        - 'name'
                                                                            This will contain a case-insensitive array of strings to check against the object_name column.
                                                                        - 'tags'
                                                                            This should be accompanied by an array (up to 10 elements) of one or more case-insensitive strings, representing specific tag values.
                                                                        - 'location'
                                                                            This requires that the parameter be a 3-element associative array of floating-point numbers:
                                                                                - 'longtude'
                                                                                    This is the search center location longitude, in degrees.
                                                                                - 'latitude'
                                                                                    This is the search center location latitude, in degrees.
                                                                                - 'radius'
                                                                                    This is the search radius, in Kilometers.
                                                                    */
                                    $page_size = 0,                 ///< If specified with a 1-based integer, this denotes the size of a "page" of results. NOTE: This is only applicable to MySQL or Postgres, and will be ignored if the DB is not MySQL or Postgres.
                                    $initial_page = 0,              ///< This is ignored unless $page_size is greater than 0. If so, then this 0-based index will specify which page of results to return.
                                    $and_writeable = false,         ///< If true, then we only want records we can modify.
                                    $count_only = false,            ///< If true (default is false), then only a single integer will be returned, with the count of items that fit the search.
                                    $ids_only = false               ///< If true (default is false), then the return array will consist only of integers (the object IDs). If $count_only is true, this is ignored.
                                    ) {
        $my_owner_id = intval($this->my_owner_id) ? intval($this->my_owner_id) : $this->id();
        $in_search_parameters['owner'] = $my_owner_id;
        return $this->get_access_object()->generic_search($in_search_parameters, false, $page_size, $initial_page, $and_writeable, $count_only, $ids_only);
        
        return $ret;
    }
}