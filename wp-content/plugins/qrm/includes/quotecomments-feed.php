<?php
/**
 * JSON Feed of all comments.
 *
 */

header('Content-Type: application/json');

$comm = array();

get_currentuserinfo();
if (is_user_logged_in()){
	echo "Logged In";
	$current_user = wp_get_current_user();
	echo json_encode($current_user, JSON_PRETTY_PRINT);
} else {
	echo "Not Logged in";
}




foreach(get_comments( array('post_type' => 'quote') ) as &$c){

	$d = get_metadata('post', $c->ID, 'quote_date', true);
	$di = substr($d,3,2) . substr($d,0,2);
	
	array_push($comm, array(
			"cid" => $c->comment_ID,
		    "c" => $c->comment_content,
		    "qid" => $c->ID,
		    "di" => $di,
		    "q" => $c->post_content,
		    "dn" => $c->comment_author,
		    "e" => $c->comment_author_email
	));
}
echo "null(".json_encode($comm, JSON_PRETTY_PRINT).");";