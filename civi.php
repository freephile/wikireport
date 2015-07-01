<head>
    <style>
        .msg {
            background-color:gray;
            font-size:0.8em;
        }
    </style>
</head>
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

/*
 * Client code using CiviCRM API
 * 
 * We want to be able to test if a wiki website is in our database.
 * If it is, then document the features of that wiki website
 * 
 * Given a contact email, check if that email is in our database.
 * If it is not, then associate it with the website in question.
 * 
// example of a Chained API call
// Note the param 'api.Contact.get'
// A chained API call results in a deeply nested array
// $website = '100frontiers.com';
// $params = array(
//     'sequential' => 1,
//     'url' => array('LIKE' => "%$website%"),
//     'api.Contact.get' => array(),
// );
//
// $CiviApi = new \eqt\wikireport\CiviApi();
// $result = $CiviApi->makeCall('website', 'get' ,$params);
// pre_print($result);


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
 *
 * 
 * 
 * 
 */

function explodeNote ($note) {
    if (strstr($note['note'], ';')) {
        print "exploding " . $note['id'] . " having " . $note['note'] . " <br />\n";
        $urls = explode(';', $note['note']);
        $count = count($urls);
        $counter = 0;
        foreach ($urls as $url) {
            $params =  array (
                'entity_table' => 'civicrm_contact',
                'entity_id' => $note['entity_id'],
                'note' => $url,
                'contact_id' => 2,
            );
            $CiviApi = new \eqt\wikireport\CiviApi();
            $result = $CiviApi->makeCall('Note', 'create', $params);
            if ($result) {
                $counter++;
            }
        }
        echo "Counter hit $counter for note {$note['id']}<br />\n";
        if ($counter == $count) {
            $params =  array (
                'id' => $note['id'],
            );            
            $CiviApi = new \eqt\wikireport\CiviApi();
            $result = $CiviApi->makeCall('Note', 'delete', $params);
            if ($result) {
                echo "<div>Cleaned up the original note {$note['id']} "
                . "that contained $count urls.  See "
                . "<a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid={$note['entity_id']}\">contact {$note['entity_id']}</a>'s record</div>\n";
            } else {
                echo "Something went wrong with cleaning up the original note for {$note['id']}<br />\n";
            }
        } else {
            echo "Test for $counter = $count didn't work out for {$note['id']}?<br />\n";
        }
    } else {
        print "skipping " . $note['id'] . " having " . $note['note'] . " <br />\n";
    }
    
}


/**
 * A convenience function to check the contents of a note for a valid wikiUrl
 * and if so, create a website record tied to the same contact as the note.
 * 
 * @param array $note is the array returned from the API when you get a note
 * @param bool $delete whether the note should be deleted after processing
 */
function processNote ($note, $delete=false) {
    $note_id   = $note['id'];
    $entity_id = $note['entity_id'];
    $url       = trim($note['note']);
    $staleNotes = array();
    // extract the url and turn it into a wiki website url
    $wurl    = new \eqt\wikireport\UrlWiki($url);
    if ( $wurl->isWiki() ) {
        $website_id = getWebsiteId($wurl->wikiUrl); // check if it already exists
        // now we can call create; with or without an id
        $params = array(
            'id' => $website_id,
            'contact_id' => $entity_id,
            'url' => $wurl->wikiUrl,
            'website_type_id' => 'wiki',
        ); 
        $result = createWebsiteRecord($params);
        if ( $result ) {
            echo "<div>remapped $url to $wurl->wikiUrl and created/updated website record for <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$entity_id\">contact $entity_id</a>\n<br />";
            // print $wurl->__toString();
            $staleNotes[] = $note_id;
        } else {
            echo "<div class=\"error\">Note $note_id is not affected because we couldn't create a website record for it</div>\n";
        }
    } else {
        echo "<div class=\"error\">No wiki found at $url from <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$entity_id\">contact $entity_id</a>'s note record (#$note_id)</div>\n";
    }
    
    if (count($staleNotes) ) {
        foreach ($staleNotes as $nid) {
            // delete the Note record now that it's transcribed to a Website record
            $params = array (
                'id' => $nid,
            );
            $CiviNoteApi = new \eqt\wikireport\CiviApi();
            $action = ($delete)? 'delete' : 'get';
            $result = $CiviNoteApi->makeCall('Note', $action, $params);
            echo "<div>$action note {$result['id']} from contact {$result['entity_id']} is {$result['note']}</div>\n";
            // echo "<div>Note deleted</div>";
        }
    }
}


function testNote ($note, $delete=false) {
    $note_id   = $note['id'];
    $entity_id = $note['entity_id'];
    $url       = trim($note['note']);
    $staleNotes = array();
    // extract the url and turn it into a wiki website url
    $wurl    = new \eqt\wikireport\UrlWiki($url);
    if ( $wurl->isWiki() ) {
        $website_id = getWebsiteId($wurl->wikiUrl); // check if it already exists
        // now we can call create; with or without an id
        $params = array(
            'id' => $website_id,
            'contact_id' => $entity_id,
            'url' => $wurl->wikiUrl,
            'website_type_id' => 'wiki',
        ); 
        

            echo "<div>remapped $url to $wurl->wikiUrl and created/updated website record for <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$entity_id\">contact $entity_id</a> using params \n<br />" . print_r($params, true) .  "</div>";
            // print $wurl->__toString();
            $staleNotes[] = $note_id;
    } else {
        echo "<div class=\"error\">No wiki found at $url from <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$entity_id\">contact $entity_id</a>'s note record (#$note_id)</div>\n";
    }
    
    if (count($staleNotes) ) {
        foreach ($staleNotes as $nid) {
            // delete the Note record now that it's transcribed to a Website record
            $params = array (
                'id' => $nid,
            );
            $CiviNoteApi = new \eqt\wikireport\CiviApi();
            $action = ($delete)? 'delete' : 'get';
            $result = $CiviNoteApi->makeCall('Note', 'get', $params);
            echo "<div>note {$result['id']} from contact {$result['entity_id']} is {$result['note']}</div>\n";
            // echo "<div>Note deleted</div>";
        }
    }
}


/**
 * check if there is an existing website record, and get the id 
 * @param string $url is a full URI
 * @return (integer) the id of the record or null if none exists.
 */
// $id = getWebsiteId($wurl->wikiUrl);
function getWebsiteId($url) {

    $id = null;
    $params = array(
        'sequential' => 1,
        // 'url' => array('LIKE' => "%$url%"),
        'url' => $url,
    );
    $CiviApi = new \eqt\wikireport\CiviApi();
    $result = $CiviApi->makeCall('website', 'get' ,$params);
    $id = $CiviApi->id;
    return $id;
}

/**
 * Utility for printing
 */
function pre_print($result) {
    echo "<pre>\n";
    print_r($result);
    echo "</pre>\n";
}

/**
 * A convenience function for creating or updating a Website entity in CiviCRM
 * As long as $params contains an 'id', then it will update the existing record.
 * 'contact_id' must be included in $params because all website records are 
 * associated to another contact entity.
 * 
 * @param array $params
 * @return array the array is passed back to the caller.
 */
function createWebsiteRecord($params) {
    if ( !in_array('contact_id', array_keys($params)) ) {
        die ("You can not create a Website record without a 'contact_id'");
    }
    $CiviApi = new \eqt\wikireport\CiviApi();
    $result = $CiviApi->makeCall('website', 'create' ,$params);
    return $result;
}


// We can get a list of all websites:
/*
$params = array(
    'sequential' => 1,
    'website_type_id' => 'Work',
    //'extra' => array("url", "website_type_id", "contact_id"),
    );
/*
$CiviApi = new \eqt\wikireport\CiviApi();
$result = $CiviApi->makeCall('Website', 'get', $params);
var_dump ($result);
//pre_print($result);
*/

/**
 * Create/update record

$params = array(
    'id' => 3542,
    'contact_id' => 2,
    'url' => 'https://freephile.org',
    'website_type_id' => 'wiki',
); 
        
$CiviApi = new \eqt\wikireport\CiviApi();
$result = $CiviApi->makeCall('Website', 'create', $params);
var_dump ($result);

 */

/** Get 50 notes
 * like 
 *  [0] => Array
        (
            [id] => 2315
            [entity_table] => civicrm_contact
            [entity_id] => 3908
            [note] => wiki.mozilla.org
            [contact_id] => 2
            [modified_date] => 2015-05-11
            [privacy] => 0
        )
$params = array(
    'sequential' => 1,
    'options' => array('limit' => 50),
);

$CiviApi = new \eqt\wikireport\CiviApi();
$result = $CiviApi->makeCall('Note', 'get', $params);

pre_print($result);
 * 
*/
/*
echo "Hello World"; die();
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_URL);
if ( (int) $id ) {
    echo $id;
}
 
    
$params = array(
    'sequential' => 1,
    'options' => array('limit' => 5)
);
$CiviApi = new \eqt\wikireport\CiviApi();
$result = $CiviApi->makeCall('Note', 'get', $params);
$notes = $result['values'];


foreach ($notes as $note) {
    // processNote($note, false);
    //explodeNote($note);
    testNote($note, false);
}

*/ 

function UrlWikiTest() {
    $testData = array(
       'ballotpedia.org',
        // 'marvel.com',
        // 'library.techguy.org',
        //'https://wow.gamepedia.com/api.php',
       //'wikitravel.org', some source doesn't include any discernable reference to MW, this case does show the login link
       // 'http://wikitravel.org/wiki/en/api.php',
       'freephile.org',
    );
    foreach ($testData as $k => $v) {
        echo "<div>testing $k</div>\n";
        $UrlWiki = new \eqt\wikireport\UrlWiki($v);
        // $UrlWiki->find_redirect(); // http://wikitravel.org/en/Main_Page
        $isWiki = $UrlWiki->isWiki();
        echo "<div>$k is a wiki? ";
        echo ($isWiki)? "TRUE": "FALSE";
        echo "</div>\n";
        echo "<pre class=\"msg\">\n";
        var_dump($UrlWiki);
        echo "\n</pre>\n";
    }
}
 UrlWikiTest();
