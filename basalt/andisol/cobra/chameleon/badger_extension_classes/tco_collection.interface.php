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

/***************************************************************************************************************************/
/**
This is a trait for the basic "collection" aggregator functionality.

Yes, I know that a lot of the code could do with some refactoring, and we should do that, but what we have here does work, and
having pretty code is less important than having effective, tested, working code. We have a lot on our plate.
 */
trait tCO_Collection {
    protected $_container;      ///< This contains instances of the records referenced by the IDs stored in the object.
	
    /***********************/
    /**
    This is a "garbage collection" method.
    This checks each of the contained items, and removes the ID if the item does not actually exist (security is not taken into account, so it's an accurate check).
     */
    protected function _scrub() {
        if (isset($this->context['children_ids'])) {
            $children_ids = $this->context['children_ids'];
        
            if (isset($children_ids) && is_array($children_ids) && count($children_ids)) {
                $new_ids = Array();
                foreach ($children_ids as $id) {
                    $id = intval($id); // Belt and suspenders.
                    if ($this->get_access_object()->item_exists($id)) {
                        $new_ids[] = $id;
                    }
                }
                $new_ids = array_unique($new_ids);
                sort($new_ids);
                $this->context['children_ids'] = $new_ids;
                $this->update_db();
            }
        } else {
            $this->_container = Array();
        }
    }
	
    /***********************/
    /**
    This method simply sets up the internal container from the object's tags.
    The tags already need to be loaded when this is called, so it should be called towards the end of the
    object's constructor.
     */
    protected function _set_up_container() {
        if (isset($this->context['children_ids'])) {
            $children_ids = $this->context['children_ids'];
        
            if (isset($children_ids) && is_array($children_ids) && count($children_ids)) {
                $this->_container = $this->get_access_object()->get_multiple_data_records_by_id($children_ids);
            }
        }
    }
	
    /***********************/
    /**
    This method forces a reload of the collection data.
     */
    public function reload_collection() {
        $this->_scrub();    // Garbage collection.
        $this->_set_up_container();
    }
    
    /***********************/
    /**
    This inserts one record to just before the indexed item (0-based index). If the index is -1, the length of the collection or larger, then the item will be appeneded.
    Collection elements cannot be already in the collection at any level, as that could cause a loop.
    We also don't allow duplicates of any instance in the same level of a collection. Only the first instance is retained. Subsequent copies are removed.
    The logged-in user must have write access to the collection object (not the data object) in order to add the item.
    You can opt out of the automatic database update.
    
    \returns true, if the data was successfully added. If a DB update was done, then the response is the one from the update.
     */
    public function insertElement(  $in_element,            ///< The database record to add.
                                    $in_before_index = -1,  ///< The index of the element (in the current list) BEFORE which the insertion will be made. Default is -1 (append).
                                    $dont_update = false    ///< true, if we are to skip the DB update (default is false).
                                ) {
        $ret = false;
        
        if ($in_element instanceof A_CO_DB_Table_Base) {
            if ($this->user_can_write() ) { // You cannot add to a collection if you don't have write privileges.
                if (!(method_exists($in_element, 'insertElement') && $this->areYouMyDaddy($in_element))) {   // Make sure that a collection isn't already in the woodpile somewhere.
                    $id = intval($in_element->id());
                    if (!isset($this->_container) || !is_array($this->_container)) {
                        $this->_container = [];
                    }
                
                    if (!isset($this->context['children_ids']) || !is_array($this->context['children_ids'])) {
                        $this->context['children_ids'] = [];
                    }
                    
                    if (!in_array($id, $this->context['children_ids'])) {
                        if ((-1 == $in_before_index) || (NULL == $in_before_index) || !isset($in_before_index)) {
                            $in_before_index = count($this->_container);
                        }
                
                        $before_array = Array();
                
                        if ($in_before_index) {
                            $before_array = array_slice($this->_container, 0, $in_before_index, false);
                        }
                
                        $after_array = Array();
                
                        if ($in_before_index < count($this->_container)) {
                            $end_count = count($this->_container) - $in_before_index;
                            $after_array = array_slice($this->_container, $end_count, false);
                        }
                
                        $element_array = Array($in_element);
                
                        $merged = array_merge($before_array, $element_array, $after_array);
                
                        $this->_container = $merged;
                
                        $ret = true;
                        if (!isset($this->context['children_ids'])) {
                            $this->context['children_ids'] = Array();
                        }
                
                        $ids = array_map('intval', $this->context['children_ids']);
                        if (!in_array($id, $ids)) {
                            $ids[] = $id;
                            $ids = array_unique($ids);
                            sort($ids);
                            $this->context['children_ids'] = $ids;
                        }
                    }
                }
        
                if ($ret && !$dont_update) {
                    $ret = $this->update_db();
                }
            } else {
                $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_collection_error_code_user_not_authorized,
                                                CO_CHAMELEON_Lang::$co_collection_error_name_user_not_authorized,
                                                CO_CHAMELEON_Lang::$co_collection_error_desc_user_not_authorized);
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This inserts multiple records to just before the indexed item (0-based index). If the index is -1, the length of the collection or larger, then the items will be appeneded.
    Collection elements cannot be already in the collection at any level, as that could cause a loop.
    We also don't allow duplicates of any class in the same level of a collection. Only the first instance is retained. Subsequent copies are removed.
    The logged-in user must have write access to the collection object (not the data objects) in order to add the items.
    You can opt out of the automatic database update.
    
    \returns true, if the data was successfully updated in the DB. false, if none of the items were added.
     */
    public function insertElements( $in_element_array,      ///< An array of database element instances to be inserted.
                                    $in_before_index = -1   ///< The index of the element (in the current list) BEFORE which the insertion will be made. Default is -1 (append).
                                ) {
        $ret = false;
        
        if ($this->user_can_write() ) { // You cannot add to a collection if you don't have write privileges.
            $i_have_a_daddy = false;
            
            foreach ($in_element_array as $element) {
                // We can't insert nested collections.
                if (method_exists($element, 'insertElement') && $this->areYouMyDaddy($element)) {
                    $i_have_a_daddy = true;
                    break;
                }
            }
            
            if (!$i_have_a_daddy) { // DON'T CROSS THE STREAMS!
                if (!isset($this->_container) || !is_array($this->_container)) {
                    $this->_container = Array();
                }
                
                if ((-1 == $in_before_index) || (NULL == $in_before_index) || !isset($in_before_index)) {
                    $in_before_index = count($this->_container);
                }
                
                $before_array = Array();
                
                if ($in_before_index) {
                    $before_array = array_slice($this->_container, 0, $in_before_index, false);
                }
                
                $after_array = Array();
                
                if ($in_before_index < count($this->_container)) {
                    $end_count = count($this->_container) - $in_before_index;
                    $after_array = array_slice($this->_container, $end_count, false);
                }
                
                $merged = array_merge($before_array, $in_element_array, $after_array);
                
                $unique  = array();

                foreach ($merged as $current) {
                    if (!in_array($current, $unique)) {
                        $unique[] = $current;
                    }
                }
                
                $this->_container = $unique;
                
                $ret = true;
            
                if (!isset($this->context['children_ids'])) {
                    $this->context['children_ids'] = Array();
                }
                
                foreach ($in_element_array as $element) {
                    $id = intval($element->id());
                    $ids = array_map('intval', $this->context['children_ids']);
                    if (!in_array($id, $ids)) {
                        $ids[] = $id;
                        $ids = array_unique($ids);
                        sort($ids);
                        $this->context['children_ids'] = $ids;
                    }
                }
            }
        
            if ($ret) {
                $ret = $this->update_db();
            }
        } else {
            $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_collection_error_code_user_not_authorized,
                                            CO_CHAMELEON_Lang::$co_collection_error_name_user_not_authorized,
                                            CO_CHAMELEON_Lang::$co_collection_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Deletes multiple elements from the collection.
    It should be noted that this does not delete the elements from the database, and it is not recursive.
    This is an atomic operation. If any of the elements can't be removed, then non of the elements can be removed.
    The one exception is that the deletion length can extend past the boundaries of the collection. It will be truncated.
    
    \returns true, if the elements were successfully removed from the collection.
     */
    public function deleteElements( $in_first_index,    ///< The starting 0-based index of the first element to be removed from the collection.
                                    $in_deletion_length ///< The number of elements to remove (including the first one). If this is negative, then elements will be removed from the index, backwards (-1 is the same as 1).
                                ) {
        $ret = false;
        
        if ($this->user_can_write() ) { // You cannot add to a collection if you don't have write privileges.
            $element_ids = Array(); // We will keep track of which IDs we delete, so we can delete them from our context variable.
            
            // If negative, we're going backwards.
            if (0 > $in_deletion_length) {
                $in_deletion_length = abs($in_deletion_length);
                $in_first_index -= ($in_deletion_length - 1);
                $in_first_index = max(0, $in_first_index);  // Make sure we stay within the lane markers.
            }
            
            $last_index_plus_one = min(count($this->_container), $in_first_index + $in_deletion_length);
        
            // We simply record the IDs of each of the elements we'll be deleting.
            for ($i = $in_first_index; $i < $last_index_plus_one; $i++) {
                $element = $this->_container[$i];
                $element_ids[] = $element->id();
            }
            
            if ($in_deletion_length == count($element_ids)) {  // Belt and suspenders. Make sure we are actually deleting the requested elements.
                $new_container = Array();
                
                // We build a new container that doesn't have the deleted elements.
                foreach ($this->_container as $element) {
                    $element_id = $element->id();
                    
                    if (!in_array($element_id, $element_ids)) {
                        $new_container[] = $element_id;
                    }
                }
                
                $new_list = Array();
                
                // We build a new list that doesn't have the deleted element IDs.
                while ($element_id = array_shift($this->context['children_ids'])) {
                    if (!in_array($element_id, $element_ids)) {
                        $new_list[] = $element_id;
                    }
                }
                
                $new_list = array_unique($new_list);
                sort($new_list);
                $this->context['children_ids'] = $new_list;
                
                $ret = $this->update_db();
                if (!$this->_batch_mode) {
                    $this->_scrub();
                }
            }
        } else {
            $this->error = new LGV_Error(   CO_CHAMELEON_Lang_Common::$co_collection_error_code_user_not_authorized,
                                            CO_CHAMELEON_Lang::$co_collection_error_name_user_not_authorized,
                                            CO_CHAMELEON_Lang::$co_collection_error_desc_user_not_authorized);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Deletes a single element, by its 0-based index (not recursive).
    It should be noted that this does not delete the element from the database, and it is not recursive.
    
    \returns true, if the element was successfully removed from the collection.
     */
    public function deleteElement(  $in_index   ///< The 0-based index of the element we want to delete.
                                ) {
        return $this->deleteElements($in_index, 1);
    }
    
    /***********************/
    /**
    Deletes a single element, by its actual object reference (not recursive).
    It should be noted that this does not delete the element from the database, and it is not recursive.
    
    \returns true, if the element was successfully removed from the collection.
     */
    public function deleteThisElement(  $in_element ///< The element we want to delete.
                                    ) {
        $ret = false;
        $index = $this->indexOfThisElement($in_element);
        
        if (false !== $index) {
            $ret = $this->deleteElement(intval($index));
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    This appends one record to the end of the collection.
    The element cannot be already in the collection at any level, as that could
    cause a loop.
    The logged-in user must have write access to the collection object (not the data object)
    in order to add the item.
    You can opt out of the automatic database update.
    
    \returns true, if the data was successfully added. If a DB update was done, then the response is the one from the update.
     */
    public function appendElement(  $in_element             ///< The database record to add.
                                ) {
        return $this->insertElement($in_element, -1);
    }
    
    /***********************/
    /**
    This appends multiple elements (passed as an array).
    The logged-in user must have write access to the collection object (not the data object)
    in order to add the items.
    
    \returns true, if the data was successfully updated in the DB. false, if none of the items were added.
     */
    public function appendElements( $in_element_array       ///< An array of database element instances to be appended.
                                ) {
        return $this->insertElements($in_element_array, -1);
    }
    
    /***********************/
    /**
    This deletes all children of the container.
    However, the container may have children we can't see, so we don't delete those. We only delete the ones we know about (which could be all of them).
    
    \returns true, if the new configuration was successfully updated in the DB.
     */
    public function deleteAllChildren() {
        if ($this->user_can_write() ) { // You cannot delete from a collection if you don't have write privileges.
            $new_list = [];
        
            if (isset($this->context['children_ids']) && is_array($this->context['children_ids']) && count($this->context['children_ids'])) {
                foreach ($this->context['children_ids'] as $child_id) {
                    // We save items in the list that we can't see.
                    if ($this->get_access_object()->item_exists($child_id) && !$this->get_access_object()->can_i_see_this_data_record($child_id)) {
                        $new_list[] = $child_id;
                    }
                }
            }
        
            $this->_children = Array();
            $this->context['children_ids'] = $new_list;
            return $this->update_db();
        }
        
        return false;
    }
    
    /***********************/
    /**
    \returns the 0-based index of the given element, or false, if the element is not in the collection (This is not recursive).
     */
    public function indexOfThisElement(  $in_element    ///< The element we're looking for.
                                        ) {
        return array_search($in_element, $this->children());
    }
    
    /***********************/
    /**
    This takes an element, and returns its parent collection object (if available).
    This only checks the current collection and its "child" collection objects.
    
    \returns an array of instances of a collection class, if that instance is the "parent" of the presented object. It may be this instance, or a "child" instance of this class.
     */
    public function whosYourDaddy(  $in_element ///< The element to check.
                                ) {
        $ret = NULL;
        $id = intval($in_element->id());
        
        $ret_array = $this->recursiveMap(function($instance, $hierarchy_level, $parent){
                $id = intval($instance->id());
                return Array($id, $parent);
            });
        
        if (isset($ret_array) && is_array($ret_array) && count($ret_array)) {
            $ret = Array();
            foreach ($ret_array as $item) {
                if ($item[0] == $id) {
                    $ret[] = $item[1];
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This takes an element, and checks to see if it already exists in our hierarchy (anywhere).
    
    \returns true, if this instance already has the presented object.
     */    
    public function areYouMyDaddy(  $in_element,            ///< The element to check. This can be an array, in which case, each element is checked.
                                    $full_hierachy = true   ///< If false, then only this level (not the full hierarchy) will be searched. Default is true.
                                ) {
        $ret = false;
        
        $children = $this->children();
        
        if (isset($children) && is_array($children) && count($children)) {
            foreach ($children as $object) {
                if ($object == $in_element) {
                    $ret = true;
                    break;
                } else {
                    if ($full_hierachy && method_exists($object, 'areYouMyDaddy')) {
                        if ($object->areYouMyDaddy($in_element)) {
                            $ret = true;
                            break;
                        }
                    }
                }
            }
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    This applies a given function to each of the elements in the child list.
    The function needs to have a signature of function mixed map_func(mixed $item);
    
    \returns a flat array of function results. The array maps to the children array.
     */
    public function map(    $in_function    ///< The function to be applied to each element.
                        ) {
        $ret = Array();
        
        $children = $this->children();
        
        foreach ($children as $child) {
            $result = $in_function($child);
            $ret[] = $result;
        }
        
        return self::class;
    }
    
    /***********************/
    /**
    This applies a given function to each of the elements in the child list, and any embedded (recursive) ones.
    The function needs to have a signature of function mixed map_func(mixed $item, integer $hierarchy_level, mixed $parent_object);
    
    \returns a flat array of function results. This array may be larger than the children array, as it will also contain any nested collections.
     */
    public function recursiveMap(   $in_function,               ///< This is the function to be applied to all elements.
                                    $in_hierarchy_level = 0,    ///< This is a 0-based integer that tells the callback how many "levels deep" the function is.
                                    $in_parent_object = NULL,   ///< This is the collection object that is the "parent" of the current array.
                                    $loop_stopper = Array()     /**< This is used to prevent "hierarchy loops."
                                                                     As we descend into recursion, we save the collection ID here.
                                                                     If the ID shows up in a "lower" collection, we don't add that collection.
                                                                     This shouldn't happen anyway, as were're not supposed to have been able to add embedded collections, but we can't be too careful.
                                                                     There can only be one...
                                                                */
                                ) {
        $in_hierarchy_level = intval($in_hierarchy_level);
        $ret = Array($in_function($this, $in_hierarchy_level, $in_parent_object));
        $children = $this->children();
        
        foreach ($children as $child) {
            if (method_exists($child, 'recursiveMap')) {
                if (!in_array($child->id(), $loop_stopper)) {
                    $loop_stopper[] = $child->id();
                    $result = $child->recursiveMap($in_function, ++$in_hierarchy_level, $this, $loop_stopper);
                }
            } else {
                $result = Array($in_function($child, ++$in_hierarchy_level, $this));
            }
            $ret = array_merge($ret, $result);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This counts the direct children of this collection, and returns that count.
    If recursive, then it counts everything inside, including owners.
    Remember that this is "security-aware." The collection may have children that are not visible to the current login.
        
    \returns the number of direct children.
     */
    public function count(  $is_recursive = false   ///< If true, then this will also count all "child" collections. Default is false.
                        ) {
        $children = $this->children();
        $my_count = 0;
        
        if (isset($children) && is_array($children)) {
            $my_count = count($children);
        
            if ($is_recursive) {
                foreach ($children as $child) {
                    if (method_exists($child, 'count')) {
                        $my_count += $child->count($is_recursive);
                    }
                }
            }
        }
        
        return $my_count;
    }
    
    /***********************/
    /**
    This is an accessor for the child object array (instances).
    It should be noted that this may not be the same as the 'children' context variable, because the user may not be allowed to see all of the items.
    
    \returns the child objects array.
     */
    public function children() {
        if (!isset($this->_container) || !$this->_container || (!count($this->_container))) {
            $this->_scrub();
            $this->_set_up_container();
        }
        return $this->_container;
    }
    
    /***********************/
    /**
    This is an accessor that returns a list of IDs for the direct children of this instance.
    This is "security vetted," so only IDs of children visible to the logged-in user are returned.
    
    \returns the child ids array (array of integer).
     */
    public function children_ids(   $in_raw = false    ///< This is only valid for "God Mode." If true, then the array is returned with no scrub, and no checks.
                                ) {
        $ret = [];
        if ($in_raw && $this->get_access_object()->god_mode()) {   // God gets it all.
            if (isset($this->context['children'])) {
                return (array_map('intval', explode(',', $this->context['children'])));
            } else {
                return [];
            }
        }
        
        $this->_scrub();
        if (isset($this->context['children_ids']) && is_array($this->context['children_ids']) && count ($this->context['children_ids'])) {
            $ids = $this->context['children_ids'];
            if ($this->get_access_object()->god_mode()) {   // God gets it all.
                $ret = $ids;
            } else {
                foreach ($ids as $id) {
                    if ($this->get_access_object()->item_exists($id, true)) {
                        $ret[] = intval($id);
                    }
                }
            }
        }
        
        return $ret;
    }
        
    /***********************/
    /**
    This is a "God Mode-only" method that is used to wholesale replace the entire children array.
    
    \returns true, if the operation was allowed and successful.
     */
    public function set_children_ids(   $in_new_ids ///< This is an array of integers, with the IDs of new children. This entirely replaces the current array.
                                    ) {
        $ret = false;
        
        if ($this->get_access_object()->god_mode()) {
            $this->_children = Array();
            $this->context['children'] = implode(',', $in_new_ids);
            unset($this->context['children_ids']);
            $ret = $this->update_db();
        }
        
        return $ret;
    }

    /***********************/
    /**
    \returns an array of any direct parents of the current object. The returned objects will be collection instances. An empty array will be returned if no parents found.
     */
    public function who_are_my_parents() {
        $ret = [];
        
        $result = $this->get_access_object()->generic_search(Array('access_class' => Array('%_Collection%', 'use_like' => 1)));
        
        if (isset($result) && is_array($result) && count($result)) {
            foreach ($result as $object) {
                if (($object instanceof CO_Main_DB_Record) && method_exists($object, 'areYouMyDaddy')) {
                    if ($object->areYouMyDaddy($this, false)) {
                        $ret[] = $object;
                    }
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an instance "map" of the collection. It returns an array of associative arrays.
    Each associative array has the following elements:
        - 'object' (Required). This is the actual instance that maps to this object.
        - 'children' (optional -may not be instantiated). This is an array of the same associative arrays for any "child objects" of the current object.
     */
    public function getHierarchy(   $loop_stopper = Array()     /**< This is used to prevent "hierarchy loops."
                                                                     As we descend into recursion, we save the collection ID here.
                                                                     If the ID shows up in a "lower" collection, we don't add that collection.
                                                                     This shouldn't happen anyway, as were're not supposed to have been able to add embedded collections, but we can't be too careful.
                                                                     There can only be one...
                                                                */
                                    ) {
        $this->_scrub();
        
        $instance = Array('object' => $this);
        
        if (method_exists($this, 'children') && count($this->children())) {
            $children = $this->children();
            $instance['children'] = Array();
        
            foreach ($children as $child) {
                if (method_exists($child, 'getHierarchy')) {
                    if (!in_array($child->id(), $loop_stopper)) {
                        $loop_stopper[] = $child->id();
                        $instance['children'][] = $child->getHierarchy($loop_stopper);
                    }
                } else {
                    $instance['children'][] = Array('object' => $child);
                }
            }
        }
        
        return $instance;
    }
}