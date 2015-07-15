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
            // echo "<div>$action note {$result['id']} from contact {$result['entity_id']} is {$result['note']}</div>\n";
            pre_print($result);
            
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
        // print "<pre>";print_r ($wurl->msg); print "</pre>";
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
 * 
 * @deprecated Use CiviApi->getWebsite which returns the full response
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
    pre_print($CiviApi);
    $contact_id = $CiviApi['values'][0]['contact_id'];
    return array($contact_id => $id);
    // return $id;
}

/**
 * 
 * @param string $url the website address to look for in our database
 * @param bool $fuzzy whether to search using LIKE comparison, or '=' if not fuzzy
 * @return mixed false on error.  Array of results on success
 * 
 * The way the API works, you get the id right away.  The contact id is nested in
 * the values array.  You'll get something like this:
    [is_error] => 0
    [version] => 3
    [count] => 1
    [id] => 3542
    [values] => Array
        (
            [0] => Array
                (
                    [id] => 3542
                    [contact_id] => 2
                    [url] => https://freephile.org/
                    [website_type_id] => 16
                )

        )
 */
function getWebsite($url, $fuzzy=false) {
    $params = array(
        'sequential' => 1,
    );
    if ($fuzzy) {
        $params2 = array ('url' => array('LIKE' => "%$url%"));
    } else {
        $params2 = array ('url' => $url);            
    }
    $params = $params + $params2; // array union

    $CiviApi = new \eqt\wikireport\CiviApi();
    $result = $CiviApi->makeCall('website', 'get' ,$params);
    if ($result['is_error']) {
        echo "Error finding website for $url";
        return false;
    }
    return $result;
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
       // 'ballotpedia.org',
       // 'http://encyclopedia.sabr.org',
       // 'marvel.com',
       // 'library.techguy.org',
       // 'https://wow.gamepedia.com/api.php',
       // 'wikitravel.org', some source doesn't include any discernable reference to MW, this case does show the login link
       // 'http://wikitravel.org/wiki/en/api.php',
       // 'freephile.org',
       // 'http://www.newworldencyclopedia.org/entry/Info:Main_Page',
       // 'http://wiki.newworldencyclopedia.org/',
       // 'wiki.splunk.com',
       // 'http://wiki.splunk.com/api.php'
       // 'faq.he.net',
       // 'http://aboutus.org',
       // 'http://wiki.lighthousecatholicmedia.org/Main_Page',
        // 'https://wow.gamepedia.com/',
        // 'http://wikicafe.metacafe.com/en/Main_Page',
        // 'http://help.pingg.com/index.php/Main_Page',
        'http://wiki.seiu.org',
    );
    foreach ($testData as $k => $v) {
        echo "<div>testing $k</div>\n";
        $UrlWiki = new \eqt\wikireport\UrlWiki($v);
        // $UrlWiki->find_redirect(); // http://wikitravel.org/en/Main_Page
        $isWiki = $UrlWiki->isWiki();
        echo "<div>$v is a wiki? ";
        echo ($isWiki)? "TRUE": "FALSE";
        echo "</div>\n";
        echo "<pre class=\"msg\">\n";
        var_dump($UrlWiki);
        echo "\n</pre>\n";
    }
}
// UrlWikiTest();

/**
 * How to process or check/test notes
 * 
 *
$noteID = 3656; // d9.wikibruce.com  entity 4144
// $entityID = 3961;
$entityID = 3940;

$params = array(
    'sequential' => 1,
    // 'id' => $noteID,
    'entity_id' => $entityID, // all the notes for a contact
    'options' => array('limit' => 30)
);
$CiviApi = new \eqt\wikireport\CiviApi();
$result = $CiviApi->makeCall('Note', 'get', $params);
$notes = $result['values'];


foreach ($notes as $note) {
    processNote($note, true);
    //explodeNote($note);
    // testNote($note, false);
}
*/

/**
 * 
/// Here we're going to report on all the notes we have,  
// We can't show the contact name because the 'contact_id' of the note is 
// the person who set the note (Greg).  The entity_id is the record id that we
// are interested in, but the chained api doesn't use that.
// If somehow you already know the entity id, then you can pass that to the chained
// api call
$params = array(
    'sequential' => 1,
    // 'id' => $noteID,
    // 'entity_id' => $entityID, // all the notes for a contact
    // 'api.Contact.get' => array(),
    // 'api.Contact.get' => array('id' => $entityID),
    'options' => array('limit' => 30)
);
$CiviApi = new \eqt\wikireport\CiviApi();
$result = $CiviApi->makeCall('Note', 'get', $params);
$notes = $result['values'];
echo "<ol>";
foreach ($notes as $note) {
    // processNote($note, false);
    // testNote($note, false);
    $note_id   = $note['id'];
    $entity_id = $note['entity_id'];
    $url       = trim($note['note']);
    if ( strstr($url, " ") ) {
        continue;
    }
    $UrlWiki = new \eqt\wikireport\UrlWiki($url);
    $isWiki = $UrlWiki->isWiki();

    echo "<li><a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$entity_id\">$entity_id</a> note: <b>$url</b>\n";
    // echo "<!-- by {$note['api.Contact.get']['values'][0]['display_name']} --> ";
    if ($isWiki) {
        echo "$UrlWiki->wikiUrl is backed by $UrlWiki->apiUrl";
    } else {
        echo "Borken " . pre_print($UrlWiki);
    }
    echo "</li> \n";
}
echo "</ol>";
 * 
 * 
 */


$genKeys = array (
    "wUrl" => "custom_40",
    "mainpage" => "custom_41",
    "base" => "custom_42",
    "sitename" => "custom_43",
    "logo" => "custom_44",
    "generator" => "custom_45 ",
    "phpversion" => "custom_46",
// "phpsapi", 
    "dbtype" => "custom_47 ",
    "dbversion" => "custom_48",
// "externalimages", 
// "langconversion", 
// "titleconversion", 
// "linkprefixcharset", 
// "linkprefix", 
// "linktrail", 
// "legaltitlechars", 
// "git-hash", 
// "git-branch", 
// "case", 
// "lang", 
// "fallback", 
// "fallback8bitEncoding", 
    "writeapi" => "custom_49",
    "timezone" => "custom_50",
    "timeoffset" => "custom_51 ",
    "articlepath" => "custom_52",
    "scriptpath" => "custom_53",
// "script", 
// "variantarticlepath", 
    "server" => "custom_54",
    "servername" => "custom_55",
    "wikiid" => "custom_56",
    "time" => "custom_57",
    "maxuploadsize" => "custom_58",
// "thumblimits", 
// "imagelimits", 
    "favicon" => "custom_59",
);

// https://freephile.org/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=txt

$statKeys = array(
    "wUrl" => "custom_60",
    "pages" => "custom_61",
    "articles" => "custom_62",
    "edits" => "custom_63",
    "images" => "custom_64",
    "users" => "custom_65",
    "activeusers" => "custom_66",
    "admins" => "custom_67",
    "jobs" => "custom_68",
);

/**
 *  The custom fields result in data like
 *         {
            "id": "21",
            "custom_group_id": "5",
            "name": "sitename",
            "label": "sitename",
            "data_type": "String",
            "html_type": "Text",
            "is_required": "0",
            "is_searchable": "0",
            "is_search_range": "0",
            "weight": "3",
            "is_active": "1",
            "is_view": "0",
            "text_length": "255",
            "note_columns": "60",
            "note_rows": "4",
            "column_name": "sitename_21",
            "in_selector": "0"
        },
        {
            "id": "22",
            "custom_group_id": "5",
            "name": "logo",
            "label": "logo",
            "data_type": "String",
            "html_type": "Text",
            "is_required": "0",
            "is_searchable": "0",
            "is_search_range": "0",
            "weight": "4",
            "is_active": "1",
            "is_view": "0",
            "text_length": "255",
            "note_columns": "60",
            "note_rows": "4",
            "column_name": "logo_22",
            "in_selector": "0"
        },
 * 
 * Using the API, you can request 'CustomGroup' and 
 * chain a subrequest with the group_id to receive all the fields in the 
 * group.  But we want records, not the fields / data definitions
$result = civicrm_api3('CustomGroup', 'get', array(
  'debug' => 1,
  'sequential' => 1,
  'id' => 7,
  'api.CustomField.get' => array('group_id' => 7),
));

 * code like 
 * 
$result = civicrm_api3('Contact', 'get', array(
  'sequential' => 1,
  'return' => "display_name,city,state_province,custom_19,custom_20,custom_21",
  'id' => 1,
));
 * 
 * will get some of the custom data fields for contact id 1
 * 
 $result = civicrm_api3('Contact', 'get', array(
  'debug' => 1,
  'sequential' => 1,
  'custom_60' => array('IS NOT NULL' => 1),
));

 * will give you the Contact record(s) with values in custom_60
 * 
 * code like 
 * 
$result = civicrm_api3('Contact', 'setvalue', array(
  'sequential' => 1,
  'field' => "custom_21",
  'id' => 1,
  'value' => "Newburyport GNUs",
));
 * 
 * will set the value of 'sitename' (custom field 21)
 */

// $url = 'https://freephile.org/wiki/Main_Page';
$url = 'http://www.taxrates.com/wiki/Main_Page';
// $url = 'http://ballotpedia.org/Main_Page';
// $url = 'https://marvel.com/universe/Main_Page';
// $url = 'library.techguy.org';
// $url = 'https://wiki.mozilla.org/Main_Page';

 // exit();
 
/**
 * GOOD CODE FOR UPDATING/INSERT CUSTOM DATA
 *
 */
        $wurl = new \eqt\wikireport\UrlWiki($url);
        if ($wurl->isWiki()) {
            $apiQuery = '?action=query&meta=siteinfo&format=json&siprop=general';

            $MwApi = new \eqt\wikireport\MwApi($wurl->apiUrl);
            $MwApi->makeQuery($apiQuery);
            $data         = $MwApi->data;
            $fresh        = $MwApi->getFreshness();
            $canonicalUrl = $MwApi->base;
            echo "<b>'" . $MwApi->sitename . "'</b> is a wiki at $canonicalUrl<br />\n";
            
            $CiviApi = new \eqt\wikireport\CiviApi();
            $civirecord = $CiviApi->getWebsite($canonicalUrl, false);

            if ( $civirecord['count'] !== 1 ) {
                echo "Found too many or too few records for $canonicalUrl<br />\n";
                exit();
            }

            $contactId = $civirecord['values'][0]['contact_id'];
            $websiteId = $civirecord['id'];
            $type = $civirecord['values'][0]['website_type_id'];
            if ($type !== '16') {
                echo "The Civi record is type $type; it should be updated to type 16<br />\n";
            }
            $general = $MwApi->data['query']['general'];
            $values = array();
            $values['custom_40'] = (string) $canonicalUrl; // we set this ourselves
                foreach ($general as $k => $v) {
                    if ( in_array($k, array_keys($genKeys)) ) {
                        $values["$genKeys[$k]"] = $v;
                    }
                }
            if (version_compare($wurl->versionString, '1.10.0') >= 0) {
                // get stats
                $stats = $MwApi->getStats();
                $stats = $stats['query']['statistics'];
                $values['custom_60'] = (string) $canonicalUrl; // we set this ourselves
                foreach ($stats as $k => $v) {
                    if ( in_array($k, array_keys($statKeys)) ) {
                        $values["$statKeys[$k]"] = $v;
                    }
                }
            }
            // extensions become available in 1.16
            if (version_compare($wurl->versionString, '1.16.0') >= 0) {
                // get extensions data
                // we currently do not support Extensions in custom data
            }
            
            // record data
            $params = array(
              'sequential' => 1,
              'id' => $contactId,
            );
            $params += $values; // union arrays
            $result = $CiviApi->makeCall('Contact', 'create', $params);
            if ($result['is_error']) {
                echo "Crap we had an error trying to set custom data for <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$contactId\">$contactId</a><br />\n";
            } else {
                echo "Updated custom data for <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$contactId\">$contactId</a><br />\n";
            }


        }


      

/**
 * In getting custom values, you specify the field name as a return parameter
 * Our custom field 'pages' (a member of the stats group) is known as 'custom_61'
 * 
 * When requesting 
$result = $CiviApi->makeCall('CustomValue', 'get', array(
  'sequential' => 1,
  'entity_id' => 2,
  'entity_type' => "Contact",
  'return.custom_61' => 1,
));

You get 

Array (
    [is_error] => 0
    [version] => 3
    [count] => 1
    [id] => 61
    [values] => Array
        (
            [0] => Array
                (
                    [entity_id] => 2
                    [latest] => 1380
                    [id] => 61
                    [1] => 1379
                    [2] => 1380
                )
        )
)
 * 
 * When I tried using the custom_61:1 specifier to get the first "set", instead 
 * I got a values array with count 9 (elements in the group).  Each element of the 
 * array was a series for that data point, with 'latest' accessible too.
 Array (
    [is_error] => 0
    [version] => 3
    [count] => 9
    [values] => Array
        (
            [0] => Array
                (
                    [entity_id] => 2
                    [latest] => 
                    [id] => 60
                    [1] => https://freephile.org/wiki/Main_Page
                    [2] => 
                )

            [1] => Array
                (
                    [entity_id] => 2
                    [latest] => 1380
                    [id] => 61
                    [1] => 1379
                    [2] => 1380
                )

            [2] => Array
                (
                    [entity_id] => 2
                    [latest] => 
                    [id] => 62
                    [1] => 206
                    [2] => 
                )
 * 
    

$CiviApi = new \eqt\wikireport\CiviApi();
$result = $CiviApi->makeCall('CustomValue', 'get', array(
  'sequential' => 1,
  'entity_id' => 2,
  'entity_type' => "Contact",
  'return.custom_61' => 1,
));


$CiviApi = new \eqt\wikireport\CiviApi();
$result = $CiviApi->makeCall('Contact', 'setvalue', array(
  'sequential' => 1,
  'field' => "custom_61:2", // pages field, and :record_id of custom data
  'id' => 2,
  'value' => 1381,
));

pre_print($result);


 *
 * A "CustomValue' get, with the id of the contact, will return all
 * the custom values associated with that contact.  Each element will have 
 * an 'id' (custom value id) and an integer key of the set value (set 1, 2, 3...)
 * for multiple records
 *
$result = civicrm_api3('CustomValue', 'get', array(
  'debug' => 1,
  'sequential' => 1,
  'entity_id' => 2,
));

    "count": 9,
    "values": [
        {
            "entity_id": "2",
            "latest": "https://freephile.org/wiki/Main_Page",
            "id": "60",
            "1": "https://freephile.org/wiki/Main_Page"
        },
        {
            "entity_id": "2",
            "latest": "1379",
            "id": "61",
            "1": "1379"
        },
        {
 * 
 * I finally figured out how to set multiple values at once.
 * 
 $result = civicrm_api3('Contact', 'create', array(
  'sequential' => 1,
  'id' => 2,
  'custom_40' => "https://freephile.org/wiki/Main_Page",
  'custom_43' => "wiki",
  'custom_42' => "https://freephile.org/w/api.php",
));
 * 
 * instead of params like this where we DON'T know the id of the custom data
 array(
  'sequential' => 1,
  // 'field' => "$token:1", // custom_60, and :record_id of custom data
  'field' => "$token", // 'custom_60'
  'id' => $contactId,
  'value' => $value,
));
 */
        

