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
$form   = new \eqt\wikireport\Form();
// whitelist myself so I don't have to answer the captcha
$ipWhitelist = array('50.177.140.82', '127.0.0.1');
// echo $_SERVER['REMOTE_ADDR'];
// url is pre-fillable via querystring
// FILTER_VALIDATE_URL will not work with non-ascii domains, but we're only in the U.S.
$url = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);

if ( isset($_POST["submit"]) ) {  
  // Check if url has been entered
  $url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
  if ( empty($url)) {
    $errUrl = 'Please enter the full location where your wiki is hosted (e.g. example.com/wiki)';
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

  if ( !isset($errUrl) && !isset($errHuman) ) {

      $format = new \eqt\wikireport\Format();
      $wurl    = new \eqt\wikireport\UrlWiki($url);
 

      if ( $wurl->isWiki() ) {
        $mwApi  = new \eqt\wikireport\mwApi($wurl->apiUrl);
        $mwApi->makeQuery();
        $data = $mwApi->data;
        // $format->pre_print($data);
        // exit();
      } else {
        // bad url
        $errUrl = "No wiki found at that URL";
      }
    }
    
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
    
    $canonicalUrl = $general['base'];
    if (empty($canonicalUrl)) {
      $errWikiPerm = "No Soup for YOU!";
    }
    
    if ( isset($errUrl) ) {
      $result =  <<<HERE
        <div class="alert alert-danger">We could not detect a wiki at $url
        </div>
HERE;
    } else if ( isset($errWikiPerm) ) {
      $result =  <<<HERE
        <div class="alert alert-danger">Wiki detected at $url, but we can't report on it.
        </div>
HERE;
    } else {
      $result =  <<<HERE
        <div class="alert alert-success">You're running $version at $url<br />
        This is compared to {$mwApi->current_version} which was found running at $mwApi->current_url as of $mwApi->current_date
        </div>
HERE;
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
              <input type="url" class="form-control" id="url" name="url" placeholder="https://example.com" value="<?php echo $url; ?>">
              <?php if ( isset($errUrl) ) { echo "<p class='text-danger'>$errUrl</p>"; } ?>
              <?php if ( isset($errWikiPerm) ) { echo "<p class='text-danger'>$errWikiPerm</p>"; } ?>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label">Show Me</label>
            <div class="col-sm-10 col-sm-offset-2"> 
              <label class="checkbox checkbox-success" for="general">
                <input type="checkbox" value="general" id="general" name="options[]" <?php echo ($form->isChecked("options", "general"))? 'checked="checked"' : '' ?>/>
                Wiki Report
              </label>
              <label class="checkbox checkbox-success" for="extensions">
                <input type="checkbox" value="extensions" id="extensions" name="options[]" <?php echo ($form->isChecked("options", "extensions"))? 'checked="checked"' : '' ?>/>
                Extensions
              </label>
              <label class="checkbox checkbox-success" for="statistics">
                <input type="checkbox" value="statistics" id="statistics" name="options[]" <?php echo ($form->isChecked("options", "statistics"))? 'checked="checked"' : '' ?>/>
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
    // use get_object_vars()
    //$tabledata = get_object_vars($statistics);
    // use casting
    // $tabledata = (array) $general;

    $options = array();
    if ( isset($_POST['options']) ) {
        $options = $_POST['options'];
    }
    
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
        $format->linkify($v);
        $v = $format->implode($v); // avoid array to string conversion and no output
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
        var url = $( "#url" ).val();
        var fullUrl = url +  apiQuery;
        $.ajax({
          url: fullUrl
        })
        .done(function( data ) {
          if ( console && console.log ) {
            console.log( "Sample of data:" +  data);
          }
        });
      });
    
    function makeQueryUrl (url, apiQuery) {
      if ( (url == '') || (typeof url == 'undefined') ) {
        url = $( "#url" ).val();
      }
      if ( (apiQuery == '') || (typeof apiQuery == 'undefined') ) {
        apiQuery = '/api.php?action=query&meta=siteinfo&format=json&siprop=general|extensions|statistics';
      }
      return url + apiQuery;
    }

    $( "#wr" ).submit(function( event ) {
        var apiQuery = '/api.php?action=query&meta=siteinfo&format=json&siprop=general|extensions|statistics';
        var fullUrl = $( "#url" ).val() + apiQuery;
        $( "#wikigeneral" ).text( fullUrl ).show().fadeIn( 100 );
        event.preventDefault();
    });  
 */
    </script>

  </body>
</html>