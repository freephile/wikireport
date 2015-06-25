<?php

/**
 * A reporting interface to the MediaWiki API interfacing with the CiviCRM API
 *
 * @copyright 2015 Gregory Scott Rundlett <greg@freephile.com>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program in the LICENSE file
 *  If not, see <http://www.gnu.org/licenses/>.
 */


$loader = require 'vendor/autoload.php';

// ----------------------------------------------------------------------------
// object-oriented client code
// -----------------------------------------------------------------------------
// $v = "freephile.org";
// $v = "equality-tech.com";
// $obj = new \eqt\wikireport\UrlFixer($v);
// $obj->prefix_scheme();
// $obj->validate_url();
// $obj->find_redirect();
// echo $obj->url;
// echo $obj;

// could use https://php.net/manual/en/function.filter-input-array.php to setup sanitization for the whole form
// if there are multiple inputs, but we only need to deal with email and URL
// will not work with non-ascii domains, but we're only in the U.S.
// $wikiUrl = filter_input(INPUT_GET, 'wikiUrl', FILTER_VALIDATE_URL);

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
$config = CRM_Core_Config::singleton();
//civicrm_initialize();

/**
 * Utility for printing
 */
function pre_print($result) {
    echo "<pre>\n";
    print_r($result);
    echo "</pre>\n";
}

function getEntity($entity, $params) {
    // Check if CiviCRM is installed here.
    if (!module_exists('civicrm')) {
        return false;
    }
    // Initialization call is required to use CiviCRM APIs.
    civicrm_initialize();
    try {
        $result = civicrm_api3($entity, 'get', $params);
    } catch (CiviCRM_API3_Exception $e) {
        $errorMessage = $e->getMessage();
        $errorCode = $e->getErrorCode();
        $errorData = $e->getExtraParams();
        return array(
            'error' => $errorMessage,
            'error_code' => $errorCode,
            'error_data' => $errorData
        );
    }
    if (!$result) {
        return false;
    }
    
    return $result['values'];
}


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

//   A chained API call results in a deeply nested array

function createEntity($entity, $params) {
    // Check if CiviCRM is installed here.
    if (!module_exists('civicrm'))
        return false;
    // Initialization call is required to use CiviCRM APIs.
    civicrm_initialize();
    try {
        $result = civicrm_api3($entity, 'create', $params);
    } catch (CiviCRM_API3_Exception $e) {
        $errorMessage = $e->getMessage();
        $errorCode = $e->getErrorCode();
        $errorData = $e->getExtraParams();
        return array(
            'error' => $errorMessage,
            'error_code' => $errorCode,
            'error_data' => $errorData
        );
    }
    if (!$result)
        return false;
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


// See the data
// -----------------------------------------------------------------------------
// pre_print($result);
// $result['values'] is an array where each member looks like this
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


// object client code
/*
 * 
$result = civicrm_api3('Note', 'get', array(
  'sequential' => 1,
  'note' => array('IS NOT NULL' => 1),
  'options' => array('limit' => 2),
));

if ( count($result['values']) ) {
    echo "Working on " . count($result['values']) . " results.\n<br />";    
}
// pre_print($result['values']);

foreach ($result['values'] as $k => $v) {
    // if it's semi-colon separated, make an array
    // if it's just a string, we'll still get an array
    $urls = explode(';', $v['note']);
    // those with multiple values are often/always duplicates
    $urls = array_unique($urls);
    //echo (count($urls) > 1) ? "working on multiple\n<br />" : "";
    foreach ($urls as $key => $value) {
        // echo nl2br("Working on $value\n");
        $obj = new \eqt\wikireport\UrlWiki($value);
        $obj->prefix_scheme();
        $obj->validate_url();
        $obj->find_redirect();
        $isWiki = $obj->isWiki();
        // echo $obj->url . "<br />";
        echo $obj->__toString() . "\n<br />";
        echo "it's a wiki? <strong>$isWiki</strong>\n<br />";
        // echo "We should update " . $v['entity_id'] . " in " . $v['entity_table'] . "\n<br />";
    } 
}
*/

$t = "http:freephile.org";
$t = "http://freephile.org";

$url = new \eqt\wikireport\UrlWiki($t);

echo "We started with $t.  Is it a wiki?<br />";

echo ($url->isWiki())? "yes<br />" : "no<br />";

echo "ok, then let's prefix it<br />";

$url->prefix_scheme();

echo "<hr>Here's our transcript<br />";
echo $url->__toString();