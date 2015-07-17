<?php

/*
 * Copyright (C) 2015 Gregory Scott Rundlett
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

namespace eqt\wikireport;

/**
 * This class facilitates talking to the CiviCRM API (v3)
 * 
 * The 'sequential' param just means order the array numerically
 * @author greg
 */
class CiviApi {
    
    /**
     * When you make an API call to CiviCRM, you get back an array.
     * That array has several useful elements besides the values.
     * However, the shape of the array changes if you make a chained API call.
     * 
     * And, a create action doesn't return 'values'
     * 
     * @var bool if there was an error in the API response
     */
    var $is_error;
    var $version; // 3
    var $count;   // 1
    var $id;      // 2525
    var $values;  // Array

    var $msg;

    /**
     * This map is particular to our instance of CiviCRM
     * If you're using this code, you will have to change this to match your
     * particular setup AFTER you create the custom fields in CiviCRM
     * @var array map of General wiki data to Civi Custom fields
     */
    var $genKeys = array (
        "wUrl"       => "custom_40",
        "mainpage"   => "custom_41",
        "base"       => "custom_42",
        "sitename"   => "custom_43",
        "logo"       => "custom_44",
        "generator"  => "custom_45",
        "phpversion" => "custom_46",
    // "phpsapi", 
        "dbtype"     => "custom_47",
        "dbversion"  => "custom_48",
    // "externalimages", 
    // "langconversion", 
    // "titleconversion", 
    // "linkprefixcharset", 
    // "linkprefix", 
    // "linktrail", 
    // "legaltitlechars", 
    // "git-hash", 
    // "git-branch", 
    // "case", 
    // "lang", 
    // "fallback", 
    // "fallback8bitEncoding", 
        "writeapi"    => "custom_49",
        "timezone"    => "custom_50",
        "timeoffset"  => "custom_51",
        "articlepath" => "custom_52",
        "scriptpath"  => "custom_53",
    // "script", 
    // "variantarticlepath", 
        "server"      => "custom_54",
        "servername"  => "custom_55",
        "wikiid"      => "custom_56",
        "time"        => "custom_57",
        "maxuploadsize" => "custom_58",
    // "thumblimits", 
    // "imagelimits", 
        "favicon"     => "custom_59",
    );

    // https://freephile.org/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=txt

    var $statKeys = array(
        "wUrl"     => "custom_60",
        "pages"    => "custom_61",
        "articles" => "custom_62",
        "edits"    => "custom_63",
        "images"   => "custom_64",
        "users"    => "custom_65",
        "activeusers" => "custom_66",
        "admins"   => "custom_67",
        "jobs"     => "custom_68",
    );
    
    
    public function __construct() {
        // bootstrap Drupal
        define('DRUPAL_ROOT', '/var/www/equality-tech.com/www/drupal/');
        require_once DRUPAL_ROOT . 'includes/bootstrap.inc';
        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
        // then bootstrap CiviCRM
        require_once DRUPAL_ROOT . 'sites/default/civicrm.settings.php';
        $config = \CRM_Core_Config::singleton();
        $this->msg = array();
    }
    
    /**
     * curiously, we have to actually call this method by name because when 
     * I try to just echo $url (the object); I get the output of the webpage??
     */
    function __toString() {
        echo nl2br(implode(PHP_EOL , $this->msg));
    }

    /**
     * makeCall is a generic wrapper around the CiviCRM API so that we can more
     * easily make "get", "create" and other calls to the API.
     * 
     * Note that if you add debug => 1 to your params, you'll get a 'trace' value
     * in the results to help identify the cause of your error.
     * 
     * @param type $entity
     * @param string $action
     * @param type $params
     * @return boolean
     */
    function makeCall($entity, $action= null, $params) {
        if ($action == null) { $action = 'get'; }
        // Check if CiviCRM is installed here.
        if (!module_exists('civicrm')) {
            return false;
        }
        // Initialization call is required to use CiviCRM APIs.
        civicrm_initialize();
        try {
            $result = civicrm_api3($entity, $action, $params);
        } catch (CiviCRM_API3_Exception $e) {
            $errorMessage = $e->getMessage();
            $errorCode = $e->getErrorCode();
            $errorData = $e->getExtraParams();
            return array(
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'error_data' => $errorData
            );
        }
        if (!$result) {
            $this->msg[] = __METHOD__ . " No result, returning false.";
            return false;
        }
        // These assignments may not be valid bc array shape changes w chained
        // API calls (and different entities?)
        $this->is_error = $result['is_error'];
        $this->version  = $result['version'];
        $this->count    = $result['count'];
        $this->id       = $result['id'];
        $this->values   = $result['values'];
        $this->msg[]    = __METHOD__ . " called on $entity with action $action, returning $this->id.";
        return $result;
    }
    
    
    /**
     * Make sure when using the result that you use the 'values' element
     */
    function getWebsite($url, $fuzzy=false) {
        $params = array(
            'sequential' => 1,
        );
        if ($fuzzy) {
            $params2 = array ('url' => array('LIKE' => "%$url%"));
        } else {
            $params2 = array ('url' => $url);            
        }
        $params = $params + $params2; // array union

        $result = $this->makeCall('website', 'get' ,$params);
        if ($result['is_error']) {
            echo "Error finding website for $url";
            return false;
        }
        return $result;
    }

    /**
     * A convenience function for creating or updating a Website entity in CiviCRM
     * As long as $params contains an 'id', then it will update the existing record.
     * 'contact_id' must be included in $params because all website records are 
     * associated to another contact entity.
     * 
     * @param array $params
     * @return array the array is passed back to the caller.
     */
    function createWebsiteRecord($params) {
        if ( !in_array('contact_id', array_keys($params)) ) {
            die ("You can not create a Website record without a 'contact_id'");
        }
        $result = $this->makeCall('website', 'create' ,$params);
        return $result;
    }
    
    /**
     * We'll check if the $url is a wiki
     * If it is, then we'll check for a website record in our database
     * We will add or update that website record
     * @param type $url
     */
    function add_custom_data($url) {
        $wurl = new \eqt\wikireport\UrlWiki($url);
        if ($wurl->isWiki()) {
            $apiQuery = '?action=query&meta=siteinfo&format=json&siprop=general';
            $MwApi = new \eqt\wikireport\MwApi($wurl->apiUrl);
            $MwApi->makeQuery($apiQuery);
            $data        = $MwApi->data;
            $general     = $MwApi->data['query']['general'];
            // $this->msg[] = print_r($general, true);
            $values      = array();

            // we don't use 
            // $fresh        = $MwApi->getFreshness();
            $canonicalUrl = $MwApi->base;
            $this->msg[] = "<b>'" . $MwApi->sitename . "'</b> is a wiki at $canonicalUrl";
            
            $civiRecord = $this->getWebsite($canonicalUrl, false);

            if ( $civiRecord['count'] !== 1 ) {
                $this->msg[] = 
                   "Found too many or too few records ({$civiRecord['count']}) for $canonicalUrl";
                $this->msg[] = "Exiting " . __METHOD__ . " without adding data";
                exit();
            }

            $contactId = $civiRecord['values'][0]['contact_id'];
            $websiteId = $civiRecord['id'];
            $type = $civiRecord['values'][0]['website_type_id'];
            if ($type !== '16') {
                $this->msg[] = "The Civi website record {$civiRecord['id']} is type $type; it should be updated to type 16";
            }
            // get general
            $values['custom_40'] = (string) $canonicalUrl; // we set this ourselves
                foreach ($general as $k => $v) {
                    if ( in_array($k, array_keys($this->genKeys)) ) {
                        $values["{$this->genKeys[$k]}"] = $v;
                    }
                }
            // get stats
            if (version_compare($wurl->versionString, '1.10.0') >= 0) {
                $stats = $MwApi->getStats();
                $stats = $stats['query']['statistics'];
                $this->msg[] = print_r($stats, true);
                $values['custom_60'] = (string) $canonicalUrl; // we set this ourselves
                foreach ($stats as $k => $v) {
                    if ( in_array($k, array_keys($this->statKeys)) ) {
                        $values["{$this->statKeys[$k]}"] = $v;
                    }
                }
            }
            // get extensions data
            if (version_compare($wurl->versionString, '1.16.0') >= 0) {
                // we currently do not support Extensions in custom data
                // maybe store in a note? how searchable would they be?
            }
            
            // record data
            $timestamp = time();
            $recorded = date("Y-m-d", $timestamp);
            $params = array(
              'sequential' => 1,
              'id' => $contactId,
              'custom_69' => $recorded,
              'custom_70' => $recorded,
            );
            $params += $values; // union arrays
            $result = $this->makeCall('Contact', 'create', $params);
            if ($result['is_error']) {
                $this->msg[] = "Error trying to set custom data for <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$contactId\">$contactId</a>";
            } else {
                $msg = "Success! Updated custom data for <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$contactId\">$contactId</a>";
                $this->msg[] = $msg;
                echo $msg;
            }
        }
        // $this->msg[] = print_r($MwApi, true);
    }
    
    /**
     * 
     * @param array $params
     * @return array API response where 'values' is the key for records
     */
    function getNote($params) {
        $params += array(
            'sequential' => 1,
        );
        $result = $this->makeCall('Note', 'get', $params);
        return $result;
    }
}
