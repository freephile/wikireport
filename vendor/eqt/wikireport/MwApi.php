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
 * Description of mwApi
 *
 * @author greg
 */
class MwApi {
    /**
     * The API endpoint to communicate with
     * @var string
     */
    var $endpoint;
    // versions before 1.10.0 will not support siprop |statistics 
    var $apiQuery = '?action=query&meta=siteinfo&format=json&siprop=general';
    
    /**
     * the decoded (array) data returned from, or sent to the api
     * json is only used over the wire
     * @var array $data
     */
    var $data;
        
    /**
     * Instead of a string like "MediaWiki 1.26.wmf"
     * We'll use just the "1.26.wmf" as a version "number" to compare with
     * @var string
     */
    var $versionString;
    
    var $articlepath;
    var $base;
    var $case;
    var $dbtype;
    var $dbversion;
    var $externalimages;
    var $fallback;
    var $fallback8bitEncoding;
    var $favicon;
    var $generator;
    var $git_branch; // problematic due to hyphen?
    var $git_hash;
    var $imagelimits;
    var $lang;
    var $langconversion;
    var $legaltitlechars;
    var $linkprefix;
    var $linkprefixcharset;
    var $linktrail;
    var $logo;
    var $mainpage;
    var $maxuploadsize;
    var $phpsapi;
    var $phpversion;
    var $script;
    var $scriptpath;
    var $server;
    var $servername;
    var $sitename;
    var $thumblimits;
    var $time;
    var $timeoffset;
    var $timezone;
    var $titleconversion;
    var $variantarticlepath;
    var $wikiid;
    var $writeapi;

    var $reference_version = '1.26wmf13';
    var $reference_url = 'https://en.wikipedia.org/';
    var $reference_date = "2015/07/15";
    
    /**
     * 
     * @param string $endpoint the API Url for a given wiki
     */
    public function __construct($endpoint) {
        if (is_null($endpoint) || empty($endpoint) ) {
            die( __METHOD__ . " died because \$endpoint is null or empty");
        }
        $this->endpoint = $endpoint;
        $this->makeQuery();
        //populate all our variables, not using magic methods
        foreach (get_object_vars($this) as $prop => $val) {
            if (array_key_exists($prop, $this->data['query']['general'])) {
                $this->$prop = $this->data['query']['general'][$prop];
            }
        }
        $this->versionString = trim(str_ireplace("MediaWiki", '', $this->generator));
    }
    /**
     * We could redo this function so that it returns the query response
     * instead of populating a neverending series of parameters.
     * @param type $query
     */
    function makeQuery($query='') {
        if ($query == '') {
            $query = $this->apiQuery;
        }
        $data = file_get_contents($this->endpoint . $query);
        /*
        if (strstr($data, '\\/')) { // if we see http:\/\/foo.example.com
            $data = stripslashes($data);
        }
         * 
         */
        UrlWiki::removeBOM($data);
        $data = json_decode($data, true);
        $this->data = $data;
        return $data;
    }
    
    function getStats () {
        $query = '?action=query&meta=siteinfo&format=json&siprop=statistics';
        $data = file_get_contents($this->endpoint . $query);
        UrlWiki::removeBOM($data);
        $data = json_decode($data, true);
        return $data;
    }
    
    function getGeneral () {
        $query = '?action=query&meta=siteinfo&format=json&siprop=general';
        $data = file_get_contents($this->endpoint . $query);
        UrlWiki::removeBOM($data);
        $data = json_decode($data, true);
        return $data;
    }
    
    function getExtensions () {
        $query = '?action=query&meta=siteinfo&format=json&siprop=extensions';
        $data = file_get_contents($this->endpoint . $query);
        UrlWiki::removeBOM($data);
        $data = json_decode($data, true);
        $this->data['query']['extensions'] = $data['query']['extensions'];
        return $data;
    }
    
    /**
     * Find out how recent this wiki is compared to MediaWiki releases
     * 
     * The freshness values correspond to class names in Bootstrap
     * so we can re-use in UI for messaging
     */
    function getFreshness ($v = null) {
        $fresh='danger';
        if ( is_null($v) ) {
            $v = $this->versionString;
        }
        switch ($v) {
            case ( version_compare($v, '1.26.0') >= 0 ) :
                $fresh='success';
                break;
            case ( version_compare($v, '1.25.0') >= 0 ) :
                $fresh='info';
                break;
            case ( version_compare($v, '1.24.0') >= 0 ) :
                $fresh='warning';
                break;
            case ( version_compare($v, '1.23.0') >= 0 ) :
                $fresh='danger';
                break;

            default:
                $fresh='danger';
        }
        return $fresh;
    }
    
    
}
