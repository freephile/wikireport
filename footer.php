<?php

/* 
 * Copyright (C) 2015 greg
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
?>
<style>
.rotator {
  -webkit-transform: rotate(180deg);  /* Chrome, Opera 15+, Safari 3.1+ */
      -ms-transform: rotate(180deg);  /* IE 9 */
          transform: rotate(180deg);  /* Firefox 16+, IE 10+, Opera */
          position:inherit;
          float:left;
}

</style>
<div id="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <p class="muted credit"><a href="https://github.com/freephile/wikireport" class="rotator">&copy; </a> 2015 <a href="https://eQuality-Tech.com">eQuality Technology</a> and <a href="https://linkedin.com/in/freephile/">Greg Rundlett</a></p>
            </div>
        </div>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="https://freephile.org/wikireport/vendor/jquery-number/jquery.number.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
<script>
   // format any class="number" element
   // we're using a jQuery plugin, but could use regular JavaScript
   // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/NumberFormat/format
   $(".number").number(true, 0);

    // onSubmit handler to fix up the URL in case someone pastes a full URL
    $('#wr').submit(function(e) {
       $('#url').val( $('#url').val().replace(/^(http:\/\/)https?:\/\//g, "$1", '') );               
    });

   // add Google Analytics
   // UA-39339059-2

   (function (i, s, o, g, r, a, m) {
       i['GoogleAnalyticsObject'] = r;
       i[r] = i[r] || function () {
           (i[r].q = i[r].q || []).push(arguments)
       }, i[r].l = 1 * new Date();
       a = s.createElement(o),
               m = s.getElementsByTagName(o)[0];
       a.async = 1;
       a.src = g;
       m.parentNode.insertBefore(a, m)
   })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

   ga('create', 'UA-39339059-2', 'auto');
   ga('send', 'pageview');

</script>