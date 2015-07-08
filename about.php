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
        <meta name="description" content="All about the Wiki Report">
        <meta name="author" content="eQuality-Tech.com">
        <title>Wiki Report => About</title>
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
                    <h1 class="page-header text-center">About</h1>

                </div>
            </div>

<?php
// make this query https://freephile.org/w/api.php?action=parse&page=Wiki%20report
$apiQuery = "?action=parse&page=Wiki%20report&format=json";
$MwApi = new \eqt\wikireport\MwApi2('https://freephile.org/w/api.php');
$MwApi->makeQuery($apiQuery);
$data = $MwApi->arrayData;
print ($data['parse']['text']['*']);
?>
            

            
        </div>
        <div id="footer">
            <div class="container">
                <p class="muted credit">courtesy <a href="https://eQuality-Tech.com">eQuality Technology</a> and <a href="https://linkedin.com/in/freephile/">Greg Rundlett</a></p>
            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script src="https://freephile.org/wikireport/vendor/jquery-number/jquery.number.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
        <script>
           // format any class="number" element
           // we're using a jQuery plugin, but could use regular JavaScript
           // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/NumberFormat/format
           $(".number").number(true, 0);

           // add Google Analytics
           // UA-39339059-2

           (function (i, s, o, g, r, a, m) {
               i['GoogleAnalyticsObject'] = r;
               i[r] = i[r] || function () {
                   (i[r].q = i[r].q || []).push(arguments)
               }, i[r].l = 1 * new Date();
               a = s.createElement(o),
                       m = s.getElementsByTagName(o)[0];
               a.async = 1;
               a.src = g;
               m.parentNode.insertBefore(a, m)
           })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

           ga('create', 'UA-39339059-2', 'auto');
           ga('send', 'pageview');

        </script>

    </body>
</html>