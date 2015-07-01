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
require __DIR__ . '/vendor/autoload.php';
$form = new \eqt\wikireport\Form();
// whitelist myself so I don't have to answer the captcha
$ipWhitelist = array('50.177.140.82', '127.0.0.1');
// echo $_SERVER['REMOTE_ADDR'];
// url is pre-fillable via querystring
// FILTER_VALIDATE_URL will not work with non-ascii domains, but we're only in the U.S.
$url = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);

if (isset($_POST["submit"])) {
    // Check if url has been entered
    $url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
    if (empty($url)) {
        $errUrl = 'Please enter the full location where your wiki is hosted (e.g. http://www.example.com/wiki)';
    }

    // Check if email has been entered and is valid
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errEmail = 'Please enter a valid email address';
    }

    // do reCaptcha verification for anyone not from hq
    if (!in_array($_SERVER['REMOTE_ADDR'], $ipWhitelist)) {
        require_once( __DIR__ . "secret.php" );
        $recaptcha = new \ReCaptcha\ReCaptcha($secret);
        $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
        if ($resp->isSuccess()) {
            // verified! $human 
        } else {
            $errHuman = (string) $resp->getErrorCodes();
        }
    }

    if (!isset($errUrl) && !isset($errHuman) && !isset($errEmail)) {
        // We're good to go, do processing

        $data = '';
        $format = new \eqt\wikireport\Format();
        $wurl = new \eqt\wikireport\UrlWiki($url);

        if ($wurl->isWiki()) {
            //$format->pre_print($wurl);
            // we've pre-fetched the basics.
            // We can't get 'extensions' info on older wikis
            $apiQuery = '?action=query&meta=siteinfo&format=json&siprop=general|statistics';
            $versionString = $wurl->data['query']['general']['generator'];
            $versionString = trim(str_ireplace("MediaWiki", '', $versionString));
            // echo "The version is $versionString";
            // extensions become available in 1.16
            if (version_compare($versionString, '1.16.0') >= 0) {
                $apiQuery .= '|extensions';
            }

            $MwApi = new \eqt\wikireport\MwApi2($wurl->apiUrl);
            $MwApi->makeQuery($apiQuery);
            $data = $MwApi->data;
            $fresh = $MwApi->getFreshness();
            //$format->pre_print(print_r($MwApi->arrayData['query']['general'], true));
            // $format->pre_print(print_r(get_object_vars($MwApi), true));
            //$format->pre_print($data);
            // exit();
        } else {
            // bad url
            $errUrl = "No wiki found at that URL";
        }

        $data = $MwApi->arrayData;

        $version = $MwApi->generator;
        $general = $data['query']['general'];
        $extensions = $data['query']['extensions'];
        $statistics = $data['query']['statistics'];

        $canonicalUrl = $MwApi->base;
        if (empty($canonicalUrl)) {
            $errWikiPerm = "Unable to access basic info. (non-standard API endpoint; or permission problem)";
        }
        
        $result .= <<<HERE
        <div class="alert alert-$fresh">You're running $version at <a href="$url" target="_blank">$url</a><br />
        This is compared to {$MwApi->current_version} which was found running at $MwApi->current_url as of $MwApi->current_date
        What's been <a href="https://git.wikimedia.org/blob/mediawiki%2Fcore.git/HEAD/HISTORY" target="_blank">added, fixed or changed</a>?</div>
HERE;
        
        // mail the report
        $name = "Anonymous";
        $email = $_POST['email'];
        $sender = "eQuality Technology <info@eQuality-Tech.com>";
        $to = $_POST['email'];
        $bcc = "eQuality Technology <info@eQuality-Tech.com";
        $subject = "Wiki Report for $MwApi->sitename ($MwApi->base).";
        // $message = print_r($MwApi->arrayData, true);
        $message = $result;

        $headers =  "From: $sender" . "\r\n";
        $headers .= "E-Mail: 'info@eQuality-Tech.com'" . "\r\n";
        $headers .= "Bcc: $bcc" . "\r\n";
       
        $body = wordwrap($message, 70, "\r\n");
        if (mail ($to, $subject, $body, $headers)) {
            $result .= '<div class="alert alert-success">Report sent!</div>';
        } else {
            $result .='<div class="alert alert-danger">Sorry there was an error sending your report. Please let us know at info@eQuality-Tech.com</div>';
        }

        
    } else {
        // errors present in the submit, build error messages
        
        if (isset($errUrl)) {
            $result = <<<HERE
            <div class="alert alert-danger">We could not detect a wiki at <a href="$url" target="_blank">$url</a>
            </div>
HERE;
        }
        if (isset($errWikiPerm)) {
            $result .= <<<HERE
            <div class="alert alert-danger">Wiki detected at <a href="$url" target="_blank">$url</a>, but we can't report on it.
            </div>
HERE;
        } 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="What's that wiki running?">
        <meta name="author" content="eQuality-Tech.com">
        <title>Wiki Report</title>
        <link rel="shortcut icon" href="//freephile.org/wikireport/favicon.ico" type="image/png">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
        <!-- integrate Google reCaptcha -->
        <script src='https://www.google.com/recaptcha/api.js'></script>
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <h1 class="page-header text-center">What's that wiki running?</h1>
                    <form id="wr" class="form-horizontal" role="form" method="post" action="">
                        <div class="form-group">
                            <label for="url" class="col-sm-2 control-label">Wiki URL</label>
                            <div class="col-sm-10">
                                <input type="url" class="form-control typeahead" id="url" name="url" placeholder="https://example.com" value="<?php echo $url; ?>"
                                       onfocus="if (!this.value) {
                                                   this.value = 'http://';
                                               } else
                                                   return false" 
                                       onblur="if (this.value == 'http://') {
                                                   this.value = '';
                                               } else
                                                   return false" />
<?php if (isset($errUrl)) {
    echo "<p class='text-danger'>$errUrl</p>";
} ?>
<?php if (isset($errWikiPerm)) {
    echo "<p class='text-danger'>$errWikiPerm</p>";
} ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Show Me</label>
                            <div class="col-sm-10 col-sm-offset-2"> 
                                <label class="checkbox checkbox-success" for="general">
                                    <input type="checkbox" value="general" id="general" name="options[]" 
<?php echo ($form->isChecked("options", "general")) ? 'checked="checked"' : '' ?>/>
                                    Wiki Report
                                </label>
                                <label class="checkbox checkbox-success" for="extensions">
                                    <input type="checkbox" value="extensions" id="extensions" name="options[]" 
<?php echo ($form->isChecked("options", "extensions")) ? 'checked="checked"' : '' ?>/>
                                    Extensions
                                </label>
                                <label class="checkbox checkbox-success" for="statistics">
                                    <input type="checkbox" value="statistics" id="statistics" name="options[]" 
<?php echo ($form->isChecked("options", "statistics")) ? 'checked="checked"' : '' ?>/>
                                    Statistics
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email" class="col-sm-2 control-label">Email</label>
                            <div class="col-sm-10">
                                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" 
                                       value="<?php echo htmlspecialchars($_POST['email']); ?>">
<?php if (isset($errEmail)) {
    echo "<p class='text-danger'>$errEmail</p>";
} ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="human" class="col-sm-2 control-label">Are you a robot?</label>
                            <div class="col-sm-10">
                                <div class="g-recaptcha" data-sitekey="6LcjPwgTAAAAACwnvsybTIDSyvsNs0EkbxFkb-qw"></div>
                                <input type="hidden" class="form-control" id="human" name="human" placeholder="Not a bot">
<?php if (isset($errHuman)) {
    echo "<p class='text-danger'>$errHuman</p>";
} ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-10 col-sm-offset-2">
                                <input id="submit" name="submit" type="submit" value="Check wiki" class="btn btn-primary">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-10 col-sm-offset-2">
<?php if (isset($result)) {
    echo $result;
} ?>  
                            </div>
                        </div>
                    </form> 
                </div>
            </div>
            
        </div>
        <div id="footer">
            <div class="container">
                <p class="muted credit">courtesy <a href="https://eQuality-Tech.com">eQuality Technology</a> and <a href="https://linkedin.com/in/freephile/">Greg Rundlett</a></p>
            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script src="https://freephile.org/wikireport/vendor/jquery-number/jquery.number.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
        <?php
        if (isset($_POST['submit'])) {

            $options = array();
            if (isset($_POST['options'])) {
                $options = $_POST['options'];
            }

            if (in_array('general', $options) || count($options) == 0) {
                $tabledata = (array) $general;
                echo <<<HERE
      <div class="col-md-6 col-md-offset-3">
      <h2>Wiki Report</h2>
      <div class="table-responsive">
        <table id="wiki-general-table" class="table table-striped table-condensed table-bordered table-hover">
          <thead>
          <tr><th>Item</th><th>Value</th></tr>
          </thead>
          <tbody>
HERE;
                foreach ($tabledata as $k => $v) {
                    $format->linkify($v);
                    $v = $format->implode($v); // avoid array to string conversion and no output
                    echo "<tr><th>$k</th><td>$v</td></tr>";
                }
                echo "</tbody>
        </table>
      </div>
      </div>";
            }
            if (in_array('extensions', $options)) {
                $tabledata = (array) $extensions;
                $extensions_count = count($tabledata);
                echo <<<HERE
      <div class="col-md-6 col-md-offset-3">
      <h2>Extensions</h2>
HERE;
                foreach ($tabledata as $k => $v) {
                    echo <<<HERE
        <div class="table-responsive">
          <table id="wiki-extensions-table-$k" class="table table-striped table-condensed table-bordered table-hover">
            <thead>
            <tr><th colspan="2" class="text-center">{$v["name"]}</th></tr>
            </thead>
            <tbody>
HERE;
                    foreach ($v as $key => $value) {
                        if ((strlen($value)) && ($key != 'name')) {
                            $format->linkify($value);
                            $value = $format->implode($value);
                            echo "<tr><th>$key</th><td>$value</td></tr>";
                        }
                    }
                    echo "</tbody>
        </table>
      </div>";
                }
                echo "
      </div>";
            }
            if (in_array('statistics', $options)) {
                $tabledata = (array) $statistics;
                $headings = array_keys($tabledata);
                $len = count($tabledata);
                $values = array_values($tabledata);
                echo <<<HERE
      <h2>Statistics</h2>
      <div class="table-responsive">
        <table id="wiki-statistics-table" class="table table-striped table-condensed table-bordered table-hover">
          <thead>
          <tr>
HERE;
                for ($i = 0; $i < $len; $i++) {
                    echo "<th>$headings[$i]</th>";
                }

                echo "</tr></thead>
        <tbody>";
                echo "\n<tr>";
                for ($i = 0; $i < $len; $i++) {
                    echo "<td class=\"number\">$values[$i]</td>";
                }
                echo "</tr>";
                echo "</tbody>
        </table>
      </div>";
            }
        }// end if $POST
        ?>
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
        <script>
            /** The Bootstrap typeahead feature and Civi REST interface
             $(document).ready(function) {
             $('input.typeahead').typeahead({
             name: 'websites',
             prefetch: '/civicrm/extern/rest.php?entity=GroupContact&action=get&group_id=2&options[limit]=10',
             limit: 10
             });
             }
             */
            /** The Civi JavaScript API
             CRM.api3('Website', 'get', {
             "sequential": 1,
             "website_type_id": "Work"
             }).done(function(result) {
             // do something
             });
             */
        </script>

    </body>
</html>
