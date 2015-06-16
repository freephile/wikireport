<?php

/**
 * This file probably lives at https://freephile.org/wikireport/index.php
 * 
 * Using the MediaWiki API, we want to query a wiki installation about the 
 * site's metadata that tells us the version, and the extensions running there.
 * 
 * We want to be able to retrieve this data, and then import it into our CiviCRM database
 * We also want to be able to create a nice public-facing reporting tool that we 
 * can use to do one-off reports, or to show to site owners.
 * 
 * So we'll develop it in 3 phases:
 * #1 The public-facing reporting tool will be developed first
 * #2 The conduit to read and write data to the CiviCRM system
 * #3 Publicize the reporting tool 
 *      through campaigns to the people we have in the database
 *      through social and other networks
 *      possibly as a case study or info example on how to make APIs work for you and talk to each other.
 * The goal of publicizing the reporting tool is to make it an inbound marketing tool
 * If we can collect information about wikis that we don't know about, then we can market to those people.
 * 
 * 
 * To create the UI, we'll use Bootstrap 
 * @see https://en.wikipedia.org/wiki/Bootstrap_%28front-end_framework%29
 * @see http://getbootstrap.com/getting-started/#examples
 * @see https://freephile.org/wiki/Bootstrap
 * 
 * For some wikis, we may need to determine the api URL when we only have the "wiki" URL.
 * Using the common setup where the code is hosted at w/ and the pretty URLs are at wiki/, we 
 * find this in the source of the Main_Page:
 * <link rel="EditURI" type="application/rsd+xml" href="https://freephile.org/w/api.php?action=rsd" />
 * 
 * The MediaWiki API supports many formats, with json and jsonfm the most useful. json is what you think it is,
 * and jsonfm is formatted for viewing in the browser (good for development only)
 * 
 * The API has many possible parameters.  https://www.mediawiki.org/wiki/API:Siteinfo
 * The default is 'general', and you should combine as many as you want.  So, to get
 * all the info that we're after, we would compose a query like so:
 * https://freephile.org/w/api.php?action=query&meta=siteinfo&format=jsonfm&siprop=general|extensions|statistics
 * 
  api.php?action=query&meta=siteinfo&format=jsonfm&siprop=general
  From 'General' we're interested in 
 * "base" (which we already have? chicken/egg)
 * "sitename"
 * "logo"
 * "generator"
 * "phpversion"
 * "phpsapi"
 * "dbtype"
 * "dbversion" 
 * "lang" --just in case it's not 'en'
 * "timezone"
 * "time"
 * "favicon"
 *
 * I've found that there can be empty values; and which ones are empty depend on the instance, so we won't 
 * hard-code what is in the report... we'll just report on what we find.  Likewise, we'll endeavor to collect all the info
 * that is useful, but there will be information that is unavailable in some cases.
 
 After the general info, we are especially interested in 
 extensions.  Again, the info available for any given extension is going to vary, so we'll report on what's available,
 and likewise we will record what we can find.
 
 Do we timestamp in the CiviCRM database?  The "profile" will change over time, and so do we care about what it used to be?
 
{
    "query": {
        "extensions": [
            {
                "type": "skin",
                "name": "Vector",
                "namemsg": "skinname-vector",
                "descriptionmsg": "vector-skin-desc",
                "author": "Trevor Parscal, Roan Kattouw, ...",
                "url": "https://www.mediawiki.org/wiki/Skin:Vector",
                "license-name": "GPLv2+",
                "license": "/wiki/Special:Version/License/Vector"
            },
            {
                "type": "other",
                "name": "Lockdown",
                "descriptionmsg": "lockdown-desc",
                "author": "Daniel Kinzler, Platonides",
                "url": "http://mediawiki.org/wiki/Extension:Lockdown",
                "vcs-system": "git",
                "vcs-version": "db7023e0576a86ed3e0833aac3e25683fcf128c6",
                "vcs-url": "https://git.wikimedia.org/tree/mediawiki%2Fextensions%2FLockdown.git/db7023e0576a86ed3e0833aac3e25683fcf128c6",
                "vcs-date": "2012-12-31T20:48:02Z"
            },
            {
                "type": "other",
                "name": "Html2Wiki",
                "descriptionmsg": "html2wiki-desc",
                "author": "Greg Rundlett",
                "url": "https://www.mediawiki.org/wiki/Extension:Html2Wiki",
                "version": "2015.02",
                "vcs-system": "git",
                "vcs-version": "c24896064a6a604f71f7e3253373a59d04fe19bc",
                "vcs-url": false,
                "vcs-date": "2015-04-28T17:41:26Z",
                "license-name": "GPL-2.0+",
                "license": "/wiki/Special:Version/License/Html2Wiki"
            },
            {
                "type": "other",
                "name": "Bad Behavior",
                "description": "Detects and blocks unwanted Web accesses",
                "author": "Michael Hampton",
                "url": "http://bad-behavior.ioerror.us/",
                "version": "2.2.13"
            },
            {
                "type": "other",
                "name": "WikiEditor",
                "descriptionmsg": "wikieditor-desc",
                "author": "Trevor Parscal, Roan Kattouw, Nimish Gautam, Adam Miller",
                "url": "https://www.mediawiki.org/wiki/Extension:WikiEditor",
                "version": "0.4.0",
                "vcs-system": "git",
                "vcs-version": "b13e25de978f68806d6b9653b4080746873aeaa3",
                "vcs-url": "https://git.wikimedia.org/tree/mediawiki%2Fextensions%2FWikiEditor.git/b13e25de978f68806d6b9653b4080746873aeaa3",
                "vcs-date": "2014-10-11T22:26:25Z",
                "license-name": "",
                "license": "/wiki/Special:Version/License/WikiEditor"
            },
            {
                "type": "other",
                "name": "VisualEditor",
                "descriptionmsg": "visualeditor-desc",
                "author": "Trevor Parscal, Inez Korczy\u0144ski, Roan Kattouw, Neil Kandalgaonkar, Gabriel Wicke, Brion Vibber, Christian Williams, Rob Moen, Subramanya Sastry, Timo Tijhof, Ed Sanders, David Chan, Moriel Schottlender",
                "url": "https://www.mediawiki.org/wiki/Extension:VisualEditor",
                "version": "0.1.0",
                "vcs-system": "git",
                "vcs-version": "c416e78dcfda879e6983c4f7d34cbf126691c1c6",
                "vcs-url": false,
                "vcs-date": "2014-05-07T21:36:05Z",
                "license-name": "MIT",
                "license": "/wiki/Special:Version/License/VisualEditor",
                "credits": "/wiki/Special:Version/Credits/VisualEditor"
            },
            {
                "type": "media",
                "name": "PDF Handler",
                "descriptionmsg": "pdf-desc",
                "author": "Martin Seidel, Mike Po\u0142tyn",
                "url": "https://www.mediawiki.org/wiki/Extension:PdfHandler",
                "vcs-system": "git",
                "vcs-version": "f7044ec891d68cb31273e0b34eda1f83c48c2102",
                "vcs-url": "https://git.wikimedia.org/tree/mediawiki%2Fextensions%2FPdfHandler.git/f7044ec891d68cb31273e0b34eda1f83c48c2102",
                "vcs-date": "2014-01-22T00:50:35Z",
                "license-name": "",
                "license": "/wiki/Special:Version/License/PDF_Handler"
            },
            {
                "type": "parserhook",
                "name": "Wiki Category Tag Cloud",
                "description": "A Category Tag Cloud derived, improved, and fixed from the YetAnotherTagCloud Extension",
                "author": "[http://mediawiki.org/wiki/User:Dantman Daniel Friesen]",
                "url": "https://www.mediawiki.org/wiki/Extension:WikiCategoryTagCloud",
                "version": "1.1"
            },
            {
                "type": "semantic",
                "name": "Semantic Bundle",
                "descriptionmsg": "semanticbundle-desc",
                "author": "[https://www.mediawiki.org/wiki/User:Yaron_Koren Yaron Koren], [https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw], ...",
                "url": "https://www.mediawiki.org/wiki/Semantic_Bundle",
                "version": "1.8.0.5.3",
                "vcs-system": "git",
                "vcs-version": "97c0b0aee75e63174d608a786b6d9f31fbb20e80",
                "vcs-url": "https://git.wikimedia.org/tree/mediawiki%2Fextensions%2FSemanticBundle.git/97c0b0aee75e63174d608a786b6d9f31fbb20e80",
                "vcs-date": "2014-01-03T19:33:07Z"
            }
        ]
    }
}
 
 and 
 siprop=statistics
 gives a whole bunch of useful info
 {
    "query": {
        "statistics": {
            "pages": 1363,
            "articles": 198,
            "edits": 5459,
            "images": 963,
            "users": 6,
            "activeusers": 1,
            "admins": 2,
            "jobs": 3660
        }
    }
}

siprop=usergroups
is interesting in case they've gone out of their way to do anything with groups


api.php?action=query&meta=siteinfo&format=jsonfm&siprop=rightsinfo
gives the copyright url and text
{
    "query": {
        "rightsinfo": {
            "url": "",
            "text": "Creative Commons Attribution-ShareAlike 4.0 International License"
        }
    }
}

api.php?action=query&meta=siteinfo&format=jsonfm&siprop=namespaces|namespacealiases
gives the namespaces and aliases, which on my site shows the 'private' namespace

api.php?action=query&meta=siteinfo&format=jsonfm&siprop=fileextensions
shows you the file extensions allows for file upload.

siprop=libraries is kind of interesting in that you probably didn't know you ran that in your wiki

https://freephile.org/w/api.php?action=query&meta=siteinfo&format=jsonfm&siprop=showhooks
is really interesting because it shows you what code is listening to what hooks.

https://freephile.org/w/api.php?action=query&meta=siteinfo&format=jsonfm&siprop=extensiontags
is useful as a documentation page that shows authors the additional tags usable on this wiki

 *
 */


// /w/api.php?action=query&meta=siteinfo&format=json&siprop=general

/** returns
{
    "query": {
        "general": {
            "mainpage": "Main Page",
            "base": "https://en.wikipedia.org/wiki/Main_Page",
            "sitename": "Wikipedia",
            "logo": "//en.wikipedia.org/static/images/project-logos/enwiki.png",
            "generator": "MediaWiki 1.26wmf6",
            "phpversion": "5.6.99-hhvm",
            "phpsapi": "srv",
            "hhvmversion": "3.6.1",
            "dbtype": "mysql",
            "dbversion": "10.0.16-MariaDB-log",
            "imagewhitelistenabled": "",
            "langconversion": "",
            "titleconversion": "",
            "linkprefixcharset": "",
            "linkprefix": "",
            "linktrail": "/^([a-z]+)(.*)$/sD",
            "legaltitlechars": " %!\"$&'()*,\\-.\\/0-9:;=?@A-Z\\\\^_`a-z~\\x80-\\xFF+",
            "invalidusernamechars": "@:",
            "git-hash": "84635a11fc9b739d84d9d108565d53d4ff4342b9",
            "git-branch": "wmf/1.26wmf6",
            "case": "first-letter",
            "lang": "en",
            "fallback": [],
            "fallback8bitEncoding": "windows-1252",
            "writeapi": "",
            "timezone": "UTC",
            "timeoffset": 0,
            "articlepath": "/wiki/$1",
            "scriptpath": "/w",
            "script": "/w/index.php",
            "variantarticlepath": false,
            "server": "//en.wikipedia.org",
            "servername": "en.wikipedia.org",
            "wikiid": "enwiki",
            "time": "2015-05-26T17:46:31Z",
            "misermode": "",
            "maxuploadsize": 1048576000,
            "thumblimits": [
                120,
                150,
                180,
                200,
                220,
                250,
                300
            ],
            "imagelimits": [
                {
                    "width": 320,
                    "height": 240
                },
                {
                    "width": 640,
                    "height": 480
                },
                {
                    "width": 800,
                    "height": 600
                },
                {
                    "width": 1024,
                    "height": 768
                },
                {
                    "width": 1280,
                    "height": 1024
                }
            ],
            "favicon": "//en.wikipedia.org/static/favicon/wikipedia.ico"
        }
    }
}
*/


/**
  * a convenience function to remember if a checkbox was submitted.
  */
function isChecked($checkname, $checkvalue) {
  if( !empty($_POST[$checkname])) {
    foreach($_POST[$checkname] as $value) {
      if ($checkvalue == $value) {
        return true;
      }
    }
  }
  return false;
}


// whitelist myself so I don't have to answer the captcha
$ipWhitelist = array('50.177.140.82', '127.0.0.1');
// echo $_SERVER['REMOTE_ADDR'];

$wikiUrl = isset($_GET["wikiUrl"])? htmlspecialchars($_GET["wikiUrl"]) : '';

if ( isset($_POST["submit"]) ) {
  // composer libraries
  require __DIR__ . '/vendor/autoload.php';
  
  $wikiUrl = htmlspecialchars($_POST['wikiUrl']);
  // Check if wikiUrl has been entered
  if (!$wikiUrl) {
    $errWikiUrl = 'Please enter the full location where your wiki is hosted (e.g. http://example.com/wiki)';
  } elseif (!stristr($wikiUrl,'http')) {
    $errWikiUrl = 'Please include the protocol portion (either http:// or https://)';
  }
  // do reCaptcha verification for anyone not from hq
  if (!in_array($_SERVER['REMOTE_ADDR'], $ipWhitelist)) {
    require_once( __DIR__ . "secret.php" );
    $recaptcha = new \ReCaptcha\ReCaptcha($secret);
    $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
    if ($resp->isSuccess()) {
        // verified! $human 
    } else {
        $errHuman = (string)$resp->getErrorCodes();
    }    
  }

  // If there are no errors, do work and display
  // This is still hard-coded, but eventually we may want to put in an interface that lets you look at other
  // parts of the api
  // finding the location of api.php
  if ( !isset($errWikiUrl) && !isset($errHuman) ) {
    $apiQuery = '?action=query&meta=siteinfo&format=json&siprop=general|extensions|statistics';
    if ( substr($wikiUrl,-7) == 'api.php' ) {
      $apiUrl = $wikiUrl;
      $fullUrl = $apiUrl . $apiQuery;
      $data = file_get_contents($fullUrl);
    } else {
      // two requests to get the json
      $data = file_get_contents($wikiUrl);
      // a valid MediaWiki page will have a link like so
      // <link rel="EditURI" type="application/rsd+xml" href="https://freephile.org/w/api.php?action=rsd" />
      // if ( preg_match( '#<link rel="EditURI" type="application/rsd\+xml" href="(.*)\?action=rsd"#', $data, $matches) ) {
      if ( preg_match( '#EditURI.* href\="(.*)\?action\=rsd"#U', $data, $matches) ) {
        $apiUrl = $matches[1];
        $fullUrl = $apiUrl . $apiQuery;
        $data = file_get_contents($fullUrl);
        // we can just record whatever URL is given as the wiki URL.  We don't have to canonicalize it
        // We could record the apiUrl in our database as the canonical reference to identify a wiki.
        // OR, even better, the 'base' attribute in the 'general' property shows the "Main Page" URL
        // There is <link rel="alternate" hreflang="x-default" href="/wiki/Music" /> that we could use
        // to canonicalize the wikiUrl from the client, but I don't see the point.
      } else {
        // bad wikiUrl
        $errWikiUrl = "Couln't find a wiki at that URL";
      }
    }
    
    
    
    // define constants for comparison
    define( "CURRENT_VERSION", '1.26wmf8');
    define( "CURRENT_URL", 'https://en.wikipedia.org/');
    define( "CURRENT_DATE", "2015/06/05");
    // assign them to variables for easier output
    $current_version = CURRENT_VERSION;
    $current_url = CURRENT_URL;
    $current_date = CURRENT_DATE;
    
    // https://php.net/manual/en/function.json-decode.php
    // With json_decode(), you either get an object, or using the optional second
    // parameter, you can force the return value to an array.
    // in the former case, you access the content using object notation
    // in the latter case, you use array notation
    // ie.    $data = json_decode($data);
    //     $version = $data->query->general->generator;
    // or     $data = json_decode($data, true);
    // $version = $data['query']['general']['generator'];
    
    $data = json_decode($data, true);
    $version = $data['query']['general']['generator'];
    $general = $data['query']['general'];
    $extensions = $data['query']['extensions'];
    $statistics = $data['query']['statistics'];
    
    $canonicalWikiUrl = $general['base'];
    

    $result =  <<<HERE
      <div class="alert alert-success">You're running $version at $wikiUrl<br />
      This is compared to $current_version which was found running at $current_url as of $current_date
      </div>
HERE;
  } else {
    $result='<div class="alert alert-danger">Sorry there was an error.</div>';
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
            <label for="wikiUrl" class="col-sm-2 control-label">Wiki URL</label>
            <div class="col-sm-10">
              <input type="url" class="form-control" id="wikiUrl" name="wikiUrl" placeholder="https://example.com" value="<?php echo $wikiUrl; ?>">
              <?php if ( isset($errWikiUrl) ) { echo "<p class='text-danger'>$errWikiUrl</p>"; } ?>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label">Show Me</label>
            <div class="col-sm-10 col-sm-offset-2"> 
              <label class="checkbox checkbox-success" for="general">
                <input type="checkbox" value="general" id="general" name="options[]" <?php echo (isChecked("options", "general"))? 'checked="checked"' : '' ?>/>
                Wiki Report
              </label>
              <label class="checkbox checkbox-success" for="extensions">
                <input type="checkbox" value="extensions" id="extensions" name="options[]" <?php echo (isChecked("options", "extensions"))? 'checked="checked"' : '' ?>/>
                Extensions
              </label>
              <label class="checkbox checkbox-success" for="statistics">
                <input type="checkbox" value="statistics" id="statistics" name="options[]" <?php echo (isChecked("options", "statistics"))? 'checked="checked"' : '' ?>/>
                Statistics
              </label>
            </div>
          </div>
          <div class="form-group">
            <label for="human" class="col-sm-2 control-label">Are you a robot?</label>
            <div class="col-sm-10">
              <div class="g-recaptcha" data-sitekey="6LcjPwgTAAAAACwnvsybTIDSyvsNs0EkbxFkb-qw"></div>
              <input type="hidden" class="form-control" id="human" name="human" placeholder="Not a bot">
            <?php if ( isset($errHuman) ) { echo "<p class='text-danger'>$errHuman</p>"; } ?>
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-10 col-sm-offset-2">
              <input id="submit" name="submit" type="submit" value="Check wiki" class="btn btn-primary">
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-10 col-sm-offset-2">
              <?php if ( isset($result) ) { echo $result; } ?>  
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
  if ( isset($_POST['submit']) ) {
    function objectToArray ($object) {
      if(!is_object($object) && !is_array($object))
          return $object;

      return array_map('objectToArray', (array) $object);
    }
    
    /**
     * operate on a value to create a hyperlink
     * if the value is a scalar URL
     * @return boolean TRUE if linkified
     */
    function linkify (&$v) {
      if ( is_string($v) && strlen($v) ) {
        if ( parse_url($v, PHP_URL_SCHEME)  ) {
          $v = "<a href=\"$v\">$v</a>";
        }
        return true;
      }
      return false;
    }
    // use get_object_vars()
    //$tabledata = get_object_vars($statistics);
    // use casting
    // $tabledata = (array) $general;

    $options = $_REQUEST['options'];
    
    if( in_array('general', $options) || count($options)==0) {
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
      foreach ( $tabledata as $k => $v ) {
        linkify($v);
        echo "<tr><th>$k</th><td>$v</td></tr>";
      }
      echo "</tbody>
        </table>
      </div>
      </div>";
    }
    if( in_array('extensions', $options) ) {
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
          if ( (strlen($value)) && ($key != 'name') ) {
            // if value starts with http or is a protocol-relative URL, then linkify it
            linkify($value);
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
    if( in_array('statistics', $options) ) {
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
      for ($i=0; $i < $len; $i++) {
        echo "<th>$headings[$i]</th>";
      }

      echo "</tr></thead>
        <tbody>";
      echo "\n<tr>";
        for ($i=0; $i< $len; $i++) {
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
    $(".number").number( true, 0 );
    
    // add Google Analytics
    // UA-39339059-2
    
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

      ga('create', 'UA-39339059-2', 'auto');
      ga('send', 'pageview');
    
    /*
$(document).ready(function() {
  $.ajax({
    url: "https://freephile.org/w/api.php",
    // url: "http://en.banglapedia.org/api.php",
    jsonp: "callback",
    dataType: "jsonp",
    data: {
      action: "query",
      meta: "siteinfo",
      format: "json",
      siprop: "general|extensions|statistics"
      // siprop: "general|extensions|statistics"
    },
    success: function( data ) {
      // console.log(Object.keys(data));
      var text = '';
      var myObj = data.query.general;
      Object.getOwnPropertyNames(myObj).every(function(val, idx, array) {
        if ( (myObj[val] == '') || (typeof myObj[val] == 'undefined') ) {
          return false;
        } else {
          console.log( val + ' -> ' + myObj[val]);
          text +=  '<li>' + val + ' -> ' + myObj[val] + "</li>\n";
          return true;
        }
      });
      $( "#wikigeneral" ).html(text);
      //alert(text);
      delete myObj;
      var text = '';
      var myObj = data.query.extensions;
      Object.getOwnPropertyNames(myObj).every(function(val, idx, array) {
        if ( (myObj[val] == '') || (typeof myObj[val] == 'undefined') ) {
          return false;
        } else if (typeof myObj[val] == 'object') {
          console.log( val + ' -> nested ');
          text +=  '<li>' + val + ' -> nested ';
          nestedObj = myObj[val];
          Object.getOwnPropertyNames(nestedObj).every(function(val, idx, array) {
            console.log( val + ' -> ' + nestedObj[val]);
            text +=  val + ' -> ' + nestedObj[val] + "</li>\n";
          });
        } else {
          console.log( val + ' -> ' + myObj[val]);
          text +=  '<li>' + val + ' -> ' + myObj[val] + "</li>\n";
          return true;
        }
      });
      $( "#wikiextensions" ).html(text);
      delete myObj;
      var text = '';
      var myObj = data.query.statistics;
      // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/getOwnPropertyNames
      Object.getOwnPropertyNames(myObj).forEach(function(val, idx, array) {
        console.log( val + ' -> ' + myObj[val]);
        text +=  '<li>' + val + ' -> ' + myObj[val] + "</li>\n";
      });
      $( "#wikistatistics" ).html(text);

    }
  })
});    
    
/**
      $(document).ready(function() {
        var wikiUrl = $( "#wikiUrl" ).val();
        var fullUrl = wikiUrl +  apiQuery;
        $.ajax({
          url: fullUrl
        })
        .done(function( data ) {
          if ( console && console.log ) {
            console.log( "Sample of data:" +  data);
          }
        });
      });
    
    function makeQueryUrl (wikiUrl, apiQuery) {
      if ( (wikiUrl == '') || (typeof wikiUrl == 'undefined') ) {
        wikiUrl = $( "#wikiUrl" ).val();
      }
      if ( (apiQuery == '') || (typeof apiQuery == 'undefined') ) {
        apiQuery = '/api.php?action=query&meta=siteinfo&format=json&siprop=general|extensions|statistics';
      }
      return wikiUrl + apiQuery;
    }

    $( "#wr" ).submit(function( event ) {
        var apiQuery = '/api.php?action=query&meta=siteinfo&format=json&siprop=general|extensions|statistics';
        var fullUrl = $( "#wikiUrl" ).val() + apiQuery;
        $( "#wikigeneral" ).text( fullUrl ).show().fadeIn( 100 );
        event.preventDefault();
    });  
 */
    </script>

  </body>
</html>