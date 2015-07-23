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
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <p>Nothing to see here.  Check the <a href="examples.php">examples</a> page.</p>
<?php

// ini_set("display_errors", 1);

/**
 * The way the API works, you get the entity "id" right away.  The related 
 * "contact_id" is nested in the "values" element.  
 * You'll get something like this:
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

/*
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
    'return' => array('subject', 'note', 'id', 'entity_id'),
    'options' => array('limit' => 3000)
);
$CiviApi = new \eqt\wikireport\CiviApi();
$result = $CiviApi->make_call('Note', 'get', $params);
$notes = $result['values'];
$staleNotes = array();
$cids = array();
echo "<ol>";
foreach ($notes as $note) {

    $note_id   = $note['id'];
    $entity_id = $note['entity_id'];
    $url       = trim($note['note']);
    $subject = $note['subject'];

    if ( !empty($note['subject']) || strstr($url, " ") ) {
        // continue;
    }
    
    if ($subject != 'Problem converting note') {
        continue;
    }
    $cids[] = $entity_id;
    echo "<li><a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$entity_id\">$entity_id</a> $subject : <b>$url</b>\n";
    // echo "<!-- by {$note['api.Contact.get']['values'][0]['display_name']} --> ";       

    echo "</li> \n";
}
echo "</ol>";

// print (count($cids)) . " records to work on";
// There are 331 'read permission' problem wikis
// Lets see if we might fix some of those

$CiviApi = new \eqt\wikireport\CiviApi();
$params = array(
    'subject' => 'Problem converting note',
    'options' => array('limit' => 1000, 'offset'=>0),
);
$delete = true;
$debug = false;
$CiviApi->note_to_website($params, $delete, $debug);
*/

    
/**
 * GOOD CODE FOR UPDATING/INSERT CUSTOM DATA
 *
 */

function custom_data_fetch_all () {
    $CiviApi = new \eqt\wikireport\CiviApi();
    $results = $CiviApi->make_call('Contact', 'get', array(
      'sequential' => 1,
      'custom_40' => array('IS NULL' => 1),
      'options' => array('limit' => 2000, 'offset' => 0)
    ));

    if (!$results['is_error']) {
        $contacts = $results['values'];
        $out .= "<ol>\n";
        foreach($contacts as $contact) {
            $CiviApi = new \eqt\wikireport\CiviApi();
            $out .= "<li>working on <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid={$contact['contact_id']}\">{$contact['contact_id']}</a>\n";
            ob_start();
            $CiviApi->custom_data_fetch($contact['contact_id']);
            $out .= ob_get_clean();
            $out .= "\n</li>\n";
        }
        $out .= "</ol>\n";
    } else {
        pre_print($results);
    }
    print $out;
}




/////////////////      FOOTER     /////////////////////////////////////////////
?>
                </div>
            </div>

        </div>
<?php
include('footer.php');
?>

    </body>
</html>