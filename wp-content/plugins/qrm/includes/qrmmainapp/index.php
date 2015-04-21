<?php 
// The page would never find all the relatively referenced files 
// so a redirect is used so that all the relative requests
// have got the right base URL 

$location = plugin_dir_url ( __FILE__ )."index.html";
wp_redirect($location, 303);
exit;

