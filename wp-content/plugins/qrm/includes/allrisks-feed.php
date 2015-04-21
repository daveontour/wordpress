<?php
/**
 * JSON Feed of all comments.
 *
 */

header('Content-Type: application/json');


require plugin_dir_path ( __FILE__ ) . 'class-data.php';
// $creds = array();
// $creds['user_login'] = "Dave";
// $creds['user_password'] = "mouse11";
// $creds['remember'] = true;

// $user = wp_signon ($creds,true);
// if ( is_wp_error($user) )
// 	echo $user->get_error_message();
// echo "null(".json_encode($user, JSON_PRETTY_PRINT).");";

$risks = array();

while( have_posts()) : the_post();
	$risk = json_decode(get_post_meta($post->ID, "riskdata", true));
	
	$r = new SmallRisk();
	$r->description = $risk->description;
	$r->title = $risk->title;
	$r->id = $risk->id;
	$r->owner = $risk->owner->name;
	$r->manager = $risk->manager->name;
    $r->currentTolerance = $risk->currentTolerance;
    $r->inherentProb = $risk->inherentProb;
    $r->inherentImpact = $risk->inherentImpact;
    $r->treatedProb = $risk->treatedProb;
    $r->treatedImpact = $risk->treatedImpact;
    $r->treated = $risk->treated;
    
	array_push($risks, $r);
endwhile;

$data = new Data();
$data->data = $risks;
echo json_encode($data, JSON_PRETTY_PRINT);
