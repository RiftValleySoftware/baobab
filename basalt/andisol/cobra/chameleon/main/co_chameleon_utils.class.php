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
defined( 'LGV_CHAMELEON_UTILS_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

/***************************************************************************************************************************/
/**
 */
class CO_Chameleon_Utils {    
    /***********************************************************************************************************************/
    /***********************/
    /**
        \brief This is a function that returns the results of an HTTP call to a URI.
        It is a lot more secure than file_get_contents, but does the same thing.
    
        \returns a string, containing the response. Null if the call fails to get any data.
    */
    static function call_curl (	$in_uri,				        ///< A string. The URI to call.
                                $in_post = false,		        ///< If true, the transaction is a POST, not a GET. Default is false.
                                &$http_status = NULL,           ///< Optional reference to a string. Returns the HTTP call status.
                                &$content_failure_note = NULL   ///< If there's a content failure, instead of throwing an exception, we will put it in here (if provided).
                                ) {
        $ret = null;
    
        // If the curl extension isn't loaded, we're screwed.
        if (extension_loaded('curl')) {
            // This gets the session as a cookie.
            if (isset($_COOKIE['PHPSESSID']) && $_COOKIE['PHPSESSID']) {
                $strCookie = 'PHPSESSID='.$_COOKIE['PHPSESSID'].'; path=/';

                session_write_close();
            }

            // Create a new cURL resource.
            $resource = curl_init();
        
            if (isset($strCookie)) {
                curl_setopt($resource, CURLOPT_COOKIE, $strCookie);
            }
        
            // If we will be POSTing this transaction, we split up the URI.
            if ($in_post) {
                $spli = explode("?", $in_uri, 2);
            
                if (is_array($spli) && count($spli)) {
                    $in_uri = $spli[0];
                    $in_params = $spli[1];
                    // Convert query string into an array using parse_str(). parse_str() will decode values along the way.
                    parse_str($in_params, $temp);
                
                    // Now rebuild the query string using http_build_query(). It will re-encode values along the way.
                    // It will also take original query string params that have no value and appends a "=" to them
                    // thus giving them and empty value.
                    $in_params = http_build_query($temp);
            
                    curl_setopt($resource, CURLOPT_POST, true);
                    curl_setopt($resource, CURLOPT_POSTFIELDS, $in_params);
                }
            }
        
            // Set url to call.
            curl_setopt($resource, CURLOPT_URL, $in_uri);
        
            // Make curl_exec() function (see below) return requested content as a string (unless call fails).
            curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        
            // By default, cURL prepends response headers to string returned from call to curl_exec().
            // You can control this with the below setting.
            // Setting it to false will remove headers from beginning of string.
            // If you WANT the headers, see the Yahoo documentation on how to parse with them from the string.
            curl_setopt($resource, CURLOPT_HEADER, false);
        
            // Set maximum times to allow redirection (use only if needed as per above setting. 3 is sort of arbitrary here).
            curl_setopt($resource, CURLOPT_MAXREDIRS, 3);
        
            // Set connection timeout in seconds (very good idea).
            curl_setopt($resource, CURLOPT_CONNECTTIMEOUT, 10);
        
            // Direct cURL to send request header to server allowing compressed content to be returned and decompressed automatically (use only if needed).
            curl_setopt($resource, CURLOPT_ENCODING, 'gzip,deflate');
            
            // Pretend we're a browser, so that anti-cURL settings don't pooch us.
            curl_setopt($resource, CURLOPT_USERAGENT, "cURL Mozilla/5.0 (Windows NT 5.1; rv:21.0) Gecko/20130401 Firefox/21.0 CHAMELEON/BADGER"); 

            // Trust meeeee...
            curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, false);
    
            // Execute cURL call and return results in $content variable.
            $content = curl_exec($resource);
        
            // Check if curl_exec() call failed (returns false on failure) and handle failure.
            if (false === $content) {
                // Cram as much info into the content note as possible.
                $content_failure_note = "curl failure calling $in_uri, ".curl_error($resource).", ".curl_errno ($resource);
            } else {
                // Do what you want with returned content (e.g. HTML, XML, etc) here or AFTER curl_close() call below as it is stored in the $content variable.
        
                // You MIGHT want to get the HTTP status code returned by server (e.g. 200, 400, 500).
                // If that is the case then this is how to do it.
                $http_status = curl_getinfo($resource, CURLINFO_HTTP_CODE);
            }
        
            // Close cURL and free resource.
            curl_close ($resource);
        
            // Maybe echo $contents of $content variable here.
            if (false !== $content) {
                $ret = $content;
            }
        }
    
        return $ret;
    }
};
