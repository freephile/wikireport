<!DOCTYPE html>
<!--
Copyright (C) 2015 greg

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as 
published by the Free Software Foundation, either version 3 of the 
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the LICENSE file.  
If not, see <http://www.gnu.org/licenses/>.
-->
<?php
// composer libraries
require __DIR__ . '/vendor/autoload.php';
?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="What's that wiki running?">
        <meta name="author" content="eQuality-Tech.com">
        <title>Wiki Report => Examples</title>
        <link rel="shortcut icon" href="//freephile.org/wikireport/favicon.ico" type="image/png">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
<?php 
include('navline.php'); 
?>
                    <h1 class="page-header text-center">Examples</h1>

                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-md-offset-3 text-center">
            
<!-- Good Wikis -->
                    <div>
                        <p>(Click to load in the reporter)</p>

<?php
    $params =  array(
        'sequential' => 1,
        'return' => "contact_id",
        'custom_44' => array('IS NOT NULL' => 1), // 44 is the logo
        'options' => array ('limit' => 30),
    );
    $CiviApi = new \eqt\wikireport\CiviApi();
    $result = $CiviApi->make_call('Contact', 'get', $params);
    $contacts = $result['values'];

    $i = 0;
    $rows = array();
    foreach ($contacts as $contact) {
        $i++;
        $cid = $contact['contact_id'];
        $params = array(
            'sequential' => 1,
            'entity_id' => $cid,
            'format' => 'array',
        );
        $rows[] = $CiviApi->customvalue_get($cid, $params);
    }
    foreach ($rows as $row) {
        if (empty($row['logo'])) {
            continue;
        }
        $Url = new \eqt\wikireport\Url($row['wUrl']);
        $Url->make_absolute($row['logo']);
        echo "<a href=\"index.php?url={$row['wUrl']}\" style=\"margin:5px;\"class=\"padded\" title=\"{$row['sitename']} at {$row['wUrl']}\" target=\"_blank\"><img src=\"{$row['logo']}\" /></a>\n";
    }
        
?>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <h2 class="page-header text-center">Non-Examples</h2>

                    To report on a wiki, we communicate with it via it's 
                    <abbr title="Application Programming Interface">
                    <a href="https://freephile.org/wiki/API">API</a></abbr>. 
                    <a href="https://en.wikipedia.org/wiki/Comparison_of_wiki_software">
                    Not all wikis have or expose an API</a>.  
                    Actually most don't.  But MediaWiki does!  We 
                    <span class="glyphicon glyphicon-heart" aria-hidden="true"></span><span class="sr-only">heart</span>
                    MediaWiki! So, we can't report on other types of wikis like "WikiSpaces" wikis.
                    But if your wiki does have an API, that platform could be added to the Wiki Report. 
                    See the Development section of <a href="about.php">About</a>.

                </div>
            </div>

        </div>
<?php
include('footer.php');
?>
    </body>
</html>
