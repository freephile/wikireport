<!DOCTYPE html>
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
// composer libraries
$loader = require __DIR__ . '/vendor/autoload.php';
?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="All about the Wiki Report">
        <meta name="author" content="eQuality-Tech.com">
        <title>Wiki Report => CiviCRM / MediaWiki API client</title>
        <link rel="shortcut icon" href="//freephile.org/wikireport/favicon.ico" type="image/png">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
        <style>
            .msg {
                background-color:gray;
                font-size:0.8em;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
<?php 
include('navline.php'); 
?>
                    <h1 class="page-header text-center">CiviCRM and MediaWiki API client</h1>

                </div>
            </div>

<?php

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
            $result = $CiviApi->make_call('Note', 'create', $params);
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
            $result = $CiviApi->make_call('Note', 'delete', $params);
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
    if ( $wurl->is_wiki() ) {
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
            $result = $CiviNoteApi->make_call('Note', $action, $params);
            // echo "<div>$action note {$result['id']} from contact {$result['entity_id']} is {$result['note']}</div>\n";
            pre_print($result);
            
            // echo "<div>Note deleted</div>";
        }
    }
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
    $result = $CiviApi->make_call('website', 'create' ,$params);
    return $result;
}


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
       // 'http://wiki.seiu.org',
    );
    foreach ($testData as $k => $v) {
        echo "<div>testing $k</div>\n";
        $UrlWiki = new \eqt\wikireport\UrlWiki($v);
        // $UrlWiki->find_redirect(); // http://wikitravel.org/en/Main_Page
        $isWiki = $UrlWiki->is_wiki();
        echo "<div>$v is a wiki? ";
        echo ($isWiki)? "TRUE": "FALSE";
        echo "</div>\n";
        echo "<pre class=\"msg\">\n";
        var_dump($UrlWiki);
        echo "\n</pre>\n";
    }
}
// UrlWikiTest();




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
$result = $CiviApi->make_call('Note', 'get', $params);
$notes = $result['values'];
$staleNotes = array();
echo "<ol>";
foreach ($notes as $note) {
    // processNote($note, false);
    // testNote($note, false);
    $note_id   = $note['id'];
    $entity_id = $note['entity_id'];
    $url       = trim($note['note']);
    if ( !empty($note['subject']) || strstr($url, " ") ) {
        // notes with subjects or multiple words are real notes
        // so leave them alone
        continue;
    }
    $UrlWiki = new \eqt\wikireport\UrlWiki($url);
    $isWiki = $UrlWiki->is_wiki();

    echo "<li><a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$entity_id\">$entity_id</a> note: <b>$url</b>\n";
    // echo "<!-- by {$note['api.Contact.get']['values'][0]['display_name']} --> ";
    if ( $isWiki && !empty($UrlWiki->wikiUrl) ) {
        echo "$UrlWiki->wikiUrl is backed by $UrlWiki->apiUrl";
        // 
        $results = $CiviApi->website_get($UrlWiki->wikiUrl);
        $websiteId = $results->id; // null in most cases
        // now we can call create with and id to update/null to create
        $params = array(
            'id' => $websiteId,
            'contact_id' => $entity_id,
            'url' => $UrlWiki->wikiUrl,
            'website_type_id' => 'wiki',
        );
        $results = $CiviApi->website_create($params);
        if ($results) {
            echo "<div>remapped $url to $UrlWiki->wikiUrl and created/updated website record for <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$entity_id\">contact $entity_id</a>\n<br />";
            // print $UrlWiki->__toString();
            $staleNotes[] = $note_id;
        } else {
            echo "<div class=\"error\">Note $note_id is not affected because we couldn't create a website record for it</div>\n";
        }
    
        
        $CiviApi->add_custom_data($UrlWiki->wikiUrl);
    } else {
        echo "$UrlWiki->url is not accessible.  Add to Group 11 ";
    }
    echo "</li> \n";
}
echo "</ol>";



 
/**
 * GOOD CODE FOR UPDATING/INSERT CUSTOM DATA
 *
 */
$CiviApi = new \eqt\wikireport\CiviApi();
$results = $CiviApi->make_call('Website', 'get', array(
  'sequential' => 1,
  'website_type_id' => 16,
  // 'return' => "id,contact_id,url",
));

if (!$results['is_error']) {
    $urls = $results['values'];
    foreach($urls as $url) {
        $CiviApi->add_custom_data($url['url']);
    }
} else {
    pre_print($results);
}


// print('<pre class="msg">' . $CiviApi->__toString()  . '</pre>');



/////////////////      FOOTER     /////////////////////////////////////////////
?>
        </div>
<?php
include('footer.php');
?>

    </body>
</html>