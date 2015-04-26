<?php 
/*** 
 * Plugin Name: Quay Systems Risk Manager * Description: Quay Risk Manager * Version: The plugin's version number. Example: 1.0.0
 * Author: Dave Burton
 * License: A short license name. Example: GPL2
 */

// Register Custom Post Type
if (! defined ( 'WPINC' )) {
	die ();
}

// Required files for registering the post type and taxonomies.
require plugin_dir_path ( __FILE__ ) .'includes/class-post-type.php';
require plugin_dir_path ( __FILE__ ) .'includes/class-risk-post-type-registration.php';
require plugin_dir_path ( __FILE__ ) .'includes/class-post-type-metaboxes.php';

require plugin_dir_path ( __FILE__ ) .'includes/class-custom-feed.php';
require plugin_dir_path ( __FILE__ ) .'includes/class-risk.php';

require plugin_dir_path ( __FILE__ ) . 'includes/class-data.php';


//Initiallise the page templater (allows us to provide page templates in plugin)
require plugin_dir_path ( __FILE__ ) .'includes/templater.php';


// Instantiate registration class, so we can add it as a dependency to main plugin class.
$post_type_registrations = new Risk_Post_Type_Registrations ();

// Instantiate main plugin file, so activation callback does not need to be static.
$post_type = new Risk_Post_Type ( $post_type_registrations );

// Register callback that is fired when the plugin is activated.
register_activation_hook ( __FILE__, array (
		$post_type,
		'activate' 
) );

// Initialize registrations for post-activation requests.
$post_type_registrations->init ();

// Initialize metaboxes
$post_type_metaboxes = new Risk_Post_Type_Metaboxes ();
$post_type_metaboxes->init ();

wp_enqueue_style ('jquery-style','http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

add_action ('admin_init','my_plugin_admin_init' );
function my_plugin_admin_init() {
	wp_enqueue_script ('jquery-ui-datepicker' );
	
}

function add_qrm_roles_on_plugin_activation() {
	add_role( 'risk_admin', 'Risk Administrator', array( 'read' => true ) );
	add_role( 'risk_owner', 'Risk Owner', array( 'read' => true ) );
	add_role( 'risk_manager', 'Risk Manager', array( 'read' => true ) );
	add_role( 'risk_user', 'Risk User', array( 'read' => true ) );
}
register_activation_hook( __FILE__, 'add_qrm_roles_on_plugin_activation' );


add_action ('parse_request','my_plugin_parse_request' );
function my_plugin_parse_request($wp) {
	if (array_key_exists ('qrmfn', $wp->query_vars )) {
		
		// Overall QRM security check. User needs to be logged in to Wordpress, and have and approriate role
		if ( !is_user_logged_in() ){
			http_response_code(400);
			echo '{"error":true,"msg":"Not Logged In"}';
			exit;
		}
		
		$roles = wp_get_current_user()->roles;
		
		if ( !in_array("risk_owner", $roles) 
				&& !in_array("risk_manager", $roles) 
				&& !in_array("risk_user", $roles)
				&& !in_array("risk_admin", $roles)){
			http_response_code(400);
			echo '{"error":true,"msg":"Not Authorised"}';
			exit;
		}
		
		// Pass to the specific function
		switch ($wp->query_vars ['qrmfn']) {
			
			case "saveRisk" :
				saveRisk();
				break;
			case "getRisk" :
   				getRisk();
				break;
			case "getAllRisks" :
				getAllRisks();
				break;
			case "addComment" :
				addComments();
				break;				
			case "uploadFile" :
				uploadFile();
				break;
			case "getRiskAttachments":
				getRiskAttachments();
				break;
			default :
				wp_die ( $wp->query_vars ['qrmfn'] );
		}
	}
}

function uploadFile(){
	
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	}
	
	$uploadedfile = $_FILES['file'];
	$upload_overrides = array( 'test_form' => false );
	
	$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
	
	if ( $movefile && !isset( $movefile['error'] ) ) {
		
		// $filename should be the path to a file in the upload directory.
		$filename = $movefile['file'];
		
		// The ID of the post this attachment is for.
		$parent_post_id = $_POST["riskID"];
		
		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $filename ), null );
		
		// Get the path to the upload directory.
		$wp_upload_dir = wp_upload_dir();
		
		// Prepare an array of post data for the attachment.
		$attachment = array(
				'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit'
		);
		
		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );
		
		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		
		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );
	} else {
		/**
		 * Error generated by _wp_handle_upload()
		 * @see _wp_handle_upload() in wp-admin/includes/file.php
		 */
		echo $movefile['error'];
	}
	
	exit;
	
}

function addComments(){
	
	$comment = json_decode(file_get_contents("php://input"));	
	$time = current_time('mysql');
	
	global $user_identity, $user_email,$user_ID, $current_user;
	get_currentuserinfo();
	
	$data = array(
			'comment_post_ID' => $comment->riskID,
 			'comment_author' => $current_user->display_name,
 			'comment_author_email' => $current_user->user_email,
			'comment_content' => $comment->comment,
			'comment_type' => '',
			'comment_parent' => 0,
			'user_id' => $user_ID,
			'comment_date' => $time,
			'comment_approved' => 1,
	);
	
	wp_insert_comment($data);
	
	$emptyRisk = new Risk();
	$emptyRisk->comments = get_comments(array('post_id' => $comment->riskID));
	echo json_encode($emptyRisk, JSON_PRETTY_PRINT);	exit;	
}
function getRiskAttachments (){
	$riskID = json_decode(file_get_contents("php://input"));
	$attachments = get_children(array("post_parent"=>$riskID, "post_type"=>"attachment"));
	echo json_encode($attachments, JSON_PRETTY_PRINT);
	exit;
}
function getRisk (){
	$riskID = json_decode(file_get_contents("php://input"));
	$risk = json_decode(get_post_meta($riskID, "riskdata", true));
	$risk->comments = get_comments(array('post_id' => $riskID));
	$risk->attachments = get_children(array("post_parent"=>$riskID, "post_type"=>"attachment"));
	echo json_encode($risk, JSON_PRETTY_PRINT);
	exit;
}
function getAllRisks(){
	
	global $post;
	$args = array(
			'post_type' => 'risk'
	);
	
	$the_query = new WP_Query($args);
	$risks = array();
	
	while( $the_query->have_posts()) : $the_query->the_post();
	
	$risk = json_decode(get_post_meta($post->ID, "riskdata", true));
	
	//echo var_dump($post);
	
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
	
	array_push($risks, $risk);
	endwhile;
	
	$data = new Data();
	$data->data = $risks;
	echo json_encode($data, JSON_PRETTY_PRINT);
	exit;
}
function saveRisk (){
	 $postdata = file_get_contents("php://input");
	 $risk = json_decode($postdata);
	 
	 

	 $postID = null;
	 
	 if (!empty($risk->id)){
         // Update the existing post
	 	$post['ID'] = $risk->id;
	 	wp_update_post(array(
	 		'ID' => $risk->id,
            'post_content' => $risk->description,
            'post_title' => $risk->title,
            'post_status'   =>'publish',
            'post_type' =>'risk', 
            'post_author'   => 1			
         ));
	 	$postID = $risk->id;
	 } else {
         //Create a new one and record the ID
	 	$postID = wp_insert_post(array(
            'post_content' => $risk->description,
            'post_title' => $risk->title,
	 		'post_type' => 'risk',
            'post_status'   =>'publish',
            'post_author'   => 1			
         ));
	 	$risk->id = $postID;
	 }
    // The Bulk of the data is held in the post's meta data
	 update_post_meta( $postID, "riskdata", json_encode($risk) );
	 
	 // Add any comments to the returned object
	 $risk->comments = get_comments(array('ID' => $riskID));
	 $risk->attachments = get_children(array("post_parent"=>$riskID, "post_type"=>"attachment"));
	 
	echo json_encode($risk);
	exit;
}

add_filter ('query_vars','my_plugin_query_vars' );
function my_plugin_query_vars($vars) {
	$vars [] ='qrmfn';
	return $vars;
}


/**
 * Adds styling to the dashboard for the post type and adds quote posts
 * to the "At a Glance" metabox.
 */
if (is_admin ()) {
	
	// Loads for users viewing the WordPress dashboard
	if (! class_exists ('Dashboard_Glancer' )) {
		require plugin_dir_path ( __FILE__ ) .'includes/class-dashboard-glancer.php'; // WP 3.8
	}
	
	require plugin_dir_path ( __FILE__ ) .'includes/class-risk-post-type-admin.php';
	
	$post_type_admin = new Risk_Post_Type_Admin ( $post_type_registrations );
	$post_type_admin->init ();
}

// add_action ('save_post','qrm_save_post' );
// function qrm_save_post($post_id) {
// 	// If this is a revision, get real post ID
// 	if ($parent_id = wp_is_post_revision ( $post_id ))
// 		$post_id = $parent_id;
	
// 	$post = get_post ( $post_id );

// 	remove_action ('save_post','qrm_save_post' );
	
// 	if ($post->post_type =='risk') {
// 		Risk::postSave($post_id);
// 	}
	
// 	add_action ('save_post','set_save_post' );
// }
// Hook acrion to set quote title to be first part of quote content


$custom_feed = new custom_feed ();

add_action ('init','customRSS' );
function customRSS() {
	add_feed ('allquotes','all_quotes_feed' );
	add_feed ('quotecomment','quote_comments_feed' );
	add_feed ('allRisks','all_risks_feed');
}
function get_quay_quote($p) {
	$d = get_metadata ('post', $p->ID,'quote_date', true );
	$di = substr ( $d, 3, 2 ) . substr ( $d, 0, 2 );
	
	return array (
			'q' => get_the_content (),
			'a' => get_metadata ('post', $p->ID,'quote_author', true ),
			'di' => $di,
			'id' => $p->ID 
	);
}
function get_quay_risk($p) {

	$risk = get_metadata ('post', $p->ID,'risk', true );
	
	return array (
			'title' => get_the_title(),
			'description' => get_the_content (),
			'causes' => $risk->causes,
			'consequences' => $risk->consequences
	);
}
function all_quotes_feed() {
	load_template ( dirname ( __FILE__ ) .'/includes/allquotes-feed.php' );
}
function quote_comments_feed() {
	load_template ( dirname ( __FILE__ ) .'/includes/quotecomments-feed.php' );
}
function all_risks_feed() {
	load_template ( dirname ( __FILE__ ) .'/includes/allrisks-feed.php' );
}

add_filter('single_template','get_custom_post_type_template');
function get_custom_post_type_template($single_template){
	global $post;
	
	if ($post->post_type ='risk'){
		$single_template = dirname(__FILE__).'/templates\risk-type-template.php';
	}
	return $single_template;
}

add_filter('page_template','qrm_custom_page_template');
function qrm_custom_page_template($page_template){
	if (is_page('qrm-explorer-page-slug')){
		$page_template = dirname(__FILE__).'\templates\explorer-page.php';
	}
	return $page_template;	
}



// 

add_action('add_meta_boxes','qrm_add_meta_box');
function qrm_add_meta_box() {
	add_meta_box('mytaxonomy_id','My Radio Taxonomy','qrm_mytaxonomy_metabox','risk' ,'side','core');
}

function qrm_mytaxonomy_metabox( $post ) {
    //Get taxonomy and terms
    $taxonomy ='qrmtreatment';
 
    //Set up the taxonomy object and get terms
    $tax = get_taxonomy($taxonomy);
    $terms = get_terms($taxonomy,array('hide_empty' => 0));
 
    //Name of the form
    $name ='tax_input[' . $taxonomy .']';
 
    //Get current and popular terms
    $popular = get_terms( $taxonomy, array('orderby' =>'count','order' =>'DESC','number' => 10,'hierarchical' => false ) );
    $postterms = get_the_terms( $post->ID,$taxonomy );
    $current = ($postterms ? array_pop($postterms) : false);
    $current = ($current ? $current->term_id : 0);
    ?>
 
    <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
 
        <!-- Display tabs-->
        <ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
            <li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
            <li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e('Most Used' ); ?></a></li>
        </ul>
 
        <!-- Display taxonomy terms -->
        <div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
            <ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
                <?php   foreach($terms as $term){
                    $id = $taxonomy.'-'.$term->term_id;
                    echo "<li id='$id'><label class='selectit'>";
                    echo "<input type='radio' id='in-$id' name='{$name}'".checked($current,$term->term_id,false)."value='$term->term_id' />$term->name
<br />"; echo "</label>
</li>"; }?>
</ul>
</div>

<!-- Display popular taxonomy terms
        <div id="<?php //echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
            <ul id="<?php //echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
                <?php   foreach($popular as $term){
                    $id ='popular-'.$taxonomy.'-'.$term->term_id;
                    //echo "<li id='$id'><label class='selectit'>";
                    //echo "<input type='radio' id='in-$id'".checked($current,$term->term_id,false)."value='$term->term_id' />$term->name<br />";
                    //echo "</label></li>";
                }?>
           </ul>
       </div>
        -->

</div>
<?php }