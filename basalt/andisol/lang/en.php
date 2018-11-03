<?php
/***************************************************************************************************************************/
/**
    COBRA Security Administration Layer
    
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
defined( 'LGV_LANG_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

/***************************************************************************************************************************/
/**
 */
class CO_ANDISOL_Lang {
    static  $andisol_error_name_user_not_authorized = 'User Not Authorized';
    static  $andisol_error_desc_user_not_authorized = 'This user is not Authorized to Perform This Operation';
    static  $andisol_error_name_login_instance_failed_to_initialize = 'Login Failed to Initialize';
    static  $andisol_error_desc_login_instance_failed_to_initialize = 'The server was unable to create the requested login resource.';
    static  $andisol_error_name_login_instance_unavailable = 'Login Not Available';
    static  $andisol_error_desc_login_instance_unavailable = 'The requested login item was not available.';
    static  $andisol_error_name_user_instance_unavailable = 'User Not Available';
    static  $andisol_error_desc_user_instance_unavailable = 'The requested user item was not available.';
    static  $andisol_error_name_user_not_deleted = 'User Not Deleted';
    static  $andisol_error_desc_user_not_deleted = 'The user was not deleted by ANDISOL.';
    static  $andisol_error_name_login_not_deleted = 'Login Not Deleted';
    static  $andisol_error_desc_login_not_deleted = 'The login was not deleted by ANDISOL.';
    static  $andisol_error_name_insufficient_location_information = 'Insufficient Location Information';
    static  $andisol_error_desc_insufficient_location_information = 'The location creator needs more infomation to create the location.';
    static  $andisol_error_name_location_failed_to_initialize = 'Location Object Failed to Initialize';
    static  $andisol_error_desc_location_failed_to_initialize = 'The location object was not created.';
    static  $andisol_new_unnamed_user_name_format = 'New User %d';
}
?>