<?php

/*
 * Copyright (C) 2015 Gregory Rundlett
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
 * Profiler can tell you how much clock time your script takes as well as the 
 * actual CPU time.  It works using two separate methods.  One is the 
 * getrusage() interface to the system call by the same name to determine
 * both user CPU and kernel CPU time.
 * 
 * The other method uses the 'traditional' microtime() to calculate wall clock time.
 * 
 * Usage:
 * 
 * // first create the object
 * $Profiler = new \eqt\wikireport\Profiler();
 * 
 * // Get CPU time
 * $Profiler->start();
 * // some code
 * $Profiler->end();
 * print $Profiler;
 * or $log .= $Profiler->__toString();
 * 
 * // Get clock time
 * $Profiler->stopwatch();
 * // some code
 * $Profiler->stopwatch();
 * // you have control over how you print out the results:
 * print $Profiler->getElapsedTime();
 * echo $Profiler->getElapsedTime("Page generated in ", " seconds (like an eternity)", false);
 * echo $Profiler->getElapsedTime("You just lost ", " seconds of your life waiting for this page");
 * or profiling a particular point in your application: 
    function __construct($url) {
        parent::__construct($url);
        $timer = new \eqt\wikireport\Profiler();
        $timer->stopwatch();
        $this->find_endpoint();
        $timer->stopwatch();
        $this->msg[] = $timer->getElapsedTime( __METHOD__ . " took ", "on line ". __LINE__, false);
    }
 * 
 * @author Gregory Rundlett
 * 
 */
class Profiler {
     private $startTime;
     private $endTime;
     private $microtime1 = null;
     private $microtime2;
     
     /**
      * for measuring CPU cycles
      */
     public function start(){
         $this->startTime = getrusage();
     }

     /**
      * for measuring CPU cycles
      */
     public function end(){
         $this->endTime = getrusage();
     }

     /**
      * for measuring CPU cycles
      */
     private function runTime($ru, $rus, $index) {
         return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
     }    

     /**
      * The first time you click the stopwatch anywhere in your script, you're 
      * "starting" the stopwatch.  We're using the beginning of script execution
      * 
      * The second, and subsequent times you click the stopwatch, you're setting
      * the second time so that you can check TOTAL elapsed time between 1 and 2
      * 
      */
     public function stopwatch() {
         if (is_null($this->microtime1)) {
             $this->microtime1 = $_SERVER["REQUEST_TIME_FLOAT"]; // as of php 5.4.0 , FILTER_SANITIZE_NUMBER_FLOAT
         } else {
             $this->microtime2 = microtime(true);
         }
         
     }
     
     /**
      * Tells you how much wall clock time has elapsed since the start of the script
      * until the last time the stopwatch was clicked.
      * 
      * Something like 1437071617.9433 - 1437071613.244 = 4.6993000507355 sec
      * 
      * @return string message about execution time.
      */
     public function getElapsedTime($prefix="page delivered in ", $postfix=" sec.", $round=true) {
         $return = $prefix;
         $return .= ($round)? round(($this->microtime2 - $this->microtime1), 2): 
                              ($this->microtime2 - $this->microtime1);
         $return .= $postfix;
         return $return;
     }

     /**
      * Tells you how much CPU time has elapsed and also time spent in System calls
      * 
      * @return string message about execution time.
      */
     public function __toString(){
         return  $this->runTime($this->endTime, $this->startTime, "utime") .
        " ms CPU time\n+" . $this->runTime($this->endTime, $this->startTime, "stime") .
        " ms system\n";
     }
 
}
