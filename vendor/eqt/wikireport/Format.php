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
 * Description of Format
 *
 * @author greg
 */
class Format {
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

    function pre_print ($v) {
        echo "<pre>\n";
        echo $v;
        echo "</pre>\n";
    }
    
    function implode ($v, $glue = ", ") {
        $return = "";
        if ( is_string($v) ) {
            $return .=  "$v{$glue}";
        } elseif ( is_array($v) ) {
            foreach ($v as $key => $value) {
                if ( is_array ($value) ) {
                    $this->implode ($value);
                } else {
                   $return .= "$value{$glue}";   
                }
            }
        }
        $return = substr( $return, 0, 0-strlen($glue) );
        return $return;
    }
}
