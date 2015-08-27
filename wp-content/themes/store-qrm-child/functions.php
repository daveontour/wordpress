<?php
add_action ( "wp_ajax_getUpdateInfo", "getQRMUpdateInfo" );
add_action ( "wp_ajax_nopriv_getUpdateInfo", "getQRMUpdateInfo" );
function getQRMUpdateInfo() {
	
	// set up the properties common to both requests
	$obj = new stdClass ();
	$obj->slug = 'qrm.php';
	$obj->name = 'Quay Risk Manager';
	$obj->plugin_name = 'qrm.php';
	$obj->new_version = '2.6.0';
	// the url for the plugin homepage
	$obj->url = 'http://www.quaysystems.com.au';
	// the download location for the plugin zip file (can be any internet host)
	$obj->package = 'https://s3-ap-southeast-2.amazonaws.com/qrm-wordpress-plugin/qrm-2.6.0.zip';
	
	switch ($_GET ['fn']) {
		case 'version' :
			echo serialize ( $obj );
			wp_die ();
			break;
		case 'info' :
			$obj->requires = '4.0';
			$obj->tested = '4.3';
			$obj->downloaded = 12540;
			$obj->last_updated = '2012-08-26';
			$obj->sections = array (
					'description' => 'The new version of the Auto-Update plugin',
					'another_section' => 'This is another section',
					'changelog' => 'Some new features' 
			);
			$obj->download_link = $obj->package;
			echo serialize ( $obj );
			wp_die ();
			break;
		case 'license' :
			echo serialize ( $obj );
			wp_die ();
			break;
	}
}

add_action ( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
	wp_enqueue_style ( 'parent-style', get_template_directory_uri () . '/style.css' );
}
add_action ( 'woocommerce_add_order_item_meta', 'qrm_save_item_order_key_itemmeta', 10, 3 );
function qrm_save_item_order_key_itemmeta($item_id, $values, $cart_item_key) {
	$item_sku = get_post_meta ( $values ['product_id'], '_sku', true );
	
	if ($item_sku == "RPS001") {
		wc_add_order_item_meta ( $item_id, 'Site Key', getSiteKeyString (), false );
		wc_add_order_item_meta ( $item_id, 'Site ID', getSiteIDString (), false );
	}
}
function qrm_woocommerce_payment_complete($order_id) {
	$order = wc_get_order ( $order_id );
	
	$items = $order->get_items ();
	
	foreach ( $items as $item ) {
		if ($item ["item_meta"] ["Site Key"]) {
			$response = wp_remote_post ( "http://localhost:8080/order?action=neworder", array (
					'body' => array (
							'orderID' => $order_id,
							'orderEmail' => $order->billing_email,
							'siteID' => $item ["item_meta"] ["Site ID"] [0],
							'siteKey' => $item ["item_meta"] ["Site Key"] [0] 
					) 
			) );
			if ($response ["body"] == "ok") {
				$order->update_status ( 'completed' );
			}
		}
	}
}
add_action ( 'woocommerce_payment_complete', 'qrm_woocommerce_payment_complete' );
function getSiteKeyString($length = 8) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+';
	$string = '';
	
	for($i = 0; $i < $length; $i ++) {
		$string .= $characters [mt_rand ( 0, strlen ( $characters ) - 1 )];
	}
	
	return $string;
}
function getSiteIDString($length = 8) {
	$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$string = '';
	
	for($i = 0; $i < $length; $i ++) {
		$string .= $characters [mt_rand ( 0, strlen ( $characters ) - 1 )];
	}
	
	return $string;
}