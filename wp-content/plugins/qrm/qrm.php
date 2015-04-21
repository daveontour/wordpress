<?php /** * Plugin Name: Quay Systems Risk Manager * Description: Quay Risk Manager * Version: The plugin's version number. Example: 1.0.0
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


add_action ('parse_request','my_plugin_parse_request' );
function my_plugin_parse_request($wp) {
	if (array_key_exists ('qrmfn', $wp->query_vars )) {
		
		switch ($wp->query_vars ['qrmfn']) {
			
			case "saveRisk" :
				saveRisk();
				break;
			case "getRisk" :
   				getRisk();
				break;
				
			default :
				wp_die ( $wp->query_vars ['qrmfn'] );
		}
	}
}

function getRisk (){

	$riskID = json_decode(file_get_contents("php://input"));
	$risk = get_post_meta($riskID, "riskdata", true);
	echo $risk;
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
            'post_content' => $risk->description,
            'post_title' => $risk->title,
            'post_status'   =>'publish',
            'post_type' =>'risk', 
            'tags_input' => array($risk->primcat->name, $risk->seccat->name),
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
            'tags_input' => array($risk->primcat->name, $risk->seccat->name),
            'post_author'   => 1			
         ));
	 	$risk->id = $postID;
	 }
    // The Bulk of the data is held in the post's meta data
	 update_post_meta( $postID, "riskdata", json_encode($risk) );
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