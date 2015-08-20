<?php
if ( !isset( $_POST['action'] ) ) {
	echo '0';
	exit;
}
//set up the properties common to both requests 
$obj = new stdClass();
$obj->slug = 'qrm.php';  
$obj->name = 'Quay Risk Manager';
$obj->plugin_name = 'qrm.php';
$obj->new_version = '4.1';
// the url for the plugin homepage
$obj->url = 'http://www.quaysystems.com.au';
//the download location for the plugin zip file (can be any internet host)
$obj->package = 'http://localhost/wordpress/qrm.zip';
switch ( $_POST['action'] ) {
case 'version':  
	echo serialize( $obj );
	break;  
case 'info':   
	$obj->requires = '4.3';  
	$obj->tested = '4.3';  
	$obj->downloaded = 12540;  
	$obj->last_updated = '2015-08-19';  
	$obj->sections = array(  
		'description' => 'The new version of the Quay Risk Manager plugin',  
		'another_section' => 'This is another section',  
		'changelog' => 'Some new features'  
	);
	$obj->download_link = $obj->package;  
	echo serialize($obj);  
case 'license':  
	echo serialize( $obj );  
	break;  
}  
