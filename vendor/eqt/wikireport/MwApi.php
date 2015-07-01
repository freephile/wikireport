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
class mwApi {
    /**
     * The API endpoint to communicate with
     * @var string
     */
    var $endpoint;
    
    var $apiQuery = '?action=query&meta=siteinfo&format=json&siprop=general|statistics';
    
    /**
     * the data returned from, or sent to the api
     * @var mixed
     */
    var $data;
    
    
    var $current_version = '1.26wmf8';
    var $current_url = 'https://en.wikipedia.org/';
    var $current_date = "2015/06/05";
    
    public function __construct($endpoint) {
        $this->endpoint = $endpoint;
    }
    
    function makeQuery($query='') {
        if ($query == '') {
            $query = $this->apiQuery;
        }
        $this->data = file_get_contents($this->endpoint . $query);
    }
    
    
    
    
}
