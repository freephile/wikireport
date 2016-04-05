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
    var $UrlWiki; // can hold an UrlWiki object
    var $MwApi; // can hold MwApi object

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
        $out = __CLASS__;
        $out .= "\n<pre>\n" . nl2br(implode(PHP_EOL , $this->msg)) . "\n</pre>\n";
        return $out;
    }

    /**
     * make_call is a generic wrapper around the CiviCRM API so that we can more
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
    function make_call($entity, $action= null, $params) {
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
        $msg = (isset($result['id']))? "id {$result['id']}" : "count {$result['count']}";
        $msg = __METHOD__ . " called on $entity with action $action, returning " . $msg;
        $this->msg[]    = $msg; 
        return $result;
    }
    
    function org_create_from_url(UrlWiki $UrlWiki) {
        if (empty($UrlWiki->wikiUrl)) {
            return false;
            //die ("Need URL to create a contact");
        }
        $fuzzy = true; // do a wildcard search
        $result = $this->website_get($UrlWiki->wikiUrl, $fuzzy);
        // print '<pre>'; print_r ($result); print'</pre>';
        if ( $result['count'] === 0 ) {
            $this->msg[] = "Adding new org from website URL {$UrlWiki->wikiUrl}";
            $params2 = array(
                'sequential' => 1,
                'contact_type' => "Organization",
                'organization_name' => $UrlWiki->sitename,
                // "custom_18" => $email,
            );
            // don't need the return value, because we can use object properties
            $result2 = $this->make_call('Contact', 'create', $params2);
            $contact_id = $this->id;
            // add a wiki website URL for this contact
            $params3 = array(
                'contact_id' => $contact_id, // the new contact
                'url' => $UrlWiki->wikiUrl,
                'website_type_id' => 'wiki', // type 16
            );
            $result3 = $this->website_create($params3);
            // add the contact to the 'Incoming' group
            $result4 = $this->group_create( 
                array(
                    'sequential' => 1,
                    'group_id' => "Inbound_16",
                    'contact_id' => $contact_id,
                ));
        } else {
            // we already have a record for this website;
            // let's update the data for it
            /*
            $contact = $result['values'];
            $out = '';
            ob_start();
            $this->custom_data_fetch($contact['contact_id']);
            $out .= ob_get_clean();
             * 
             */
            return true;
        }
        if ( $result3['count'] === 1 ) {
            return true;
        }
        return false;
    }
    
    /**
     * Make sure when using the result that you use the 'values' element
     */
    function website_get($url, $fuzzy=false) {
        $params = array(
            'sequential' => 1,
            'options' => array('limit' => 1),
        );
        if ($fuzzy) {
            $params2 = array ('url' => array('LIKE' => "%$url%"));
        } else {
            $params2 = array ('url' => $url);            
        }
        $params = $params + $params2; // array union

        $result = $this->make_call('website', 'get' ,$params);
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
    function website_create($params) {
        if ( !in_array('contact_id', array_keys($params)) ) {
            die ("You can not create a Website record without a 'contact_id'");
        }
        if (!in_array('id', array_keys($params))) {
            // let's do a lookup to see if a pre-existing record exists so as
            // not to have multiple website records for the same URL
            $r1 = $this->website_get($params['url']);
            if ($r1['count']) {
                $this->msg[] = "Website already exists, adding in the id {$r1['id']}";
                $params += array('id' => $r1['id']);
            }
        }
        $result = $this->make_call('website', 'create' ,$params);
        return $result;
    }
    
    /**
     * @todo add switch to not fetch or record data unless 'forced' when data exists
     * 
     * @param mixed $cid either integer contact id or array of integers
     * @return boolean
     */
    function custom_data_fetch($cid) {
        if (is_array($cid)) {
            foreach ($cid as $id) {
                $this->custom_data_fetch( (int) $id );
            }
            return true;
        }
        $timestamp = time();
        $recorded = date("Y-m-d", $timestamp);
        /** don't need to refetch contact
        $params = array("id" => $cid, 'sequential' => 1,);
        $result = $this->make_call("Contact", "get", $params);
        if ($result["is_error"]) {
            die ("Unable to get Contact");
        }
        $contact = $result["values"];
         * 
         */
        $webparams = array(
            "sequential" => 1,
            "contact_id" => $cid,
            "website_type_id" => "16",
            "options" => array ("limit" => 1),
        );
        $webresult = $this->make_call("Website", "get", $webparams);
        if ($webresult["is_error"]) {
            die ("Unable to get Website due to error {$webresult["is_error"]}");
        }
        if ($webresult['count'] == 0) {
            $this->msg[] = "No wiki website for $cid; not fetching custom data";
            return true;
        }
        $website = $webresult["values"][0];
        $url = $website["url"];
        $this->msg[] = "Getting data for $url";
        $UrlWiki = new \eqt\wikireport\UrlWiki($url);
        
        if ($UrlWiki->is_wiki()) {
            // var_dump($UrlWiki);
            $MwApi = new \eqt\wikireport\MwApi($UrlWiki->apiUrl);
            $data        = $MwApi->data;
            $general     = $MwApi->data['query']['general'];
            $values      = array();
            $canonicalUrl = $MwApi->base;
            $this->msg[] = "<b>'" . $MwApi->sitename . "'</b> is a wiki at $canonicalUrl";
            // set general data into values that we'll store
            $values['custom_40'] = (string) $canonicalUrl; // we set this ourselves
            $values['custom_69'] = $recorded; // timestamp
            foreach ($general as $k => $v) {
                if ( in_array($k, array_keys($this->genKeys)) ) {
                    $values["{$this->genKeys[$k]}"] = $v;
                }
            }
            // get stats
            if (version_compare($UrlWiki->versionString, '1.10.0') >= 0) {
                $stats = $MwApi->getStats();
                $stats = $stats['query']['statistics'];
                $this->msg[] = print_r($stats, true);
                // custom_60 is the wUrl so we can display it with this group
                $values['custom_60'] = (string) $canonicalUrl;
                $values['custom_70'] = $recorded;
                foreach ($stats as $k => $v) {
                    if ( in_array($k, array_keys($this->statKeys)) ) {
                        $values["{$this->statKeys[$k]}"] = $v;
                    }
                }
            }
            // get extensions data
            if (version_compare($UrlWiki->versionString, '1.16.0') >= 0) {
                // we currently do not support Extensions in custom data
                // maybe store in a note? how searchable would they be?
            }
            
            // record data
            
            $params = array(
              'sequential' => 1,
              'id' => $cid, // update our record
            );
            $params += $values; // union arrays
            $result = $this->make_call('Contact', 'create', $params);
            if ($result['is_error']) {
                $this->msg[] = "Error trying to set custom data for <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$cid\">$cid</a>";
            } else {
                $msg = "Success! Updated custom data for <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$cid\">$cid</a>";
                $this->msg[] = $msg;
                echo $msg;
            }
        } else {
            $this->msg[] = __METHOD__ . " \$UrlWiki not is_wiki()"; 
            $this->msg[] = print($UrlWiki->__toString());
        }
        return true;
    }
    
    /**
     * For a contact, get all the custom data values, map those to labels
     * and return the 'latest' value of each field.
     * 
        $result = civicrm_api3('Contact', 'get', array(
            'sequential' => 1,
            'return' => "contact_id",
            'custom_40' => array('IS NOT NULL' => 1),
            'options' => array ('limit' => 500),
         ));

         // get a list of contact ids
        foreach ($result['values'] as $contact) {
            $cids[] = $contact['contact_id'];
        }
        $params = array(
            'sequential' => 1,
            'format' => 'array',
        );
        $rows = $CiviApi->customvalue_get($cids, $params);
     * 
     * rows looks like this
     * 
            [wUrl] => https://wiki.mozilla.org/Main_Page
            [mainpage] => Main Page
            [base] => https://wiki.mozilla.org/Main_Page
            [sitename] => MozillaWiki
            [logo] => https://wiki.mozilla.org/assets/logos/mozilla-wiki-logo-alt-135px.png
            [generator] => MediaWiki 1.23.9
            [phpversion] => 5.3.3
            [dbtype] => mysql
            [dbversion] => 5.6.17-log
            [writeapi] => 
            [timezone] => America/Los_Angeles
            [timeoffset] => -420
            [articlepath] => /$1
            [scriptpath] => 
            [server] => https://wiki.mozilla.org
            [servername] => 
            [wikiid] => wiki_mozilla_org
            [time] => 2015-07-15T20:15:27Z
            [maxuploadsize] => 104857600
            [favicon] => https://wiki.mozilla.org/assets/favicon.ico
            [wiki Url] => https://wiki.mozilla.org/Main_Page
            [pages] => 104538
            [articles] => 20382
            [edits] => 1208336
            [images] => 10024
            [users] => 331322
            [activeusers] => 496
            [admins] => 3
            [jobs] => 1059
            [recorded]
     * 
     * 
     * 
        foreach ($rows as $row) {
            $out .= "<tr>";
            $out .= "<td>{$row['wUrl']}</td>";
            $out .= "<td>{$row['mainpage']}</td>";
            $out .= "<td>{$row['base']}</td>";
            $out .= "<td>{$row['sitename']}</td>";
            $out .= "<td><img src=\"{$row['logo']}\" /></td>";
            $out .= "<td>{$row['generator']}</td>";
            $out .= "<td>{$row['phpversion']}</td>";
            $out .= "<td>{$row['dbtype']}</td>";
            $out .= "<td>{$row['dbversion']}</td>";
            $out .= "<td>{$row['timezone']}</td>";
            $out .= "<td><img src=\"{$row['favicon']}\" /></td>";
            $out .= "<td>{$row['pages']}</td>";
            $out .= "<td>{$row['articles']}</td>";
            $out .= "</tr>";
        }
        $out = "<table>$out</table>";
        print $out;
     * 
     * @param int $cid
     * @param int $params
     */
    function customvalue_get($cid, $params=array()) {
        $ret = null;
        if (is_array($cid)) {
            $return = null;
            foreach ($cid as $id) {
                $foo = $this->customvalue_get($id, $params);
                if (is_array($foo)) {
                    $return[] = $foo;
                } else {
                    $return .= $foo;
                }
            }
            return $return;
        }
        $defaults = array(
            'sequential' => 1,
            'entity_id' => $cid,
            'format' => html,
        );
        $params +=$defaults;
        $haystack = $this->genKeys + $this->statKeys;
        $addLabels = array (
            'wiki Url' => 'custom_60',
            'recorded' => 'custom_69',
            'recorded' => 'custom_70',
            );
        $haystack += $addLabels;
        $result = $this->make_call('CustomValue', 'get', $params);
        
        foreach ($result['values'] as $cvset) {
            $needle = 'custom_' . $cvset['id'];
            $label = array_search($needle,$haystack);
            switch ($params['format']) {
                case 'html':
                    $ret .= "<div><strong>$label</strong> {$cvset['latest']}</div>\n";
                    break;
                case 'array':
                    $ret[$label] = $cvset['latest'];
                    break;
                case 'txt':
                default:
                    $ret .= "$label , {$cvset['latest']}\n";
                    break;
            }
            
        }
        return $ret;
    }
   

    
    /**
     * When you get a note via the Civi API, you don't get back the subject by 
     * default, so if you want that field too, then specify it in  the params
     *   'return' => "subject, id, entity_table, entity_id, note",
     * 
     * If you want to get more than a single note and know the ids, then pass them
     * as an array 
     *   'id' => array('IN' => array(2316, 2334, 2357, 2359)),
     * 
     * If you are just looking for notes with/without a subject, you can qualify
     * using the various operators
     * '=', '<=', '>=', '>', '<', 'LIKE',"<>", "!=",  "NOT LIKE" , 'IN', 'NOT IN'
     * 'BETWEEN', 'NOT BETWEEN', 'IS NULL', 'IS NOT NULL'
     * 
     *   'subject' => array('IS NULL' => 1),
     * or 
     *   'subject' => array('IS NOT NULL' => 1)
     * Oddly, you can't do 'IS NULL' => 0 to look for subjects
     * 
     * @param array $params
     * @return array API response where 'values' is the key for records
     */
    function note_get($params) {
        $params += array(
            'sequential' => 1,
        );
        $result = $this->make_call('Note', 'get', $params);
        return $result;
    }
    
    /**
     * When you pass in $params like
     *   'entity_id' => 2000,
     *   'note' => "This is just a test",
     * 
     * You will be creating a note for contact record 2000
     * 
     * It won't have the 'created by' info, so if you want that also, add
     * 'contact_id' => 2,
     * to make the note 'by Greg Rundlett'
     * 
     * The default (contact_id not specified) is to attribute the note to the 
     * same entity that the note is being created for.  So if I create a note
     * for ACME Widgets, without specifying a contact_id, the changelog will 
     * show that a note was created for ACME Widgets __by__ ACME Widgets
     * 
     * The result of creating a note looks like this:
     * 
        {
            "is_error": 0,
            "undefined_fields": [
                "entity_table",
                "modified_date",
                "entity_id",
                "note",
                "privacy"
            ],
            "version": 3,
            "count": 1,
            "id": 3790,
            "values": [
                {
                    "id": "3790",
                    "entity_table": "civicrm_contact",
                    "entity_id": "2000",
                    "note": "This is just a test",
                    "contact_id": "2",
                    "modified_date": "20150718000000",
                    "subject": "",
                    "privacy": "0"
                }
            ]
        }
     * 
     * @param array $params
     * 
     * Deleting a note is straightforward.  You just need the note id
     * Instead of creating a new method, just call makeCall()
     * The result you get back will look like this:
        {
            "is_error": 0,
            "version": 3,
            "count": 1,
            "values": 1
        }
     * Apparently the ONLY operator that is valid is '=' when deleting a note
     * Therefore, you can't delete 'id'=> array('IN'=> array(6, 7, 8)),
     * You can only delete 'id' => 6
     * 
     * 
     *
     * More examples or tests: 
     * Create and Delete a note
     *
        $params = array(
            'entity_id' => 3976, // hostbaby.com
            'note' => 'Testing you fool',
        );
        $results = $CiviApi->note_create($params);

        print "<div>created note<br /><pre>"; print_r ($results); print'</pre></div>';

        $nid = $results['id'];

        $params = array(
          'id' => $nid,
        );
        $results = $CiviApi->make_call('Note', 'delete', $params);
        print "<div>deleted $nid<br /><pre>"; print_r ($results); print'</pre></div>';

     *
     *  Delete a list of notes
     * 
     * 
        $cids = array(3976, 3988, 3989, 3995, 3997, 3998, 4002, 4004);
        $count = 0;
        foreach ($cids as $contact_id) {
            $params = array('entity_id' => $contact_id);
            $results = $CiviApi->note_get($params);
            foreach ($results['values'] as $note) {
                $count++;
                $nid = $note['id'];
                $params2 = array('id'=> $nid);

                $result = $CiviApi->make_call('Note', 'delete', $params2);
                if ($result) {
                    echo "Deleted note $nid for contact $contact_id<br />";
                }
            }
        }
        echo "<br />deleted $count notes";
     * 
     */
    function note_create($params) {
        $required = array('entity_id', 'note');
        foreach ($required as $v) {
            if(!array_key_exists($v, $params)) {
                die("Unable to create a Note without $v");
            }
        }
        $params += array(
            'sequential' => 1,
            'contact_id' => 2, // this attributes all notes to Greg Rundlett
        );
        $result = $this->make_call('Note', 'create', $params);
        return $result;
    }
    
    
    /**
     * Groups
     * 
     * You can do a Group get with the API and you'll get a response with values 
     * like the following which tells you all about the groups in the system
        {
            "id": "11",
            "name": "Read_Restricted_No_API_11",
            "title": "Read Restricted",
            "description": "API present, but read permission denied",
            "is_active": "1",
            "visibility": "User and User Admin Only",
            "where_clause": " ( `civicrm_group_contact-11`.group_id IN ( 11 )  AND `civicrm_group_contact-11`.status IN (\"Added\") ) ",
            "select_tables": "a:8:{s:15:\"civicrm_contact\";i:1;s:15:\"civicrm_address\";i:1;s:15:\"civicrm_country\";i:1;s:13:\"civicrm_email\";i:1;s:13:\"civicrm_phone\";i:1;s:10:\"civicrm_im\";i:1;s:19:\"civicrm_worldregion\";i:1;s:26:\"`civicrm_group_contact-11`\";s:167:\" LEFT JOIN civicrm_group_contact `civicrm_group_contact-11` ON (contact_a.id = `civicrm_group_contact-11`.contact_id AND `civicrm_group_contact-11`.group_id IN ( 11 ))\";}",
            "where_tables": "a:2:{s:15:\"civicrm_contact\";i:1;s:26:\"`civicrm_group_contact-11`\";s:167:\" LEFT JOIN civicrm_group_contact `civicrm_group_contact-11` ON (contact_a.id = `civicrm_group_contact-11`.contact_id AND `civicrm_group_contact-11`.group_id IN ( 11 ))\";}",
            "group_type": [
                "2"
            ],
            "parents": "3",
            "is_hidden": "0",
            "is_reserved": "0",
            "created_id": "2",
            "modified_id": "2"
        },
     * 
     * To find out which groups a contact belongs to
     * you can issue a GroupContact get with the 'contact_id'
        $result = civicrm_api3('GroupContact', 'get', array(
          'sequential' => 1,
          'contact_id' => 4988,
        ));
     * which will tell you about all the groups that a contact belongs to such as
        {
            "is_error": 0,
            "version": 3,
            "count": 3,
            "values": [
                {
                    "id": "7167",
                    "group_id": "3",
                    "title": "MediaWiki",
                    "visibility": "Public Pages",
                    "is_hidden": "0",
                    "in_date": "2015-05-11 14:11:38",
                    "in_method": "Admin"
                },
                {
                    "id": "9548",
                    "group_id": "7",
                    "title": "tmpMediaWikiGrp",
                    "visibility": "User and User Admin Only",
                    "is_hidden": "0",
                    "in_date": "2015-06-17 15:36:26",
                    "in_method": "Admin"
                },
                {
                    "id": "8399",
                    "group_id": "4",
                    "title": "US",
                    "visibility": "User and User Admin Only",
                    "is_hidden": "0",
                    "in_date": "2015-05-11 14:11:38",
                    "in_method": "Admin"
                }
            ]
        }
     * 
     * When you do something wrong, you'll get 'is_error' = 1 and 'error_message'
        {
            "is_error": 1,
            "error_message": "group_id is a required field"
        }

     * To add a contact to a group, use 'create' on the 'GroupContact' entity.
     * Specify the contact_id and the group_id
        $result = civicrm_api3('GroupContact', 'create', array(
          'sequential' => 1,
          'group_id' => "mediawiki_3",
          'contact_id' => 2,
        ));
        {
            "is_error": 0,
            "version": 3,
            "count": 1,
            "values": 1,
            "total_count": 1,
            "added": 1,
            "not_added": 0
        }
     * when that contact is already a member of the group in question, they will
     * be part of the 'not_added' count:
        {
            "is_error": 0,
            "version": 3,
            "count": 1,
            "values": 1,
            "total_count": 1,
            "added": 0,
            "not_added": 1
        }
     */
    
    /**
     * $groups = array (
     * 11 => "Read Restricted",
     * 12 => "Not Wiki",
     * 13 => "No API",
     * 14 => "No email",
     * 15 => "BOM",
     * 16 => "Inbound",
     * 17 => "QA_17"
     * 18 => "resend_18",
     * );
     * 
     * According to the API Browser, you can use an array for contact_id but
     * it did not work in testing. 
     * contact_id parameter.  
       $result = civicrm_api3('GroupContact', 'create', array(
          'sequential' => 1,
          'group_id' => "resend_18",
          'contact_id' => array(2, 1),
        ));
     * 
     * @param array $params
     * @return type
     */
    function group_create($params = array()) {
        $required = array('contact_id', 'group_id');
        foreach ($required as $v) {
            if(!array_key_exists($v, $params)) {
                die("Unable to assign a Group without $v");
            }
        }
        $params += array(
            'sequential' => 1,
        );
        $result = $this->make_call('GroupContact', 'create', $params);
        if ($result 
                && $result['is_error'] === 0 
                && (int) $result['count'] === 1 
                && $result['added'] === 1
            ) {
           $this->msg[] = "set Group {$params['group_id']} on contact <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid={$params['contact_id']}\">{$params['contact_id']}</a>";
        } elseif ( (int) $result['count'] === 1 && $result['added'] === 0 ) {
            $this->msg[] = "contact {$params['contact_id']} was already a member of Group {$params['group_id']}";
        }
        return $result;
    }
    
    function add_UrlWiki( \eqt\wikireport\UrlWiki $UrlWiki ) {
        $this->UrlWiki = $UrlWiki;
    }
    
    
    /**
     * A function that will retrieve note records, and convert them to website
     * records.  As a by-product, it will also add the contact to certain groups
     * such as No API or Permission Denied when the site is a wiki but is not
     * accessible.
     * 
     * A single invocation would look like this
        $nid = 3802;
        $params=array('id'=>$nid);
        $CiviApi->note_to_website($params, true);
     * 
     * A fuller invocation would look like this
        $params = array(
            'subject' => array('IS NULL' => 1),
            'options' => array('limit' => 1000, 'offset'=>0),
        );
        $delete = true;
        $debug = false;
        $CiviApi->note_to_website($params, $delete, $debug);
     * 
     * @param array $params the options to select which notes to operate on
     * @param bool $delete whether to delete the notes on successful conversion
     */
    function note_to_website($params, $delete = false, $debug = false) {
        $results = $this->note_get($params);
        if ($results['is_error']) {
            die ("Could not complete operation due to error");
        }
        foreach ($results['values'] as $note) {
            $nid = $note['id'];
            $url = trim($note['note']);
            // skip notes that contain a space
            if (strstr($url, ' ')) {
                $msg = "Ignoring note $nid because it doesn't look like a URL";
                $this->msg[] = $msg;
                echo "$msg<br/>";
                continue;
            }
            $entity_id = $note['entity_id'];
            $UrlWiki = new \eqt\wikireport\UrlWiki($url);
            $isWiki = $UrlWiki->is_wiki();
            // $isWiki can be true, but without a wikiUrl because the Api is not readable
            if ($isWiki && $UrlWiki->wikiUrl) { // create the website record, delete the note
                $params = array(
                    'contact_id' => $entity_id,
                    'url' => $UrlWiki->wikiUrl,
                    'website_type_id' => 'wiki', // type 16
                );
                $results3 = $this->website_create($params);
                // dispose of the note
                if ($results3 && !$results3->is_error) {
                    $params = array('id'=>$nid);
                    if ($delete) {
                        $this->make_call('Note', 'delete', $params);
                    } else {
                        $this->msg[] = "Should have deleted Note $nid";
                    }
                    $msg = "<a href=\"{$UrlWiki->wikiUrl}\">{$UrlWiki->wikiUrl}</a> converted for contact <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$entity_id\">$entity_id</a>";
                    $this->msg[] = $msg;
                    echo "$msg<br />";
                }
            }
            
            // is_wiki will populate 'error' when determining if we can read the endpoint
            // set the group membership appropriately
            if ( isset($UrlWiki->error) && $UrlWiki->error > 0 )  {
                $this->group_create($params = array(
                        'group_id' => $UrlWiki->error,
                        'contact_id' => $entity_id
                    ));
                // also move the note "out of the way" by adding a subject
                
                $params = array(
                    'entity_id' => $entity_id,
                    'id' => $nid,
                    'subject' => "Problem converting note",
                    'note' => "$url"
                );
                $this->note_create($params);
                echo "Ignoring note $nid (Problem converting $url for <a href=\"https://equality-tech.com/civicrm/contact/view?reset=1&cid=$entity_id\">$entity_id</a>)<br />";
            }
            
            if ($debug) {
                $this->msg[] = $UrlWiki->__toString();
            }
        }
    }
    
}
