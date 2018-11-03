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
class CO_COBRA_Lang {
    static  $cobra_error_name_invalid_chameleon = 'Invalid CHAMELEON Instance';
    static  $cobra_error_desc_invalid_chameleon = 'COBRA cannot be initialized with the given CHAMELEON instance.';
    static  $cobra_error_name_user_not_authorized = 'Current User Not Authorized';
    static  $cobra_error_desc_user_not_authorized_instance = 'The current user is not authorized to instantiate COBRA.';
    static  $cobra_error_desc_user_not_authorized = 'The current user is not authorized to create user objects.';
    static  $cobra_error_name_instance_failed_to_initialize = 'User Not Initialized';
    static  $cobra_error_desc_instance_failed_to_initialize = 'The user object failed to initialize properly.';
    static  $cobra_error_name_user_already_exists = 'User Already Exists';
    static  $cobra_error_desc_user_already_exists = 'The specified user already exists.';
    static  $cobra_error_name_login_unavailable = 'The Login Is Unavailable';
    static  $cobra_error_desc_login_unavailable = 'The reqested login is unavailable to this user.';
    static  $cobra_error_name_login_error = 'Login Error';
    static  $cobra_error_desc_login_error = 'There was an unspecified error with this login.';
    static  $cobra_error_name_token_instance_failed_to_initialize = 'Token Not Initialized';
    static  $cobra_error_desc_token_instance_failed_to_initialize = 'The security token object failed to initialize properly.';
    static  $cobra_error_name_token_id_not_set = 'Token ID Not Set';
    static  $cobra_error_desc_token_id_not_set = 'The security token object was not created, because the ID could not be set.';
    static  $cobra_error_name_password_too_short = 'Password Too Short';
    static  $cobra_error_desc_password_too_short = 'The password is too short.';
}
?>