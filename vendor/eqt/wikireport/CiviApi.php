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
        $this->version = $result['version'];
        $this->count = $result['count'];
        $this->id = $result['id'];
        $this->values = $result['values'];
        $this->msg[] = __METHOD__ . " called on $entity with action $action, returning $this->id.";
        return $result;
    }
}
