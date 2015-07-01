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

namespace eqt\wikireport;

/**
 * UrlWiki is for working with URLs that are MediaWiki related
 * 
 * Specifically, it can tell if a URL is generated by MediaWiki.
 * 
 * In so doing, it will also set the $apiUrl and $wikiUrl
 * 
 * usage example:
 * $UrlWiki = new \eqt\wikireport\UrlWiki($url);
 *
 * @author greg
 */
class UrlWiki extends \eqt\wikireport\Url {
    public $orginalUrl;
    
    public $url;
    
    public $wikiUrl;
    
    public $sitename; // from json data
    
    public $generator; // version string
    
    public $apiUrl;
    
    public $msg;
    
    public $parsedUrl;
    
    private $isWiki;
    
    public $data;
    

    /**
     * getter/setter for $this->isWiki
     * 
     * Using a tell-tale 'signature' of a MediaWiki generated page, we can tell
     * if a particular URI is generated by MediaWiki.
     * 
     * @return (bool) $this->isWiki
     */
    function isWiki () {
        $apiQuery = '?action=query&meta=siteinfo&format=json&siprop=general';
        
        if( isset($this->isWiki) ) {
            return $this->isWiki;
        }
            
        if ( substr($this->url, -7) == 'api.php' ) {
            $this->isWiki = true;
            $this->apiUrl = $this->url;
            $json = file_get_contents($this->apiUrl . $apiQuery);
        } else {
            // we only redirect if starting off without an endpoint (api.php)
            $this->find_redirect();
            
            $data = file_get_contents($this->url);
            
            switch ($data) {
                // short-circuit if we can't even connect to $this->url    
                case ($data === false) :
                    $this->isWiki = false;
                    $this->msg[] = __METHOD__ . ": Unable to connect to $this->url (bad URL?)";
                    return false;

                // As of v1.17 The API now has a Really Simple Discovery module, 
                // useful for publishing service information by the API. 
                // The RSD link looks like
                // <link rel="EditURI" type="application/rsd+xml" href="https://freephile.org/w/api.php?action=rsd" />
                // <link rel="EditURI" type="application/rsd+xml" href="//en.wikipedia.org/w/api.php?action=rsd" />
                // if ( preg_match( '#<link rel="EditURI" type="application/rsd\+xml" href="(.*)\?action=rsd"#', $data, $matches) ) {
                case ( preg_match('#EditURI.* href\="(.*)\?action\=rsd"#U', $data, $matches) && $matches[1] ):
                    $this->isWiki = true;
                    $this->apiUrl = $matches[1];
                    $this->prefix_scheme($this->parsedUrl['scheme'], $this->apiUrl);
                    $this->msg[] = __METHOD__ . "setting apiURL from EditURI/Really Simple Discovery";
                    break;
                
                // Find api.php from the RSS feed link (accurate)
                case ( preg_match('#link rel="alternate" type="application/rss[^>]* href="(.*)\?title=#U', $data, $matches) && $matches[1] ):
                    $this->isWiki = true;
                    $this->apiUrl = str_ireplace('index.php', 'api.php', $matches[1]);
                    $this->msg[] = __METHOD__ . "setting apiURL from RSS link";
                    break;

                // older versions of MediaWiki don't have a EditURI link, but 
                // do have the "generator" link so, if we detect that, then we'll
                // <meta name="generator" content="MediaWiki 1.15.1" />
                // <meta name="generator" content="MediaWiki 1.16.5" />
                case ( preg_match('#meta name="generator"[^>]* content="MediaWiki#U', $data, $matches) && $matches[1] ):
                    $this->isWiki = true;
                    $apiUrl = $this->parsedUrl['scheme'] . '://';
                    $apiUrl .= $this->parsedUrl['host'];
                    $apiUrl .= isset($this->parsedUrl['port'])? ':' . $this->parsedUrl['port'] : '';
                    // if the path contains 'index.php', grab the portion before that; eg. wiki/index.php.
                    if (strstr($this->parsedUrl['path'], 'index.php')) { 
                        $apiUrl .= substr($this->parsedUrl['path'], 0, 
                                strpos($this->parsedUrl['path'], 'index.php'));
                    } elseif (strstr($this->parsedUrl['path'], 'wiki')) { 
                    // clean URLs with 'wiki' (no index.php), grab the portion before that (probably just a slash)
                        $apiUrl .= substr($this->parsedUrl['path'], 0, 
                                strpos($this->parsedUrl['path'], 'wiki'));
                        $apiUrl .= 'wiki/'; // add back the 'wiki' portion
                    }
                    $apiUrl .= "api.php";
                    $this->apiUrl = $apiUrl;
                    $this->msg[] = __METHOD__ . "setting apiURL from generator";
                    break;

                default:
                    $this->isWiki = false;
                    $this->apiUrl = $this->url;
                    $this->msg[] = __METHOD__ . ": No wiki found at $this->apiUrl (given $this->url)";
                    return false;
            }
            $json = file_get_contents($this->apiUrl . $apiQuery);
        }
        
        $this->data = json_decode($json, true);
        $this->wikiUrl = $this->data['query']['general']['base'];
        $this->sitename = $this->data['query']['general']['sitename'];
        $this->generator = $this->data['query']['general']['generator'];
        $this->msg[] = __METHOD__ . ": wiki found at $this->wikiUrl via $this->apiUrl (starting with $this->orginalUrl)";

        return $this->isWiki;
    }
    

}

