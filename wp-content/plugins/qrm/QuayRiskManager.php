<?php 
final class QuayRiskManager {
	protected static $_instance = null;
	public static function instance() {
		if (is_null ( self::$_instance )) {
			self::$_instance = new self ();
		}
		return self::$_instance;
	}
	public function __construct() {
		$this->init_hooks ();
		$this->defineAJAXFunctions ();
	}
	private function init_hooks() {
		add_filter ( 'user_has_cap', array ($this,'qrm_prevent_riskproject_parent_deletion'	), 10, 3 );
		add_filter ( 'manage_riskproject_posts_columns', array ($this,'qrm_riskproject_table_head') );
		add_filter ( 'manage_risk_posts_columns', array ($this,'qrm_risk_table_head') );
		add_filter ( 'upload_mimes', array ($this,'add_custom_mime_types') );
		add_filter ( 'single_template', array (	$this,'get_custom_post_type_template') );
		add_filter ( 'plugin_row_meta', array ($this,'plugin_row_meta'	), 10, 2 );
		add_filter ( 'plugin_action_links_' . plugin_basename ( __FILE__ ), array (	$this,	'qrm_add_plugin_action_links') );
		
		
		add_action ( 'manage_riskproject_posts_custom_column', array ($this,'qrm_riskproject_table_content'), 10, 2 );
		add_action ( 'manage_risk_posts_custom_column', array (	$this,'qrm_risk_table_content'), 10, 2 );
		add_action ( 'admin_menu', array ($this,'qrm_admin_menu_config'	) );
		add_action ( 'admin_init', array ($this,'redirect_about_page'), 1 );
		add_action ( 'add_meta_boxes', array ($this,'riskproject_meta_boxes') );
		add_action ( 'plugins_loaded', array ('PageTemplater','get_instance') );
		add_action ( 'init', array ($this,'register_types') );
		add_action ( 'init', array ($this,'qrm_init_options') );
		add_action ( 'init', array ($this,'qrm_scripts_styles'	) );
		add_action ( 'init', array ($this, 'qrm_init_user_cap'	) );
		add_action ( 'init', array ($this,'qrm_start_session') );
		add_action ( 'trashed_post', array ($this,'qrm_trashed_post') );

		add_option ( "qrm_reportServerURL", "http://127.0.0.1:8080/reportEngineURL" );
		add_option ( "qrm_displayUser", "userlogin" );

		register_activation_hook ( __FILE__, array ($this,	'qrmplugin_activate') );
		register_deactivation_hook ( __FILE__, array ($this,'qrmplugin_deactivate') );
		
		$this->activate_au ();

	}
	public function qrm_start_session() {
		if (! session_id ()) {
			session_start ();
		}
	}
	public function activate_au() {
		$plugin_current_version = QRM_VERSION;
		$plugin_remote_path = 'http://www.quaysystems.com.au/wp-admin/admin-ajax.php?action=getUpdateInfo';
		$plugin_slug = plugin_basename ( __FILE__ );
		
		require_once 'QRMAutoUpdate.php';
		new QRMAutoUpdate ( $plugin_current_version, $plugin_remote_path, $plugin_slug );
	}
	public static function plugin_row_meta($links, $file) {
		if (strpos ( $file, 'qrm.php' ) !== false) {
			$new_links = array (
					'<a href="http://www.quaysystems.com.au/docs" target="_blank">Docs</a>'
			);
				
			$links = array_merge ( $links, $new_links );
		}

		return $links;
	}
	public function qrm_add_plugin_action_links($links) {
		// Add 'Settings' to plugin entry
		return array_merge ( array (
				'settings' => '<a href="' . admin_url ( 'options-general.php?page=qrmadmin' ) . '">Settings</a>'
		), $links );
	}
	public function riskproject_meta_boxes() {
		add_meta_box ( 'riskproject_fields', 'Project Details', array (
				$this,
				'render_riskproject_meta_boxes'
		), 'riskproject', 'normal', 'high' );
	}
	public function qrm_trashed_post($postID) {
		$post = get_post ( $postID );
		if ($post->post_type == "risk") {
			QRM::reindexRiskCount ();
		}
	}
	public function render_riskproject_meta_boxes($post) {
		wp_enqueue_style ( 'font-awesome' );
		wp_enqueue_style ( 'ui-grid' );
		wp_enqueue_style ( 'qrm-angular' );
		wp_enqueue_style ( 'qrm-style' );
		wp_enqueue_style ( 'select' );
		wp_enqueue_style ( 'select2' );
		wp_enqueue_style ( 'selectize' );
		wp_enqueue_style ( 'ngDialog' );
		wp_enqueue_style ( 'ngDialogTheme' );
		wp_enqueue_style ( 'ngNotify' );

		wp_enqueue_script ( 'qrm-boostrap' );
		wp_enqueue_script ( 'qrm-angular' );
		wp_enqueue_script ( 'qrm-projadmin' );
		wp_enqueue_script ( 'qrm-bootstraptpl' );
		wp_enqueue_script ( 'qrm-uigrid' );
		wp_enqueue_script ( 'qrm-d3' );
		wp_enqueue_script ( 'qrm-common' );
		wp_enqueue_script ( 'qrm-select' );
		wp_enqueue_script ( 'qrm-sanitize' );
		wp_enqueue_script ( 'qrm-ngDialog' );
		wp_enqueue_script ( 'qrm-ngNotify' );
		wp_enqueue_script ( 'qrm-services' );

		echo "<script>";
		echo "projectID = " . $post->ID . ";";
		echo "</script>";
		include 'qrm-riskproject-widget.html';
	}
	public function qrm_init_options() {
		add_option ( "qrm_objective_id", 1000 );
		add_option ( "qrm_category_id", 1000 );
	}
	public function qrm_init_user_cap() {
		add_role ( 'risk_admin', 'Risk Administrator', array (
				'read' => true
		) );

		$role = get_role ( 'risk_admin' );
		$role->add_cap ( 'risk_admin' );
		$role->add_cap ( 'edit_posts' );
		$role->add_cap ( 'edit_pages' );
		$role->add_cap ( 'edit_others_posts' );
		$role->add_cap ( 'edit_others_pages' );
		$role->add_cap ( 'edit_private_posts' );
		$role->add_cap ( 'edit_private_pages' );
		$role->add_cap ( 'edit_published_posts' );
		$role->add_cap ( 'edit_published_pages' );
		$role->add_cap ( 'delete_pages' );
		$role->add_cap ( 'delete_posts' );
		$role->add_cap ( 'delete_others_posts' );
		$role->add_cap ( 'delete_others_pages' );
		$role->add_cap ( 'delete_published_posts' );
		$role->add_cap ( 'delete_published_pages' );

		$role = get_role ( "administrator" );
		$role->add_cap ( 'risk_admin' );
	}
	public function qrmplugin_activate() {
		require_once 'QRMActivate.php';
		require_once 'QRMUtil.php';

		QRMUtil::dropReportTables();
		QRMActivate::activate ();
	}
	public function qrmplugin_deactivate() {
		require_once 'QRMUtil.php';

		QRMUtil::dropReportTables();
	}
	public function get_custom_post_type_template($single_template) {
		global $post;

		if ($post->post_type == 'risk') {
			$single_template = dirname ( __FILE__ ) . '/templates/single-risk.php';
		}
		if ($post->post_type == 'riskproject') {
			$single_template = dirname ( __FILE__ ) . '/templates/single-riskproject.php';
		}
		if ($post->post_type == 'incident') {
			$single_template = dirname ( __FILE__ ) . '/templates/single-incident.php';
		}
		if ($post->post_type == 'review') {
			$single_template = dirname ( __FILE__ ) . '/templates/single-review.php';
		}

		return $single_template;
	}
	public function defineAJAXFunctions() {
		add_action ( "wp_ajax_getProject", array ('QRM',"getProject") );
		add_action ( "wp_ajax_getAllRisks", array ('QRM',"getAllRisks") );
		add_action ( "wp_ajax_getProjects", array ('QRM',"getProjects") );
		add_action ( "wp_ajax_getSiteUsersCap", array ('QRM',"getSiteUsersCap") );
		add_action ( "wp_ajax_getSiteUsers", array ('QRM',"getSiteUsers") );
		add_action ( "wp_ajax_saveSiteUsers", array ('QRM',"saveSiteUsers") );
		add_action ( "wp_ajax_saveProject", array ('QRM',"saveProject"	) );
		add_action ( "wp_ajax_getAllProjectRisks", array ('QRM',"getAllProjectRisks"	) );
		add_action ( "wp_ajax_getRisk", array ('QRM',"getRisk"	) );
		add_action ( "wp_ajax_saveRisk", array ('QRM',"saveRisk") );
		add_action ( "wp_ajax_updateRisksRelMatrix", array ('QRM',"updateRisksRelMatrix") );
		add_action ( "wp_ajax_getAttachments", array ('QRM',"getAttachments") );
		add_action ( "wp_ajax_uploadFile", array ('QRM',"uploadFile") );
		add_action ( "wp_ajax_uploadImport", array ('QRM',"uploadImport") );
		add_action ( "wp_ajax_getCurrentUser", array ('QRM',"getCurrentUser") );
		add_action ( "wp_ajax_saveRankOrder", array ('QRM',"saveRankOrder") );
		add_action ( "wp_ajax_registerAudit", array ('QRM',"registerAudit"	) );
		add_action ( "wp_ajax_getAllIncidents", array ('QRM',"getAllIncidents") );
		add_action ( "wp_ajax_getIncident", array ('QRM',"getIncident") );
		add_action ( "wp_ajax_saveIncident", array ('QRM',"saveIncident") );
		add_action ( "wp_ajax_addGeneralComment", array ('QRM',	"addGeneralComment") );
		add_action ( "wp_ajax_getAllReviews", array ('QRM',	"getAllReviews") );
		add_action ( "wp_ajax_getReview", array ('QRM',	"getReview") );
		add_action ( "wp_ajax_saveReview", array ('QRM',"saveReview") );
		add_action ( "wp_ajax_nopriv_login", array ('QRM',	"login"	) );
		add_action ( "wp_ajax_login", array ('QRM',	"login"	) );
		add_action ( "wp_ajax_logout", array ('QRM',"logout") );
		add_action ( "wp_ajax_checkSession", array ('QRM',"checkSession") );
		add_action ( "wp_ajax_newPushDown", array ('QRM',"newPushDown") );
		add_action ( "wp_ajax_installSample", array ('QRM',"installSample") );
		add_action ( "wp_ajax_installSampleProjects", array ('QRM',"installSampleProjects") );
		add_action ( "wp_ajax_removeSample", array ('QRM',"removeSample") );
		add_action ( "wp_ajax_downloadJSON", array ('QRM',"downloadJSON") );
		add_action ( "wp_ajax_getJSON", array ('QRM',"downloadJSON"	) );
		add_action ( "wp_ajax_getReportOptions", array ('QRM',"getReportOptions") );
		add_action ( "wp_ajax_saveReportOptions", array ('QRM',	"saveReportOptions") );
		add_action ( "wp_ajax_getServerMeta", array ('QRM',	"getServerMeta") );
		add_action ( "wp_ajax_createDummyRiskEntry", array ('QRM',"createDummyRiskEntry") );
		add_action ( "wp_ajax_createDummyRiskEntryMultiple", array ('QRM',"createDummyRiskEntryMultiple") );
		add_action ( "wp_ajax_reindexRiskCount", array ('QRM',	"reindexRiskCount") );
		add_action ( "wp_ajax_saveDisplayUser", array ('QRM',"saveDisplayUser") );
		add_action ( "wp_ajax_getDisplayUser", array ('QRM',"getDisplayUser") );
		add_action ( "wp_ajax_getReports", array ('QRM',"getReports") );
		add_action ( "wp_ajax_updateReport", array ('QRM',"updateReport") );
		add_action ( "wp_ajax_deleteReport", array ('QRM',"deleteReport") );
		add_action ( "wp_ajax_initReportData", array ('QRM',"initReportData") );
	}
	public function qrm_prevent_riskproject_parent_deletion($allcaps, $caps, $args) {
		// Prevent the deletion of any riskproject post that has children projects
		// Accomplished by checking for a non-zero count of projects with this as a parent
		// and removing delete capability from user for that post
		global $wpdb;
		if (isset ( $args [0] ) && isset ( $args [2] ) && $args [0] == 'delete_post') {
			$post = get_post ( $args [2] );
			if ($post->post_status == 'publish' && $post->post_type == 'riskproject') {

				// Prevent Deletion of parent with child projects
				$query = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'riskproject' AND post_parent = %s";
				$num_posts = $wpdb->get_var ( $wpdb->prepare ( $query, $post->ID ) );
				if ($num_posts > 0) {
					$allcaps [$caps [0]] = false;
				} else {
					$allcaps [$caps [0]] = true;
				}

				// Prevent deletion if there are still risks
				$query = "SELECT COUNT(*) FROM {$wpdb->postmeta}  JOIN {$wpdb->posts} ON {$wpdb->postmeta}.post_id = wp_posts.ID  AND {$wpdb->posts}.post_type = 'risk'  AND {$wpdb->posts}.post_status = 'publish' WHERE meta_key = 'projectID' AND meta_value = %s";
				$num_posts = $wpdb->get_var ( $wpdb->prepare ( $query, $post->ID ) );
				if ($num_posts > 0)
					$allcaps [$caps [0]] = false;
			}
		}
		return $allcaps;
	}
	public function qrm_riskproject_table_head($defaults) {
		$defaults ['manager'] = 'Risk Project Manager';
		$defaults ['number'] = 'Number of Risks';
		$defaults ['author'] = 'Added By';
		return $defaults;
	}
	public function qrm_risk_table_head($defaults) {
		$defaults ['project'] = 'Risk Project';
		$defaults ['owner'] = 'Risk Owner';
		return $defaults;
	}
	public function qrm_riskproject_table_content($column_name, $post_id) {
		if ($column_name == 'manager') {
			echo get_post_meta ( $post_id, "projectriskmanager", true );
		}

		if ($column_name == 'number') {
			echo get_post_meta ( $post_id, "numberofrisks", true );
		}
	}
	public function qrm_risk_table_content($column_name, $post_id) {
		if ($column_name == 'project') {
			echo get_post_meta ( $post_id, "riskProjectTitle", true );
		}

		if ($column_name == 'owner') {
			echo get_post_meta ( $post_id, "owner", true );
		}
	}
	public function qrm_admin_menu_config() {
		add_submenu_page ( 'options-general.php', 'Quay Risk Manager', 'Quay Risk Manager', 'manage_options', 'qrmadmin', array (
				$this,
				'qrmadminpage'
		) );
		remove_meta_box ( 'pageparentdiv', 'riskproject', 'normal' );
		remove_meta_box ( 'pageparentdiv', 'riskproject', 'side' );
	}
	public function redirect_about_page() {

		// Redirect to QRM Setting Page when first activated

		// only do this if the user can activate plugins
		if (! current_user_can ( 'manage_options' ))
			return;
				
			// don't do anything if the transient isn't set
			if (! get_transient ( 'qrm_about_page_activated' ))
				return;

				delete_transient ( 'qrm_about_page_activated' );
				wp_safe_redirect ( admin_url ( 'options-general.php?page=qrmadmin' ) );
				exit ();
	}
	public function add_custom_mime_types($mimes) {
		return array_merge ( $mimes, array (
				'qrm' => 'application/qrm',
				'json' => 'application/json'
		) );
	}
	public function register_types(){
		require_once 'QRMActivate.php';
		QRMActivate::register_types();
	}
	public function qrm_scripts_styles() {
		wp_register_style ( 'font-awesome', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/font-awesome/css/font-awesome.css" );
		wp_register_style ( 'bootstrap', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/bootstrap.min.css" );
		wp_register_style ( 'animate', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/animate.css" );
		wp_register_style ( 'dropzone', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/dropzone/dropzone.css" );
		wp_register_style ( 'ui-grid', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/ui-grid/ui-grid-unstable.css" );
		wp_register_style ( 'notify', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/angular-notify/angular-notify.min.css" );
		wp_register_style ( 'pace', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/pace/pace.css" );
		wp_register_style ( 'style', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/style.css" );
		wp_register_style ( 'qrm-angular', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/qrm_angular.css" );
		wp_register_style ( 'qrm-style', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/qrm_styles.css" );
		wp_register_style ( 'qrm-wpstyle', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/qrm_wp_styles.css" );
		wp_register_style ( 'icheck', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/iCheck/custom.css" );
		wp_register_style ( 'treecontrol', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/tree-control/tree-control.css" );
		wp_register_style ( 'treecontrolAttr', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/tree-control/tree-control-attribute.css" );
		wp_register_style ( 'select', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/select/select.css" );
		wp_register_style ( 'select2', "http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.5/select2.css" );
		wp_register_style ( 'selectize', "http://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.8.5/css/selectize.default.css" );
		wp_register_style ( 'ngDialog', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/ngDialog/ngDialog.min.css" );
		wp_register_style ( 'ngDialogTheme', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/ngDialog/ngDialog-theme-default.min.css" );
		wp_register_style ( 'ngNotify', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/ngNotify/ng-notify.min.css" );
		wp_register_style ( 'nv', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/nv/nv.d3.min.css" );

		// qrm-type-template styles

		wp_register_style ( 'q1', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/font-awesome/css/font-awesome.css" );
		wp_register_style ( 'q2', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/bootstrap.min.css" );
		wp_register_style ( 'q3', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/dropzone/dropzone.css" );
		wp_register_style ( 'q4', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/ui-grid/ui-grid-unstable.css" );
		wp_register_style ( 'q5', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/angular-notify/angular-notify.min.css" );
		wp_register_style ( 'q6', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/iCheck/custom.css" );
		wp_register_style ( 'q7', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/ngNotify/ng-notify.min.css" );
		wp_register_style ( 'q8', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/ngDialog/ngDialog.min.css" );
		wp_register_style ( 'q9', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/ngDialog/ngDialog-theme-default.min.css" );
		wp_register_style ( 'q10', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/select/select.css" );
		wp_register_style ( 'q11', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/select2.css" );
		wp_register_style ( 'q12', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/selectize.default.css" );
		wp_register_style ( 'q13', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/textAngular/textAngular.css" );
		wp_register_style ( 'q14', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/loading-bar/loading-bar.min.css" );
		wp_register_style ( 'q15', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/plugins/nv/nv.d3.min.css" );
		wp_register_style ( 'q16', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/daterangepicker-bs3.css" );
		wp_register_style ( 'q17', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/animate.css" );
		wp_register_style ( 'q18', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/style.css" );
		wp_register_style ( 'q19', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/qrm_angular.css" );
		wp_register_style ( 'q20', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/css/qrm_styles.css" );

		// qrm-type-template scripts

		wp_register_script ( 's3', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/bootstrap/bootstrap.min.js" );
		wp_register_script ( 's4', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/angular/angular.min.js" );
		wp_register_script ( 's5', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/angular/angular-animate.min.js" );
		wp_register_script ( 's6', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/oclazyload/dist/ocLazyLoad.min.js" );
		wp_register_script ( 's7', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/ui-router/angular-ui-router.min.js" );
		wp_register_script ( 's8', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/bootstrap/ui-bootstrap-tpls-0.12.0.min.js" );
		wp_register_script ( 's9', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/angular-idle/angular-idle.js" );
		wp_register_script ( 's10', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/ui-grid/ui-grid-unstable.js" );
		wp_register_script ( 's11', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/iCheck/icheck.min.js" );
		wp_register_script ( 's12', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/angular-notify/angular-notify.min.js" );
		wp_register_script ( 's13', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/dropzone/dropzone.js" );
		wp_register_script ( 's14', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/moment.js" );
		wp_register_script ( 's15', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/ngDialog/ngDialog.min.js" );
		wp_register_script ( 's16', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/textAngular/textAngular.min.js" );
		wp_register_script ( 's17', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/textAngular/textAngular-rangy.min.js" );
		wp_register_script ( 's18', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/textAngular/textAngular-sanitize.min.js" );
		wp_register_script ( 's19', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/d3/d3.min.js" );
		wp_register_script ( 's20', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/nv/nv.d3.js" );
		wp_register_script ( 's21', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/qrm-common.js" );
		wp_register_script ( 's22', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/services.js" );
		wp_register_script ( 's23', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/ngNotify/ng-notify.min.js" );
		wp_register_script ( 's24', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/select/select.min.js" );
		wp_register_script ( 's25', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/sanitize/angular-sanitize.min.js" );
		wp_register_script ( 's26', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/plugins/loading-bar/loading-bar.min.js" );
		wp_register_script ( 's27', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/app.js" );
		wp_register_script ( 's28', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/config.js" );
		wp_register_script ( 's29', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/directives.js" );
		wp_register_script ( 's30', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/daterangepicker.js" );
		wp_register_script ( 's31', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/controllers.js" );
		wp_register_script ( 's31m', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/controllers.min.js" );
		wp_register_script ( 's32', plugin_dir_url ( __FILE__ ) . "/includes/qrmmainapp/js/qrm.min.js" );

		wp_register_script ( 'qrm-bootstrap', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/bootstrap/bootstrap.min.js', array (), "", true );
		wp_register_script ( 'qrm-angular', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/angular/angular.min.js', array (), "", true );
		wp_register_script ( 'qrm-projadmin', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/projectadmin.js', array (
				'jquery',
				'qrm-angular',
				'qrm-common',
				'qrm-services'
		), "", true );
		wp_register_script ( 'qrm-mainadmin', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/mainadmin.js', array (
				'jquery',
				'qrm-angular',
				'qrm-common',
				'qrm-services'
		), "", true );
		wp_register_script ( 'qrm-lazyload', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/oclazyload/dist/ocLazyLoad.min.js', array (), "", true );
		wp_register_script ( 'qrm-router', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/ui-router/angular-ui-router.min.js', array (), "", true );
		wp_register_script ( 'qrm-bootstraptpl', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/bootstrap/ui-bootstrap-tpls-0.12.0.min.js', array (), "", true );
		wp_register_script ( 'qrm-uigrid', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/ui-grid/ui-grid-unstable.js', array (), "", true );
		wp_register_script ( 'qrm-icheck', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/iCheck/icheck.min.js', array (), "", true );
		wp_register_script ( 'qrm-notify', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/angular-notify/angular-notify.js', array (), "", true );
		wp_register_script ( 'qrm-dropzone', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/dropzone/dropzone.js', array (), "", true );
		wp_register_script ( 'qrm-moment', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/moment.js', array (), "", true );
		wp_register_script ( 'qrm-app', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/app.js', array (), "", true );
		wp_register_script ( 'qrm-config', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/config.js', array (), "", true );
		wp_register_script ( 'qrm-directives', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/directives.js', array (), "", true );
		wp_register_script ( 'qrm-controllers', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/controllers.js', array (), "", true );
		wp_register_script ( 'qrm-services', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/services.js', array (), "", true );
		wp_register_script ( 'qrm-d3', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/d3/d3.min.js', array (), "", true );
		wp_register_script ( 'qrm-common', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/qrm-common.js', array (
				'qrm-d3'
		), "", true );
		wp_register_script ( 'treecontrol', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/js/plugins/tree-control/angular-tree-control.js" );
		wp_register_script ( 'qrm-select', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/select/select.min.js', array (), "", true );
		wp_register_script ( 'qrm-sanitize', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/sanitize/angular-sanitize.min.js', array (), "", true );
		wp_register_script ( 'qrm-ngDialog', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/js/plugins/ngDialog/ngDialog.min.js" );
		wp_register_script ( 'qrm-ngNotify', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/js/plugins/ngNotify/ng-notify.min.js" );
		wp_register_script ( 'qrm-nv', plugin_dir_url ( __FILE__ ) . "includes/qrmmainapp/js/plugins/nv/nv.d3.min.js" );
	}

	public function qrmadminpage() {
		wp_enqueue_style ( 'bootstrap' );
		wp_enqueue_style ( 'animate' );
		wp_enqueue_style ( 'ui-grid' );
		wp_enqueue_style ( 'ngNotify' );
		wp_enqueue_style ( 'style' );
		wp_enqueue_style ( 'qrm-angular' );
		wp_enqueue_style ( 'qrm-style' );
		wp_enqueue_style ( 'dropzone' );

		wp_enqueue_script ( 'jquery' );
		wp_enqueue_script ( 'qrm-bootstrap' );
		wp_enqueue_script ( 'qrm-angular' );
		wp_enqueue_script ( 'qrm-bootstraptpl' );
		wp_enqueue_script ( 'qrm-uigrid' );
		wp_enqueue_script ( 'qrm-common' );
		wp_enqueue_script ( 'qrm-ngNotify' );
		wp_enqueue_script ( 'qrm-mainadmin' );
		wp_enqueue_script ( 'qrm-sanitize' );
		wp_enqueue_script ( 'qrm-dropzone' );

		require_once 'qrm-adminpage.html';
	}
}

?>