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


//Ajax Callbacks
add_action("wp_ajax_getProject", array(QRM, "getProject"));
add_action("wp_ajax_getProjects", array(QRM, "getProjects"));
add_action("wp_ajax_getSiteUsersCap", array(QRM, "getSiteUsersCap"));
add_action("wp_ajax_getSiteUsers", array(QRM, "getSiteUsers"));
add_action("wp_ajax_saveProject", array(QRM, "saveProject"));
add_action("wp_ajax_getAllProjectRisks", array(QRM, "getAllProjectRisks"));
add_action("wp_ajax_getRisk", array(QRM, "getRisk"));
add_action("wp_ajax_saveRisk", array(QRM, "saveRisk"));
add_action("wp_ajax_addComment", array(QRM, "addComment"));
add_action("wp_ajax_updateRisksRelMatrix", array(QRM, "updateRisksRelMatrix"));
add_action("wp_ajax_getRiskAttachments", array(QRM, "getRiskAttachments"));
add_action("wp_ajax_uploadFile", array(QRM, "uploadFile"));

add_action('init', 'qrm_init_options');

if (is_admin ()) {	
	add_filter('user_has_cap', 'qrm_prevent_riskproject_parent_deletion', 10, 3);
	add_filter('manage_riskproject_posts_columns', 'bs_riskproject_table_head');
	add_action('manage_riskproject_posts_custom_column', 'bs_riskproject_table_content', 10, 2 );
	add_action('admin_menu', 'qrm_admin_menu_config');
}

function bs_riskproject_table_head( $defaults ) {
	$defaults['manager']  = 'Risk Project Manager';
	$defaults['number']  = 'Number of Risks';
	$defaults['author'] = 'Added By';
	return $defaults;
}
function bs_riskproject_table_content( $column_name, $post_id ) {
	if ($column_name == 'manager') {
		echo get_post_meta ( $post_id, "projectriskmanager", true);
	}
	
	if ($column_name == 'number') {
		echo get_post_meta ( $post_id, "numberofrisks", true);
	}
}


add_filter('single_template','get_custom_post_type_template');
function get_custom_post_type_template($single_template){
	// Template for viewing risk or projects
	// Template loads the QRM app
	global $post;
	
	if ($post->post_type == 'risk'){
		$single_template = dirname(__FILE__).'/templates/risk-type-template.php';
	}
	if ($post->post_type == 'riskproject'){
		$single_template = dirname(__FILE__).'/templates/riskproject-type-template.php';
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

function qrm_admin_menu_config (){

	add_menu_page( 'Quay Risk Risk Manager', 'Risk Manager', 'manage_options', plugin_dir_path ( __FILE__ ) .'admin.php', '', plugins_url( 'myplugin/images/icon.png' ), "20.9" );
	
	remove_meta_box('pageparentdiv', 'riskproject', 'normal');
	remove_meta_box('pageparentdiv', 'riskproject', 'side');
}

function qrm_init_options(){
	add_option("qrm_objective_id", 1000);
	add_option("qrm_category_id", 1000);
	
	qrm_scripts_styles();
}

function qrm_prevent_riskproject_parent_deletion ($allcaps, $caps, $args) {
	// Prevent the deletion of any riskproject post that has children projects
	// Accomplished by checking for a non-zero count of projects with this as a parent 
	// and removing delete capability from user for that post
	global $wpdb;
	if (isset($args[0]) && isset($args[2]) && $args[0] == 'delete_post') {
		$post = get_post ($args[2]);
		if ($post->post_status == 'publish' && $post->post_type == 'riskproject') {
			$query = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = %s AND post_parent = %s";
			$num_posts = $wpdb->get_var ($wpdb->prepare ($query, $post->post_type, $post->ID));
			if ($num_posts > 0)
				$allcaps[$caps[0]] = false;
		}
	}
	return $allcaps;
}

function qrm_scripts_styles(){
	wp_register_style ('font-awesome',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/font-awesome/css/font-awesome.css" );
	wp_register_style ('boosstrap',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/bootstrap.min.css" );
	wp_register_style ('animate',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/animate.css" );
	wp_register_style ('dropzone',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/dropzone/dropzone.css" );
	wp_register_style ('ui-grid',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/ui-grid/ui-grid-unstable.css" );
	wp_register_style ('notify',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/angular-notify/angular-notify.min.css" );
	wp_register_style ('pace',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/pace/pace.css" );
	wp_register_style ('style',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/style.css" );
	wp_register_style ('qrm-angular',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/qrm_angular.css" );
	wp_register_style ('qrm-style',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/qrm_styles.css" );
	wp_register_style ('qrm-wpstyle',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/qrm_wp_styles.css" );
	wp_register_style ('icheck',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/iCheck/custom.css" );
	wp_register_style ('treecontrol',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/tree-control/tree-control.css" );
	wp_register_style ('treecontrolAttr',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/tree-control/tree-control-attribute.css" );
	wp_register_style ('select',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/select/select.css" );
	wp_register_style ('select2',"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.5/select2.css" );
	wp_register_style ('selectize',"http://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.8.5/css/selectize.default.css" );
	wp_register_style ('ngDialog',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/ngDialog/ngDialog.min.css" );
	wp_register_style ('ngDialogTheme',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/ngDialog/ngDialog-theme-default.min.css" );
	wp_register_style ('ngNotify',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/css/plugins/ngNotify/ng-notify.min.css" );
	
		
	wp_register_script( 'qrm-jquery', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/jquery/jquery-2.1.1.min.js',array(), "", true );
	wp_register_script( 'qrm-jqueryui', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/jquery-ui/jquery-ui.js',array(), "", true );
	wp_register_script( 'qrm-boostrap', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/bootstrap/bootstrap.min.js', array(), "", true );
	wp_register_script( 'qrm-metis', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/metisMenu/jquery.metisMenu.js', array(), "", true );
	wp_register_script( 'qrm-slimscroll', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/slimscroll/jquery.slimscroll.min.js', array(), "", true );
	wp_register_script( 'qrm-pace', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/pace/pace.min.js', array(), "", true );
	wp_register_script( 'qrm-inspinia', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/inspinia.js', array('qrm-jquery'), "", true );
	wp_register_script( 'qrm-angular', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/angular/angular.min.js', array(), "", true );
	wp_register_script( 'qrm-projadmin', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/projectadmin.js', array('qrm-jquery', 'qrm-angular','qrm-common', 'qrm-services'), "", true );
	wp_register_script( 'qrm-lazyload', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/oclazyload/dist/ocLazyLoad.min.js', array(), "", true );
	wp_register_script( 'qrm-router', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/ui-router/angular-ui-router.min.js', array(), "", true );
	wp_register_script( 'qrm-bootstraptpl', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/bootstrap/ui-bootstrap-tpls-0.12.0.min.js', array(), "", true );
	wp_register_script( 'qrm-uigrid', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/ui-grid/ui-grid-unstable.js', array(), "", true );
	wp_register_script( 'qrm-icheck', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/iCheck/icheck.min.js', array(), "", true );
	wp_register_script( 'qrm-notify', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/angular-notify/angular-notify.js', array(), "", true );
	wp_register_script( 'qrm-dropzone', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/dropzone/dropzone.js', array(), "", true );
	wp_register_script( 'qrm-moment', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/moment.js', array(), "", true );
	wp_register_script( 'qrm-app', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/app.js', array(), "", true );
	wp_register_script( 'qrm-config', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/config.js', array(), "", true );
	wp_register_script( 'qrm-directives', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/directives.js', array(), "", true );
	wp_register_script( 'qrm-controllers', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/controllers.js', array(), "", true );
	wp_register_script( 'qrm-services', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/services.js', array(), "", true );
	wp_register_script( 'qrm-d3', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/d3/d3.min.js', array(), "", true );
	wp_register_script( 'qrm-common', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/qrm-common.js', array('qrm-d3'), "", true );
	wp_register_script('treecontrol',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/js/plugins/tree-control/angular-tree-control.js" );
	wp_register_script( 'qrm-select', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/select/select.min.js', array(), "", true );
	wp_register_script( 'qrm-sanitize', plugin_dir_url ( __FILE__ ).'includes/qrmmainapp/js/plugins/sanitize/angular-sanitize.min.js', array(), "", true );
	wp_register_script('qrm-ngDialog',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/js/plugins/ngDialog/ngDialog.min.js" );
	wp_register_script('qrm-ngNotify',plugin_dir_url ( __FILE__ )."includes/qrmmainapp/js/plugins/ngNotify/ng-notify.min.js" );
	
}
