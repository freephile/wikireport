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
$MwApi = new \eqt\wikireport\MwApi('https://freephile.org/w/api.php');
$MwApi->makeQuery($apiQuery);
$data = $MwApi->data;
print ($data['parse']['text']['*']);
?>
            

            
        </div>
<?php
include('footer.php');
?>

    </body>
</html>