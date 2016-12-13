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
		$export->reportParam1 = get_option ( "qrm_reportParam1" );
		$export->reportParam2 = get_option ( "qrm_reportParam2" );
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
		require_once 'QRMUtil.php';
		return QRMUtil::commonJSON($projectIDs, $riskIDs, $basicsOnly);
	}
	static function checkSampleUser() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		require 'QRMSample.php';
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
		
		$options->reportParam1 = get_option ( "qrm_reportParam1" );
		$options->reportParam2 = get_option ( "qrm_reportParam2" );
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
		
		update_option ( "qrm_reportServerURL", $options->url );
		update_option ( "qrm_reportParam1", $options->reportParam1 );
		update_option ( "qrm_reportParam2", $options->reportParam2 );
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
		if (isset($incident->risks)) {
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
		$incident->date = $incident->incidentDate;
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
			$post ['ID'] = $review->id;
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
		if (isset($review->risks)) {
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
		
		require_once 'QRMSample.php';
		
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
				$r->currentProb = $r->treatedProb;
				$r->currentImpact = $r->treatedImpact;
				$r->currentTolerance = $r->treatedTolerance;
			} else {
				$r->currentProb = $r->inherentProb;
				$r->currentImpact = $r->inherentImpact;
				$r->currentTolerance = $r->inherentTolerance;
			}
			
			update_post_meta ( $risk->riskID, "riskdata", json_encode ( $r ) );
			
			WPQRM_Model_Risk::replace ( $r );
		}
		wp_send_json ( array (
				"status" => "OK"
		) );
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
		wp_send_json ( array (
				"status" => "OK"
		) );
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
		
		if (isset($data->childProjects)) {
			if ($data->childProjects){
			$children = QRM::get_project_children ( $projectID );
			foreach ( $children as $child ) {
				array_push ( $ids, $child->ID );
			}
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
		WPQRM_Model_Risk::replace ( $risk );
		$risk = json_decode ( get_post_meta ( $postID, "riskdata", true ) );
		
		// Key Data for searching etc
		update_post_meta ( $postID, "projectID", $risk->projectID );
		update_post_meta ( $postID, "risProjectCode", $risk->riskProjectCode );
		update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
		update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
//		update_post_meta ( $postID, "project", $project->post_title );
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
		// Key Data for searhing etc
		update_post_meta ( $postID, "projectID", $risk->projectID );
		update_post_meta ( $postID, "riskProjectCode", $risk->riskProjectCode );
		update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
		update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
//		update_post_meta ( $postID, "project", $project->post_title );
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


require_once 'QuayRiskManager.php';

$GLOBALS ['quayriskmanager'] = QuayRiskManager::instance ();
