                                                                                                                       <?php
/**
 * JSON Feed of all quotes.
 *
 */

header('Content-Type: application/json');

$creds = array();
$creds['user_login'] = "Dave";
$creds['user_password'] = "mouse11";
$creds['remember'] = true;

$user = wp_signon ($creds,true);
if ( is_wp_error($user) )
		echo $user->get_error_message();
echo "null(".json_encode($user, JSON_PRETTY_PRINT).");";

$quotes = array();

while( have_posts()) : the_post();
	array_push($quotes, get_quay_quote($post));
endwhile;

echo "null(".json_encode($quotes, JSON_PRETTY_PRINT).");";