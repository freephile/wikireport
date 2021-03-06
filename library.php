<?php

/* 
 * Copyright (C) 2015 greg
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as 
 * published by the Free Software Foundation, either version 3 of the 
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program in the LICENSE file.  
 * If not, see <http://www.gnu.org/licenses/>.
 */

// composer libraries
require __DIR__ . '/vendor/autoload.php';

function mail_report($report, $MwApi, $email=null) {
    global $quiet;
    require __DIR__ . '/secret.php';
    $byline = <<<HERE
<div>
<br />
<br />
    This report was brought to you by 
    <img src="https://freephile.org/wikireport/favicon.ico" alt="="/> eQuality Technology<br />
    a leader in free software and wikis.<br />
    <a href="https://freephile.org/wikireport/">Get another Wiki Report</a>
</div>
HERE;
    // echo "sending for $gmailUser\n<br />";
    // return true;
    // mail the report
    $report .= $byline;
    $mail = new PHPMailer;
    $mail->isSMTP();      // Set mailer to use SMTP
    $mail->SMTPDebug = 0; // 0 no debugging - 4 is maximum
    //Ask for HTML-friendly debug output
    $mail->Debugoutput = 'html';

    //$mail->Host = 'smtp.gmail.com';              // Specify SMTP server(s)
    $mail->Host = gethostbyname("smtp.gmail.com");
    $mail->SMTPAuth = true;                      // Enable SMTP authentication
    $mail->Username = $gmailUser;                // SMTP username
    $mail->Password = $gmailPassword;            // SMTP password
    $mail->SMTPSecure = 'tls';                   // Enable TLS encryption
    $mail->Port = 587;                           // TCP port to connect to
    // Google rewrites this to the default set in your account
    $mail->setFrom('info@eQuality-Tech.com', 'Wiki Report');
    if (! is_null($email)) {
        $mail->addAddress($email);                   // Add a recipient
    }
    // I can suppress bcc by adding ?quiet=1 "true", "on", "yes"
    //if (isset($quiet) && $quiet != true) {
    if (!in_array($_SERVER['REMOTE_ADDR'], $ipWhitelist)) {
        $mail->addBCC('info@eQuality-Tech.com');
    }
    $mail->isHTML(true);                         // Set email format to HTML
    $mail->Subject = "Wiki Report for $MwApi->sitename ($MwApi->base).";
    $mail->Body = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html><body>';
    $mail->Body    .= $report;
    $mail->Body    .= '</body></html>';
    $mail->AltBody = strip_tags($report);

    if(!$mail->send()) {
        return $mail->ErrorInfo;
    } else {
        return true;
    }            
}


function populate_err_message($errors, $url) {
    $msg = '';
    if (! count($errors)) {
        return '';
    }
    foreach ($errors as $k => $v) {
        if ($k == 'Url') {
            $msg .= <<<HERE
            <div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error: </span>
                We could not detect a wiki at <a href="$url" target="_blank">$url</a>
            </div>
HERE;
        }
        if ($k == 'WikiPerm') {
            $msg .= <<<HERE
            <div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error: </span>
                Wiki detected at <a href="$url" target="_blank">$url</a>, but we can't report on it.
            </div>
HERE;
        }
    }
    return $msg;
}

function show_statistics_data_table($tabledata, $section, $title) {
    $return = '';
    $format = new \eqt\wikireport\Format();
    $headings = array_keys($tabledata);
    $len = count($tabledata);
    $values = array_values($tabledata);
    $return .= <<<HERE
    <div class="col-md-6 col-md-offset-3">
      <h2>$title</h2>
      <div class="table-responsive">
        <table id="wiki-$section-table" class="table table-striped table-condensed table-bordered table-hover">
          <thead>
          <tr>
HERE;
                for ($i = 0; $i < $len; $i++) {
                    $return .= "<th>$headings[$i]</th>";
                }

                $return .= "</tr></thead>
        <tbody>";
                $return .= "\n<tr>";
                for ($i = 0; $i < $len; $i++) {
                    $return .= "<td class=\"number\">$values[$i]</td>";
                }
                $return .= "</tr>";
                $return .= "</tbody>
        </table>
      </div>
    </div>";
    return $return;
}

function show_general_data_table($tabledata, $section, $title) {
    $return = '';
    $format = new \eqt\wikireport\Format();
    $return .= <<<HERE
    <div class="col-md-6 col-md-offset-3">
        <h2>$title</h2>
        <div class="table-responsive">
            <table id="wiki-$section-table" class="table table-striped table-condensed table-bordered table-hover">
            <thead>
            <tr><th>Item</th><th>Value</th></tr>
            </thead>
            <tbody>
HERE;
    foreach ($tabledata as $k => $v) {
        $format->linkify($v);
        $v = $format->implode($v); // avoid array to string conversion and no output
        $return .= "<tr><th>$k</th><td>$v</td></tr>";
    }
    $return .= "  </tbody>
        </table>
    </div>
</div>";
    return $return;
}

function show_extensions_data_table($tabledata, $section, $title) {
    $return = '';
    $format = new \eqt\wikireport\Format();
    $return .= <<<HERE
    <div class="col-md-12">
        <h2>$title</h2>
HERE;
    foreach ($tabledata as $k => $v) {
        $return .= <<<HERE
        <div class="table-responsive">
            <table id="wiki-$section-table-$k" class="table table-striped table-condensed table-bordered table-hover">
            <thead>
                <tr><th colspan="2" class="text-center">{$v["name"]}</th></tr>
            </thead>
            <tbody>
HERE;
        foreach ($v as $key => $value) {
            if ((strlen($value)) && ($key != 'name')) {
                $format->linkify($value);
                $value = $format->implode($value);
                $return .= "<tr><th>$key</th><td>$value</td></tr>";
            }
        }
        $return .= "</tbody>
        </table>
      </div>";
    }
    
    $return .= "
    </div>";
    return $return;
}

    /**
     * convert a HSL colorscheme to either Hexadecimal (default) or RGB.
     * 
     * We want a method where we can programmatically generate a series of colors
     * between two values (eg. red to green) which is easy to do with HSL because
     * you just change the hue. (0 = red, 120 = green).  You can use this function
     * to convert those hsl color values to either the rgb or hexadecimal color scheme.
     * e.g. You have
     *   hsl(50, 100%, 50%)
     * To convert,
     * $hex = convertHSL(50,100,50);  // returns #ffd500
     * or 
     * $rgb = convertHSL(50,100,50, false);  // returns rgb(255, 213, 0)
     *  
     * see https://coderwall.com/p/dvsxwg/smoothly-transition-from-green-to-red
     * @param int $h the hue
     * @param int $s the saturation
     * @param int $l the luminance
     * @param bool $toHex whether you want hexadecimal equivalent or rgb equivalent
     * @return string usable in HTML or CSS
     */

    function convertHSL($h, $s, $l, $toHex=true){
        $h /= 360;
        $s /=100;
        $l /=100;

        $r = $l;
        $g = $l;
        $b = $l;
        $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
        if ($v > 0){
              $m;
              $sv;
              $sextant;
              $fract;
              $vsf;
              $mid1;
              $mid2;

              $m = $l + $l - $v;
              $sv = ($v - $m ) / $v;
              $h *= 6.0;
              $sextant = floor($h);
              $fract = $h - $sextant;
              $vsf = $v * $sv * $fract;
              $mid1 = $m + $vsf;
              $mid2 = $v - $vsf;

              switch ($sextant)
              {
                    case 0:
                          $r = $v;
                          $g = $mid1;
                          $b = $m;
                          break;
                    case 1:
                          $r = $mid2;
                          $g = $v;
                          $b = $m;
                          break;
                    case 2:
                          $r = $m;
                          $g = $v;
                          $b = $mid1;
                          break;
                    case 3:
                          $r = $m;
                          $g = $mid2;
                          $b = $v;
                          break;
                    case 4:
                          $r = $mid1;
                          $g = $m;
                          $b = $v;
                          break;
                    case 5:
                          $r = $v;
                          $g = $m;
                          $b = $mid2;
                          break;
              }
        }
        $r = round($r * 255, 0);
        $g = round($g * 255, 0);
        $b = round($b * 255, 0);

        if ($toHex) {
            $r = ($r < 15)? '0' . dechex($r) : dechex($r);
            $g = ($g < 15)? '0' . dechex($g) : dechex($g);
            $b = ($b < 15)? '0' . dechex($b) : dechex($b);
            return "#$r$g$b";
        } else {
            return "rgb($r, $g, $b)";    
        }
}

