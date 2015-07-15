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
 * Manipulate common URLs like "example.com" into actual complete URIs like
 * https://www.example.com/index.php
 * 
 * Here is an example of usage:
 * 
 $v = "example.org";
 $obj = new \eqt\wikireport\Url($v);
 $obj->validate_url();
 $obj->find_redirect();
 echo $obj->url;
 echo $obj;
 echo $obj->__toString();
 * 
 */
class Url {
    /**
     * WARNING this data is unsafe (not escaped or sanitized)
     * @var string The url that we started with
     * e.g. 'example.com' when invoked with 'new Url("example.com")'
     */
    public $orginalUrl;
    
    /**
     * Because we don't trust user input, $url is sanitized in the constructor.
     * @var string The current url we are working on
     */
    public $url;
    
    /**
     *
     * @var array A series of messages that the class can emit to a log, STDOUT
     */
    public $msg;
    
    /**
     *
     * @var array The results of PHP's parse_url($this->url)
     */
    public $parsedUrl;
    
    /**
     * Setup our messages array, remember the original value we were given,
     * sanitize the working value, and parse it.
     * @param string $url
     */
    function __construct($url) {
        $this->msg = array();
        $this->orginalUrl = $this->url = $url;
        $this->sanitize_url();
        $this->url = $this->prefix_scheme(); // web form will have scheme
        $this->parsedUrl = parse_url($this->url); // will be used by find-redirect, and re-called
        $this->find_redirect();
    }
    
    /**
     * curiously, we have to actually call this method by name because when 
     * I try to just echo $url (the object); I get the output of the webpage??
     */
    function __toString() {
        echo nl2br(implode(PHP_EOL , $this->msg));
    }
    
    /**
     * Glue all the pieces we know about url back together again.
     * @return string 
     */
    function unparse_url() {
        $scheme= isset($this->parsedUrl['scheme']) ? $this->parsedUrl['scheme'] . '://' : '';
        $host  = isset($this->parsedUrl['host'])   ? $this->parsedUrl['host'] : '';
        $port  = isset($this->parsedUrl['port'])   ? ':' . $this->parsedUrl['port'] : '';
        $user  = isset($this->parsedUrl['user'])   ? $this->parsedUrl['user'] . ':' : '';
        $pass  = isset($this->parsedUrl['pass'])   ? $this->parsedUrl['pass'] : '';
        $pass  = ($user || $pass) ? "$pass@" : ''; // not sure if you can have just user, but if so, then this will still append '@'
        $path  = isset($this->parsedUrl['path'])   ? $this->parsedUrl['path'] : '';
        $query = isset($this->parsedUrl['query'])  ? '?' . $this->parsedUrl['query'] : '';
        $frag  = isset($this->parsedUrl['fragment']) ? '#' . $this->parsedUrl['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$frag";
    }

    /**
     * A non-destructive convenience function to make a hyperlink from url
     * @return string an HTML hyplerlinked version of $this->url
     */
    function html_linkify() {
        if (is_string($this->url) && strlen($this->url)) {
            if (parse_url($this->url, PHP_URL_SCHEME)) {
                $this->msg[] = __METHOD__ . ": used to create a link";
                $v = "<a href=\"$this->url\">$this->url</a>";
                return $v;
            }
        } else {
            $this->msg[] = __METHOD__ . ": unable to create a hyperlink from $this->url";
            return false;            
        }
    }
    
    /**
     * Turn a 'common' url like example.com into an actual resolvable URI that
     * has a scheme, like http://example.com.  It's a fixer-upper that also 
     * handles 'bare' or protocol relative URLs such as //example.com
     * 
     * We check first to see if url already begins with the scheme.
     * 
     * We don't set any flag, so you can also use this to change
     * the scheme from 'http' to 'https' if you cut out the existing scheme first.
     * @param string $scheme
     * @return boolean
     */
    function prefix_scheme($scheme = 'http://', &$url='') {
        if ($url == '') {
            $url = $this->url;
        }
        if ( substr($url, 0, strlen($scheme)) == $scheme ) {
            $this->msg[] = __METHOD__ . ": $url already begins with $scheme";
            return $url;
        }
        if ( substr($url, 0, 2) == '//' ) {
            $this->mgs[] = __METHOD__ . ": $url used protocol relative '//', now using full $scheme";
            $url = $scheme . "://". substr($url, 2);
            return $url;
        }
        if (parse_url($url, PHP_URL_SCHEME)) {
            return $url;
        }
        $this->msg[] = __METHOD__ . ": $url prefixed with $scheme";
        $url = $scheme . $url;
        return $url;
    }
    
    /**
     * get_headers is helpful in that it recursively follows redirects to find
     * the ultimate destination of a given url.
     * 
     * By setting the optional second parameter to get_headers(), we will find
     * the redirect in the Location array (or string).
     * 
     * @return boolean true when we followed a redirect to arrive at a final url
     */
    function find_redirect() {
        $headers = get_headers($this->url, 1);
        if (!stristr($headers[0], '200')) {
            if ( isset($headers['Location']) && !empty($headers['Location']) ) {
                // pickup the new target
                $this->url = is_array($headers['Location'])? array_pop($headers['Location']) : $headers['Location'];
                // for 302 (found) protocol relative redirects, add only the scheme
                if ( substr($this->url, 0, 2) == '//' ) {
                    $this->url = $this->parsedUrl['scheme'] . '://' . 
                                 $this->url;
                }
                // for 302 (found) site relative redirects, add back the host
                if ( substr($this->url, 0, 1) == '/' ) {
                    $this->url = $this->parsedUrl['scheme'] . '://' . 
                                 $this->parsedUrl['host'] . 
                                 $this->url;
                }
                //$this->msg[] = print_r($headers, 1);
                $this->parsedUrl = parse_url($this->url);
                $this->msg[] = __METHOD__ . ": $this->orginalUrl redirected to $this->url";
                return true;
            }
        }
        return false;
    }
    
    function make_absolute( &$url ) {
        if ( substr($url, 0, 4) == 'http' || stristr($url, $this->parsedUrl['host']) ) {
            return true;
        }
        $url = $this->parsedUrl['scheme'] . '://' . 
               $this->parsedUrl['host'] . $url;
        return true;
    }

    /**
     * Using filter_var(), we make sure that our url is not dangerous (exploit)
     * during the object initialization.
     * 
     * filter_var() is known to be useless for working with non-ascii characters
     * but that's OK because we're only dealing with U.S. domains.
     * 
     * We could use https://php.net/manual/en/function.filter-input-array.php 
     * to setup sanitization for the whole form, but right now we're only going
     * to be working with a url value and an email value for user data
     * 
     * @return bool true when sanitized
     */
    function sanitize_url() {
        if ( filter_var($this->url, FILTER_SANITIZE_URL) ) { // clean
            $this->msg[] = __METHOD__ . ": $this->url was sanitized";
            return true;
        } else {
            $this->msg[] = __METHOD__ . ": $this->url did not need to be sanitized";
            return false;
        }
    }
    
    /**
     * Will check to see if url conforms to specification of a valid URI
     * 
     * @return boolean true when the url validates as conforming (NOT a resolver)
     */
    function validate_url() {
        if ( filter_var($this->url, FILTER_VALIDATE_URL) ) { // valid
            $this->msg[] = __METHOD__ . ": $this->url is valid";
            $this->parsedUrl = parse_url($this->url);
        } else {
            return false;
        }
    }
    
    /**
     * This function will make a request to url to determine if the url actually
     * exists and answers to the request.
     * 
     * renamed from is_valid_url()
     * @param type $url
     * @return boolean
     */
    function url_resolves($url = "") {
        if ($url == "") {
            $url=$this->url;
        }
        $url = @parse_url($url);
        if (!$url) {
            return false;
        }
        $url = array_map('trim', $url);
        $url['port'] = (!isset($url['port'])) ? 80 : (int) $url['port'];
        $path = (isset($url['path'])) ? $url['path'] : '';

        if ($path == '') {
            $path = '/';
        }
        $path .= ( isset($url['query']) ) ? "?$url[query]" : '';
        if (isset($url['host']) AND $url['host'] != gethostbyname($url['host'])) {
            if (PHP_VERSION >= 5) {
                $headers = get_headers("$url[scheme]://$url[host]:$url[port]$path");
            } else {
                $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);
                if (!$fp) {
                    return false;
                }
                fputs($fp, "HEAD $path HTTP/1.1\r\nHost: $url[host]\r\n\r\n");
                $headers = fread($fp, 128);
                fclose($fp);
            }
            $headers = ( is_array($headers) ) ? implode("\n", $headers) : $headers;
            return (bool) preg_match('#^HTTP/.*\s+[(200|301|302)]+\s#i', $headers);
        }
        return false;
    }
}