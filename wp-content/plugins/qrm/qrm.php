<?php 
/*** 
 * Plugin Name: Quay Systems Risk Manager 
 * Description: Quay Risk Manager 
 * Version: The plugin's version number. Example: 1.0.0
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

require plugin_dir_path ( __FILE__ ) .'includes/qrm-class-definitions.php';
require plugin_dir_path ( __FILE__ ) .'includes/qrm-data-functions.php';

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

register_activation_hook( __FILE__, 'add_qrm_roles_on_plugin_activation' );
function add_qrm_roles_on_plugin_activation() {
	// Various roles within the system to impose a level of security
	add_role( 'risk_admin', 'Risk Administrator', array( 'read' => true ) );
	add_role( 'risk_project_manager', 'Risk Project Manager', array( 'read' => true ) );
	add_role( 'risk_owner', 'Risk Owner', array( 'read' => true ) );
	add_role( 'risk_manager', 'Risk Manager', array( 'read' => true ) );
	add_role( 'risk_user', 'Risk User', array( 'read' => true ) );
}

add_action ('parse_request','qrm_plugin_parse_request' );
function qrm_plugin_parse_request($wp) {
	
	if (array_key_exists ('qrmfn', $wp->query_vars )) {
		
		// Overall QRM security check. User needs to be logged in to Wordpress.
		if ( !is_user_logged_in() ){
			http_response_code(400);
			echo '{"error":true,"msg":"Not Logged In"}';
			exit;
		}
			
		// Pass to the specific function which will also check role security
		QRM::router($wp->query_vars ['qrmfn']);
	}
}

add_filter ('query_vars','qrm_plugin_query_vars' );
function qrm_plugin_query_vars($vars) {
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

add_filter('single_template','get_custom_post_type_template');
function get_custom_post_type_template($single_template){
	global $post;
	
	if ($post->post_type ='risk'){
		$single_template = dirname(__FILE__).'/templates/risk-type-template.php';
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

add_action('add_meta_boxes','qrm_add_meta_box');
function qrm_add_meta_box() {
	add_meta_box('mytaxonomy_id','My Radio Taxonomy','qrm_mytaxonomy_metabox','risk' ,'side','core');
}


add_action( 'admin_menu', 'register_my_custom_menu_page' );
function register_my_custom_menu_page(){
	add_menu_page( 'custom menu title', 'custom menu', 'manage_options', plugin_dir_path ( __FILE__ ) .'admin.php', '', plugins_url( 'myplugin/images/icon.png' ), 6 );
}

function my_custom_menu_page(){
	echo "Admin Page Test";
}

add_action('init', 'qrm_scripts_styles');
function qrm_scripts_styles(){
	wp_register_style ('font-awesome',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/font-awesome.css" );
	wp_register_style ('boostrap',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/bootstrap.min.css" );
	wp_register_style ('animate',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/animate.css" );
	wp_register_style ('dropzone',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/dropzone/dropzone.css" );
	wp_register_style ('ui-grid',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/ui-grid/ui-grid-unstable.css" );
	wp_register_style ('notify',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/angular-notify/angular-notify.min.css" );
	wp_register_style ('pace',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/pace/pace.css" );
	wp_register_style ('style',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/style.css" );
	wp_register_style ('qrm-angular',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/qrm_angular.css" );
	wp_register_style ('qrm-style',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/qrm_styles.css" );
	wp_register_style ('icheck',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/iCheck/custom.css" );
	
	wp_register_script( 'qrm-jquery', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/jquery/jquery-2.1.1.min.js',array(), "", true );
	wp_register_script( 'qrm-jqueryui', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/jquery-ui/jquery-ui.js',array(), "", true );
	wp_register_script( 'qrm-boostrap', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/bootstrap/bootstrap.min.js', array(), "", true );
	wp_register_script( 'qrm-metis', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/metisMenu/jquery.metisMenu.js', array(), "", true );
	wp_register_script( 'qrm-slimscroll', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/slimscroll/jquery.slimscroll.min.js', array(), "", true );
	wp_register_script( 'qrm-pace', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/pace/pace.min.js', array(), "", true );
	wp_register_script( 'qrm-inspinia', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/inspinia.js', array('qrm-jquery'), "", true );
	wp_register_script( 'qrm-angular', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/angular/angular.min.js', array(), "", true );
	wp_register_script( 'qrm-test', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/test.js', array('qrm-jquery', 'qrm-angular'), "", true );
	wp_register_script( 'qrm-lazyload', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/oclazyload/dist/ocLazyLoad.min.js', array(), "", true );
	wp_register_script( 'qrm-router', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/ui-router/angular-ui-router.min.js', array(), "", true );
	wp_register_script( 'qrm-bootstraptpl', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/bootstrap/ui-bootstrap-tpls-0.12.0.min.js', array(), "", true );
	wp_register_script( 'qrm-uigrid', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/ui-grid/ui-grid-unstable.js', array(), "", true );
	wp_register_script( 'qrm-icheck', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/iCheck/icheck.min.js', array(), "", true );
	wp_register_script( 'qrm-notify', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/angular-notify/angular-notify.min.js', array(), "", true );
	wp_register_script( 'qrm-dropzone', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/dropzone/dropzone.js', array(), "", true );
	wp_register_script( 'qrm-moment', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/moment.js', array(), "", true );
	wp_register_script( 'qrm-app', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/app.js', array(), "", true );
	wp_register_script( 'qrm-config', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/config.js', array(), "", true );
	wp_register_script( 'qrm-directives', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/directives.js', array(), "", true );
	wp_register_script( 'qrm-controllers', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/controllers.js', array(), "", true );
	wp_register_script( 'qrm-services', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/services.js', array(), "", true );
	wp_register_script( 'qrm-d3', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/d3/d3.min.js', array(), "", true );
	wp_register_script( 'qrm-common', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/qrm-common.js', array(), "", true );
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

</div>
<?php }