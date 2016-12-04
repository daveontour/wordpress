<?php
/*** 
 * Plugin Name: Quay Risk Manager
 * Plugin URI: http://www.quaysystems.com.au 
 * Description: Mangage your organisations risks. Quay Risk Manager enables you to identify, evaluate, mitigate and manage your risks. Watermarked report in PDF format are produced using a webservice. For non watermaked reports contact <a href="http://www.quaysystems.com.au">Quay Systems Consulting</a>   
 * Version: 1.3.3
 * Requires at least: 4.2.1
 * Tested up to: 4.3
 * Author: <a href="http://www.quaysystems.com.au">Quay Systems Consulting</a>
 * License: GPLv2 or later
 */

// Register Custom Post Type
if (! defined ( 'WPINC' )) {
	die ();
}

define ( 'QRM_VERSION', '1.3.3' );
defined ( 'ABSPATH' ) or die ();

require_once (plugin_dir_path ( __FILE__ ) . '/qrm-db.php');
class stdObject {
	public function __construct(array $arguments = array()) {
		if (! empty ( $arguments )) {
			foreach ( $arguments as $property => $argument ) {
				$this->{$property} = $argument;
			}
		}
	}
	public function __call($method, $arguments) {
		$arguments = array_merge ( array (
				"stdObject" => $this 
		), $arguments ); // Note: method argument 0 will always referred to the main class ($this).
		if (isset ( $this->{$method} ) && is_callable ( $this->{$method} )) {
			return call_user_func_array ( $this->{$method}, $arguments );
		} else {
			throw new Exception ( "Fatal error: Call to undefined method stdObject::{$method}()" );
		}
	}
}
final class Project {
	public $id;
	public $title;
	public $description;
	public $projectCode;
	public $riskIndex;
	public $parent_id;
	public $categories;
	public $projectRiskManager;
	public $useAdvancedConsequences;
	public $useAdvancedLiklihood;
	public $ownersID;
	public $managersID;
	public $usersID;
	public $matrix;
	public $objectives;
	public $inheritParentObjectives;
	public $riskCategories;
	public $inheritParentCategories;
	public $children;
}
final class PageTemplater {
	protected $plugin_slug;
	protected $templates;
	private static $instance;
	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new PageTemplater ();
		}
		return self::$instance;
	}
	private function __construct() {
		$this->templates = array ();
		add_filter ( 'page_attributes_dropdown_pages_args', array (
				$this,
				'register_project_templates' 
		) );
		add_filter ( 'wp_insert_post_data', array (
				$this,
				'register_project_templates' 
		) );
		add_filter ( 'template_include', array (
				$this,
				'view_project_template' 
		) );
		// Add your templates to this array.
		$this->templates = array (
				'templates/qrm-type-template.php' => 'Quay Risk Manager Main Page' 
		);
	}
	public function register_project_templates($atts) {
		$cache_key = 'page_templates-' . md5 ( get_theme_root () . '/' . get_stylesheet () );
		$templates = wp_get_theme ()->get_page_templates ();
		if (empty ( $templates )) {
			$templates = array ();
		}
		wp_cache_delete ( $cache_key, 'themes' );
		$templates = array_merge ( $templates, $this->templates );
		wp_cache_add ( $cache_key, $templates, 'themes', 1800 );
		return $atts;
	}
	public function view_project_template($template) {
		global $post;
		if (! isset ( $this->templates [get_post_meta ( $post->ID, '_wp_page_template', true )] )) {
			return $template;
		}
		$file = plugin_dir_path ( __FILE__ ) . get_post_meta ( $post->ID, '_wp_page_template', true );
		
		// Just to be safe, we check if the file exist first
		if (file_exists ( $file )) {
			return $file;
		} else {
			echo $file;
		}
		return $template;
	}
}
final class QRM {
	static function qrmUser() {
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		return ($current_user->has_cap ( "risk_admin" ) || $current_user->has_cap ( "risk_user" ));
	}
	static function checkSession() {
		wp_send_json ( array (
				'loggedin' => QRM::qrmUser () 
		) );
	}
	static function getGuestObject() {
		$guest = new stdObject ();
		$guest->msg = "Guest logins cannot save data";
		$guest->error = true;
		return $guest;
	}
	static function getJSON() {
		$export = QRM::commonJSON ();
		wp_send_json ( $export );
	}
	static function exportMetadata(&$export) {
		global $current_user;
		global $wpdb;
		
		$export->dbprefix = $wpdb->prefix;
		$export->userEmail = $current_user->user_email;
		$export->userLogin = $current_user->user_login;
		$export->userDisplayName = $current_user->display_name;
		$export->siteName = get_option ( "qrm_siteName" );
		$export->siteKey = get_option ( "qrm_siteKey" );
		$export->siteID = get_option ( "qrm_siteID" );
		$export->reportServerURL = get_option ( "qrm_reportServerURL" );
		$export->displayUser = get_option ( "qrm_displayUser" );
		$export->reports = QRM::getReportsObject ();
		$export->sessionToken = wp_get_session_token ();
	}
	static function getServerMeta() {
		$export = new stdObject ();
		QRM::exportMetadata ( $export );
		wp_send_json ( $export );
	}
	static function downloadJSON() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		header ( 'Content-Description: File Transfer' );
		header ( 'Content-Type: application/octet-stream' );
		header ( 'Content-Disposition: attachment; filename=QRMData.json' );
		header ( 'Content-Transfer-Encoding: binary' );
		header ( 'Connection: Keep-Alive' );
		header ( 'Expires: 0' );
		header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header ( 'Pragma: public' );
		
		$export = QRM::commonJSON ();
		
		wp_send_json ( $export );
	}
	static function commonJSON($projectIDs = array(), $riskIDs = array(), $basicsOnly = false) {
		global $post;
		
		$args = array (
				'post_type' => 'riskproject',
				'posts_per_page' => - 1 
		);
		// Restrict Selection to just the selected project
		if (sizeof ( $projectIDs ) > 0) {
			$args ['post__in'] = $projectIDs;
		}
		
		$the_query = new WP_Query ( $args );
		$projects = array ();
		
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$project = json_decode ( get_post_meta ( $post->ID, "projectdata", true ) );
			array_push ( $projects, $project );
		endwhile
		;
		
		// Restrict selection just to the selected risks
		if (sizeof ( $riskIDs ) > 0) {
			
			$args = array (
					'post_type' => 'risk',
					'post__in' => $riskIDs,
					'posts_per_page' => - 1 
			);
		} else if (sizeof ( $projectIDs ) > 0) {
			
			$args = array (
					'post_type' => 'risk',
					'posts_per_page' => - 1,
					'meta_query' => array (
							array (
									'key' => 'projectID',
									'value' => $projectIDs,
									'compare' => 'IN' 
							) 
					) 
			);
		} else {
			
			$args = array (
					'post_type' => 'risk',
					'posts_per_page' => - 1 
			);
		}
		
		$the_query = new WP_Query ( $args );
		$risks = array ();
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
			
			if (! $basicsOnly) {
				$risk->audit = json_decode ( get_post_meta ( $post->ID, "audit", true ) );
				$risk->incidents = get_post_meta ( $post->ID, "incident" );
				$risk->reviews = get_post_meta ( $post->ID, "review" );
				$risk->comments = get_comments ( array (
						'post_id' => $post->ID 
				) );
			}
			$risk->projectID = get_post_meta ( $post->ID, "projectID", true );
			$risk->rank = get_post_meta ( $post->ID, "rank", true );
			$risk->ID = $post->ID;
			if ($risk->primcat == 0) {
				$risk->primcat = new stdObject ();
			}
			if ($risk->seccat == 0) {
				$risk->seccat = new stdObject ();
			}
			if (isset ( $risk->response->respPlan )) {
				foreach ( $risk->response->respPlan as $step ) {
					if ($step->cost == "No Cost Allocated") {
						$step->cost = 0;
					}
				}
			}
			array_push ( $risks, $risk );
		endwhile
		;
		
		$args = array (
				'post_type' => 'review',
				'post_per_page' => - 1 
		);
		
		if (! $basicsOnly) {
			$args ["post_type"] = 'review';
			$the_query = new WP_Query ( $args );
			$reviews = array ();
			while ( $the_query->have_posts () ) :
				$the_query->the_post ();
				$review = json_decode ( get_post_meta ( $post->ID, "reviewdata", true ) );
				array_push ( $reviews, $review );
			endwhile
			;
			
			$args = array (
					'post_type' => 'incident',
					'post_per_page' => - 1 
			);
			
			$the_query = new WP_Query ( $args );
			$incidents = array ();
			while ( $the_query->have_posts () ) :
				$the_query->the_post ();
				$incident = json_decode ( get_post_meta ( $post->ID, "incidentdata", true ) );
				array_push ( $incidents, $incident );
			endwhile
			;
		}
		
		$export = new stdObject ();
		$export->projects = $projects;
		$export->risks = $risks;
		
		if (! $basicsOnly) {
			$export->incidents = $incidents;
			$export->reviews = $reviews;
		}
		
		$users = array ();
		$user_query = new WP_User_Query ( array (
				'fields' => "all" 
		) );
		foreach ( $user_query->results as $user ) {
			$u = new stdObject ();
			$u->id = $user->data->ID;
			$u->display_name = $user->data->display_name;
			$u->user_email = $user->data->user_email;
			array_push ( $users, $u );
		}
		
		$export->users = $users;
		
		return $export;
	}
	static function checkSampleUser() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		require 'qrm-sample.php';
	}
	static function installSample() {
		QRM::checkSampleUser ();
		wp_send_json ( array (
				"msg" => QRMSample::installSample () 
		) );
	}
	static function installSampleProjects() {
		QRM::checkSampleUser ();
		wp_send_json ( array (
				"msg" => QRMSample::createSampleProjects () 
		) );
	}
	static function createDummyRiskEntry() {
		QRM::checkSampleUser ();
		wp_send_json ( array (
				"msg" => QRMSample::createDummyRiskEntry () 
		) );
	}
	static function createDummyRiskEntryMultiple() {
		QRM::checkSampleUser ();
		wp_send_json ( array (
				"msg" => QRMSample::createDummyRiskEntryMultiple () 
		) );
	}
	static function removeSample() {
		QRM::checkSampleUser ();
		
		$a = json_decode ( file_get_contents ( "php://input" ) );
		$sampleOnly = $a->sampleOnly;
		
		wp_send_json ( array (
				"msg" => QRMSample::removeSample ( $sampleOnly ) 
		) );
	}
	static function login() {
		$data = json_decode ( file_get_contents ( "php://input" ) );
		$user = $data->user;
		$pass = $data->pass;
		
		$info = array ();
		$info ['user_login'] = $user;
		$info ['user_password'] = $pass;
		$info ['remember'] = true;
		
		$user_signon = wp_signon ( $info, false );
		
		if (is_wp_error ( $user_signon )) {
			wp_send_json ( array (
					'loggedin' => false,
					'message' => __ ( 'Wrong username or password.' ) 
			) );
		} else {
			
			$qrmuser = $user_signon->allcaps ["risk_admin"] || $user_signon->allcaps ["risk_user"];
			
			wp_send_json ( array (
					'loggedin' => true,
					'qrmuser' => $qrmuser,
					'message' => __ ( 'Login successful, redirecting...' ) 
			) );
		}
	}
	static function logout() {
		wp_destroy_current_session ();
		wp_send_json ( array (
				'loggedout' => true,
				'message' => __ ( 'Logout successful, redirecting...' ) 
		) );
	}
	static function getAllIncidents() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $post;
		$args = array (
				'post_type' => 'incident',
				'posts_per_page' => - 1 
		);
		
		$the_query = new WP_Query ( $args );
		$incs = array ();
		
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$incident = json_decode ( get_post_meta ( $post->ID, "incidentdata", true ) );
			array_push ( $incs, $incident );
		endwhile
		;
		wp_send_json ( $incs );
	}
	static function getIncident() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$incidentID = json_decode ( file_get_contents ( "php://input" ) );
		$incident = json_decode ( get_post_meta ( $incidentID, "incidentdata", true ) );
		$incident->comments = get_comments ( array (
				'post_id' => $incidentID 
		) );
		$incident->attachments = get_children ( array (
				"post_parent" => $incidentID,
				"post_type" => "attachment" 
		) );
		
		wp_send_json ( $incident );
	}
	static function getReportOptions() {
		$options = new stdObject ();
		
		$options->siteKey = get_option ( "qrm_siteKey" );
		$options->siteName = get_option ( "qrm_siteName" );
		$options->siteID = get_option ( "qrm_siteID" );
		$options->url = get_option ( "qrm_reportServerURL" );
		
		wp_send_json ( $options );
	}
	static function getDisplayUser() {
		$options = new stdObject ();
		
		$options->displayUser = get_option ( "qrm_displayUser" );
		wp_send_json ( $options );
	}
	static function saveReportOptions() {
		$options = json_decode ( file_get_contents ( "php://input" ) );
		
		update_option ( "qrm_siteKey", $options->siteKey );
		update_option ( "qrm_siteName", $options->siteName );
		update_option ( "qrm_siteID", $options->siteID );
		update_option ( "qrm_reportServerURL", $options->url );
	}
	static function saveDisplayUser() {
		$options = json_decode ( file_get_contents ( "php://input" ) );
		update_option ( "qrm_displayUser", $options->displayUser );
	}
	static function saveIncident() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		
		$postdata = file_get_contents ( "php://input" );
		$incident = json_decode ( $postdata );
		
		if ($incident->reportedby == 0 || $incident->reportedby == "") {
			$incident->reportedby = $current_user->ID;
		}
		$postID = null;
		
		if (($incident->id > 0)) {
			// Update the existing post
			$post ['ID'] = $incident->id;
			wp_update_post ( array (
					'ID' => $incident->id,
					'post_content' => $incident->description,
					'post_title' => $incident->title,
					'post_status' => 'publish',
					'post_type' => 'incident',
					'post_author' => $current_user->ID 
			) );
			$postID = $incident->id;
		} else {
			// Create a new one and record the ID
			$postID = wp_insert_post ( array (
					'post_content' => $incident->description,
					'post_title' => $incident->title,
					'post_status' => 'publish',
					'post_type' => 'incident',
					'post_author' => $current_user->ID 
			) );
			$incident->id = $postID;
		}
		
		$incident->incidentCode = "INCIDENT-" . $incident->id;
		
		wp_update_post ( array (
				'ID' => $incident->id,
				'post_title' => $incident->incidentCode . " - " . $incident->title,
				'post_type' => 'incident' 
		) );
		
		update_post_meta ( $postID, "incidentdata", json_encode ( $incident, JSON_HEX_QUOT ) );
		update_post_meta ( $postID, "incidenttitle", $incident->incidentCode . " - " . $incident->title );
		
		// record the incident in the risk metadata
		
		$args = array (
				'posts_per_page' => - 1,
				'meta_key' => 'incident',
				'meta_value' => $postID,
				'post_type' => 'risk' 
		);
		foreach ( get_posts ( $args ) as $post ) {
			delete_post_meta ( $post->ID, "incident", $postID );
		}
		if ($incident->risks != null) {
			foreach ( $incident->risks as $risk ) {
				add_post_meta ( $risk, "incident", intval ( $postID ) );
			}
		}
		
		// Add any comments to the returned object
		$incident->comments = get_comments ( array (
				'post_id' => $postID 
		) );
		$incident->attachments = get_children ( array (
				"post_parent" => $postID,
				"post_type" => "attachment" 
		) );
		
		WPQRM_Model_Incident::replace ( $incident );
		
		wp_send_json ( $incident );
	}
	static function getRiskIncidents($riskID) {
		$incidentIDs = get_post_meta ( $riskID, "incident" );
		if (count ( $incidentIDs ) > 0) {
			$query = new WP_Query ( array (
					'post_type' => 'incident',
					'posts_per_page' => - 1,
					'post__in' => $incidentIDs 
			) );
			if ($query->have_posts ()) {
				$incidents = array ();
				while ( $query->have_posts () ) {
					$query->the_post ();
					$o = new stdClass ();
					$o->title = get_post_meta ( $query->post->ID, "incidenttitle", true );
					$o->incidentID = $query->post->ID;
					array_push ( $incidents, $o );
				}
				return $incidents;
			} else {
				return array ();
			}
		} else {
			return array ();
		}
	}
	static function getRiskReviews($riskID) {
		$reviewIDs = get_post_meta ( $riskID, "review" );
		if (count ( $reviewIDs ) > 0) {
			$query = new WP_Query ( array (
					'post_type' => 'review',
					'posts_per_page' => - 1,
					'post__in' => $reviewIDs 
			) );
			if ($query->have_posts ()) {
				$reviews = array ();
				while ( $query->have_posts () ) {
					$query->the_post ();
					$review = json_decode ( get_post_meta ( $query->post->ID, "reviewdata", true ) );
					$o = new stdClass ();
					$o->title = get_post_meta ( $query->post->ID, "reviewtitle", true );
					$o->reviewID = $query->post->ID;
					$o->scheddate = $review->scheddate;
					
					if ($review->riskComments != null) {
						foreach ( $review->riskComments as $riskComment ) {
							if ($riskComment->riskID == $riskID) {
								$o->comment = $riskComment->comment;
							}
						}
					}
					array_push ( $reviews, $o );
				}
				return $reviews;
			} else {
				return array ();
			}
		} else {
			return array ();
		}
	}
	static function addGeneralComment() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$comment = json_decode ( file_get_contents ( "php://input" ) );
		$time = current_time ( 'mysql' );
		
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
		}
		
		$data = array (
				'comment_post_ID' => $comment->ID,
				'comment_author' => $current_user->display_name,
				'comment_author_email' => $current_user->user_email,
				'comment_content' => $comment->comment,
				'comment_type' => '',
				'comment_parent' => 0,
				'user_id' => $user_ID,
				'comment_date' => $time,
				'comment_approved' => 1 
		);
		
		wp_insert_comment ( $data );
		$comments = get_comments ( array (
				'post_id' => $comment->ID 
		) );
		wp_send_json ( $comments );
	}
	static function getAllReviews() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $post;
		$args = array (
				'post_type' => 'review',
				'posts_per_page' => - 1 
		);
		
		$the_query = new WP_Query ( $args );
		$revs = array ();
		
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$review = json_decode ( get_post_meta ( $post->ID, "reviewdata", true ) );
			array_push ( $revs, $review );
		endwhile
		;
		
		wp_send_json ( $revs );
	}
	static function getReview() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$reviewID = json_decode ( file_get_contents ( "php://input" ) );
		$review = json_decode ( get_post_meta ( $reviewID, "reviewdata", true ) );
		$review->comments = get_comments ( array (
				'post_id' => $reviewID 
		) );
		$review->attachments = get_children ( array (
				"post_parent" => $reviewID,
				"post_type" => "attachment" 
		) );
		
		wp_send_json ( $review );
	}
	static function saveReview() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		
		$postdata = file_get_contents ( "php://input" );
		$review = json_decode ( $postdata );
		
		if ($review->responsible == 0 || $review->responsible == "") {
			$review->responsible = $current_user->ID;
		}
		$postID = null;
		
		if (($review->id > 0)) {
			// Update the existing post
			$post ['ID'] = $reviewt->id;
			wp_update_post ( array (
					'ID' => $review->id,
					'post_content' => $review->description,
					'post_title' => $review->title,
					'post_status' => 'publish',
					'post_type' => 'review',
					'post_author' => $current_user->ID 
			) );
			$postID = $review->id;
		} else {
			// Create a new one and record the ID
			$postID = wp_insert_post ( array (
					'post_content' => $review->description,
					'post_title' => $review->title,
					'post_status' => 'publish',
					'post_type' => 'review',
					'post_author' => $current_user->ID 
			) );
			$review->id = $postID;
		}
		
		$review->reviewCode = "REVIEW-" . $review->id;
		
		wp_update_post ( array (
				'ID' => $review->id,
				'post_title' => $review->reviewCode . " - " . $review->title,
				'post_type' => 'review' 
		) );
		
		update_post_meta ( $postID, "reviewdata", json_encode ( $review, JSON_HEX_QUOT ) );
		update_post_meta ( $postID, "reviewtitle", $review->reviewCode . " - " . $review->title );
		
		$args = array (
				'posts_per_page' => - 1,
				'meta_key' => 'review',
				'meta_value' => $postID,
				'post_type' => 'risk' 
		);
		foreach ( get_posts ( $args ) as $post ) {
			delete_post_meta ( $post->ID, "review", $postID );
		}
		if ($review->risks != null) {
			foreach ( $review->risks as $risk ) {
				add_post_meta ( $risk, "review", intval ( $postID ) );
			}
		}
		
		// Add any comments to the returned object
		$review->comments = get_comments ( array (
				'post_id' => $postID 
		) );
		$review->attachments = get_children ( array (
				"post_parent" => $postID,
				"post_type" => "attachment" 
		) );
		
		WPQRM_Model_Review::replace ( $review );
		
		wp_send_json ( $review );
	}
	static function getCurrentUser() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		wp_send_json ( wp_get_current_user () );
	}
	static function getSiteUsersCap() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$user_query = new WP_User_Query ( array (
				'fields' => 'all' 
		) );
		
		$userSummary = array ();
		foreach ( $user_query->results as $user ) {
			$u = new StdClass ();
			$u->display_name = $user->data->display_name;
			$u->user_email = $user->data->user_email;
			$u->ID = $user->ID;
			
			$u->bAdmin = $user->has_cap ( "risk_admin" );
			$u->bUser = $user->has_cap ( "risk_user" );
			
			if ($u->bAdmin || $u->bUser) {
				array_push ( $userSummary, $u );
			}
		}
		
		wp_send_json ( $userSummary );
	}
	static function registerAudit() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		
		$audit = json_decode ( file_get_contents ( "php://input" ) );
		
		$riskID = $audit->riskID;
		
		$a = new stdObject ();
		$a->auditComment = $audit->auditComment;
		$a->auditDate = date ( "M j, Y" );
		$a->auditPerson = $current_user->ID;
		$a->riskID = $riskID;
		
		$auditObj = json_decode ( get_post_meta ( $riskID, "audit", true ) );
		if ($auditObj == null) {
			$auditObjEval = new stdObject ();
			$auditObjEval->auditComment = "Risk Entered";
			$auditObjEval->auditDate = date ( "M j, Y" );
			$auditObjEval->auditPerson = $current_user->ID;
			
			$auditObjIdent = new stdObject ();
			$auditObjIdent->auditComment = "Risk Entered";
			$auditObjIdent->auditDate = date ( "M j, Y" );
			$auditObjIdent->auditPerson = $current_user->ID;
			
			$auditObj = new stdObject ();
			$auditObj->auditIdent = $auditObjIdent;
			$auditObj->auditEval = $auditObjEval;
		}
		switch ($audit->auditType) {
			case 0 :
				break;
			case 1 :
				$auditObj->auditIdentRev = $a;
				$a->auditType = 1;
				break;
			case 2 :
				$auditObj->auditIdentApp = $a;
				$a->auditType = 2;
				break;
			case 3 :
				break;
			case 4 :
				$auditObj->auditEvalRev = $a;
				$a->auditType = 4;
				break;
			case 5 :
				$auditObj->auditEvalApp = $a;
				$a->auditType = 5;
				break;
			case 6 :
				$auditObj->auditMit = $a;
				$a->auditType = 6;
				break;
			case 7 :
				$auditObj->auditMitRev = $a;
				$a->auditType = 7;
				break;
			case 8 :
				$auditObj->auditMitApp = $a;
				$a->auditType = 8;
				break;
		}
		
		update_post_meta ( $riskID, "audit", json_encode ( $auditObj ) );
		
		WPQRM_Model_Audit::replace ( $a );
		
		wp_send_json ( json_decode ( get_post_meta ( $riskID, "audit", true ) ) );
	}
	static function getSiteUsers() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$user_query = new WP_User_Query ( array (
				'fields' => 'all' 
		) );
		foreach ( $user_query->results as $result ) {
			$result->data->nickname = get_user_meta ( $result->data->ID, "nickname" ) [0];
			$result->data->first_name = get_user_meta ( $result->data->ID, "first_name" ) [0];
			$result->data->last_name = get_user_meta ( $result->data->ID, "last_name" ) [0];
		}
		wp_send_json ( $user_query->results );
	}
	static function uploadFile() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		if (! function_exists ( 'wp_handle_upload' )) {
			require_once (ABSPATH . 'wp-admin/includes/file.php');
		}
		
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		
		$uploadedfile = $_FILES ['file'];
		$upload_overrides = array (
				'test_form' => false 
		);
		
		$movefile = wp_handle_upload ( $uploadedfile, $upload_overrides );
		
		if ($movefile && ! isset ( $movefile ['error'] )) {
			
			// $filename should be the path to a file in the upload directory.
			$filename = $movefile ['file'];
			
			// The ID of the post this attachment is for.
			$parent_post_id = $_POST ["postID"];
			
			// Check the type of file. We'll use this as the 'post_mime_type'.
			$filetype = wp_check_filetype ( basename ( $filename ), null );
			
			// Get the path to the upload directory.
			$wp_upload_dir = wp_upload_dir ();
			
			// Prepare an array of post data for the attachment.
			$attachment = array (
					'guid' => $wp_upload_dir ['url'] . '/' . basename ( $filename ),
					'post_mime_type' => $filetype ['type'],
					'post_title' => preg_replace ( '/\.[^.]+$/', '', basename ( $filename ) ),
					'post_content' => '',
					'post_status' => 'inherit' 
			);
			
			// Insert the attachment.
			$attach_id = wp_insert_attachment ( $attachment, $filename, $parent_post_id );
			
			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once (ABSPATH . 'wp-admin/includes/image.php');
			
			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata ( $attach_id, $filename );
			wp_update_attachment_metadata ( $attach_id, $attach_data );
			
			// Add Description and other info
			
			global $user_identity, $user_email, $user_ID, $current_user;
			get_currentuserinfo ();
			$args = array (
					'ID' => $attach_id,
					'post_excerpt' => $current_user->display_name,
					'post_content' => $_POST ["description"] 
			);
			
			wp_update_post ( $args );
		} else {
			/**
			 * Error generated by _wp_handle_upload()
			 *
			 * @see _wp_handle_upload() in wp-admin/includes/file.php
			 */
			echo $movefile ['error'];
		}
		
		exit ();
	}
	static function uploadImport() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		if (! function_exists ( 'wp_handle_upload' )) {
			require_once (ABSPATH . 'wp-admin/includes/file.php');
		}
		
		require_once 'qrm-sample.php';
		
		$uploadedfile = $_FILES ['file'];
		$upload_overrides = array (
				'test_form' => false 
		);
		
		$movefile = wp_handle_upload ( $uploadedfile, $upload_overrides );
		
		if ($movefile && ! isset ( $movefile ['error'] )) {
			// $filename should be the path to a file in the upload directory.
			$filename = $movefile ['file'];
			// Get the path to the upload directory.
			$wp_upload_dir = wp_upload_dir ();
			
			// Process the file
			$returnMessage = QRMSample::installImport ( $filename );
			
			// Remove the file
			unlink ( $filename );
			
			wp_send_json ( array (
					"msg" => $returnMessage 
			) );
		} else {
			/**
			 * Error generated by _wp_handle_upload()
			 *
			 * @see _wp_handle_upload() in wp-admin/includes/file.php
			 */
			echo $movefile ['error'];
		}
		
		exit ();
	}
	static function updateRisksRelMatrix() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$risks = json_decode ( file_get_contents ( "php://input" ) );
		
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		
		foreach ( $risks as $risk ) {
			$r = json_decode ( get_post_meta ( $risk->riskID, "riskdata", true ) );
			$r->inherentProb = $risk->newInherentProb;
			$r->inherentImpact = $risk->newInherentImpact;
			$r->treatedProb = $risk->newTreatedProb;
			$r->treatedImpact = $risk->newTreatedImpact;
			
			if ($r->treated) {
				$r->currentProb = $risk->treatedProb;
				$r->currentImpact = $risk->treatedImpact;
				$r->currentTolerance = $risk->treatedTolerance;
			} else {
				$r->currentProb = $risk->inherentProb;
				$r->currentImpact = $risk->inherentImpact;
				$r->currentTolerance = $risk->inherentTolerance;
			}
			
			update_post_meta ( $risk->riskID, "riskdata", json_encode ( $r ) );
			
			WPQRM_Model_Risk::replace ( $r );
		}
		exit ();
	}
	static function saveSiteUsers() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$users = json_decode ( file_get_contents ( "php://input" ) );
		
		if ($users == null) {
			QRM::getSiteUsers ();
			return;
		}
		foreach ( $users as $u ) {
			
			if (array_key_exists ( "dirty", $u )) {
				
				$wpUser = get_user_by ( "id", $u->ID );
				$wpUser->remove_cap ( "risk_admin" );
				$wpUser->remove_cap ( "risk_user" );
				
				if (isset ( $u->caps->risk_admin ) && $u->caps->risk_admin == true) {
					$wpUser->add_cap ( "risk_admin" );
				}
				if (isset ( $u->caps->risk_user ) && $u->caps->risk_user == true) {
					$wpUser->add_cap ( "risk_user" );
				}
			}
		}
		QRM::getSiteUsers ();
	}
	static function getAttachments() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$postID = json_decode ( file_get_contents ( "php://input" ) );
		$attachments = get_children ( array (
				"post_parent" => $postID,
				"post_type" => "attachment" 
		) );
		wp_send_json ( $attachments );
	}
	static function getRisk($riskID = null) {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		
		if ($riskID == null){
			$riskID = json_decode ( file_get_contents ( "php://input" ) );
		}
		
		$json = get_post_meta ( $riskID, "riskdata", true );
		$risk = json_decode ( get_post_meta ( $riskID, "riskdata", true ) );
		
		// Make sure the user is authorised to get the risk
		$projectRiskManagerID = get_post_meta ( $risk->projectID, "projectRiskManagerID", true );
		$project = json_decode ( get_post_meta ( $risk->projectID, "projectdata", true ) );
		
		if (! ($current_user->ID == $projectRiskManagerID || in_array ( $current_user->ID, $project->ownersID ) || in_array ( $current_user->ID, $project->managersID ) || in_array ( $current_user->ID, $project->usersID ))) {
			wp_send_json ( array (
					"msg" => "You are not authorised to view this risk" 
			) );
		}
		
		$risk->comments = get_comments ( array (
				'post_id' => $riskID 
		) );
		$risk->attachments = get_children ( array (
				"post_parent" => $riskID,
				"post_type" => "attachment" 
		) );
		$risk->audit = json_decode ( get_post_meta ( $riskID, "audit", true ) );
		$risk->incidents = QRM::getRiskIncidents ( $riskID );
		$risk->reviews = QRM::getRiskReviews ( $riskID );
		wp_send_json ( $risk );
	}
	static function saveRankOrder() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		
		$risks = json_decode ( file_get_contents ( "php://input" ) );
		
		global $wpdb;
		foreach ( $risks as $risk ) {
			update_post_meta ( $risk->id, "rank", $risk->rank );
			$sql = sprintf ( 'UPDATE %s SET rank = %%s WHERE id = %%s', $wpdb->prefix . 'qrm_risk' );
			$wpdb->query ( $wpdb->prepare ( $sql, $risk->rank, $risk->id ) );
		}
		exit ();
	}
	static function getProjects() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $post;
		$args = array (
				'post_type' => 'riskproject',
				'posts_per_page' => - 1,
				'orderby' => "ID",
				'order' => 'ASC' 
		);
		$the_query = new WP_Query ( $args );
		$projects = array ();
		
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$project = json_decode ( get_post_meta ( $post->ID, "projectdata", true ) );
			array_push ( $projects, $project );
		endwhile
		;
		
		wp_send_json ( $projects );
	}
	static function getProject() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$projectID = json_decode ( file_get_contents ( "php://input" ) );
		$project = json_decode ( get_post_meta ( $projectID, "projectdata", true ) );
		wp_send_json ( $project );
	}
	static function getAllRisks() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $post;
		$args = array (
				'post_type' => 'risk',
				'posts_per_page' => - 1 
		);
		
		$the_query = new WP_Query ( $args );
		$risks = array ();
		
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			
			$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
			
			$r = new stdObject ();
			$r->description = $risk->description;
			$r->title = $risk->title;
			$r->id = $risk->id;
			$r->riskProjectCode = $risk->riskProjectCode;
			$r->projectID = $risk->projectID;
			
			array_push ( $risks, $r );
		endwhile
		;
		wp_send_json ( $risks );
	}
	static function getReports() {
		wp_send_json ( QRM::getReportsObject () );
	}
	static function getReportsObject() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $post;
		$args = array (
				'post_type' => 'report',
				'posts_per_page' => - 1 
		);
		
		$the_query = new WP_Query ( $args );
		$reports = array ();
		
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			
			$report = json_decode ( get_post_meta ( $post->ID, "reportdata", true ) );
			
			array_push ( $reports, $report );
		endwhile
		;
		return $reports;
	}
	static function updateReport() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		
		$postdata = file_get_contents ( "php://input" );
		$report = json_decode ( $postdata );
		
		$postID = null;
		
		if (! empty ( $report->id )) {
			wp_update_post ( array (
					'ID' => $report->id,
					'post_content' => $report->description,
					'post_title' => $report->menuName,
					'post_status' => 'publish',
					'post_type' => 'report' 
			) );
			$postID = $report->id;
		} else {
			// Create a new one and record the ID
			$postID = wp_insert_post ( array (
					'post_content' => $report->description,
					'post_title' => $report->menuName,
					'post_status' => 'publish',
					'post_type' => 'report',
					'post_author' => $user_ID 
			) );
			$report->id = $postID;
		}
		
		update_post_meta ( $postID, "reportdata", json_encode ( $report, JSON_HEX_QUOT ) );
		QRM::getReports ();
	}
	static function deleteReport() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		
		$postdata = file_get_contents ( "php://input" );
		$report = json_decode ( $postdata );
		if (! empty ( $report->id )) {
			wp_delete_post ( $report->id, true );
		}
		QRM::getReports ();
	}
	static function getAllProjectRisks() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$data = json_decode ( file_get_contents ( "php://input" ) );
		$projectID = $data->projectID;
		if ($projectID == null) {
			wp_send_json ( array () );
		}
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		
		$ids = array ();
		array_push ( $ids, $projectID );
		
		if ($data->childProjects) {
			$children = QRM::get_project_children ( $projectID );
			foreach ( $children as $child ) {
				array_push ( $ids, $child->ID );
			}
		}
		
		global $post;
		$args = array (
				'post_type' => 'risk',
				'posts_per_page' => - 1,
				'meta_query' => array (
						array (
								'key' => 'projectID',
								'value' => $ids,
								'compare' => 'IN' 
						) 
				) 
		);
		
		$the_query = new WP_Query ( $args );
		$risks = array ();
		
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			
			$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
			$project = json_decode ( get_post_meta ( $risk->projectID, "projectdata", true ) );
			
			
			if ($project == null){
				//var_dump($risk->projectID);
				continue;
			}
			if ( $project->ownersID == null ) {
				$project->ownersID = array(-1);
			}
			if ( $project->managersID == null  ) {
				$project->managersID = array(-1);
			}
			if ( $project->usersID == null ) {
				$project->usersID = array(-1);
			}
				
			if (! ($current_user->ID == $project->projectRiskManager || in_array ( $current_user->ID, $project->ownersID ) || in_array ( $current_user->ID, $project->managersID ) || in_array ( $current_user->ID, $project->usersID ))) {
				continue;
			}
			
			$risk->rank = get_post_meta ( $post->ID, "rank", true );
			
			array_push ( $risks, $risk );
		endwhile
		;
		
		// $data = new Data ();
		// $data->data = $risks;
		wp_send_json ( $risks );
	}
	static function get_project_children($parent_id) {
		$children = array ();
		$posts = get_posts ( array (
				'numberposts' => - 1,
				'post_status' => 'publish',
				'post_type' => 'riskproject',
				'post_parent' => $parent_id 
		) );
		// now grab the grand children
		foreach ( $posts as $child ) {
			// recursion!! hurrah
			$gchildren = QRM::get_project_children ( $child->ID );
			// merge the grand children into the children array
			if (! empty ( $gchildren )) {
				$children = array_merge ( $children, $gchildren );
			}
		}
		// merge in the direct descendants we found earlier
		$children = array_merge ( $children, $posts );
		return $children;
	}
	static function newPushDown() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		
		$postdata = file_get_contents ( "php://input" );
		$risk = json_decode ( $postdata );
		
		$project = json_decode ( get_post_meta ( $risk->projectID, "projectdata", true ) );
		
		// Test for Project Risk Owner
		if ($current_user->ID != $project->projectRiskManager) {
			wp_send_json ( array (
					"status" => "failed",
					"msg" => "not_project_riskmanager" 
			) );
			return;
		}
		
		$riskTitle = $risk->title;
		$risk->owner = $project->projectRiskManager;
		$risk->manager = $project->projectRiskManager;
		$risk->title = $risk->title . " (Push Down Parent)";
		$risk->pushdownparent = true;
		
		// Create a new one and record the ID
		$postID = wp_insert_post ( array (
				'post_content' => $risk->description,
				'post_title' => $risk->title,
				'post_type' => 'risk',
				'post_status' => 'publish',
				'post_author' => 1 
		) );
		$risk->id = $postID;
		
		update_post_meta ( $postID, "audit", json_encode ( QRM::getAuditObject ( $current_user ) ) );
		
		$risk->riskProjectCode = get_post_meta ( $risk->projectID, "projectCode", true ) . $postID;
		
		wp_update_post ( array (
				'ID' => $risk->id,
				'post_title' => $risk->riskProjectCode . " - " . $risk->title,
				'post_type' => 'risk' 
		) );
		// The Bulk of the data is held in the post's meta data
		update_post_meta ( $postID, "riskdata", json_encode ( $risk ) );
		
		// Key Data for searching etc
		update_post_meta ( $postID, "projectID", $risk->projectID );
		update_post_meta ( $postID, "risProjectCode", $risk->riskProjectCode );
		update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
		update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
		update_post_meta ( $postID, "project", $project->post_title );
		update_post_meta ( $postID, "pushdownparent", true );
		
		// Update the count for riskd for the impacted project
		$args = array (
				'post_type' => 'risk',
				'posts_per_page' => - 1,
				'meta_key' => 'projectID',
				'meta_value' => $risk->projectID 
		);
		
		$the_query = new WP_Query ( $args );
		update_post_meta ( $risk->projectID, "numberofrisks", $the_query->found_posts );
		
		$myposts = get_posts ( array (
				'posts_per_page' => - 1,
				'post_type' => 'riskproject',
				'post_parent' => $risk->projectID 
		) );
		// Restore the Original Title
		$risk->title = $riskTitle;
		$children = array ();
		foreach ( $myposts as $post ) :
			setup_postdata ( $post );
			array_push ( $children, QRM::newPushDownChild ( $risk, $post->ID ) );
			if ($risk->type == 2) {
				QRM::recurseChildren ( $risk, $post->ID, $children );
			}
		endforeach
		;
		$risk->children = $children;
		$risk->title = $risk->title . " (Push Down Parent)";
		update_post_meta ( $risk->id, "riskdata", json_encode ( $risk ) );
		
		wp_send_json ( $risk );
	}
	static function recurseChildren($risk, $projectID, &$children) {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$myposts = get_posts ( array (
				'posts_per_page' => - 1,
				'post_type' => 'riskproject',
				'post_parent' => $projectID 
		) );
		foreach ( $myposts as $post ) :
			setup_postdata ( $post );
			array_push ( $children, QRM::newPushDownChild ( $risk, $post->ID ) );
			QRM::recurseChildren ( $risk, $post->ID, $children );
		endforeach
		;
	}
	static function newPushDownChild($parent, $projectID) {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		$risk = clone $parent;
		$project = json_decode ( get_post_meta ( $projectID, "projectdata", true ) );
		
		$risk->pushdownparent = false;
		$risk->pushdownchild = true;
		$risk->owner = $project->projectRiskManager;
		$risk->manager = $project->projectRiskManager;
		$risk->projectID = $projectID;
		$risk->parentRiskID = $parent->id;
		$risk->parentRiskProjectCode = $parent->riskProjectCode;
		$risk->title = $risk->title . " (Parent Risk = " . $parent->riskProjectCode . ")";
		
		// Create a new one and record the ID
		$postID = wp_insert_post ( array (
				'post_content' => $risk->description,
				'post_title' => $risk->title,
				'post_type' => 'risk',
				'post_status' => 'publish',
				'post_author' => 1 
		) );
		$risk->id = $postID;
		
		update_post_meta ( $postID, "audit", json_encode ( QRM::getAuditObject ( $current_user ) ) );
		
		$risk->riskProjectCode = get_post_meta ( $risk->projectID, "projectCode", true ) . $postID;
		
		wp_update_post ( array (
				'ID' => $risk->id,
				'post_title' => $risk->riskProjectCode . " - " . $risk->title,
				'post_type' => 'risk' 
		) );
		// The Bulk of the data is held in the post's meta data
		update_post_meta ( $postID, "riskdata", json_encode ( $risk ) );
		
		// Key Data for searching etc
		update_post_meta ( $postID, "projectID", $risk->projectID );
		update_post_meta ( $postID, "riskProjectCode", $risk->riskProjectCode );
		update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
		update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
		update_post_meta ( $postID, "project", $project->post_title );
		update_post_meta ( $postID, "pushdownchild", true );
		
		// Update the count for riskd for the impacted project
		$args = array (
				'post_type' => 'risk',
				'posts_per_page' => - 1,
				'meta_key' => 'projectID',
				'meta_value' => $risk->projectID 
		);
		
		$the_query = new WP_Query ( $args );
		update_post_meta ( $risk->projectID, "numberofrisks", $the_query->found_posts );
		
		// Include in reporting tables
		WPQRM_Model_Risk::replace ( $risk );
		
		return $risk->id;
	}
	static function saveRisk() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		
		$postdata = file_get_contents ( "php://input" );
		$risk = json_decode ( $postdata );
		
		// Stuff which is held elsewhere
		unset ( $risk->audit );
		unset ( $risk->incidents );
		unset ( $risk->reviews );
		
		$project = json_decode ( get_post_meta ( $risk->projectID, "projectdata", true ) );
		
		if ($risk->manager == - 1 || $risk->manager == "") {
			if (in_array ( $current_user->ID, $project->managersID )) {
				$risk->manager = $current_user->ID;
			} else {
				$risk->manager = $project->projectRiskManager;
			}
		}
		if ($risk->owner == - 1 || $risk->owner == "") {
			if (in_array ( $current_user->ID, $project->ownersID )) {
				$risk->owner = $current_user->ID;
			} else {
				$risk->owner = $project->projectRiskManager;
			}
		}
		$postID = null;
		
		if (! empty ( $risk->id )) {
			// Update the existing post
			$post ['ID'] = $risk->id;
			
			$pushdown = get_post_meta ( $risk->id, "pushdownchild", true );
			$pushdownparent = get_post_meta ( $risk->id, "pushdownparent", true );
			if ($pushdown || $pushdownparent) {
				// its a push down child, so preserve the original title
				$origrisk = json_decode ( get_post_meta ( $risk->id, "riskdata", true ) );
				$risk->title = $origrisk->title;
			}
			
			// Make sure the user is authorised to save the risk
			$projectRiskManagerID = get_post_meta ( $risk->projectID, "projectRiskManagerID", true );
			$owner = get_post_meta ( $risk->id, "owner", true );
			$manager = get_post_meta ( $risk->id, "manager", true );
			if (! ($current_user->ID == $projectRiskManagerID || in_array ( $current_user->ID, $project->ownersID ) || in_array ( $current_user->ID, $project->managersID ))) {
				wp_send_json ( array (
						"msg" => "You are not authorised to make changes to this risk" 
				) );
				exit ( 0 );
			}
			
			wp_update_post ( array (
					'ID' => $risk->id,
					'post_content' => $risk->description,
					'post_title' => $risk->title,
					'post_status' => 'publish',
					'post_type' => 'risk',
					'post_author' => 1 
			) );
			$postID = $risk->id;
		} else {
			// Create a new one and record the ID
			$postID = wp_insert_post ( array (
					'post_content' => $risk->description,
					'post_title' => $risk->title,
					'post_type' => 'risk',
					'post_status' => 'publish',
					'post_author' => 1 
			) );
			$risk->id = $postID;
			
			update_post_meta ( $postID, "audit", json_encode ( QRM::getAuditObject ( $current_user ), JSON_HEX_QUOT ) );
		}
		$risk->riskProjectCode = get_post_meta ( $risk->projectID, "projectCode", true ) . $postID;
		wp_update_post ( array (
				'ID' => $risk->id,
				'post_title' => $risk->riskProjectCode . " - " . $risk->title,
				'post_type' => 'risk' 
		) );
		// The Bulk of the data is held in the post's meta data
		update_post_meta ( $postID, "riskdata", json_encode ( $risk, JSON_HEX_QUOT ) );
		
		// Key Data for searching etc
		update_post_meta ( $postID, "projectID", $risk->projectID );
		update_post_meta ( $postID, "riskProjectCode", $risk->riskProjectCode );
		update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
		update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
		update_post_meta ( $postID, "manager", get_user_by ( "id", $risk->manager )->data->display_name );
		update_post_meta ( $postID, "ownerID", $risk->owner );
		update_post_meta ( $postID, "managerID", $risk->manager );
// 		update_post_meta ( $postID, "project", $project->post_title );
		
		// Update the count for riskd for the impacted project
		$args = array (
				'post_type' => 'risk',
				'posts_per_page' => - 1,
				'meta_key' => 'projectID',
				'meta_value' => $risk->projectID 
		);
		
		$the_query = new WP_Query ( $args );
		update_post_meta ( $risk->projectID, "numberofrisks", $the_query->found_posts );
		
		// Add any comments to the returned object
		$risk->comments = get_comments ( array (
				'post_id' => $postID 
		) );
		$risk->attachments = get_children ( array (
				"post_parent" => $postID,
				"post_type" => "attachment" 
		) );
		$risk->audit = json_decode ( get_post_meta ( $postID, "audit", true ) );
		
		// Save the risk to the regularised table
		WPQRM_Model_Risk::replace ( $risk );
		QRM::getRisk($risk->id);
	}
	static function initReportData() {
		QRM::initReportDataInternal ();
		wp_send_json ( array (
				"msg" => "Initialising Reporting Tables Completed" 
		) );
	}
	static function initReportDataInternal() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $post;
		
		$args = array (
				'post_type' => 'riskproject',
				'posts_per_page' => - 1,
				'orderby' => "ID",
				'order' => 'ASC' 
		);
		$the_query = new WP_Query ( $args );
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$project = json_decode ( get_post_meta ( $post->ID, "projectdata", true ) );
			WPQRM_Model_Project::replace ( $project );
		endwhile
		;
		
		$args = array (
				'post_type' => 'risk',
				'posts_per_page' => - 1 
		);
		
		$the_query = new WP_Query ( $args );
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			
			$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
			
			WPQRM_Model_Risk::replace ( $risk );
		endwhile
		;
		
		$args = array (
				'post_type' => 'incident',
				'posts_per_page' => - 1 
		);
		
		$the_query = new WP_Query ( $args );
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			
			$incident = json_decode ( get_post_meta ( $post->ID, "incidentdata", true ) );
			
			WPQRM_Model_Incident::replace ( $incident );
		endwhile
		;
		
		$args = array (
				'post_type' => 'incident',
				'posts_per_page' => - 1 
		);
		
		$the_query = new WP_Query ( $args );
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			
			$review = json_decode ( get_post_meta ( $post->ID, "reviewdata", true ) );
			
			WPQRM_Model_Review::replace ( $review );
		endwhile
		;
		
		return;
	}
	static function saveProject() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest") {
			wp_send_json ( QRM::getGuestObject () );
			return;
		}
		
		$postdata = file_get_contents ( "php://input" );
		$project = json_decode ( $postdata );
		
		if ($project->parent_id != 0) {
			$parent_project = json_decode ( get_post_meta ( $project->parent_id, "projectdata", true ) );
			$project->matrix = $parent_project->matrix;
		}
		
		$postID = null;
		
		if (! empty ( $project->id ) && $project->id > 0) {
			// Update the existing post
			$oldProject = json_decode ( get_post_meta ( $project->id, "projectdata", true ) );
			
			$post ['ID'] = $project->id;
			wp_update_post ( array (
					'ID' => $project->id,
					'post_content' => $project->description,
					'post_title' => $project->title,
					'post_status' => 'publish',
					'post_type' => 'riskproject',
					'post_parent' => $project->parent_id 
			) );
			$postID = $project->id;
			
			if ($oldProject->projectCode != $project->projectCode) {
				// Update the riskProjectCode of all the risks
				QRM::updateRiskProjectCodes ( $project->id, $project->projectCode );
			}
		} else {
			// Create a new one and record the ID
			$postID = wp_insert_post ( array (
					'post_content' => $project->description,
					'post_title' => $project->title,
					'post_type' => 'riskproject',
					'post_status' => 'publish',
					'post_author' => $user_ID,
					'post_parent' => $project->parent_id 
			) );
			$project->id = $postID;
		}
		
		// Fix up any category or objective IDs (negatives ID are used to handle new IDs
		$objID = intval ( get_option ( "qrm_objective_id" ) );
		
		foreach ( $project->objectives as &$obj ) {
			$obj->projectID = $project->id;
			if ($obj->id < 0) {
				$origID = $obj->id;
				$obj->id = $objID ++;
				foreach ( $project->objectives as $obj2 ) {
					if ($obj2->parentID == $origID) {
						$obj2->parentID = $obj->id;
					}
				}
			}
		}
		update_option ( "qrm_objective_id", $objID );
		
		$catID = intval ( get_option ( "qrm_category_id" ) );
		
		foreach ( $project->categories as &$cat ) {
			$cat->projectID = $project->id;
			if ($cat->id < 0) {
				$origID = $cat->id;
				$cat->id = $catID ++;
				foreach ( $project->categories as $cat2 ) {
					if ($cat2->parentID == $origID) {
						$cat2->parentID = $cat->id;
					}
				}
			}
		}
		update_option ( "qrm_category_id", $catID );
		
		// The Bulk of the data is held in the post's meta data
		update_post_meta ( $postID, "projectdata", json_encode ( $project ) );
		
		// Fill in all the other meta data
		update_post_meta ( $postID, "projectRiskManager", get_user_by ( "id", $project->projectRiskManager )->display_name );
		update_post_meta ( $postID, "projectRiskManagerID", $project->projectRiskManager );
		update_post_meta ( $postID, "projectCode", $project->projectCode );
		update_post_meta ( $postID, "projectTitle", $project->title );
		update_post_meta ( $postID, "maxProb", $project->matrix->maxProb );
		update_post_meta ( $postID, "maxImpact", $project->matrix->maxImpact );
		
		// Update number of risk
		// Update the count for riskd for the impacted project
		$args = array (
				'post_type' => 'risk',
				'posts_per_page' => - 1,
				'meta_key' => 'projectID',
				'meta_value' => $postID 
		);
		
		$the_query = new WP_Query ( $args );
		update_post_meta ( $postID, "numberofrisks", $the_query->found_posts );
		
		add_post_meta ( $postID, "riskIndex", 10, true );
		
		QRM::updateChildProjects ( $postID );
		
		WPQRM_Model_Project::replace ( $project );
		QRM::getProjects ();
	}
	static function updateChildProjects($parentID) {
		if ($parentID == null) {
			return;
		}
		
		$parent_project = json_decode ( get_post_meta ( $parentID, "projectdata", true ) );
		
		global $post;
		$the_query = new WP_Query ( 'post_type=riskproject&post_parent=' . $parentID );
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$project = json_decode ( get_post_meta ( $post->ID, "projectdata", true ) );
			$project->matrix = $parent_project->matrix;
			update_post_meta ( $post->ID, "maxProb", $project->matrix->maxProb );
			update_post_meta ( $post->ID, "maxImpact", $project->matrix->maxImpact );
			update_post_meta ( $project->id, "projectdata", json_encode ( $project ) );
			QRM::updateChildProjects ( $project->id );
		endwhile
		;
	}
	static function reindexRiskCount() {
		$my_query = new WP_Query ( 'post_type=riskproject&post_status=publish&posts_per_page=-1' );
		if ($my_query->have_posts ()) {
			while ( $my_query->have_posts () ) {
				$my_query->the_post ();
				
				$args = array (
						'post_type' => 'risk',
						'post_status' => 'publish',
						'posts_per_page' => 5000,
						'meta_query' => array (
								array (
										'key' => 'projectID',
										'value' => $my_query->post->ID,
										'compare' => '=' 
								)
								 
						) 
				);
				$the_query = new WP_Query ( $args );
				update_post_meta ( $my_query->post->ID, "numberofrisks", $the_query->found_posts );
			}
		}
	}
	static function updateRiskProjectCodes($projectID, $projectCode) {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$args = array (
				'posts_per_page' => - 1,
				'meta_key' => 'projectID',
				'meta_value' => $projectID,
				'post_type' => 'risk' 
		);
		global $wpdb;
		foreach ( get_posts ( $args ) as $post ) {
			$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
			$risk->riskProjectCode = $projectCode . $post->ID;
			
			wp_update_post ( array (
					'ID' => $post->ID,
					'post_title' => $risk->riskProjectCode . " - " . $risk->title,
					'post_type' => 'risk' 
			) );
			
			update_post_meta ( $post->ID, "riskdata", json_encode ( $risk ) );
			update_post_meta ( $post->ID, "riskProjectCode", $risk->riskProjectCode );
			// Update the tables used for reporting
			$sql = sprintf ( 'UPDATE %s SET riskProjectCode = %%s WHERE id = %%s', $wpdb->prefix . 'qrm_risk' );
			$wpdb->query ( $wpdb->prepare ( $sql, $risk->riskProjectCode, $post->ID ) );
		}
	}
	static function getAuditObject($current_user) {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$auditObjEval = new stdObject ();
		$auditObjEval->auditComment = "Risk Entered";
		$auditObjEval->auditDate = date ( "M j, Y" );
		$auditObjEval->auditPerson = $current_user->ID;
		
		$auditObjIdent = new stdObject ();
		$auditObjIdent->auditComment = "Risk Entered";
		$auditObjIdent->auditDate = date ( "M j, Y" );
		$auditObjIdent->auditPerson = $current_user->ID;
		
		$auditObj = new stdObject ();
		$auditObj->auditIdent = $auditObjIdent;
		$auditObj->auditEval = $auditObjEval;
		
		return $auditObj;
	}
}
// final class QRMReportData {
// 	static function incidentJSON($incidentIDs = array()) {
// 		global $post;
		
// 		$args = array (
// 				'post_type' => 'incident',
// 				'post_per_page' => - 1 
// 		);
// 		if (sizeof ( $incidentIDs ) > 0) {
// 			$args ['post__in'] = $incidentIDs;
// 		}
		
// 		$riskIDs = array ();
		
// 		$the_query = new WP_Query ( $args );
// 		$incidents = array ();
// 		while ( $the_query->have_posts () ) :
// 			$the_query->the_post ();
// 			$incident = json_decode ( get_post_meta ( $post->ID, "incidentdata", true ) );
// 			$incident->comments = get_comments ( array (
// 					'post_id' => $post->ID 
// 			) );
// 			if ($incident->risks != null)
// 				$riskIDs = array_merge ( $riskIDs, $incident->risks );
// 			array_push ( $incidents, $incident );
// 		endwhile
// 		;
		
// 		$risks = array ();
// 		if (sizeof ( $riskIDs ) > 0) {
// 			$args = array (
// 					'post_type' => 'risk',
// 					'post__in' => $riskIDs,
// 					'posts_per_page' => - 1 
// 			);
			
// 			$the_query = new WP_Query ( $args );
// 			while ( $the_query->have_posts () ) :
// 				$the_query->the_post ();
// 				$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
// 				// $risk->incidents = get_post_meta ( $post->ID, "incident" );
// 				$risk->projectID = get_post_meta ( $post->ID, "projectID", true );
// 				$risk->ID = $post->ID;
// 				if ($risk->primcat == 0) {
// 					$risk->primcat = new stdObject ();
// 				}
// 				if ($risk->seccat == 0) {
// 					$risk->seccat = new stdObject ();
// 				}
// 				array_push ( $risks, $risk );
// 			endwhile
// 			;
// 		}
		
// 		$export = new stdObject ();
// 		$export->risks = $risks;
		
// 		$export->incidents = $incidents;
		
// 		$users = array ();
// 		$user_query = new WP_User_Query ( array (
// 				'fields' => "all" 
// 		) );
// 		foreach ( $user_query->results as $user ) {
// 			$u = new stdObject ();
// 			$u->id = $user->data->ID;
// 			$u->display_name = $user->data->display_name;
// 			$u->user_email = $user->data->user_email;
// 			array_push ( $users, $u );
// 		}
		
// 		$export->users = $users;
		
// 		return $export;
// 	}
// 	static function reviewJSON($reviewIDs = array()) {
// 		global $post;
		
// 		$args = array (
// 				'post_type' => 'review',
// 				'post_per_page' => - 1 
// 		);
// 		if (sizeof ( $reviewIDs ) > 0) {
// 			$args ['post__in'] = $reviewIDs;
// 		}
		
// 		$riskIDs = array ();
		
// 		$the_query = new WP_Query ( $args );
// 		$reviews = array ();
// 		while ( $the_query->have_posts () ) :
// 			$the_query->the_post ();
// 			$review = json_decode ( get_post_meta ( $post->ID, "reviewdata", true ) );
// 			if ($review->risks != null)
// 				$riskIDs = array_merge ( $riskIDs, $review->risks );
// 			array_push ( $reviews, $review );
// 		endwhile
// 		;
		
// 		$args = array (
// 				'post_type' => 'risk',
// 				'post__in' => $riskIDs,
// 				'posts_per_page' => - 1 
// 		);
		
// 		$risks = array ();
		
// 		if (sizeof ( $riskIDs ) > 0) {
// 			$the_query = new WP_Query ( $args );
// 			while ( $the_query->have_posts () ) :
// 				$the_query->the_post ();
// 				$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
// 				$risk->projectID = get_post_meta ( $post->ID, "projectID", true );
// 				$risk->ID = $post->ID;
// 				if ($risk->primcat == 0) {
// 					$risk->primcat = new stdObject ();
// 				}
// 				if ($risk->seccat == 0) {
// 					$risk->seccat = new stdObject ();
// 				}
// 				array_push ( $risks, $risk );
// 			endwhile
// 			;
// 		}
		
// 		$export = new stdObject ();
// 		$export->risks = $risks;
// 		$export->reviews = $reviews;
		
// 		$users = array ();
// 		$user_query = new WP_User_Query ( array (
// 				'fields' => "all" 
// 		) );
// 		foreach ( $user_query->results as $user ) {
// 			$u = new stdObject ();
// 			$u->id = $user->data->ID;
// 			$u->display_name = $user->data->display_name;
// 			$u->user_email = $user->data->user_email;
// 			array_push ( $users, $u );
// 		}
		
// 		$export->users = $users;
		
// 		return $export;
// 	}
// 	static function getReportRiskJSON() {
// 		$config = json_decode ( file_get_contents ( "php://input" ) );
		
// 		$ids = array ();
// 		array_push ( $ids, $config->projectID );
		
// 		if ($config->childProjects) {
// 			$children = QRM::get_project_children ( $config->projectID );
// 			foreach ( $children as $child ) {
// 				array_push ( $ids, $child->ID );
// 			}
// 		}
		
// 		// Pass the project array and the risk ID array
// 		// commonJSON will apply the non null condition
// 		$export = QRM::commonJSON ( $ids, $config->risks, $config->basicsOnly );
// 		QRM::exportMetadata ( $export );
		
// 		if ($config->reportID != null) {
			
// 			$response = wp_remote_post ( $export->reportServerURL . "/report", array (
// 					'method' => 'POST',
// 					'timeout' => 60,
// 					'body' => array (
// 							'reportData' => json_encode ( $export ),
// 							'action' => "execute_report",
// 							'reportEmail' => $export->userEmail,
// 							'reportID' => $config->reportID,
// 							'sessionToken' => $export->sessionToken 
// 					) 
// 			) );
// 			if (is_wp_error ( $response )) {
// 				wp_send_json ( $export );
// 			} else {
// 				echo "OK";
// 				wp_die ();
// 			}
// 		} else {
// 			wp_send_json ( $export );
// 		}
// 	}
// 	static function getReportIncidentJSON() {
// 		$config = json_decode ( file_get_contents ( "php://input" ) );
// 		$export = QRMReportData::incidentJSON ( $config->incidents );
// 		QRM::exportMetadata ( $export );
// 		if ($config->reportID != null) {
// 			$body = array (
// 					'reportData' => json_encode ( $export ),
// 					'action' => "execute_report",
// 					'reportEmail' => $export->userEmail,
// 					'reportID' => $config->reportID,
// 					'sessionToken' => $export->sessionToken 
// 			);
// 			$response = wp_remote_post ( $export->reportServerURL . "/report", array (
// 					'method' => 'POST',
// 					'timeout' => 60,
// 					'body' => $body 
// 			) );
// 			if (is_wp_error ( $response )) {
// 				wp_send_json ( $export );
// 			} else {
// 				echo "OK";
// 				wp_die ();
// 			}
// 		} else {
// 			wp_send_json ( $export );
// 		}
// 	}
// 	static function getReportReviewJSON() {
// 		$config = json_decode ( file_get_contents ( "php://input" ) );
		
// 		$export = QRMReportData::reviewJSON ( $config->reviews );
// 		QRM::exportMetadata ( $export );
// 		if ($config->reportID != null) {
// 			$body = array (
// 					'reportData' => json_encode ( $export ),
// 					'action' => "execute_report",
// 					'reportEmail' => $export->userEmail,
// 					'reportID' => $config->reportID,
// 					'sessionToken' => $export->sessionToken 
// 			);
// 			$response = wp_remote_post ( $export->reportServerURL . "/report", array (
// 					'method' => 'POST',
// 					'timeout' => 60,
// 					'body' => $body 
// 			) );
// 			if (is_wp_error ( $response )) {
// 				wp_send_json ( $export );
// 			} else {
// 				echo "OK";
// 				wp_die ();
// 			}
// 		} else {
// 			wp_send_json ( $export );
// 		}
// 	}
// }
final class QRM_AutoUpdate {
	private $current_version;
	private $update_path;
	private $plugin_slug;
	private $slug;
	private $license_user;
	private $license_key;
	public function __construct($current_version, $update_path, $plugin_slug, $license_user = '', $license_key = '') {
		// Set the class public variables
		$this->current_version = $current_version;
		$this->update_path = $update_path;
		// Set the License
		$this->license_user = $license_user;
		$this->license_key = $license_key;
		// Set the Plugin Slug
		$this->plugin_slug = $plugin_slug;
		list ( $t1, $t2 ) = explode ( '/', $plugin_slug );
		$this->slug = str_replace ( '.php', '', $t2 );
		// define the alternative API for updating checking
		add_filter ( 'pre_set_site_transient_update_plugins', array (
				&$this,
				'check_update' 
		) );
		// Define the alternative response for information checking
		add_filter ( 'plugins_api', array (
				&$this,
				'check_info' 
		), 10, 3 );
	}
	public function check_update($transient) {
		if (empty ( $transient->checked )) {
			return $transient;
		}
		// Get the remote version
		$remote_version = $this->getRemote_version ();
		// If a newer version is available, add the update
		if (version_compare ( $this->current_version, $remote_version->new_version, '<' )) {
			$obj = new stdClass ();
			$obj->slug = $this->slug;
			$obj->new_version = $remote_version->new_version;
			$obj->url = $remote_version->url;
			$obj->plugin = $this->plugin_slug;
			$obj->package = $remote_version->package;
			$transient->response [$this->plugin_slug] = $obj;
		}
		return $transient;
	}
	public function check_info($false, $action, $arg) {
		if (isset ( $arg->slug ) && $arg->slug === $this->slug) {
			$information = $this->getRemote_information ();
			return $information;
		}
		return false;
	}
	public function getRemote_version() {
		$request = wp_remote_post ( $this->update_path . "&fn=version" );
		if (! is_wp_error ( $request ) || wp_remote_retrieve_response_code ( $request ) === 200) {
			return unserialize ( $request ['body'] );
		}
		return false;
	}
	public function getRemote_information() {
		$request = wp_remote_post ( $this->update_path . "&fn=info", $params );
		if (! is_wp_error ( $request ) || wp_remote_retrieve_response_code ( $request ) === 200) {
			return unserialize ( $request ['body'] );
		}
		return false;
	}
	public function getRemote_license() {
		$request = wp_remote_post ( $this->update_path . "&fn=license" );
		if (! is_wp_error ( $request ) || wp_remote_retrieve_response_code ( $request ) === 200) {
			return unserialize ( $request ['body'] );
		}
		return false;
	}
}
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
		add_filter ( 'user_has_cap', array (
				$this,
				'qrm_prevent_riskproject_parent_deletion' 
		), 10, 3 );
		add_filter ( 'manage_riskproject_posts_columns', array (
				$this,
				'qrm_riskproject_table_head' 
		) );
		add_filter ( 'manage_risk_posts_columns', array (
				$this,
				'qrm_risk_table_head' 
		) );
		
		add_action ( 'manage_riskproject_posts_custom_column', array (
				$this,
				'qrm_riskproject_table_content' 
		), 10, 2 );
		add_action ( 'manage_risk_posts_custom_column', array (
				$this,
				'qrm_risk_table_content' 
		), 10, 2 );
		
		add_action ( 'admin_menu', array (
				$this,
				'qrm_admin_menu_config' 
		) );
		add_action ( 'admin_init', array (
				$this,
				'redirect_about_page' 
		), 1 );
		
		add_action ( 'add_meta_boxes', array (
				$this,
				'riskproject_meta_boxes' 
		) );
		
		add_filter ( 'upload_mimes', array (
				$this,
				'add_custom_mime_types' 
		) );
		add_filter ( 'single_template', array (
				$this,
				'get_custom_post_type_template' 
		) );
		add_action ( 'plugins_loaded', array (
				'PageTemplater',
				'get_instance' 
		) );
		
		add_action ( 'init', array (
				$this,
				'register_types' 
		) );
		add_action ( 'init', array (
				$this,
				'qrm_init_options' 
		) );
		add_action ( 'init', array (
				$this,
				'qrm_scripts_styles' 
		) );
		add_action ( 'init', array (
				$this,
				'qrm_init_user_cap' 
		) );
		add_action ( 'init', array (
				$this,
				'qrm_start_session' 
		) );
		
		add_action ( 'trashed_post', array (
				$this,
				'qrm_trashed_post' 
		) );
		
		add_filter ( 'plugin_row_meta', array (
				$this,
				'plugin_row_meta' 
		), 10, 2 );
		add_filter ( 'plugin_action_links_' . plugin_basename ( __FILE__ ), array (
				$this,
				'qrm_add_plugin_action_links' 
		) );
		
		add_option ( "qrm_siteName", "Quay Risk Manager Site" );
		add_option ( "qrm_reportServerURL", "http://report.quaysystems.com.au:8080" );
		add_option ( "qrm_siteID", "Unregistered Site" );
		add_option ( "qrm_siteKey", "Unregistered Site" );
		add_option ( "qrm_displayUser", "userlogin" );
		
		$this->activate_au ();
		
		register_activation_hook ( __FILE__, array (
				$this,
				'qrmplugin_activate' 
		) );
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
		new QRM_AutoUpdate ( $plugin_current_version, $plugin_remote_path, $plugin_slug );
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
		require_once 'qrm-activate.php';
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
		add_action ( "wp_ajax_getProject", array (
				'QRM',
				"getProject" 
		) );
		add_action ( "wp_ajax_getAllRisks", array (
				'QRM',
				"getAllRisks" 
		) );
		add_action ( "wp_ajax_getProjects", array (
				'QRM',
				"getProjects" 
		) );
		add_action ( "wp_ajax_getSiteUsersCap", array (
				'QRM',
				"getSiteUsersCap" 
		) );
		add_action ( "wp_ajax_getSiteUsers", array (
				'QRM',
				"getSiteUsers" 
		) );
		add_action ( "wp_ajax_saveSiteUsers", array (
				'QRM',
				"saveSiteUsers" 
		) );
		add_action ( "wp_ajax_saveProject", array (
				'QRM',
				"saveProject" 
		) );
		add_action ( "wp_ajax_getAllProjectRisks", array (
				'QRM',
				"getAllProjectRisks" 
		) );
		add_action ( "wp_ajax_getRisk", array (
				'QRM',
				"getRisk" 
		) );
		add_action ( "wp_ajax_saveRisk", array (
				'QRM',
				"saveRisk" 
		) );
		add_action ( "wp_ajax_updateRisksRelMatrix", array (
				'QRM',
				"updateRisksRelMatrix" 
		) );
		add_action ( "wp_ajax_getAttachments", array (
				'QRM',
				"getAttachments" 
		) );
		add_action ( "wp_ajax_uploadFile", array (
				'QRM',
				"uploadFile" 
		) );
		add_action ( "wp_ajax_uploadImport", array (
				'QRM',
				"uploadImport" 
		) );
		add_action ( "wp_ajax_getCurrentUser", array (
				'QRM',
				"getCurrentUser" 
		) );
		add_action ( "wp_ajax_saveRankOrder", array (
				'QRM',
				"saveRankOrder" 
		) );
		add_action ( "wp_ajax_registerAudit", array (
				'QRM',
				"registerAudit" 
		) );
		add_action ( "wp_ajax_getAllIncidents", array (
				'QRM',
				"getAllIncidents" 
		) );
		add_action ( "wp_ajax_getIncident", array (
				'QRM',
				"getIncident" 
		) );
		add_action ( "wp_ajax_saveIncident", array (
				'QRM',
				"saveIncident" 
		) );
		add_action ( "wp_ajax_addGeneralComment", array (
				'QRM',
				"addGeneralComment" 
		) );
		add_action ( "wp_ajax_getAllReviews", array (
				'QRM',
				"getAllReviews" 
		) );
		add_action ( "wp_ajax_getReview", array (
				'QRM',
				"getReview" 
		) );
		add_action ( "wp_ajax_saveReview", array (
				'QRM',
				"saveReview" 
		) );
		add_action ( "wp_ajax_nopriv_login", array (
				'QRM',
				"login" 
		) );
		add_action ( "wp_ajax_login", array (
				'QRM',
				"login" 
		) );
		add_action ( "wp_ajax_logout", array (
				'QRM',
				"logout" 
		) );
		add_action ( "wp_ajax_checkSession", array (
				'QRM',
				"checkSession" 
		) );
		add_action ( "wp_ajax_newPushDown", array (
				'QRM',
				"newPushDown" 
		) );
		add_action ( "wp_ajax_installSample", array (
				'QRM',
				"installSample" 
		) );
		add_action ( "wp_ajax_installSampleProjects", array (
				'QRM',
				"installSampleProjects" 
		) );
		add_action ( "wp_ajax_removeSample", array (
				'QRM',
				"removeSample" 
		) );
		add_action ( "wp_ajax_downloadJSON", array (
				'QRM',
				"downloadJSON" 
		) );
		add_action ( "wp_ajax_getJSON", array (
				'QRM',
				"downloadJSON" 
		) );
// 		add_action ( "wp_ajax_getReportRiskJSON", array (
// 				'QRMReportData',
// 				"getReportRiskJSON" 
// 		) );
// 		add_action ( "wp_ajax_getReportIncidentJSON", array (
// 				'QRMReportData',
// 				"getReportIncidentJSON" 
// 		) );
// 		add_action ( "wp_ajax_getReportReviewJSON", array (
// 				'QRMReportData',
// 				"getReportReviewJSON" 
// 		) );
		add_action ( "wp_ajax_getReportOptions", array (
				'QRM',
				"getReportOptions" 
		) );
		add_action ( "wp_ajax_saveReportOptions", array (
				'QRM',
				"saveReportOptions" 
		) );
		add_action ( "wp_ajax_getServerMeta", array (
				'QRM',
				"getServerMeta" 
		) );
		add_action ( "wp_ajax_createDummyRiskEntry", array (
				'QRM',
				"createDummyRiskEntry" 
		) );
		add_action ( "wp_ajax_createDummyRiskEntryMultiple", array (
				'QRM',
				"createDummyRiskEntryMultiple" 
		) );
		add_action ( "wp_ajax_reindexRiskCount", array (
				'QRM',
				"reindexRiskCount" 
		) );
		add_action ( "wp_ajax_saveDisplayUser", array (
				'QRM',
				"saveDisplayUser" 
		) );
		add_action ( "wp_ajax_getDisplayUser", array (
				'QRM',
				"getDisplayUser" 
		) );
		add_action ( "wp_ajax_getReports", array (
				'QRM',
				"getReports" 
		) );
		add_action ( "wp_ajax_updateReport", array (
				'QRM',
				"updateReport" 
		) );
		add_action ( "wp_ajax_deleteReport", array (
				'QRM',
				"deleteReport" 
		) );
		add_action ( "wp_ajax_initReportData", array (
				'QRM',
				"initReportData" 
		) );
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
					;
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
	public function register_types() {
		/*
		 * Project Post Type
		 */
		$labels = array (
				'name' => __ ( 'Risk Projects', 'riskproject-post-type' ),
				'singular_name' => __ ( 'Risk Project', 'riskproject-post-type' ),
				'add_new' => __ ( 'Add Risk Project', 'riskproject-post-type' ),
				'add_new_item' => __ ( 'Add Risk Project', 'riskproject-post-type' ),
				'edit_item' => __ ( 'Edit Risk Project', 'riskproject-post-type' ),
				'new_item' => __ ( 'New Risk Project', 'riskproject-post-type' ),
				'view_item' => __ ( 'View Risk Project', 'riskproject-post-type' ),
				'search_items' => __ ( 'Search Risk Project', 'riskproject-post-type' ),
				'not_found' => __ ( 'No risk projects found', 'riskproject-post-type' ),
				'not_found_in_trash' => __ ( 'No risk projects in the trash', 'riskproject-post-type' ) 
		);
		
		$supports = array (
				'page-attributes' 
		)
		;
		
		$args = array (
				'labels' => $labels,
				'supports' => $supports,
				'public' => true,
				'capability_type' => 'post',
				'rewrite' => array (
						'slug' => 'riskproject' 
				), // Permalinks format
				'menu_position' => 30,
				'hierarchical' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'menu_icon' => 'dashicons-portfolio' 
		);
		
		$args = apply_filters ( 'riskproject_post_type_args', $args );
		register_post_type ( 'riskproject', $args );
		/*
		 * Risk Post Type
		 */
		
		$labels = array (
				'name' => __ ( 'Risks', 'risk-post-type' ),
				'singular_name' => __ ( 'Risk', 'risk-post-type' ),
				'search_items' => __ ( 'Search Risks', 'risk-post-type' ),
				'not_found' => __ ( 'No risks found', 'risk-post-type' ),
				'not_found_in_trash' => __ ( 'No risks in the trash', 'risk-post-type' ) 
		);
		
		$supports = array (
				'comments',
				'title' 
		);
		
		$args = array (
				'labels' => $labels,
				'supports' => $supports,
				'public' => true,
				'capability_type' => 'post',
				'rewrite' => array (
						'slug' => 'risk' 
				), // Permalinks format
				'menu_position' => 30,
				'menu_icon' => 'dashicons-sos',
				// 'show_in_menu' => 'edit.php?post_type=riskproject',
				'show_in_menu' => true,
				'capabilities' => array (
						'create_posts' => false 
				) // Removes support for the "Add New" function
,
				'map_meta_cap' => true 
		) // Allows editting and trashing which above disables
;
		
		$args = apply_filters ( 'risk_post_type_args', $args );
		register_post_type ( 'risk', $args );
		
		/*
		 * Incident Post Type
		 */
		
		$labels = array (
				'name' => __ ( 'Risk Incidents', 'incident-post-type' ),
				'singular_name' => __ ( 'Risk Incident', 'incident-post-type' ),
				'search_items' => __ ( 'Search Incidents', 'incident-post-type' ),
				'not_found' => __ ( 'No incidents found', 'incident-post-type' ),
				'not_found_in_trash' => __ ( 'No incidents in the trash', 'incident-post-type' ) 
		);
		
		$supports = array (
				'comments',
				'title' 
		);
		
		$args = array (
				'labels' => $labels,
				'supports' => $supports,
				'public' => true,
				'capability_type' => 'post',
				'rewrite' => array (
						'slug' => 'incident' 
				), // Permalinks format
				'menu_position' => 30,
				'menu_icon' => 'dashicons-sos',
				'show_in_menu' => true,
				'capabilities' => array (
						'create_posts' => false 
				) // Removes support for the "Add New" function
,
				'map_meta_cap' => true 
		) // Allows editting and trashing which above disables
;
		
		$args = apply_filters ( 'incident_post_type_args', $args );
		register_post_type ( 'incident', $args );
		
		/*
		 * Review Post Type
		 */
		
		$labels = array (
				'name' => __ ( 'Risk Reviews', 'review-post-type' ),
				'singular_name' => __ ( 'Risk Review', 'review-post-type' ),
				'search_items' => __ ( 'Search Reviews', 'review-post-type' ),
				'not_found' => __ ( 'No reviews found', 'review-post-type' ),
				'not_found_in_trash' => __ ( 'No reviews in the trash', 'review-post-type' ) 
		);
		
		$supports = array (
				'comments',
				'title' 
		);
		
		$args = array (
				'labels' => $labels,
				'supports' => $supports,
				'public' => true,
				'capability_type' => 'post',
				'rewrite' => array (
						'slug' => 'review' 
				), // Permalinks format
				'menu_position' => 30,
				'menu_icon' => 'dashicons-sos',
				// 'show_in_menu' => 'edit.php?post_type=riskproject',
				'show_in_menu' => true,
				'capabilities' => array (
						'create_posts' => false 
				) // Removes support for the "Add New" function
,
				'map_meta_cap' => true 
		) // Allows editting and trashing which above disables
;
		
		$args = apply_filters ( 'review_post_type_args', $args );
		register_post_type ( 'review', $args );
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
function QRMMaster() {
	return QuayRiskManager::instance ();
}

$GLOBALS ['quayriskmanager'] = QRMMaster ();
