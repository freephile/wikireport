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
 * Handling form submissions
 *
 */
class Form {
    
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

    function objectToArray ($object) {
      if(!is_object($object) && !is_array($object))
          return $object;

      return array_map('objectToArray', (array) $object);
    }

}
