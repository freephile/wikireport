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
require __DIR__ . '/library.php';
?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="wiki version distribution">
        <meta name="author" content="eQuality-Tech.com">
        <title>Wiki Report => Version Distribution</title>
        <link rel="shortcut icon" href="//freephile.org/wikireport/favicon.ico" type="image/png">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
        <link rel="stylesheet" href="cssCharts/cssCharts.css">
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
<?php 
include('navline.php'); 
?>
                    <h1 class="page-header text-center">Version Distribution</h1>

                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <p>
                        Here is the breakdown of the versions of MediaWiki that we've found.
                        If you're not in the upper left quadrant of this pie chart, then 
                        you're seriously behind the current security patches and feauture set
                        offered by MediaWiki.
                        Find out the version of your wiki with a free <a href="/index.php">Wiki Report</a>.
                        With a proper Development / Operations environment for your wiki, it becomes easy to maintain.
                    </p>
                    <p>
                        DevOps is a specialty of eQuality Technology
                        and MediaWiki is our passion.  
                        <a href="https://eQuality-Tech.com/contact">Contact us</a>
                        for answers.
                    </p>


                    <div class="chart">
<?php
// by instantiating the CiviApi class, we get a full Drupal and Civi bootstrap
// so our DAO code can work
    $CiviApi = new \eqt\wikireport\CiviApi();

    $sql = <<<SQL
    SELECT 
        generator_45 as version,
        count(*) as n
    FROM
        civicrm.civicrm_value_wiki_general_6
    WHERE
        generator_45 IS NOT NULL
    GROUP BY generator_45
    ORDER BY generator_45 DESC; -- 110 rows
SQL;

    // 
    $dao = new CRM_Core_DAO();
    $dao->query($sql);

    while ($dao->fetch()) {
        $records[] = clone $dao;
    }
    $pattern = '/MediaWiki ([\d]\.[\d]{1,2}\.?[\d]*).*/';
    // $total = $records[1]['N'];
    $results = array();
    foreach ($records as $record) {
        $label = preg_replace($pattern, "$1", $record->version);
        in_array($label, array_keys($results))? $results[$label] += $record->n : $results[$label] = $record->n;
    }
    // we want to order results in a normal 'version' order
    uksort($results, 'version_sort');
    function version_sort($a, $b) {
        return version_compare($a, $b);
    }

    $total = array_sum($results);
    echo "<h2>Analysis of $total wikis</h2>";
    foreach ($results as $k => $v) {
        $percentage = 100 * $v / $total;
        $dataset .= "[\"v$k\", " . number_format($percentage, 1) ."], ";
    }
    $dataset = substr($dataset, 0, -2); // remove the trailing comma
    $dataset = "[$dataset]";

    $resultCount = count($results);
    // echo "max records of $resultCount"; // 103
    // create a hsl color palette from red to green
    // that means we want h to go from 0 to 120

    $s = 100; // saturation stays the same
    $l = 50; // luminosity stays the same
    for ($i=0; $i<$resultCount; $i++) {
    // 'hsl(' + h + ',' + s + '%,' + l + '%)';
        $h = round( 120 * ($i / $resultCount), 0);
        $color = convertHSL($h, $s, $l);
        $colors[] = $color;
    }

    $colorset = implode(',', $colors);
    echo "<div class=\"pie-chart\" data-set='$dataset' data-colors=\"$colorset\"></div>";
    // echo "<div class=\"pie-chart\" data-set='$dataset'></div>";
?>
                        
            
                    </div>
                </div>
            </div>

        </div>
        <br />
<?php
include('footer.php');
?>
    </body>
</html>
