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
require 'secret.php';
if (!in_array($_SERVER['REMOTE_ADDR'], $ipWhitelist)) {
    header('Location: https://freephile.org/wikireport/index.php', true, 302);
    die();
}

// composer libraries
$loader = require __DIR__ . '/vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="All about the Wiki Report">
        <meta name="author" content="eQuality-Tech.com">
        <title>Wiki Report => CiviCRM / MediaWiki API client</title>
        <link rel="shortcut icon" href="//freephile.org/wikireport/favicon.ico" type="image/png">
        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <link rel="stylesheet" href="jquery.dynatable.css">
    </head>
    <body>

        <h1 class="page-header text-center">CiviCRM and MediaWiki API client</h1>
<?php

// ini_set("display_errors", 1);

/**
 * GOOD CODE FOR DISPLAYING TABLE OF CURRENT WIKIS
 * ***************** begin comment *****************
 */
/**
 * For a contact, get all the custom data values, map those to labels
 * and return the 'latest' value of each field.
 *
$CiviApi = new \eqt\wikireport\CiviApi();
$result = civicrm_api3('Contact', 'get', array(
    'sequential' => 1,
    'return' => "contact_id",
    'custom_40' => array('IS NOT NULL' => 1),
    'options' => array ('limit' => 2),
 ));

 // get a list of contact ids
foreach ($result['values'] as $contact) {
    $cids[] = $contact['contact_id'];
}
$params = array(
    'sequential' => 1,
    'format' => 'array',
);
$rows = $CiviApi->customvalue_get($cids, $params);

$filter = array('base', 'sitename', 'logo', 'generator', 'phpversion', 'dbtype', 'dbversion', 'pages', 'articles', 'edits', 'images', 'users', 'activeusers', 'admins', 'jobs', 'recorded');
$headings = array_keys($rows[0]);
$headings = array_intersect($filter, $headings); // let's just use those named in 'filter'
$out = '';
foreach ($headings as $heading) {
    $out .= "<th>$heading</th>\n";
}
$head = "<thead><tr>$out</tr></thead>";
$out = '';
foreach ($rows as $row) {
    $thisrow = '';
    foreach ($headings as $key) {
        // control the output for each cell
        switch ($key) {
            case 'logo' : 
                $thisrow .= "<td><img src=\"$row[$key]\" /></td>";
                break;
            case 'generator' :
                $val = str_replace('MediaWiki', '', $row[$key]);
                $thisrow .= "<td>" . trim($val) . "</td>";
                break;
            default:
                $thisrow .= "<td>$row[$key]</td>";
        }
    }
    $out .= "<tr>$thisrow</tr>\n";
}
$out = <<<HERE
<table id="civitable">
    $head
    <tbody>
    $out
    </tbody>
</table>
HERE;
print $out;

******************** end comment************* */


     /* 
     * rows looks like this
     * 
            [wUrl] => https://wiki.mozilla.org/Main_Page
            [mainpage] => Main Page
            [base] => https://wiki.mozilla.org/Main_Page
            [sitename] => MozillaWiki
            [logo] => https://wiki.mozilla.org/assets/logos/mozilla-wiki-logo-alt-135px.png
            [generator] => MediaWiki 1.23.9
            [phpversion] => 5.3.3
            [dbtype] => mysql
            [dbversion] => 5.6.17-log
            [writeapi] => 
            [timezone] => America/Los_Angeles
            [timeoffset] => -420
            [articlepath] => /$1
            [scriptpath] => 
            [server] => https://wiki.mozilla.org
            [servername] => 
            [wikiid] => wiki_mozilla_org
            [time] => 2015-07-15T20:15:27Z
            [maxuploadsize] => 104857600
            [favicon] => https://wiki.mozilla.org/assets/favicon.ico
            [wiki Url] => https://wiki.mozilla.org/Main_Page
            [pages] => 104538
            [articles] => 20382
            [edits] => 1208336
            [images] => 10024
            [users] => 331322
            [activeusers] => 496
            [admins] => 3
            [jobs] => 1059
            [recorded]
     * 
     * 

        foreach ($rows as $row) {
            $out .= "<tr>";
            $out .= "<td>{$row['wUrl']}</td>";
            $out .= "<td>{$row['mainpage']}</td>";
            $out .= "<td>{$row['base']}</td>";
            $out .= "<td>{$row['sitename']}</td>";
            $out .= "<td><img src=\"{$row['logo']}\" /></td>";
            $out .= "<td>{$row['generator']}</td>";
            $out .= "<td>{$row['phpversion']}</td>";
            $out .= "<td>{$row['dbtype']}</td>";
            $out .= "<td>{$row['dbversion']}</td>";
            $out .= "<td>{$row['timezone']}</td>";
            $out .= "<td><img src=\"{$row['favicon']}\" /></td>";
            $out .= "<td>{$row['pages']}</td>";
            $out .= "<td>{$row['articles']}</td>";
            $out .= "</tr>";
        }
        $out = "<table id=\"civitable\">$out</table>";
        print $out;
     */ 

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
            //$CiviApi->custom_data_fetch($contact['contact_id']);
            $out .= ob_get_clean();
            $out .= "\n</li>\n";
        }
        $out .= "</ol>\n";
    } else {
        pre_print($results);
    }
    print $out;
}


// custom_data_fetch_all();

/**
 * A chained API call on websites then contacts
 * displays a list with name, city and state and the wiki URL
 * sorts on url because you can only sort on the first fields returned (before we know the name of the contact)
 */
function contacts_with_wikis($report=true, $update=false, $newonly=true) {
    
    $CiviApi = new \eqt\wikireport\CiviApi();
    $params = array(
        'sequential' => 1,
        'return' => "contact_id,url",
        'website_type_id' => "wiki",
        'options' => array('limit' => 2000, 'offset' => 2, 'sort' => "url"),
        'api.Contact.get' => array(),
        );
    if($newonly) {
        array_push($params, array(
            'custom_40' => array('IS NULL' => 1), // wUrl is blank (need to fetch)
        ));
    }
        
    $results = $CiviApi->make_call('Website', 'get', $params);

    if (!$results['is_error']) {
        $contacts = $results['values'];
        $out .= "Contacts with wikis <br />\n";
        if ($update) {
            $out .= "<div style=\"color:red;\">UPDATING DATA</div>\n";
        }
        // pre_print($results); exit(); // DEBUGGING
        $out .= "<ol>\n";
        foreach($contacts as $contact) {
            $out .= "<li><a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid={$contact['contact_id']}\">{$contact['contact_id']}</a> {$contact['api.Contact.get']['values'][0]['display_name']} in {$contact['api.Contact.get']['values'][0]['city']}, {$contact['api.Contact.get']['values'][0]['state_province']} runs <a href=\"{$contact['url']}\">{$contact['url']}</a>\n";
            $out .= "\n";
            
            // handle the update case
            if ($update) {
                $CiviApi = new \eqt\wikireport\CiviApi();
                ob_start();
                $CiviApi->custom_data_fetch($contact['contact_id']);
                $out .= ob_get_clean();   
            }
            $out .= "\n</li>\n";
        }
        $out .= "</ol>\n";
    } else {
        pre_print($results);
    }
    if ($report) {
        print $out;
    }
    
}
// report but don't update
// contacts_with_wikis(true, false, true);
// contacts_with_wikis(true, false, true);



/**
 * A function to return a list of all wiki type websites 
 * via the CiviCRM API and 'Website' get
 * @param int $limit how many records
 * @param int $offset what record to start at
 * @return array $results['values'] which is an indexed array
 * of records with the 'contact_id' and 'url' as the important
 * keys
 * 
 * Data is structured like so
           [0] => Array
                (
                    [id] => 3580
                    [contact_id] => 3939
                    [url] => http://gicl.cs.drexel.edu/index.php/Main_Page
                    [website_type_id] => 16
                )

            [1] => Array
                (
                    [id] => 3582
                    [contact_id] => 3940
                    [url] => http://wiki.gimp.org/wiki/Main_Page
                    [website_type_id] => 16
                )
*/

function get_wikis($limit=20, $offset=0) {
    
    $CiviApi = new \eqt\wikireport\CiviApi();
    $params = array(
        'sequential' => 1,
        'return' => "contact_id,url",
        'website_type_id' => "wiki",
        //'options' => array('limit' => 2000, 'offset' => 0, 'sort' => "url"),
        'options' => array('limit' => $limit, 'offset' => $offset),
        );        
    $results = $CiviApi->make_call('Website', 'get', $params);

    if (!$results['is_error']) {
        return $results['values']; 
    } else {
        return false;
    }
}

/**
 * Get any custom value
 * @param int $cid the contact id
 * @param array $return an array of field names in their "numeric" form eg. array('custom_40', 'custom_42')
// custom_40 = wUrl, custom_42 = base, custom_45 = generator, custom_46 = phpversion
 * @param array $isNull an array of fields to test for NULL or IS NOT NULL in the selection of records.
*/

function get_custom_values($cid, $return=array("custom_40"), $isNull=array('custom_40', true) ) {
  $CiviApi = new \eqt\wikireport\CiviApi();
  $params= array(
    'sequential' => 1,
    'id' => $cid,
    // We need a comma-separated string, so we'll implode the arg and glue with ','
    // 'return' => "custom_40,custom_42,custom_45,custom_46",
    'return' => implode(',', $return),
    'options' => array('limit' => 1, 'offset' => 0,),
  );
  // extract($isNull); // $custom_40 = true;
  foreach ($isNull as $k => $v) {
    if ($v) {
      $params[$k] = array('IS NULL' => 1); 
    } else {
      $params[$k] = array('IS NOT NULL' => 1);
    }
  }
  // var_dump($params); exit();
  $results = $CiviApi->make_call('Contact', 'get', $params);
  if (!$results['is_error']) {
    if (count($results['values'])) {
        $values = $results['values'][0];
        return $values;
    } else {
      return null;
    }
  }
}


/**
 * Builds off get_custom_values() as a special case
 * Our return values can be more comprehensive and our isNull test is precise
 */
function get_wiki_values ($contacts,
                          $return=array(
                            'custom_40', // wUrl
                            'custom_42', // base
                            'custom_45', // generator
                            'custom_46', // phpversion
                          ),
                          $isNull = array(
                            'custom_40' => true, // wUrl
                          )) {
  $ret = array();
  foreach($contacts as $k => $contact) {
      $cid = $contact['contact_id'];
      $cvals = array();
      $cvals = get_custom_values($cid, $return, $isNull);
      // pre_print($cvals); // debug
      if ($cvals['contact_id']) {
        // print "found wiki without data for contact {$cvals['id']}<br/>\n";
        $ret[] = $cvals;
      }
  }
  return $ret;
}


function report_cv ($cvals, $verbose=true) {
      // pre_print($cvals); // debug
  $out = "<h2>Reporting on Custom Values</h2>\n";
  if ($verbose) {
    $out .="<div>Available fields</div>\n";
    $out .="<ul>\n";
    $fields = array_keys($cvals[0]);
    foreach ($fields as $field) {
      $out .= "<li>$field</li>\n";
    }
    $out .= "</ul>\n";
  }
  $out .= "<ol>\n";
  foreach($cvals as $k => $contact) {
      $cid        = $contact['contact_id'];
      $wUrl       = $contact['custom_40'];
      $generator  = $contact['custom_45'];
      $phpversion = $contact['custom_46'];
      $out .= <<<HERE
        <li>
          <a href="https://equality-tech.com/civicrm/contact/view?reset=1&cid=$cid">$cid</a> 
          <a href="$wUrl" target="_blank">$wUrl</a> running on $generator with $phpversion
        </li>
HERE;
  }
  $out .= "\n</ol>\n";
  print $out;
}


/**
 * Fetch custom data for 'new' wikis by first employing
 * get_wikis() and get_wiki_values() to find those
 * contact records that need updating.
 * @param array $contacts which has the contact ids of records to fetch
 * Can be used manually like fetch_new_wikis(array(4520));
 */
function fetch_new_wikis($contacts) {
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
  print $out;
  return true;
}

// $wikis = get_wikis(2, 0);
// $wikis = get_wikis(200, 0);
// $wikis = get_wikis(200, 400);
// $wikis = get_wikis(2000, 0);


$return=array(
  'custom_40', // wUrl
  'custom_42', // base
  'custom_45', // generator
  'custom_46', // phpversion
);
$isNull = array(
  'custom_40' => true, // wUrl
);
//$data = get_wiki_values($wikis, $return, $isNull);
// by chaining get_wikis() for bulk record selection
// with get_wiki_values() for record filtering
// we can find those contacts whose wiki data needs fetching
// We can report on that data
/////// E.g. /////////////
// report_cv($data);
/////////////////////////
// Or update it.
// The problem with custom_data_fetch_all is that it will fetch stats for ALL wikis in our database.
// whereas we only want to fetch data for the 12 records which don't have it.
// pre_print($data);
// fetch_new_wikis($data);

// fetch_new_wikis(array(4520));
// fetch_new_wikis(array(6070)); // Leaguepedia
// fetch_new_wikis(array(4112)); // https://oeis.org/wiki/Special:Version

// All in one call, get the custom data for any new wiki records that don't have it
// fetch_new_wikis(get_wiki_values(get_wikis(2000,0), $return, $isNull));


?>
<script src="jquery.dynatable.js"></script>
<script>
    $(document).ready(function() {
    $('#civitable').dynatable({
        table: {
            defaultColumnIdStyle: 'lowercase' // camelCase, trimDash, dashed, underscore, lowercase
        }
    });
});
</script>
    </body>
</html>