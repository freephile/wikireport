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
// our generic functions
require __DIR__ . '/library.php';
$err = array(); // our errors
$result = '';
$report = '';
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
        $err['Url'] = 'Please enter the full location where your wiki is hosted (e.g. http://www.example.com/wiki)';
    }

    // Check if email has been entered and is valid
    if ( !empty($_POST['email']) ) {
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if (!$email ) {
            $err['Email'] = 'Please enter a valid email address';
        }
    } 

    // do reCaptcha verification for anyone not from hq
    if (!in_array($_SERVER['REMOTE_ADDR'], $ipWhitelist)) {
        require( __DIR__ . "/secret.php" );
        $recaptcha = new \ReCaptcha\ReCaptcha($reCAPTCHAsecret);
        $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
        if ($resp->isSuccess()) {
            // verified! $human 
        } else {
            $err['Human'] = (string) $resp->getErrorCodes();
        }
    }

    if ( !count($err) ) {
        // We're good to go, do processing
        
        $data = '';
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
            
            $fresh = $MwApi->getFreshness();
            $data = $MwApi->arrayData;

            $version = $MwApi->generator;

            $canonicalUrl = $MwApi->base;
            if (empty($canonicalUrl)) {
                $err['WikiPerm'] = "Unable to access basic info. (non-standard API endpoint; or permission problem)";
            }
            $result .= <<<HERE
            <div class="alert alert-$fresh" role="alert">
                <span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>
            You're running $version at <a href="$url" target="_blank">$url</a><br />

            This is compared to {$MwApi->current_version} which was found running at 
            $MwApi->current_url as of $MwApi->current_date

            What's been <a href="https://git.wikimedia.org/blob/mediawiki%2Fcore.git/HEAD/HISTORY" 
            target="_blank">added, fixed or changed</a>?
            </div>
HERE;
            


            $options = array();
            if (isset($_POST['options'])) {
                $options = $_POST['options'];
            }

            if (in_array('general', $options) || count($options) == 0) {
                $report .= show_general_data_table($data['query']['general'], 'general', 'Wiki Report');
            }
            if (in_array('extensions', $options)) {
                $report .= show_extensions_data_table($data['query']['extensions'], 'extensions', 'Extensions');
            }
            if (in_array('statistics', $options)) {
                $report .= show_statistics_data_table($data['query']['statistics'], 'statistics', "Statistics");
            }



            // $sent = mail_report($result, $MwApi, $email);
            $sent = true;
            if ($email) {
                if( $sent === true ) {
                    $result .= '<div class="alert alert-success" role="alert">'
                    . '<span class="glyphicon glyphicon-send" aria-hidden="true"></span>'
                     // glyph is decoration only so no need for class="sr-only" span
                    . ' Report sent!'
                    . '</div>';

                } else {
                    $result .='<div class="alert alert-danger" role="alert">'
                    . '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>'
                    . '<span class="sr-only">Error:</span>'
                    . 'Sorry there was an error sending your report. '
                    . $sent
                    . 'Please let us know at info@eQuality-Tech.com'
                    . '</div>';
                }
            }
            
        } else {
            // bad url
            $err['Url'] = "No wiki found at that URL";
        }
    } else {
        // errors present in the submit, build error messages
        populate_err_message($err, $url);
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
<?php 
include('navline.php'); 
?>
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
                                               }
                                               else
                                                   return false" />
<?php if (isset($err['Url'])) {
    echo "<p class='text-danger'>{$err['Url']}</p>";
} ?>
<?php if (isset($err['WikiPerm'])) {
    echo "<p class='text-danger'>{$err['WikiPerm']}</p>";
} ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Show Me</label>
                            <div class="col-sm-10 col-sm-offset-2"> 
                                <label class="checkbox checkbox-success" for="general">
                                    <input type="checkbox" value="general" id="general" name="options[]" 
<?php echo ($form->isChecked("options", "general") || !$_POST['submit'] ) ? 'checked="checked"' : '' ?>/>
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
                                <p class="help-block">Enter your email to receive a report. (not required)</p>
<?php if (isset($err['Email'])) {
    echo "<p class='text-danger'>{$err['Email']}</p>";
} ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="human" class="col-sm-2 control-label">Are you a robot?</label>
                            <div class="col-sm-10">
                                <div class="g-recaptcha" data-sitekey="6LcjPwgTAAAAACwnvsybTIDSyvsNs0EkbxFkb-qw"></div>
                                <input type="hidden" class="form-control" id="human" name="human" placeholder="Not a bot">
<?php if (isset($err['Human'])) {
    echo "<p class='text-danger'>{$err['Human']}</p>";
} ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-10 col-sm-offset-2">
                                <input id="submit" name="submit" type="submit" 
                                       value="Check wiki" class="btn btn-primary" >
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-10 col-sm-offset-2">
<?php 
if (isset($result)) {
    echo $result;
}
?>  
                            </div>
                        </div>
                    </form> 
                </div>
            </div>
<?php 
if (isset($report)) {
    echo $report;
}
?>              
        </div>
<?php
include('footer.php');
?>
    </body>
</html>
