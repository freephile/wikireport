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
        <meta name="description" content="wiki stats">
        <meta name="author" content="eQuality-Tech.com">
        <title>Wiki Report => Statistics</title>
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
                    <h1 class="page-header text-center">Statistics</h1>

                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-md-offset-3 text-center">
                    <p>
                        Analyzed so far:
                    </p>
<?php
// by instantiating the CiviApi class, we get a full Drupal and Civi bootstrap
// so our DAO code can work
    $CiviApi = new \eqt\wikireport\CiviApi();
    $sql = <<<HERE
    SELECT
    max(pages_61) as pages
    , articles_62
    , edits_63
    , images_64
    , users_65
    , activeusers_66
    , admins_67
    FROM `civicrm`.`civicrm_value_wiki_stats_7`
    group by wurl_60;
HERE;
    $dao = new CRM_Core_DAO();
    $dao->query($sql);
    $sum = array();
    while ($dao->fetch()) {
        // $record = clone $dao;
        
        $sum['Wikis']    += 1; // we could just use record['N'] which is the # results
        $sum['Pages']    += $dao->pages;
        $sum['Articles'] += $dao->articles_62;
        $sum['Edits']    += $dao->edits_63;
        $sum['Images']   += $dao->images_64;
        $sum['Users']    += $dao->users_65;
        $sum['ActiveUsers'] += $dao->activeusers_66;
        $sum['Admins']   += $dao->admins_67;
    }
    foreach ($sum as $k => $v) {
        echo number_format($v) . " <strong>$k</strong><br />\n";
    }
?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <p>
                        The point is that we've analyzed a lot of wikis, representing
                        a lot of users and content.  However, a large number of these
                        communities are running <a href="version.php">obsolete versions</a> of software.
                    </p>
                    <p>
                        Our mission is to change that.  <a href="https://eQuality-Tech.com/contact">We're here</a> to upgrade your 
                        wiki and also help you get the most from the platform in so
                        many ways.
                    </p>

                </div>
            </div>

        </div>
        <br />
<?php
include('footer.php');
?>
    </body>
</html>
