<?php
/*** 
 * Plugin Name: Quay Risk Manager
 * Plugin URI: http://www.quaysystems.com.au 
 * Description: Mangage your organisations risks. Quay Risk Manager enables you to identify, evaluate, mitigate and manage your risks. Watermarked report in PDF format are produced using a webservice. For non watermaked reports contact <a href="http://www.quaysystems.com.au">Quay Systems Consulting</a>   
 * Version: 1.4.0
 * Requires at least: 4.2.1
 * Tested up to: 4.3
 * Author: <a href="http://www.quaysystems.com.au">Quay Systems Consulting</a>
 * License: GPLv2 or later
 */

// Register Custom Post Type
if (! defined ( 'WPINC' )) {
	die ();
}

define ( 'QRM_VERSION', '1.4.0' );
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
		return QRMUtil::commonJSON($projectIDs, $riskIDs, $basicsOnly);
	}
	static function checkSampleUser() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
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
	static function prepareAnalytics(){
		$params = json_decode ( file_get_contents ( "php://input" ) );
		$msg = new stdObject ();
		
		$msg->msg = "Prepare Analytics";
		$msg->params = $params;
		
		wp_send_json ( $msg );
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
		$risks = $review->risks;
		WPQRM_Model_Review::replace ( $review );
		$review->risks = $risks;
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
		if ($report->description == null){
			$report->description = "Description of Report";
		}
		
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
			$risk->enteredDate = date ( "M j, Y" );
			$risk ->enteredBy = $current_user->ID;
			
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


		//		$this->activate_au ();

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

		QRMUtil::dropReportTables();
		QRMActivate::activate ();

	}
	public function qrmplugin_deactivate() {
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
		add_action ( "wp_ajax_prepareAnalytics", array ('QRM',"prepareAnalytics") );
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

class QRMSample {
	static $effectiveness = array (
			"Ad Hoc",
			"Repeatable",
			"Defined",
			"Managed",
			"Optimising"
	);
	static $contribution = array (
			"Minimal",
			"Minor",
			"Significant",
			"Major"
	);
	static $emptyRiskJSON = '{"title":"Title of Risk","riskProjectCode":" New Risk ","description":"Description","cause":"Cause","consequence":"Consequence","owner":-1,"manager":-1,"inherentProb":5.5,"inherentImpact":5.5,"treatedProb":1.5,"treatedImpact":1.5,"impRep":true,"impSafety":true,"impEnviron":true,"impCost":true,"impTime":true,"impSpec":true,"treatAvoid":true,"treatRetention":true,"treatTransfer":true,"treatMinimise":true,"treated":false,"summaryRisk":false,"useCalContingency":false,"useCalProb":false,"likeType":4,"likeAlpha":1,"likeT":365,"likePostType":4,"likePostAlpha":1,"likePostT":365,"estContingency":0,"start":"2015-08-30T11:41:38.391Z","end":"2015-09-30T11:41:38.391Z","primcat":0,"seccat":0,"mitigation":{"mitPlanSummary":"Summary of the Mitigation Plan","mitPlanSummaryUpdate":"Update to the Summary of the Mitigation Plan","mitPlan":[]},"response":{"respPlanSummary":"Summary of the Response Plan","respPlanSummaryUpdate":"Update to the Summary of the Mitigation Plan","respPlan":[]},"controls":[],"objectives":{},"x":153,"y":16.999999999999996,"x1":17,"y1":153,"treatedTolerance":"1","inherentTolerance":"5","currentProb":5.5,"currentImpact":5.5,"currentTolerance":"5","inherentAbsProb":90,"treatedAbsProb":10,"comments":[],"projectID":3046,"attachments":[]}';
	static function getSampleProject() {
		$m = new stdClass ();

		$m->maxImpact = 5;
		$m->maxProb = 5;
		$m->tolString = "1123312234223443345534455555555555555555555555555555555555555555";
		$m->probVal1 = 20;
		$m->probVal2 = 40;
		$m->probVal3 = 60;
		$m->probVal4 = 80;
		$m->probVal5 = 100;
		$m->probVal6 = 100;
		$m->probVal7 = 100;
		$m->probVal8 = 100;

		$p = new stdObject ();
		$p->id = - 1;
		$p->title = "Project Title";
		$p->description = "Description of the Project";
		$p->useAdvancedConsequences = false;
		$p->projectCode = "";
		$p->ownersID = array ();
		$p->managersID = array ();
		$p->usersID = array ();
		$p->matrix = $m;
		$p->inheritParentCategories = true;
		$p->inheritParentObjectives = true;
		$p->categories = array ();
		$p->objectives = array ();
		$p->parent_id = 0;

		return $p;
	}
	static function probFromMatrix($qprob, $mat) {
		$lowerLimit = 0.0;
		$upperLimit = 0.0;

		switch (intval ( floor ( $qprob ) )) {
			case 1 :
				$lowerlimit = 0.0;
				$upperlimit = $mat->probVal1;
				break;
			case 2 :
				$lowerlimit = $mat->probVal1;
				$upperlimit = $mat->probVal2;
				break;
			case 3 :
				$lowerlimit = $mat->probVal2;
				$upperlimit = $mat->probVal3;
				break;
			case 4 :
				$lowerlimit = $mat->probVal3;
				$upperlimit = $mat->probVal4;
				break;
			case 5 :
				$lowerlimit = $mat->probVal4;
				$upperlimit = $mat->probVal5;
				break;
			case 6 :
				$lowerlimit = $mat->probVal5;
				$upperlimit = $mat->probVal6;
				break;
			case 7 :
				$lowerlimit = $mat->probVal6;
				$upperlimit = $mat->probVal7;
				break;
			case 8 :
				$lowerlimit = $mat->probVal7;
				$upperlimit = $mat->probVal8;
				break;
		}

		$prob = $lowerlimit + ($upperlimit - $lowerlimit) * ($qprob - floor ( $qprob ));
		return $prob;
	}
	static function installImport($filename) {
		$import = json_decode ( file_get_contents ( $filename ) );
		if (QRMSample::processImport ( $import, false )) {
			return "Imported Successfully";
		}
	}
	static function installSample() {
		$import = json_decode ( file_get_contents ( __DIR__ . "/QRMData.json" ) );
		if (QRMSample::processImport ( $import, true )) {
			return "Sample Data Installed";
		}
	}
	static function processImport($import, $sample) {
		$projIDMap = array ();
		$objIDMap = array ();
		$catIDMap = array ();
		$riskIDMap = array ();
		$reviewIDMap = array ();
		$incidentIDMap = array ();

		$projIDMap [0] = 0;
		$objIDMap [0] = 0;
		$riskIDMap [0] = 0;
		$catIDMap [0] = 0;
		$incidentIDMap [0] = 0;
		$reviewIDMap [0] = 0;

		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();

		foreach ( $import->projects as $project ) {
				
			if ($sample) {
				$project->title = $project->title . "**";
			}
			$project->riskProjectManager = $current_user->ID;
			$project->ownersID = array (
					$current_user->ID
			);
			$project->mangersID = array (
					$current_user->ID
			);
			$project->usersID = array (
					$current_user->ID
			);
				
			$postID = wp_insert_post ( array (
					'post_content' => $project->description,
					'post_title' => $project->title,
					'post_type' => 'riskproject',
					'post_status' => 'publish',
					'post_author' => $user_ID
			) );
				
			$projIDMap [$project->id] = $postID;
			$project->id = $postID;
				
			$objID = intval ( get_option ( "qrm_objective_id" ) );
				
			foreach ( $project->objectives as &$obj ) {
				$obj->projectID = $project->id;
				$origID = $obj->id;
				$obj->id = $objID ++;
				$objIDMap [$origID] = $obj->id;
				foreach ( $project->objectives as $obj2 ) {
					if ($obj2->parentID == $origID) {
						$obj2->parentID = $obj->id;
					}
				}
			}
			update_option ( "qrm_objective_id", $objID );
				
			$catID = intval ( get_option ( "qrm_category_id" ) );
				
			foreach ( $project->categories as &$cat ) {
				$cat->projectID = $project->id;
				$origID = $cat->id;
				$cat->id = $catID ++;
				$catIDMap [$origID] = $cat->id;
				foreach ( $project->categories as $cat2 ) {
					if ($cat2->parentID == $origID) {
						$cat2->parentID = $cat->id;
					}
				}
			}
			update_option ( "qrm_category_id", $catID );
				
			// // The Bulk of the data is held in the post's meta data
			update_post_meta ( $postID, "projectdata", json_encode ( $project ) );
				
			// Fill in all the other meta data
			update_post_meta ( $postID, "projectRiskManager", get_user_by ( "id", $project->projectRiskManager )->display_name );
			update_post_meta ( $postID, "projectCode", $project->projectCode );
			update_post_meta ( $postID, "projectTitle", $project->title );
			update_post_meta ( $postID, "maxProb", $project->matrix->maxProb );
			update_post_meta ( $postID, "maxImpactb", $project->matrix->maxImpact );
			if ($sample == true)
				update_post_meta ( $postID, "sampleqrmdata", "sample" );
		}
		foreach ( $projIDMap as $oldValue => $newValue ) {
			$project = json_decode ( get_post_meta ( $newValue, "projectdata", true ) );
			if (isset ( $project )) {
				if ($project->parent_id != 0)
					$project->parent_id = $projIDMap [$project->parent_id];
					update_post_meta ( $newValue, "projectdata", json_encode ( $project ) );
					wp_update_post ( array (
							'ID' => $newValue,
							'post_parent' => $project->parent_id
					) );
			}
		}

		foreach ( $import->risks as $risk ) {
				
			if ($sample)
				$risk->title = $risk->title . "**";
				$risk->manager = $current_user->ID;
				$risk->owner = $current_user->ID;
				$risk->projectID = $projIDMap [$risk->projectID];
					
				if ($risk->primcat != null) {
					$risk->primcat->id = $catIDMap [$risk->primcat->id];
					$risk->primcat->parentID = $catIDMap [$risk->primcat->parentID];
					$risk->primcat->projectID = $projIDMap [$risk->primcat->projectID];
				}
				if ($risk->seccat != null) {
					$risk->seccat->id = $catIDMap [$risk->seccat->id];
					$risk->seccat->parentID = $catIDMap [$risk->seccat->parentID];
					$risk->seccat->projectID = $projIDMap [$risk->seccat->projectID];
				}
					
				if ($risk->objectives != null) {
					$newObjectiveObject = new stdObject ();
					foreach ( $risk->objectives as $key => $value ) {
						$newObjectiveObject->$objIDMap [$key] = true;
					}
					$risk->objectives = $newObjectiveObject;
				}
					
				$postID = wp_insert_post ( array (
						'post_content' => $risk->description,
						'post_title' => $risk->title,
						'post_type' => 'risk',
						'post_status' => 'publish',
						'post_author' => $user_ID
				) );
					
				$riskIDMap [$risk->id] = $postID;
				$risk->id = $postID;
					
				$risk->riskProjectCode = get_post_meta ( $risk->projectID, "projectCode", true ) . $postID;
				wp_update_post ( array (
						'ID' => $risk->id,
						'post_title' => $risk->riskProjectCode . " - " . $risk->title,
						'post_type' => 'risk'
				) );
					
				// // The Bulk of the data is held in the post's meta data
				update_post_meta ( $postID, "riskdata", json_encode ( $risk ) );
				if ($sample == true)
					update_post_meta ( $postID, "sampleqrmdata", "sample" );
					update_post_meta ( $postID, "audit", json_encode ( $risk->audit ) );
					update_post_meta ( $postID, "projectID", $risk->projectID );
					update_post_meta ( $postID, "riskProjectCode", $risk->riskProjectCode );
					update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
					update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
					update_post_meta ( $postID, "manager", get_user_by ( "id", $risk->manager )->data->display_name );
					update_post_meta ( $postID, "ownerID", $risk->owner );
					update_post_meta ( $postID, "managerID", $risk->manager );
					// update_post_meta ( $postID, "project", $project->post_title );
						
					if ($risk->reviews != null) {
						foreach ( $risk->reviews as $reviewID ) {
							add_post_meta ( $postID, 'review', $reviewID );
						}
					}
					if ($risk->incidents != null) {
						foreach ( $risk->incidents as $incidentID ) {
							add_post_meta ( $postID, 'incident', $incidentID );
						}
					}
						
					// Update the count for risks for the impacted project
					$args = array (
							'post_type' => 'risk',
							'posts_per_page' => - 1,
							'meta_key' => 'projectID',
							'meta_value' => $risk->projectID
					);
						
					$the_query = new WP_Query ( $args );
					update_post_meta ( $risk->projectID, "numberofrisks", $the_query->found_posts );
		}

		foreach ( $import->reviews as $review ) {
			$review->responsible = $current_user->ID;
			if ($sample)
				$review->title = $review->title . "**";
				$postID = wp_insert_post ( array (
						'post_content' => $review->description,
						'post_title' => $review->title,
						'post_status' => 'publish',
						'post_type' => 'review',
						'post_author' => $current_user->ID
				) );
					
				$reviewIDMap [$review->id] = $postID;
				$review->id = $postID;
				$review->reviewCode = "REVIEW-" . $review->id;
				if ($sample == true)
					update_post_meta ( $postID, "sampleqrmdata", "sample" );
						
					wp_update_post ( array (
							'ID' => $review->id,
							'post_title' => $review->reviewCode . " - " . $review->title,
							'post_type' => 'review'
					) );
						
					if ($review->risks != null) {
						$newRiskArray = array ();
						foreach ( $review->risks as $riskID ) {
							array_push ( $newRiskArray, $riskIDMap [$riskID] );
						}
						$review->risks = $newRiskArray;
					}
						
					if ($review->riskComments != null) {
						foreach ( $review->riskComments as $comment ) {
							$comment->riskID = $riskIDMap [$comment->riskID];
						}
					}
						
					update_post_meta ( $postID, "reviewdata", json_encode ( $review ) );
					update_post_meta ( $postID, "reviewtitle", $review->reviewCode . " - " . $review->title );
		}

		// Fix up the risk references to the reviews
		foreach ( $reviewIDMap as $oldID => $newID ) {
			$args = array (
					'posts_per_page' => - 1,
					'meta_key' => 'review',
					'meta_value' => $oldID,
					'post_type' => 'risk'
			);
			foreach ( get_posts ( $args ) as $post ) {
				update_post_meta ( $post->ID, 'review', intval ( $newID ), $oldID );
			}
		}

		foreach ( $import->incidents as $incident ) {
				
			$incident->reportedby = $current_user->ID;
			if ($sample)
				$incident->title = $incident->title . "**";
				$postID = wp_insert_post ( array (
						'post_content' => $incident->description,
						'post_title' => $incident->title,
						'post_status' => 'publish',
						'post_type' => 'incident',
						'post_author' => $current_user->ID
				) );
				$incidentIDMap [$incident->id] = $postID;
				$incident->id = $postID;
				$incident->incidentCode = "INCIDENT-" . $incident->id;
				if ($sample == true)
					update_post_meta ( $postID, "sampleqrmdata", "sample" );
						
					wp_update_post ( array (
							'ID' => $incident->id,
							'post_title' => $incident->incidentCode . " - " . $incident->title,
							'post_type' => 'incident'
					) );
						
					if ($incident->risks != null) {
						$newRiskArray = array ();
						foreach ( $incident->risks as $riskID ) {
							array_push ( $newRiskArray, $riskIDMap [$riskID] );
						}
						$incident->risks = $newRiskArray;
					}
						
					update_post_meta ( $postID, "incidentdata", json_encode ( $incident ) );
					update_post_meta ( $postID, "incidenttitle", $incident->incidentCode . " - " . $incident->title );
		}
		// Fix up the risk references to the incident
		foreach ( $incidentIDMap as $oldID => $newID ) {
			$args = array (
					'posts_per_page' => - 1,
					'meta_key' => 'incident',
					'meta_value' => $oldID,
					'post_type' => 'risk'
			);
			foreach ( get_posts ( $args ) as $post ) {
				update_post_meta ( $post->ID, 'incident', intval ( $newID ), $oldID );
			}
		}
		return true;
	}
	static function removeSample($sampleOnly) {
		$args = array (
				'posts_per_page' => - 1
		);

		if ($sampleOnly) {
			$args ['meta_key'] = "sampleqrmdata";
			$args ['meta_value'] = "sample";
		}

		$args ['post_type'] = "risk";
		foreach ( get_posts ( $args ) as $post ) {
			if ((get_post_meta ( $post->ID, "sampleqrmdata", true ) == "sample" && $sampleOnly == true) || $sampleOnly == false)
				wp_delete_post ( $post->ID, true );
		}
		$args ['post_type'] = "review";
		foreach ( get_posts ( $args ) as $post ) {
			if ((get_post_meta ( $post->ID, "sampleqrmdata", true ) == "sample" && $sampleOnly == true) || $sampleOnly == false)
				wp_delete_post ( $post->ID, true );
		}
		$args ['post_type'] = "incident";
		foreach ( get_posts ( $args ) as $post ) {
			if ((get_post_meta ( $post->ID, "sampleqrmdata", true ) == "sample" && $sampleOnly == true) || $sampleOnly == false)
				wp_delete_post ( $post->ID, true );
		}
		$args ['post_type'] = "riskproject";
		foreach ( get_posts ( $args ) as $post ) {
			if ((get_post_meta ( $post->ID, "sampleqrmdata", true ) == "sample" && $sampleOnly == true) || $sampleOnly == false)
				wp_delete_post ( $post->ID, true );
		}
		$args ['post_type'] = "reports";
		foreach ( get_posts ( $args ) as $post ) {
			if ((get_post_meta ( $post->ID, "sampleqrmdata", true ) == "sample" && $sampleOnly == true) || $sampleOnly == false)
				wp_delete_post ( $post->ID, true );
		}

		QRMSample::deleteReportTables();
		QRM::initReportDataInternal ();
		return ($sampleOnly) ? "Sample Quay Risk Manager Data Removed" : "All Quay Risk Manager Data Removed";
	}
	static function deleteReportTables(){
		global $wpdb;

		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_controls' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_mitplan' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_respplan' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_projectowners' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_projectmanagers' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_projectusers' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_objective' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_incidentrisks' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_category' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_reviewrisks' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_reviewriskcomments' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_reports' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_projectproject' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_audit' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_riskobjectives' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_analytics' );
		$wpdb->query ( "SET FOREIGN_KEY_CHECKS = 0;" );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_review' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_incident' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_risk' );
		$wpdb->query ( "TRUNCATE " . $wpdb->prefix . 'qrm_project' );
		$wpdb->query ( "SET FOREIGN_KEY_CHECKS = 1;" );

	}
	static function make_seed() {
		list ( $usec, $sec ) = explode ( ' ', microtime () );
		return ( float ) $sec + (( float ) $usec * 100000);
	}
	static function createDummyRiskEntryMultiple($topParent = null, $min, $max, $sample = false) {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
			global $user_identity, $user_email, $user_ID, $current_user, $user_login;
			get_currentuserinfo ();

			if ($user_login == "guest") {
				wp_send_json ( QRM::getGuestObject () );
				return;
			}

			$args = array (
					'post_type' => 'riskproject',
					'posts_per_page' => - 1
			);
			global $post;
			$the_query = new WP_Query ( $args );
			$projects = array ();

			srand ( QRMSample::make_seed () );

			while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$project = json_decode ( get_post_meta ( $post->ID, "projectdata", true ) );
			$idx = rand ( $min, $max );
			for($i = 0; $i < $idx; $i ++) {
				$risk = json_decode ( QRMSample::$emptyRiskJSON );
				$risk->projectID = $post->ID;
				QRMSample::createDummyRiskEntryCommon ( $risk, $project, $topParent, $sample );
			}
			endwhile
			;

			return "OK";
	}
	static function createDummyRiskEntry() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
			global $user_identity, $user_email, $user_ID, $current_user, $user_login;
			get_currentuserinfo ();

			if ($user_login == "guest") {
				wp_send_json ( QRM::getGuestObject () );
				return;
			}

			$risk = json_decode ( file_get_contents ( "php://input" ) );
			$project = json_decode ( get_post_meta ( $risk->projectID, "projectdata", true ) );

			return QRMSample::createDummyRiskEntryCommon ( $risk, $project );
	}
	static function createDummyRiskEntryCommon($risk, $project, $topParent = null, $sample = false) {
		global $user_identity, $user_email, $user_ID, $current_user;
		srand ( QRMSample::make_seed () );

		require_once 'LoremIpsumGenerator.php';

		$lorem = new LoremIpsumGenerator ();
		$now = time ();
		$month = 60 * 60 * 24 * 30;
		$day = 60 * 60 * 24;
		$past = (rand ( 0, 1 ) == 1) ? 1 : - 1;
		$start = $now + $past * rand ( 0, 300 ) * $day;

		$risk->owner = $project->ownersID [array_rand ( $project->ownersID )];
		$risk->manager = $project->managersID [array_rand ( $project->managersID)];

		if ($risk->owner == null) {
			$risk->owner = $project->projectRiskManager;
		}
		if ($risk->manager == null) {
			$risk->manager = $project->projectRiskManager;
		}
		$risk->title = "**" . $lorem->getContent ( rand ( 6, 15 ), "plain", false );
		$risk->description = $lorem->getContent ( rand ( 150, 300 ), "html", false );
		$risk->cause = $lorem->getContent ( rand ( 50, 300 ), "html", false );
		$risk->consequence = $lorem->getContent ( rand ( 50, 300 ), "html", false );
		$risk->treated = rand ( 0, 1 ) == 1;
		$risk->impRep = rand ( 0, 1 ) == 1;
		$risk->impSafety = rand ( 0, 1 ) == 1;
		$risk->impEnviron = rand ( 0, 1 ) == 1;
		$risk->impCost = rand ( 0, 1 ) == 1;
		$risk->impTime = rand ( 0, 1 ) == 1;
		$risk->impSpec = rand ( 0, 1 ) == 1;
		$risk->treatAvoid = rand ( 0, 1 ) == 1;
		$risk->treatRetention = rand ( 0, 1 ) == 1;
		$risk->treatTransfer = rand ( 0, 1 ) == 1;
		$risk->treatMinimise = rand ( 0, 1 ) == 1;
		$risk->mitigation->mitPlanSummary = $lorem->getContent ( rand ( 100, 200 ), "html", false );
		$risk->mitigation->mitPlanSummaryUpdate = $lorem->getContent ( rand ( 100, 200 ), "html", false );
		$risk->estContingency = rand ( 1000, 5000 );
		$risk->start = date ( "Y-m-d", $start );
		$risk->end = date ( "Y-m-d", $start + rand ( 2, 24 ) * $month );
		$risk->enteredDate = date ( "M j, Y" );
		$risk->enteredBy = $current_user->ID;

		$risk->mitigation->mitPlan = array ();
		$s = rand ( 1, 6 );
		for($i = 0; $i < $s; $i ++) {
			$step = new stdObject ();
			$step->description = $lorem->getContent ( rand ( 20, 40 ), 'plain', false );
			$step->complete = rand ( 0, 100 );
			$step->cost = rand ( 50, 5000 );
			$step->person = $risk->manager;
			$past = (rand ( 0, 1 ) == 1) ? 1 : - 1;
			$step->due = date ( "Y-m-d", $start + $past * rand ( 2, 12 ) * $month );
			array_push ( $risk->mitigation->mitPlan, $step );
		}
		$risk->response->respPlanSummary = $lorem->getContent ( rand ( 100, 200 ), "html", false );
		$risk->response->respPlanSummaryUpdate = $lorem->getContent ( rand ( 100, 200 ), "html", false );

		$risk->response->respPlan = array ();
		$s = rand ( 2, 4 );
		for($i = 0; $i < $s; $i ++) {
			$step = new stdObject ();
			$step->description = $lorem->getContent ( rand ( 20, 40 ), 'plain', false );
			$step->cost = rand ( 50, 5000 );
			$step->person = $risk->manager;
			array_push ( $risk->response->respPlan, $step );
		}

		$p = rand ( 2, 5 );
		$i = rand ( 3, 5 );
		$risk->inherentProb = $p + rand ( 5, 95 ) / 100;
		$risk->inherentImpact = $i + rand ( 5, 95 ) / 100;
		$risk->treatedProb = rand ( 1, $p - 1 ) + rand ( 5, 95 ) / 100;
		$risk->treatedImpact = rand ( 1, $i - 1 ) + rand ( 5, 95 ) / 100;
		$risk->inherentAbsProb = QRMSample::probFromMatrix ( $risk->inherentProb, $project->matrix );
		$risk->treatedAbsProb = QRMSample::probFromMatrix ( $risk->treatedProb, $project->matrix );

		$index = (floor ( $risk->treatedProb - 1 )) * $project->matrix->maxImpact + floor ( $risk->treatedImpact - 1 );
		$index = min ( $index, strlen ( $project->matrix->tolString ) - 1 );

		$risk->treatedTolerance = intval ( substr ( $project->matrix->tolString, $index, 1 ) );

		$index = (floor ( $risk->inherentProb - 1 )) * $project->matrix->maxImpact + floor ( $risk->inherentImpact - 1 );
		$index = min ( $index, strlen ( $project->matrix->tolString ) - 1 );

		$risk->inherentTolerance = intval ( substr ( $project->matrix->tolString, $index, 1 ) );

		if ($risk->treated) {
			$risk->currentImpact = $risk->treatedImpact;
			$risk->currentProb = $risk->treatedProb;
			$risk->currentTolerance = $risk->treatedTolerance;
		} else {
			$risk->currentImpact = $risk->inherentImpact;
			$risk->currentProb = $risk->inherentProb;
			$risk->currentTolerance = $risk->inherentTolerance;
		}

		if ($topParent != null) {
			$project->categories = array_merge ( $project->categories, $topParent->categories );
		}
		$primCats = array_filter ( $project->categories, function ($cat) {
			return $cat->primCat;
		} );

			if (count ( $primCats ) > 0) {
				$risk->primcat = $primCats [array_rand ( $primCats, 1 )];
				$secCats = array_filter ( $project->categories, function ($cat) use ($risk) {
					if ($cat->primCat)
						return false;
						if ($cat->parentID == $risk->primcat->id)
							return true;
							return false;
				} );
					if (count ( $secCats ) > 0) {
						$risk->seccat = $secCats [array_rand ( $secCats, 1 )];
					}
			}

			$risk->controls = array ();
			$s = rand ( 2, 5 );
			for($i = 0; $i < $s; $i ++) {
				$c = new stdObject ();
				$c->description = $lorem->getContent ( rand ( 5, 10 ), "plain", false );
				$c->contribution = QRMSample::$contribution [array_rand ( QRMSample::$contribution, 1 )];
				$c->effectiveness = QRMSample::$effectiveness [array_rand ( QRMSample::$effectiveness, 1 )];
				array_push ( $risk->controls, $c );
			}

			$o = array ();
			foreach ( $project->objectives as $obj ) {
				$o [strval ( $obj->id )] = (rand ( 1, 4 ) % 4 == 0) ? true : false;
			}
			$risk->objectives = ( object ) $o;

			$postID = wp_insert_post ( array (
					'post_content' => $risk->description,
					'post_title' => $risk->title,
					'post_type' => 'risk',
					'post_status' => 'publish',
					'post_author' => 1
			) );
			$risk->id = $postID;

			$risk->riskProjectCode = get_post_meta ( $risk->projectID, "projectCode", true ) . $postID;
			wp_update_post ( array (
					'ID' => $risk->id,
					'post_title' => $risk->riskProjectCode . " - " . $risk->title,
					'post_type' => 'risk'
			) );

			update_post_meta ( $postID, "riskdata", json_encode ( $risk, JSON_HEX_QUOT ) );
			// Key Data for searching etc
			update_post_meta ( $postID, "projectID", $risk->projectID );
			update_post_meta ( $postID, "riskProjectCode", $risk->riskProjectCode );
			update_post_meta ( $postID, "audit", json_encode ( QRM::getAuditObject ( get_user_by ( "id", $risk->manager ) ), JSON_HEX_QUOT ) );
			update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
			update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
			update_post_meta ( $postID, "manager", get_user_by ( "id", $risk->manager )->data->display_name );
			update_post_meta ( $postID, "ownerID", $risk->owner );
			update_post_meta ( $postID, "managerID", $risk->manager );
			if ($sample == true)
				update_post_meta ( $postID, "sampleqrmdata", "sample" );
					
				// Update the count for riskd for the impacted project
				$args = array (
						'post_type' => 'risk',
						'posts_per_page' => - 1,
						'meta_key' => 'projectID',
						'meta_value' => $risk->projectID
				);

				$the_query = new WP_Query ( $args );
				update_post_meta ( $risk->projectID, "numberofrisks", $the_query->found_posts );

				$auditObjEval = new stdObject ();
				$auditObjEval->auditComment = "Risk Entered";
				$auditObjEval->auditDate = date ( "M j, Y" );
				$auditObjEval->auditPerson = $current_user->ID;
				$auditObjEval->auditType = 0;

				$auditObjIdent = new stdObject ();
				$auditObjIdent->auditComment = "Risk Entered";
				$auditObjIdent->auditDate = date ( "M j, Y" );
				$auditObjIdent->auditPerson = $current_user->ID;
				$auditObjIdent->auditType = 3;

				WPQRM_Model_Risk::replace ( $risk );
				// 		WPQRM_Model_Audit::replace ( $auditObjEval );
				// 		WPQRM_Model_Audit::replace ( $auditObjIdent );

				return $risk->riskProjectCode;
	}
	static function createSampleProjects() {
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();

		$minmax = json_decode ( file_get_contents ( "php://input" ) );

		$user_query = new WP_User_Query ( array (
				'fields' => 'all'
		) );
		$userSummary = array ();
		array_push ( $userSummary, $user_ID );
		foreach ( $user_query->results as $user ) {
			if (isset ( $user->caps ["risk_admin"] ) || isset ( $user->caps ["risk_user"] )) {
				array_push ( $userSummary, $user->ID );
			}
		}

		$cat = array ();
		array_push ( $cat, json_decode ( '{"title": "Vendor", "id": -1, "primCat": true, "parentID": 0, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Performance", "id": -2, "primCat": false, "parentID": -1, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Value", "id": -3, "primCat": false, "parentID": -1, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Billing", "id": -4, "primCat": false, "parentID": -1, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Delivery", "id": -5, "primCat": false, "parentID": -1, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Regulatory", "id": -6, "primCat": true, "parentID": 0, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Federal", "id": -7, "primCat": false, "parentID": -6, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "State", "id": -8, "primCat": false, "parentID": -6, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "ASIC", "id": -9, "primCat": false, "parentID": -6, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Environmental", "id": -10, "primCat": false, "parentID": -6, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Customer", "id": -11, "primCat": true, "parentID": 0, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Satisfaction", "id": -12, "primCat": false, "parentID": -11, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Relationship", "id": -13, "primCat": false, "parentID": -11, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Billing", "id": -14, "primCat": false, "parentID": -11, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Creit", "id": -15, "primCat": false, "parentID": -11, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Employee", "id": -16, "primCat": true, "parentID": 0, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Satisfaction", "id": -17, "primCat": false, "parentID": -16, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Relationship", "id": -18, "primCat": false, "parentID": -16, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Award", "id": -19, "primCat": false, "parentID": -16, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Productivity", "id": -20, "primCat": false, "parentID": -16, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Financial", "id": -21, "primCat": true, "parentID": 0, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Cashflow", "id": -22, "primCat": false, "parentID": -21, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Treasury", "id": -23, "primCat": false, "parentID": -21, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Transperancy", "id": -24, "primCat": false, "parentID": -21, "projectID": -1}' ) );
		array_push ( $cat, json_decode ( '{"title": "Viability", "id": -25, "primCat": false, "parentID": -21, "projectID": -1}' ) );

		$p1 = QRMSample::singleProject ( "*Quay Systems", "QS", $userSummary, 0, $cat, true );
		QRMSample::singleProject ( "*Board of Directors", "BOD", $userSummary, $p1->id, null, true );
		QRMSample::singleProject ( "*Executive", "EXECE", $userSummary, $p1->id, null, true );
		$it = QRMSample::singleProject ( "*Information Technology", "IT", $userSummary, $p1->id, null, true );
		QRMSample::singleProject ( "*Information Technology Security", "ITSEC", $userSummary, $it->id, null, true );
		$itops = QRMSample::singleProject ( "*Information Technology Operations", "ITOPS", $userSummary, $it->id, null, true );
		QRMSample::singleProject ( "*Information Technology End User", "ITEUC", $userSummary, $itops->id, null, true );
		QRMSample::singleProject ( "*Information Technology Data Center", "ITDC", $userSummary, $itops->id, null, true );
		QRMSample::singleProject ( "*Information Technology Network", "ITNW", $userSummary, $itops->id, null, true );

		QRMSample::singleProject ( "*Sales", "SALE", $userSummary, $p1->id, null, true );
		QRMSample::singleProject ( "*Marketing", "MARK", $userSummary, $p1->id, null, true );
		QRMSample::singleProject ( "*Business Services", "BIZ", $userSummary, $p1->id, null, true );
		QRMSample::singleProject ( "*Human Resources", "HR", $userSummary, $p1->id, null, true );
		QRMSample::singleProject ( "*Manufacturing", "MAN", $userSummary, $p1->id, null, true );
		QRMSample::singleProject ( "*Customer Support", "CUS", $userSummary, $p1->id, null, true );

		QRMSample::createDummyRiskEntryMultiple ( $p1, $minmax [0], $minmax [1], true );

		return "Sample Data Installed";
	}
	static function singleProject($title, $id, $users, $parent = 0, $cat = null, $sample = false) {
		global $user_identity, $user_email, $user_ID, $current_user;

		$p = QRMSample::getSampleProject ();
		$p->parent_id = $parent;
		$p->title = $title;
		$p->projectCode = $id;
		$p->projectRiskManager = $current_user->ID;
		$p->ownersID = $users;
		$p->managersID = $users;
		if ($cat != null) {
			$p->categories = $cat;
		}
		$p = QRMSample::saveSampleProject ( $p, $sample );
		WPQRM_Model_Project::replace ( $p );
		return $p;
	}
	static function saveSampleProject($project, $sample = false) {
		global $user_identity, $user_email, $user_ID, $current_user;
		$postID = wp_insert_post ( array (
				'post_content' => $project->description,
				'post_title' => $project->title,
				'post_type' => 'riskproject',
				'post_status' => 'publish',
				'post_author' => $user_ID,
				'post_parent' => $project->parent_id
		) );
		$project->id = $postID;

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
		update_post_meta ( $postID, "projectCode", $project->projectCode );
		update_post_meta ( $postID, "projectTitle", $project->title );
		update_post_meta ( $postID, "maxProb", $project->matrix->maxProb );
		update_post_meta ( $postID, "maxImpactb", $project->matrix->maxImpact );
		if ($sample == true)
			update_post_meta ( $postID, "sampleqrmdata", "sample" );
				
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

			return $project;
	}
}

class QRMActivate {

	static function activate() {


		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		$wpUser = get_user_by ( "id", $current_user->ID );

		$wpUser->add_cap ( "risk_admin" );
		$wpUser->add_cap ( "risk_user" );

		$pages = get_pages ( array (
				'meta_key' => '_wp_page_template',
				'meta_value' => 'templates/qrm-type-template.php'
		) );

		if (sizeof ( $pages ) == 0) {
			// Create the page to access the application
			$postdata = array (
					'post_parent' => 0,
					'post_status' => 'publish',
					'post_title' => 'Quay Risk Manager',
					'post_name' => 'riskmanager',
					'page_template' => 'templates/qrm-type-template.php',
					'post_type' => 'page'
			);
			$pageID = wp_insert_post ( $postdata );
			update_post_meta ( $pageID, '_wp_page_template', 'templates/qrm-type-template.php' );
		}

		QRMActivate::register_types ();
		flush_rewrite_rules ();

		set_transient ( 'qrm_about_page_activated', 1, 30 );

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate () . "  ENGINE = INNODB";
		$table_name = $wpdb->prefix . 'qrm_risk';
		$risk_table_name = $wpdb->prefix . 'qrm_risk';
		$post_table_name = $wpdb->prefix . 'posts';
		$user_table_name = $wpdb->prefix . 'users';
		$comment_table_name = $wpdb->prefix . 'comments';

		QRMUtil::dropReportTables ();


		$sql = "CREATE TABLE $table_name (
		id BIGINT(20) UNSIGNED NOT NULL COMMENT 'The internal identifier of the risk. Corresponds to the WordPress post ID for the risk',
		cause TEXT COMMENT 'Textual description of the cause the risk',
		consequence TEXT COMMENT 'Textual description of the consequences the risk',
		currentImpact DOUBLE COMMENT 'The current Impact of the risk determined by the treated or untreated impact, determined by the state of the risk',
		currentProb DOUBLE COMMENT 'The current Probability of the risk determined by the treated or untreated probability, determined by the state of the risk',
		currentTolerance INT(11) COMMENT 'The current Tolerance of the risk determined by the treated or untreated tolerance, determined by the state of the risk',
		description TEXT COMMENT 'Textual description of the risk',
		end VARCHAR(255),
		enteredBy bigint(20) UNSIGNED,
		enteredDate VARCHAR(255),
		estContingency DOUBLE,
		impCost TINYINT NOT NULL DEFAULT 0,
		impEnviron TINYINT NOT NULL DEFAULT 0,
		impRep TINYINT NOT NULL DEFAULT 0,
		impSafety TINYINT NOT NULL DEFAULT 0,
		impSpec TINYINT NOT NULL DEFAULT 0,
		impTime TINYINT NOT NULL DEFAULT 0,
		inherentAbsProb DOUBLE,
		inherentImpact DOUBLE,
		inherentProb DOUBLE,
		inherentTolerance INT(11),
		likeAlpha DOUBLE,
		likePostAlpha DOUBLE,
		likePostT DOUBLE,
		likePostType DOUBLE,
		likeT DOUBLE,
		likeType DOUBLE,
		manager INT(11),
		managerName VARCHAR(255),
		matImage LONGBLOB,
		tolString VARCHAR(255),
		maxProb INT (11),
		maxImpact INT (11),
		owner INT(11),
		ownerName VARCHAR(255),
		rank INT(11) NOT NULL DEFAULT 0,
		postLikeImage LONGBLOB,
		preLikeImage LONGBLOB,
		primcatID INT(11),
		primCatName VARCHAR(255),
		projectID BIGINT(20) UNSIGNED,
		riskProjectCode VARCHAR(255) DEFAULT NULL,
		seccatID INT(11),
		secCatName VARCHAR(255),
		start VARCHAR(255) DEFAULT NULL,
		summaryRisk TINYINT NOT NULL DEFAULT 0,
		title TEXT,
		treatAvoid TINYINT NOT NULL DEFAULT 0,
		treatMinimise TINYINT NOT NULL DEFAULT 0,
		treatRetention TINYINT NOT NULL DEFAULT 0,
		treatTransfer TINYINT NOT NULL DEFAULT 0,
		treated TINYINT NOT NULL DEFAULT 0,
		treatedAbsProb DOUBLE,
		treatedImpact DOUBLE,
		treatedProb DOUBLE,
		treatedTolerance INT(11),
		type INT(11),
		pushdownparent INT(11),
		pushdownchild INT(11),
		parentRiskID INT(11),
		parentRiskProjectCode VARCHAR(30),
		useCalContingency TINYINT NOT NULL DEFAULT 0,
		useCalProb TINYINT NOT NULL DEFAULT 0,
		mitPlanSummary TEXT DEFAULT NULL,
		mitPlanSummaryUpdate TEXT DEFAULT NULL,
		respPlanSummary TEXT DEFAULT NULL,
		respPlanSummaryUpdate TEXT DEFAULT NULL,
		PRIMARY KEY (id),
		FOREIGN KEY (id)
		REFERENCES $wpdb->posts (ID)
		ON DELETE CASCADE,
		FOREIGN KEY (projectID)
		REFERENCES $wpdb->posts (ID)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_controls';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		riskID bigint(20) UNSIGNED DEFAULT NULL,
		description TEXT,
		effectiveness TEXT,
		contribution TEXT,
		PRIMARY KEY  (id),
		FOREIGN KEY (riskID)
		REFERENCES $risk_table_name (id)
		ON DELETE CASCADE ) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_analytics';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		relMatrix LONBLOB ) $charset_collate;";
		$wpdb->query ( $sql );
		
		$table_name = $wpdb->prefix . 'qrm_mitplan';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		riskID bigint(20) UNSIGNED DEFAULT NULL,
		description TEXT,
		cost DOUBLE DEFAULT NULL,
		complete DOUBLE DEFAULT NULL,
		due VARCHAR(255) DEFAULT NULL,
		person INT(11),
		PRIMARY KEY  (id),
		FOREIGN KEY (riskID)
		REFERENCES $risk_table_name (id)
		ON DELETE CASCADE ) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_respplan';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		riskID bigint(20) UNSIGNED DEFAULT NULL,
		description TEXT,
		cost DOUBLE,
		person INT(11),
		PRIMARY KEY  (id),
		FOREIGN KEY (riskID)
		REFERENCES $risk_table_name (id)
		ON DELETE CASCADE ) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_project';
		$project_table_name = $wpdb->prefix . 'qrm_project';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) UNSIGNED NOT NULL,
		title TEXT,
		description TEXT,
		projectCode VARCHAR(8),
		useAdvancedConsequences TINYINT NOT NULL DEFAULT 0,
		inheritParentCategories TINYINT NOT NULL DEFAULT 0,
		inheritParentObjectives TINYINT NOT NULL DEFAULT 0,
		parent_id INT(11),
		projectRiskManager INT(11),
		tolString TEXT DEFAULT NULL,
		maxImpact INT(11) DEFAULT NULL,
		maxProb INT(11) DEFAULT NULL,
		probVal1 INT(11) DEFAULT NULL,
		probVal2 INT(11) DEFAULT NULL,
		probVal3 INT(11) DEFAULT NULL,
		probVal4 INT(11) DEFAULT NULL,
		probVal5 INT(11) DEFAULT NULL,
		probVal6 INT(11) DEFAULT NULL,
		probVal7 INT(11) DEFAULT NULL,
		probVal8 INT(11) DEFAULT NULL,
		PRIMARY KEY  (id),
		FOREIGN KEY (id)
		REFERENCES $wpdb->posts (ID)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_projectowners';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		projectID bigint (20) UNSIGNED NOT NULL,
		ownerID bigint(20) UNSIGNED NOT NULL,
		PRIMARY KEY  (id),
		FOREIGN KEY (projectID)
		REFERENCES $project_table_name (id)
		ON DELETE CASCADE,
		FOREIGN KEY (ownerID)
		REFERENCES $wpdb->users (ID)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_projectproject';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		parentID  bigint (20) UNSIGNED NOT NULL,
		projectID  bigint (20) UNSIGNED NOT NULL,
		PRIMARY KEY  (id),
		FOREIGN KEY (projectID)
		REFERENCES $project_table_name (id)
		ON DELETE CASCADE,
		FOREIGN KEY (parentID)
		REFERENCES $project_table_name (id)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_projectmanagers';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		projectID  bigint (20) UNSIGNED NOT NULL,
		managerID bigint(20) UNSIGNED NOT NULL,
		PRIMARY KEY  (id),
		FOREIGN KEY (projectID)
		REFERENCES $project_table_name (id)
		ON DELETE CASCADE,
		FOREIGN KEY (managerID)
		REFERENCES $wpdb->users (ID)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_projectusers';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		projectID  bigint (20) UNSIGNED NOT NULL,
		userID bigint(20) UNSIGNED NOT NULL,
		PRIMARY KEY  (id),
		FOREIGN KEY (projectID)
		REFERENCES $project_table_name (id)
		ON DELETE CASCADE,
		FOREIGN KEY (userID)
		REFERENCES $wpdb->users (ID)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_objective';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		projectID  bigint (20) UNSIGNED NOT NULL,
		parentID INT(11) DEFAULT NULL,
		title TEXT,
		PRIMARY KEY  (id),
		FOREIGN KEY (projectID)
		REFERENCES $project_table_name (id)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_incident';
		$incident_table_name = $wpdb->prefix . 'qrm_incident';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) UNSIGNED NOT NULL,
		incidentDate VARCHAR(255) DEFAULT NULL,
		title TEXT,
		description TEXT,
		lessons TEXT,
		actions TEXT,
		incidentCode VARCHAR(40),
		causes TINYINT NOT NULL DEFAULT 0,
		consequences TINYINT NOT NULL DEFAULT 0,
		controls TINYINT NOT NULL DEFAULT 0,
		cost TINYINT NOT NULL DEFAULT 0,
		environment TINYINT NOT NULL DEFAULT 0,
		reputation TINYINT NOT NULL DEFAULT 0,
		safety TINYINT NOT NULL DEFAULT 0,
		spec TINYINT NOT NULL DEFAULT 0,
		evaluated TINYINT NOT NULL DEFAULT 0,
		resolved TINYINT NOT NULL DEFAULT 0,
		time TINYINT NOT NULL DEFAULT 0,
		identified TINYINT NOT NULL DEFAULT 0,
		reportedby INT(11),
		PRIMARY KEY  (id),
		FOREIGN KEY (id)
		REFERENCES $wpdb->posts (ID)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_incidentrisks';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		incidentID  bigint (20) UNSIGNED NOT NULL,
		riskID BIGINT(20) UNSIGNED NOT NULL,
		PRIMARY KEY  (id),
		FOREIGN KEY (riskID)
		REFERENCES $risk_table_name (id)
		ON DELETE CASCADE,
		FOREIGN KEY (incidentID)
		REFERENCES $incident_table_name (id)
		ON DELETE CASCADE ) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_category';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		projectID bigint (20) UNSIGNED NOT NULL,
		parentID bigint(20) UNSIGNED DEFAULT NULL,
		primCat TINYINT NOT NULL DEFAULT 0,
		title TEXT,
		PRIMARY KEY  (id),
		FOREIGN KEY (projectID)
		REFERENCES $project_table_name (id)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_review';
		$review_table_name = $wpdb->prefix . 'qrm_review';

		$sql = "CREATE TABLE $table_name (
		id bigint(20) UNSIGNED NOT NULL,
		title TEXT,
		description TEXT,
		schedDate VARCHAR(255) DEFAULT NULL,
		actualDate VARCHAR(255) DEFAULT NULL,
		reviewCode VARCHAR(255) DEFAULT NULL,
		responsible INT(11),
		notes TEXT,
		complete TINYINT NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		FOREIGN KEY (id)
		REFERENCES $wpdb->posts (ID)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_reviewrisks';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		reviewID bigint(20) UNSIGNED NOT NULL,
		riskID bigint(20) UNSIGNED NOT NULL,
		PRIMARY KEY  (id),
		FOREIGN KEY (reviewID)
		REFERENCES $review_table_name (id)
		ON DELETE CASCADE,
		FOREIGN KEY (riskID)
		REFERENCES $risk_table_name (id)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_reviewriskcomments';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		reviewID bigint(20) UNSIGNED NOT NULL,
		riskID bigint(20) UNSIGNED  NOT NULL,
		comment TEXT,
		PRIMARY KEY  (id),
		FOREIGN KEY (reviewID)
		REFERENCES $review_table_name (id)
		ON DELETE CASCADE,
		FOREIGN KEY (riskID)
		REFERENCES $risk_table_name (id)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_riskobjectives';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		objectiveID INT(11) NOT NULL,
		riskID bigint(20) UNSIGNED  NOT NULL,
		PRIMARY KEY  (id),
		FOREIGN KEY (riskID)
		REFERENCES $risk_table_name (id)
		ON DELETE CASCADE ) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_audit';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		riskID bigint(20) UNSIGNED NOT NULL,
		auditComment TEXT NOT NULL,
		auditPerson INT(11),
		auditDate VARCHAR(30),
		auditType INT(11),
		PRIMARY KEY  (id),
		FOREIGN KEY (riskID)
		REFERENCES $risk_table_name (id)
		ON DELETE CASCADE) $charset_collate;";
		$wpdb->query ( $sql );

		$table_name = $wpdb->prefix . 'qrm_reports';
		$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		reportID VARCHAR(40) NOT NULL,
		menuName VARCHAR(20) NOT NULL,
		urlText TEXT NOT NULL,
		showRiskExplorer TINYINT NOT NULL DEFAULT 0,
		showSingleIncident TINYINT NOT NULL DEFAULT 0,
		showIncident TINYINT NOT NULL DEFAULT 0,
		showRank TINYINT NOT NULL DEFAULT 0,
		showRelMatrix TINYINT NOT NULL DEFAULT 0,
		showSingleReview TINYINT NOT NULL DEFAULT 0,
		showReview TINYINT NOT NULL DEFAULT 0,
		showSingleRisk TINYINT NOT NULL DEFAULT 0,
		PRIMARY KEY  (id) ) $charset_collate;";
		$wpdb->query ( $sql );
	}

	public static function register_types() {
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
}

class QRMMatrix {

	static function mat($w, $h, $tolString, $maxProb, $maxImpact, $uProb = null, $uImpact = null, $tProb = null, $tImpact = null) {
		
		
		
		$im = QRMMatrix::drawMatOutline($w, $h, $tolString, $maxProb, $maxImpact);
		
		$cw = $w / $maxImpact;
		$ch = $h / $maxProb;
		$black = imagecolorallocate ( $im, 0, 0, 0 );
		$white = imagecolorallocate ( $im, 255, 255, 255 );
		$blue = imagecolorallocate ( $im, 0, 0, 255 );
		$red = imagecolorallocate ( $im, 255, 0, 0 );
		$green = imagecolorallocate ( $im, 0, 255, 0 );
		$yellow = imagecolorallocate ( $im, 255, 255, 0 );
		$orange = imagecolorallocate ( $im, 255, 165, 0 );

		if ($uProb != null) {
			imagesetthickness ( $im, 3 );
			$p = (floor ( $uProb ) - 1) * $ch;
			$i = (floor ( $uImpact ) - 1) * $cw;
			imagefilledellipse ( $im, $i + $cw / 2, $p + $ch / 2, $cw - 6, $ch - 6, $white );
			imageellipse ( $im, $i + $cw / 2, $p + $ch / 2, $cw - 8, $ch - 8, $red );
			imageline ( $im, $i + $cw * 0.25, $p + $ch / 4, $i + $cw * 0.75, $p + $ch * 0.75, $red );
			imageline ( $im, $i + $cw * 0.25, $p + $ch * 0.75, $i + $cw * 0.75, $p + $ch * 0.25, $red );

			$p = (floor ( $tProb ) - 1) * $ch;
			$i = (floor ( $tImpact ) - 1) * $cw;
			imagefilledellipse ( $im, $i + $cw / 2, $p + $ch / 2, $cw - 6, $ch - 6, $white );
			imageellipse ( $im, $i + $cw / 2, $p + $ch / 2, $cw - 8, $ch - 8, $blue );
			imageline ( $im, $i + $cw * 0.25, $p + $ch / 4, $i + $cw * 0.75, $p + $ch * 0.75, $blue );
			imageline ( $im, $i + $cw * 0.25, $p + $ch * 0.75, $i + $cw * 0.75, $p + $ch * 0.25, $blue );
		}

		// Put it in the correct orientation
		imageflip ( $im, IMG_FLIP_VERTICAL );

		return $im;
	}
	
	static function drawMatOutline($w, $h, $tolString, $maxProb, $maxImpact) {
		
		$cw = $w / $maxImpact;
		$ch = $h / $maxProb;
		$im = imagecreatetruecolor ( $w, $h );
		$black = imagecolorallocate ( $im, 0, 0, 0 );
		$white = imagecolorallocate ( $im, 255, 255, 255 );
		$blue = imagecolorallocate ( $im, 0, 0, 255 );
		$red = imagecolorallocate ( $im, 255, 0, 0 );
		$green = imagecolorallocate ( $im, 0, 255, 0 );
		$yellow = imagecolorallocate ( $im, 255, 255, 0 );
		$orange = imagecolorallocate ( $im, 255, 165, 0 );
		
		$x = 0;
		// Draw the cells
		for($i = 0; $i < $maxProb; $i ++) {
			for($j = 0; $j < $maxImpact; $j ++) {
				switch (substr ( $tolString, $x, 1 )) {
					case "1" :
						imagefilledrectangle ( $im, $j * $cw, $i * $ch, ($j + 1) * $cw, ($i + 1) * $ch, $blue );
						break;
					case "2" :
						imagefilledrectangle ( $im, $j * $cw, $i * $ch, ($j + 1) * $cw, ($i + 1) * $ch, $green );
						break;
					case "3" :
						imagefilledrectangle ( $im, $j * $cw, $i * $ch, ($j + 1) * $cw, ($i + 1) * $ch, $yellow );
						break;
					case "4" :
						imagefilledrectangle ( $im, $j * $cw, $i * $ch, ($j + 1) * $cw, ($i + 1) * $ch, $orange );
						break;
					case "5" :
						imagefilledrectangle ( $im, $j * $cw, $i * $ch, ($j + 1) * $cw, ($i + 1) * $ch, $red );
						break;
				}
				$x = $x + 1;
			}
		}
		
		// Draw vertical lines
		for($j = 0; $j < $maxImpact; $j ++) {
			imageline ( $im, $j * $cw, 0, $j * $cw, $h, $black );
		}
		// Draw horizontal lines
		for($j = 0; $j < $maxProb; $j ++) {
			imageline ( $im, 0, $j * $ch, $w, $j * $ch, $black );
		}
		// Draw border lines
		imageline ( $im, 0, $h - 1, $w, $h - 1, $black );
		imageline ( $im, $w - 1, 0, $w - 1, $h, $black );
		
		return $im;
	}
	
	static function getMatImageString($w, $h, $tolString, $maxProb, $maxImpact, $uProb, $uImpact, $tProb, $tImpact) {
		$mat = QRMMatrix::mat ( $w, $h, $tolString, $maxProb, $maxImpact, $uProb, $uImpact, $tProb, $tImpact );
		ob_start ();
		imagepng ( $mat );
		imagedestroy ( $mat );
		$stringdata = ob_get_contents (); // read from buffer
		ob_end_clean (); // delete buffer
		return $stringdata;
	}
	static function outputMatImage($w, $h, $tolString, $maxProb, $maxImpact, $uProb, $uImpact, $tProb, $tImpact) {
		$mat = QRMMatrix::mat ( $w, $h, $tolString, $maxProb, $maxImpact, $uProb, $uImpact, $tProb, $tImpact );

		header ( 'Content-Type: image/png' );
		imagepng ( $mat );
		imagedestroy ( $mat );
	}
}


final class QRMAutoUpdate {
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
		if (isset ( $remote_version )) {
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



class QRMUtil {

	static function dropReportTables() {

		global $wpdb;

		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_controls' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_mitplan' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_respplan' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_projectusers' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_objective' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_incidentrisks' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_category' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_reviewrisks' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_reviewriskcomments' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_reports' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_projectproject' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_audit' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_riskobjectives' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_projectowners' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_projectmanagers' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_review' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_incident' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_risk' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_project' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_analytics' );
		
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
		if ( ! isset($risk->primcat)) {
			$risk->primcat = new stdObject ();
		}
		if ( ! isset($risk->seccat)) {
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

}

$GLOBALS ['quayriskmanager'] = QuayRiskManager::instance ();
