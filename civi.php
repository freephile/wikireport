<?php


class UrlFixer {

  public $url;


}


function html_linkify (&$v) {
  if ( is_string($v) && strlen($v) ) {
    if ( parse_url($v, PHP_URL_SCHEME)  ) {
      $v = "<a href=\"$v\">$v</a>";
    }
    return true;
  }
  return false;
}

function prefix_scheme (&$v, $scheme='http://') {
    $v = "$scheme$v";
}


function fix_note (&$v) {
  $a = explode(';', $v); // if it's semi-colon separated, make an array
  foreach ($a as $key => $value) {
    $a[$key] = prefix_sheme($value);
    if (! sanitize_url($a[$key]) ) {
      die ('url not valid' . $a[$key]);
    }
    

    
    //find_redirect($a[$key]);
  }
  $v = implode(';', (array) $a); // implode on a string returns null!
}


/*
record 0 (2315)
http://wiki.mozilla.org for 3908
record 1 (2316)
http://ballotpedia.org for 3909
record 2 (2317)
http://wikihow.com for 3910
record 3 (2318)
http://wikicafe.metacafe.com for 3911
record 4 (2319)
http://wiki.algebra.com for 3912
record 5 (2320)
http://familysearch.org for 3913
record 6 (2321)
http://wowwiki.com for 3914
record 7 (2322)
http://help.pingg.com for 3915
record 8 (2323)
http://help.usajobs.gov for 3916
record 9 (2324)
http://baseball-reference.com for 3917
*/



function find_redirect (&$v) {
  $headers = get_headers($v, 1);
  if ( ! stristr($headers[0], '200') ) {
    if ( isset($headers['Location']) ) {
      // pickup the new target
      $v = array_pop($headers['Location']);
      $msg = "NEW $v";
      return $msg;
    }
  }

}

$v = "http://freephile.org";

$v = find_redirect($v);
echo $v;


// could use https://php.net/manual/en/function.filter-input-array.php to setup sanitization for the whole form
// if there are multiple inputs, but we only need to deal with email and URL
// will not work with non-ascii domains, but we're only in the U.S.
// $wikiUrl = filter_input(INPUT_GET, 'wikiUrl', FILTER_VALIDATE_URL);
function sanitize_url (&$v) {
  $v = filter_var($v, FILTER_SANITIZE_URL); // clean
  $v = filter_var($v, FILTER_VALIDATE_URL); // valid
  return ($v) ? $v : false;
}


function is_valid_url ($url="") {
    if ($url=="") {
        //$url=$this->url;
        return false;
    }
    $url = @parse_url($url);
    if ( ! $url) {
        return false;
    }
    $url = array_map('trim', $url);
    $url['port'] = (!isset($url['port'])) ? 80 : (int)$url['port'];
    $path = (isset($url['path'])) ? $url['path'] : '';

    if ($path == '') {
        $path = '/';
    }
    $path .= ( isset ( $url['query'] ) ) ? "?$url[query]" : '';
    if ( isset ( $url['host'] ) AND $url['host'] != gethostbyname ( $url['host'] ) ) {
        if ( PHP_VERSION >= 5 ) {
            $headers = get_headers("$url[scheme]://$url[host]:$url[port]$path");
        }
        else {
            $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);
            if ( ! $fp ) {
                return false;
            }
            fputs($fp, "HEAD $path HTTP/1.1\r\nHost: $url[host]\r\n\r\n");
            $headers = fread ( $fp, 128 );
            fclose ( $fp );
        }
        $headers = ( is_array ( $headers ) ) ? implode ( "\n", $headers ) : $headers;
        return ( bool ) preg_match ( '#^HTTP/.*\s+[(200|301|302)]+\s#i', $headers );
    }
    return false;
}

/* 
 * Client code using CiviCRM API
 * 
 * We want to be able to test if a wiki website is in our database.
 * If it is, then document the features of that wiki website
 * 
 * Given a contact email, check if that email is in our database.
 * If it is not, then associate it with the website in question.
 */

// first bootstrap Drupal
// https://api.drupal.org/api/drupal/includes!bootstrap.inc/function/drupal_bootstrap/7
define('DRUPAL_ROOT', '/var/www/equality-tech.com/www/drupal/');
require_once DRUPAL_ROOT . 'includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);


// then bootstrap CiviCRM
require_once DRUPAL_ROOT . 'sites/default/civicrm.settings.php';
//require_once  DRUPAL_ROOT . 'sites/all/modules/contrib/civicrm/CRM/Core/Config.php';
// require_once  DRUPAL_ROOT . 'sites/all/modules/contrib/civicrm/api/api.php';
$config = CRM_Core_Config::singleton( );
//civicrm_initialize();


/**
 * Utility for printing
 */
function pre_print ($result) {
  echo "<pre>\n";
  print_r ($result);
  echo "</pre>\n";
}

/**
 * Example demonstrating the Website.get API.
 *
 * @return array
 *   API result array

function doesWebsiteExist( $website ) {
  // Check if CiviCRM is installed here.
  if (!module_exists('civicrm')) return false;

  // Initialization call is required to use CiviCRM APIs.
  civicrm_initialize( );

  $params = array(
    'sequential' => 1,
    'url' => array('LIKE' => "%$website%")
  );
  try {
    $result = civicrm_api3( 'Website', 'get', $params );
  }
  catch (CiviCRM_API3_Exception $e) {
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData
    );
  }
  if (!$result) return false;
  return $result;
}

// example invocation
$myContact = doesWebsiteExist('100frontiers.com');

Array
(
    [is_error] => 0
    [version] => 3
    [count] => 1
    [id] => 2525
    [values] => Array
        (
            [0] => Array
                (
                    [id] => 2525
                    [contact_id] => 4327
                    [url] => http://100frontiers.com
                    [website_type_id] => 2
                )

        )

)

if ($myContact) {
  echo "We've found a website (#{$myContact['values'][0]['id']}) 
  of {$myContact['values'][0]['url']} 
  of type {$myContact['values'][0]['website_type_id']} 
  for contact id {$myContact['values'][0]['contact_id']}";
}
*/


function getEntity( $entity, $params ) {
  // Check if CiviCRM is installed here.
  if (!module_exists('civicrm')) return false;
  // Initialization call is required to use CiviCRM APIs.
  civicrm_initialize( );
  try {
    $result = civicrm_api3( $entity, 'get', $params );
  }
  catch (CiviCRM_API3_Exception $e) {
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData
    );
  }
  if (!$result) return false;
  return $result['values'];
}

$params = array (
  'sequential' => 1,
  'id' => 4327);
$myContact = getEntity('contact', $params);

if ($myContact) {
  // pre_print($myContact);
}

/* A contact will look like this:
                    [contact_id] => 4327
                    [contact_type] => Organization
                    [contact_sub_type] => 
                    [sort_name] => 100frontiers.com
                    [display_name] => 100frontiers.com
                    [do_not_email] => 0
                    [do_not_phone] => 0
                    [do_not_mail] => 0
                    [do_not_sms] => 0
                    [do_not_trade] => 0
                    [is_opt_out] => 0
                    [legal_identifier] => 
                    [external_identifier] => 459
                    [nick_name] => 
                    [legal_name] => 
                    [image_URL] => 
                    [preferred_communication_method] => 
                    [preferred_language] => en_US
                    [preferred_mail_format] => Both
                    [first_name] => 
                    [middle_name] => 
                    [last_name] => 
                    [prefix_id] => 
                    [suffix_id] => 
                    [formal_title] => 
                    [communication_style_id] => 
                    [job_title] => 
                    [gender_id] => 
                    [birth_date] => 
                    [is_deceased] => 0
                    [deceased_date] => 
                    [household_name] => 
                    [organization_name] => 100frontiers.com
                    [sic_code] => 
                    [contact_is_deleted] => 0
                    [current_employer] => 
                    [address_id] => 1649
                    [street_address] => 
                    [supplemental_address_1] => 
                    [supplemental_address_2] => 
                    [city] => Mineral
                    [postal_code_suffix] => 
                    [postal_code] => 78125
                    [geo_code_1] => 
                    [geo_code_2] => 
                    [state_province_id] => 1042
                    [country_id] => 1228
                    [phone_id] => 
                    [phone_type_id] => 
                    [phone] => 
                    [email_id] => 
                    [email] => 
                    [on_hold] => 
                    [im_id] => 
                    [provider_id] => 
                    [im] => 
                    [worldregion_id] => 2
                    [world_region] => America South, Central, North and Caribbean
                    [individual_prefix] => 
                    [individual_suffix] => 
                    [communication_style] => 
                    [gender] => 
                    [state_province_name] => TX
                    [state_province] => TX
                    [country] => United States
                    [id] => 4327
*/

// chained API call
$website = '100frontiers.com';
$params = array(
  'sequential' => 1,
  'url' => array('LIKE' => "%$website%"),
  'api.Contact.get' => array(),
);
/*  
if ( $result = getEntity('website', $params) ) {
  pre_print ($result);
}
*/
/*
A chained API call results in a deeply nested array
Array
(
    [is_error] => 0
    [version] => 3
    [count] => 1
    [id] => 2525
    [values] => Array
        (
            [0] => Array
                (
                    [id] => 2525
                    [contact_id] => 4327
                    [url] => http://100frontiers.com
                    [website_type_id] => 2
                    [api.Contact.get] => Array
                        (
                            [is_error] => 0
                            [version] => 3
                            [count] => 1
                            [id] => 4327
                            [values] => Array
                                (
                                    [0] => Array
                                        (
                                            [contact_id] => 4327
                                            [contact_type] => Organization
                                            [contact_sub_type] => 
                                            [sort_name] => 100frontiers.com
                                            [display_name] => 100frontiers.com
                                            [do_not_email] => 0
                                            [do_not_phone] => 0
                                            [do_not_mail] => 0
                                            [do_not_sms] => 0
                                            [do_not_trade] => 0
                                            [is_opt_out] => 0
                                            [legal_identifier] => 
                                            [external_identifier] => 459
                                            [nick_name] => 
                                            [legal_name] => 
                                            [image_URL] => 
                                            [preferred_communication_method] => 
                                            [preferred_language] => en_US
                                            [preferred_mail_format] => Both
                                            [first_name] => 
                                            [middle_name] => 
                                            [last_name] => 
                                            [prefix_id] => 
                                            [suffix_id] => 
                                            [formal_title] => 
                                            [communication_style_id] => 
                                            [job_title] => 
                                            [gender_id] => 
                                            [birth_date] => 
                                            [is_deceased] => 0
                                            [deceased_date] => 
                                            [household_name] => 
                                            [organization_name] => 100frontiers.com
                                            [sic_code] => 
                                            [contact_is_deleted] => 0
                                            [current_employer] => 
                                            [address_id] => 1649
                                            [street_address] => 
                                            [supplemental_address_1] => 
                                            [supplemental_address_2] => 
                                            [city] => Mineral
                                            [postal_code_suffix] => 
                                            [postal_code] => 78125
                                            [geo_code_1] => 
                                            [geo_code_2] => 
                                            [state_province_id] => 1042
                                            [country_id] => 1228
                                            [phone_id] => 
                                            [phone_type_id] => 
                                            [phone] => 
                                            [email_id] => 
                                            [email] => 
                                            [on_hold] => 
                                            [im_id] => 
                                            [provider_id] => 
                                            [im] => 
                                            [worldregion_id] => 2
                                            [world_region] => America South, Central, North and Caribbean
                                            [individual_prefix] => 
                                            [individual_suffix] => 
                                            [communication_style] => 
                                            [gender] => 
                                            [state_province_name] => TX
                                            [state_province] => TX
                                            [country] => United States
                                            [id] => 4327
                                        )

                                )

                        )

                )

        )

)
*/
function createEntity( $entity, $params ) {
  // Check if CiviCRM is installed here.
  if (!module_exists('civicrm')) return false;
  // Initialization call is required to use CiviCRM APIs.
  civicrm_initialize( );
  try {
    $result = civicrm_api3( $entity, 'create', $params );
  }
  catch (CiviCRM_API3_Exception $e) {
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData
    );
  }
  if (!$result) return false;
  return $result;
}


// when creating an individual record, you can input the "organization_name" because
// it's just a text value in the contact table
 $params = array(
  'sequential' => 1,
  'contact_type' => "Individual",
  'first_name' => "Richard",
  'last_name' => "Noll",
  // 'organization_name' => "Activeworlds  Inc.", // "contact_id": "4143"
  'api.Relationship.create' => array(
    'sequential' => "1", 
    'relationship_type_id' => "5", 
    'contact_id_a' => "\$value.id",
    'contact_id_b' => "4143"
    ),
  "debug" => "1",
);

// Create a Contact, with a nested call to create a relationship (employer)
// $result = createEntity('Contact', $params);
// pre_print($result);

// I have added a new "website type" in the admin UI (Wiki => 16)
// Now, that means we can associate 'wiki' URLs with contacts of all kinds. 
// Existing URLs are in the notes table, and need to be exploded and migrated to the website table.
// This is problematic in that MySQL doesn't do explode.  May be best to do with PHP interaction with the database, probably through the API.
// Once all the existing URLs are in the website table, then we can begin to query our CRM data for extant URLs (from the report interface), 
// and load new ones if not found.
// We still need to work out how to store the wiki data (custom fields, and profile?)

// If we have some new wiki URL, we have to 
// a) search for that URL to see if we have it.
// b) create a new record for that URL, plus a new contact based on that URL.


/*
Array
(
    [is_error] => 0
    [undefined_fields] => Array
        (
            [0] => contact_type
            [1] => first_name
            [2] => last_name
            [3] => api.Relationship.create
        )

    [version] => 3
    [count] => 1
    [id] => 6061
    [values] => Array
        (
            [0] => Array
                (
                    [id] => 6061
                    [contact_type] => Individual
                    [contact_sub_type] => 
                    [do_not_email] => 0
                    [do_not_phone] => 0
                    [do_not_mail] => 0
                    [do_not_sms] => 0
                    [do_not_trade] => 0
                    [is_opt_out] => 0
                    [legal_identifier] => 
                    [external_identifier] => 
                    [sort_name] => Noll, Rick
                    [display_name] => Rick Noll
                    [nick_name] => 
                    [legal_name] => 
                    [image_URL] => 
                    [preferred_communication_method] => 
                    [preferred_language] => en_US
                    [preferred_mail_format] => Both
                    [hash] => ccc327aae6daf5210b64b53f1cc4d508
                    [api_key] => 
                    [first_name] => Rick
                    [middle_name] => 
                    [last_name] => Noll
                    [prefix_id] => 
                    [suffix_id] => 
                    [formal_title] => 
                    [communication_style_id] => 
                    [email_greeting_id] => 1
                    [email_greeting_custom] => 
                    [email_greeting_display] => 
                    [postal_greeting_id] => 1
                    [postal_greeting_custom] => 
                    [postal_greeting_display] => 
                    [addressee_id] => 1
                    [addressee_custom] => 
                    [addressee_display] => 
                    [job_title] => 
                    [gender_id] => 
                    [birth_date] => 
                    [is_deceased] => 0
                    [deceased_date] => 
                    [household_name] => 
                    [primary_contact_id] => 
                    [organization_name] => 
                    [sic_code] => 
                    [user_unique_id] => 
                    [created_date] => 2015-06-18 15:32:30
                    [modified_date] => 2015-06-18 15:32:30
                    [api.Relationship.create] => Array
                        (
                            [is_error] => 0
                            [undefined_fields] => Array
                                (
                                    [0] => is_active
                                    [1] => entity_id
                                    [2] => entity_table
                                    [3] => api.has_parent
                                    [4] => relationship_type_id
                                    [5] => contact_id_a
                                    [6] => contact_id_b
                                )

                            [version] => 3
                            [count] => 1
                            [id] => 2613
                            [values] => Array
                                (
                                    [0] => Array
                                        (
                                            [id] => 2613
                                            [contact_id_a] => 6061
                                            [contact_id_b] => 4143
                                            [relationship_type_id] => 5
                                            [start_date] => NULL
                                            [end_date] => NULL
                                            [is_active] => 1
                                            [description] => 
                                            [is_permission_a_b] => 0
                                            [is_permission_b_a] => 0
                                            [case_id] => 
                                        )

                                )

                        )

                )

        )

)
*/


// See the data

/*
$result = civicrm_api3('Note', 'get', array(
  'sequential' => 1,
  'note' => array('IS NOT NULL' => 1),
  'options' => array('limit' => 10),
));

// pre_print($result);

//      [2] => Array
//          (
//              [id] => 2317
//              [entity_table] => civicrm_contact
//              [entity_id] => 3910
//              [note] => wikihow.com
//              [contact_id] => 2
//              [modified_date] => 2015-05-11
//              [privacy] => 0
//          )


$return = '';
foreach ($result['values'] as $k => $v) {
  $return .= "record $k ({$v['id']})\n";
  fix_note ($v['note']);
  //$return .= ( is_valid_url ($v['note']) )? "valid " : "INVALID ";
  $return .= "{$v['note']} for {$v[entity_id]}\n";
}
echo nl2br($return);
*/



// $headers = get_headers("http://freephile.org", 1);
// pre_print($headers);
/*
$headers = get_headers("http://freephile.org");
Array
(
    [0] => HTTP/1.1 301 Moved Permanently
    [1] => Date: Tue, 23 Jun 2015 19:17:54 GMT
    [2] => Server: Apache/2.4.7 (Ubuntu)
    [3] => Location: https://freephile.org/
    [4] => Content-Length: 308
    [5] => Connection: close
    [6] => Content-Type: text/html; charset=iso-8859-1
    [7] => HTTP/1.1 301 Moved Permanently
    [8] => Date: Tue, 23 Jun 2015 19:17:54 GMT
    [9] => Server: Apache/2.4.7 (Ubuntu)
    [10] => X-Content-Type-Options: nosniff
    [11] => Set-Cookie: bb2_screener_=1435087074+104.236.31.19; path=/w/
    [12] => Vary: Accept-Encoding,Cookie
    [13] => Expires: Thu, 01 Jan 1970 00:00:00 GMT
    [14] => Cache-Control: private, must-revalidate, max-age=0
    [15] => Last-Modified: Tue, 23 Jun 2015 19:17:54 GMT
    [16] => Location: https://freephile.org/wiki/Main_Page
    [17] => Content-Length: 0
    [18] => Content-Type: text/html; charset=utf-8
    [19] => HTTP/1.1 200 OK
    [20] => Date: Tue, 23 Jun 2015 19:17:54 GMT
    [21] => Server: Apache/2.4.7 (Ubuntu)
    [22] => X-Content-Type-Options: nosniff
    [23] => Set-Cookie: bb2_screener_=1435087074+104.236.31.19; path=/w/
    [24] => Content-language: en
    [25] => X-UA-Compatible: IE=Edge
    [26] => X-Frame-Options: SAMEORIGIN
    [27] => Vary: Accept-Encoding,Cookie
    [28] => Expires: Thu, 01 Jan 1970 00:00:00 GMT
    [29] => Cache-Control: no-cache, no-store, max-age=0, must-revalidate
    [30] => Pragma: no-cache
    [31] => Connection: close
    [32] => Content-Type: text/html; charset=UTF-8
)
$headers = get_headers("https://freephile.org");
$headers = get_headers("https://freephile.org/wiki/");
Array
(
    [0] => HTTP/1.1 301 Moved Permanently
    [1] => Date: Tue, 23 Jun 2015 19:20:08 GMT
    [2] => Server: Apache/2.4.7 (Ubuntu)
    [3] => X-Content-Type-Options: nosniff
    [4] => Set-Cookie: bb2_screener_=1435087208+104.236.31.19; path=/w/
    [5] => Vary: Accept-Encoding,Cookie
    [6] => Expires: Thu, 01 Jan 1970 00:00:00 GMT
    [7] => Cache-Control: private, must-revalidate, max-age=0
    [8] => Last-Modified: Tue, 23 Jun 2015 19:20:09 GMT
    [9] => Location: https://freephile.org/wiki/Main_Page
    [10] => Content-Length: 0
    [11] => Content-Type: text/html; charset=utf-8
    [12] => HTTP/1.1 200 OK
    [13] => Date: Tue, 23 Jun 2015 19:20:09 GMT
    [14] => Server: Apache/2.4.7 (Ubuntu)
    [15] => X-Content-Type-Options: nosniff
    [16] => Set-Cookie: bb2_screener_=1435087209+104.236.31.19; path=/w/
    [17] => Content-language: en
    [18] => X-UA-Compatible: IE=Edge
    [19] => X-Frame-Options: SAMEORIGIN
    [20] => Vary: Accept-Encoding,Cookie
    [21] => Expires: Thu, 01 Jan 1970 00:00:00 GMT
    [22] => Cache-Control: no-cache, no-store, max-age=0, must-revalidate
    [23] => Pragma: no-cache
    [24] => Connection: close
    [25] => Content-Type: text/html; charset=UTF-8
)

$headers = get_headers("https://freephile.org/wiki/Main_Page");
Array
(
    [0] => HTTP/1.1 200 OK
    [1] => Date: Tue, 23 Jun 2015 19:22:59 GMT
    [2] => Server: Apache/2.4.7 (Ubuntu)
    [3] => X-Content-Type-Options: nosniff
    [4] => Set-Cookie: bb2_screener_=1435087379+104.236.31.19; path=/w/
    [5] => Content-language: en
    [6] => X-UA-Compatible: IE=Edge
    [7] => X-Frame-Options: SAMEORIGIN
    [8] => Vary: Accept-Encoding,Cookie
    [9] => Expires: Thu, 01 Jan 1970 00:00:00 GMT
    [10] => Cache-Control: no-cache, no-store, max-age=0, must-revalidate
    [11] => Pragma: no-cache
    [12] => Connection: close
    [13] => Content-Type: text/html; charset=UTF-8
)

When you pass the second, optional, argument to get_headers, the 
function follows redirects (and returns everything in an associative array.
You don't even need to bother with logic to figure out the real 
URL...just use the last value of ['Location']; or the original if [0] is 200
Array
(
    [0] => HTTP/1.1 301 Moved Permanently
    [Date] => Array
        (
            [0] => Tue, 23 Jun 2015 19:30:49 GMT
            [1] => Tue, 23 Jun 2015 19:30:49 GMT
            [2] => Tue, 23 Jun 2015 19:30:50 GMT
        )

    [Server] => Array
        (
            [0] => Apache/2.4.7 (Ubuntu)
            [1] => Apache/2.4.7 (Ubuntu)
            [2] => Apache/2.4.7 (Ubuntu)
        )

    [Location] => Array
        (
            [0] => https://freephile.org/
            [1] => https://freephile.org/wiki/Main_Page
        )

    [Content-Length] => Array
        (
            [0] => 308
            [1] => 0
        )

    [Connection] => Array
        (
            [0] => close
            [1] => close
        )

    [Content-Type] => Array
        (
            [0] => text/html; charset=iso-8859-1
            [1] => text/html; charset=utf-8
            [2] => text/html; charset=UTF-8
        )

    [1] => HTTP/1.1 301 Moved Permanently
    [X-Content-Type-Options] => Array
        (
            [0] => nosniff
            [1] => nosniff
        )

    [Set-Cookie] => Array
        (
            [0] => bb2_screener_=1435087850+104.236.31.19; path=/w/
            [1] => bb2_screener_=1435087850+104.236.31.19; path=/w/
        )

    [Vary] => Array
        (
            [0] => Accept-Encoding,Cookie
            [1] => Accept-Encoding,Cookie
        )

    [Expires] => Array
        (
            [0] => Thu, 01 Jan 1970 00:00:00 GMT
            [1] => Thu, 01 Jan 1970 00:00:00 GMT
        )

    [Cache-Control] => Array
        (
            [0] => private, must-revalidate, max-age=0
            [1] => no-cache, no-store, max-age=0, must-revalidate
        )

    [Last-Modified] => Tue, 23 Jun 2015 19:30:50 GMT
    [2] => HTTP/1.1 200 OK
    [Content-language] => en
    [X-UA-Compatible] => IE=Edge
    [X-Frame-Options] => SAMEORIGIN
    [Pragma] => no-cache
)
*/



