<?php
/***************************************************************************************************************************/
/**
    CHAMELEON Object Abstraction Layer
    
    © Copyright 2018, The Great Rift Valley Software Company
    
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

    The Great Rift Valley Software Company: https://riftvalleysoftware.com
*/
defined( 'LGV_DBF_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

require_once(CO_Config::db_class_dir().'/co_main_db_record.class.php');
CO_Config::require_extension_class('tco_collection.interface.php');

/***************************************************************************************************************************/
/**
This is a generic collection class.
 */
class CO_Collection extends CO_LL_Location {
    use tCO_Collection; // These are the built-in collection methods.
    
    /***********************************************************************************************************************/
    /***********************/
    /**
    Constructor (Initializer)
     */
	public function __construct(    $in_db_object = NULL,           ///< The database object for this instance.
	                                $in_db_result = NULL,           ///< The database row for this instance (associative array, with database keys).
	                                $in_owner_id = NULL,            ///< The ID of the object (in the database) that "owns" this instance.
                                    $in_tags_array = NULL           ///< The tags to be assigned to this object.
                                ) {
        
        $this->_container = Array();

        parent::__construct($in_db_object, $in_db_result, $in_owner_id, $in_tags_array);
        $this->class_description = "This is a 'Generic Collection' Class.";
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
            $count = 0;
            if (isset($this->context['children_ids']) && is_array($this->context['children_ids'])) {
                $count = count($this->context['children_ids']);
            }
        
            $this->class_description = "This is a 'Generic Collection' Class.";
            $this->instance_description = isset($this->name) && $this->name ? "$this->name" : "Generic Collection Object";
        }
    
        return $ret;
    }
    
    /***********************/
    /**
    We override this, because we want to see if we can possibly delete children objects.  
    \returns true, if the deletion was successful.
     */
    public function delete_from_db( $with_extreme_prejudice = false ///< If true (Default is false), then we will attempt to delete all contained children. Remember that this could cause problems if other collections can see the children!
                                    ) {
        if ($with_extreme_prejudice && $this->user_can_write()) {
            // We don't error-check this on purpose, as it's a given that there might be issues, here. This is a "due dilligence" thing.
            $user_items_to_delete = $this->children();
            
            foreach ($user_items_to_delete as $child) {
                if ($child->user_can_write()) {
                    $child->delete_from_db();
                }
            }
        }
        
        return parent::delete_from_db();
    }
};
