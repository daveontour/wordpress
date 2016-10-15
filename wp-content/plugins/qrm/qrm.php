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

define('QRM_VERSION', '1.3.3');
defined ( 'ABSPATH' ) or die ();

require_once (plugin_dir_path ( __FILE__ ) . '/qrm-db.php');


final class Project{
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
class stdObject {
	public function __construct(array $arguments = array()) {
		if (!empty($arguments)) {
			foreach ($arguments as $property => $argument) {
				$this->{$property} = $argument;
			}
		}
	}

	public function __call($method, $arguments) {
		$arguments = array_merge(array("stdObject" => $this), $arguments); // Note: method argument 0 will always referred to the main class ($this).
		if (isset($this->{$method}) && is_callable($this->{$method})) {
			return call_user_func_array($this->{$method}, $arguments);
		} else {
			throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
		}
	}
}
final class PageTemplater {
	protected $plugin_slug;
	private static $instance;
	protected $templates;
	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new PageTemplater();
		}
		return self::$instance;
	}
	private function __construct() {
		$this->templates = array();
		add_filter('page_attributes_dropdown_pages_args',array( $this, 'register_project_templates' ));
		add_filter('wp_insert_post_data',array( $this, 'register_project_templates' ));
		add_filter('template_include',array( $this, 'view_project_template'));
		// Add your templates to this array.
		$this->templates = array('templates/qrm-type-template.php'    => 'Quay Risk Manager Main Page');

	}
	public function register_project_templates( $atts ) {
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );
		$templates = wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = array();
		}
		wp_cache_delete( $cache_key , 'themes');
		$templates = array_merge( $templates, $this->templates );
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );
		return $atts;
	}
	public function view_project_template( $template ) {
		global $post;
		if (!isset($this->templates[get_post_meta(
				$post->ID, '_wp_page_template', true
		)] ) ) {
			return $template;
		}
		$file = plugin_dir_path(__FILE__). get_post_meta(
				$post->ID, '_wp_page_template', true
		);

		// Just to be safe, we check if the file exist first
		if( file_exists( $file ) ) {
			return $file;
		}
		else { echo $file; }
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
	static function getGuestObject(){
		$guest = new stdObject();
		$guest->msg = "Guest logins cannot save data";
		$guest->error = true;
		return $guest;
	}
	static function getJSON(){
		$export = QRM::commonJSON();
		wp_send_json($export);
	}
	static function exportMetadata(&$export){
		global  $current_user;
		$export->userEmail = $current_user->user_email;
		$export->userLogin = $current_user->user_login;
		$export->userDisplayName = $current_user->display_name;
		$export->siteName = get_option("qrm_siteName");
		$export->siteKey = get_option("qrm_siteKey");		
		$export->siteID = get_option("qrm_siteID");
		$export->reportServerURL = get_option("qrm_reportServerURL");
		$export->displayUser = get_option("qrm_displayUser");
		$export->sessionToken = wp_get_session_token ();
	}
	static function getServerMeta(){
		$export = new stdObject();
		QRM::exportMetadata($export);
		wp_send_json($export);
	}
	static function downloadJSON(){
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

		$export = QRM::commonJSON();

		wp_send_json($export);
	}
	static function commonJSON($projectIDs = array(), $riskIDs = array(), $basicsOnly = false) {

		global $post;

		$args = array (
				'post_type' => 'riskproject',
				'posts_per_page' => - 1
		);
		// Restrict Selection to just the selected project
			if (sizeof($projectIDs) > 0){
			$args['post__in'] = $projectIDs;
		}


		$the_query = new WP_Query ( $args );
		$projects = array ();

		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$project = json_decode ( get_post_meta ( $post->ID, "projectdata", true ) );
			array_push ( $projects, $project );
		endwhile
		;
		


		//Restrict selection just to the selected risks
		if (sizeof($riskIDs) > 0){
			
			$args = array( 'post_type' => 'risk', 
							'post__in' => $riskIDs, 
							'posts_per_page' => -1 	
			);
			
		} else if (sizeof($projectIDs) > 0){
			
			$args = array('post_type' => 'risk', 
					      'posts_per_page' => -1,
						  'meta_query' => array(
									array(
										'key' => 'projectID',
										'value' => $projectIDs,
										'compare' => 'IN'
									)
						)
			);
			
		} else {
			
			$args = array( 'post_type' => 'risk', 
						   'posts_per_page' => -1 
					     );
		
		}
		
		$the_query = new WP_Query ( $args );
		$risks = array ();
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
		$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
		
		if (!$basicsOnly){
			$risk->audit = json_decode ( get_post_meta ( $post->ID, "audit", true ) );
			$risk->incidents = get_post_meta ( $post->ID, "incident" );
			$risk->reviews = get_post_meta ( $post->ID, "review" );				
			$risk->comments = get_comments ( array (
					'post_id' => $post->ID
			) );
		}
			$risk->projectID = get_post_meta($post->ID, "projectID",true);
			$risk->rank = get_post_meta($post->ID, "rank",true);
			$risk->ID = $post->ID;
			if ($risk->primcat == 0){
				$risk->primcat = new stdObject();
			}
			if ($risk->seccat == 0){
				$risk->seccat = new stdObject();
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
		


		$args = array( 'post_type' => 'review',
					   'post_per_page' => -1
		);
		
		if (!$basicsOnly){
			$args ["post_type"] = 'review';
			$the_query = new WP_Query ( $args );
			$reviews = array ();
			while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$review = json_decode ( get_post_meta ( $post->ID, "reviewdata", true ) );
			array_push ( $reviews, $review );
			endwhile
			;
			
			$args = array( 'post_type' => 'incident',
					'post_per_page' => -1
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
		
		if (!$basicsOnly){
			$export->incidents = $incidents;
			$export->reviews = $reviews;
		}

		$users = array();
		$user_query = new WP_User_Query ( array ('fields' => "all"));
		foreach ($user_query->results as $user){
			$u = new stdObject();
			$u->id = $user->data->ID;
			$u->display_name = $user->data->display_name;
			$u->user_email = $user->data->user_email;
			array_push($users, $u);
		}

		$export->users = $users;

		return $export;
	}
	static function installSample() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		require plugin_dir_path ( __FILE__ ) . '/includes/qrm-sample.php';
		wp_send_json ( array (
				"msg" => QRMSample::installSample ()
		) );
	}
	static function installSampleProjects() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		require plugin_dir_path ( __FILE__ ) . '/includes/qrm-sample.php';
		wp_send_json ( array (
				"msg" => QRMSample::createSampleProjects()
		) );
	}
	static function createDummyRiskEntry() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		require plugin_dir_path ( __FILE__ ) . '/includes/qrm-sample.php';
		wp_send_json ( array (
				"msg" => QRMSample::createDummyRiskEntry ()
		) );
	}
	static function createDummyRiskEntryMultiple() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		require plugin_dir_path ( __FILE__ ) . '/includes/qrm-sample.php';
		wp_send_json ( array (
				"msg" => QRMSample::createDummyRiskEntryMultiple ()
		) );
	}
	static function removeSample() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		require plugin_dir_path ( __FILE__ ) . '/includes/qrm-sample.php';
		$all = json_decode ( file_get_contents ( "php://input" ) );
		wp_send_json ( array (
				"msg" => QRMSample::removeSample ($all)
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
	static function getReportOptions(){
		
		$options = new stdObject ();
		
		$options->siteKey = get_option("qrm_siteKey");
		$options->siteName = get_option("qrm_siteName");
		$options->siteID = get_option("qrm_siteID");
		$options->url = get_option("qrm_reportServerURL");
		
		wp_send_json ( $options );
		
	}	
	static function getDisplayUser(){
		
		$options = new stdObject ();
		
		$options->displayUser = get_option("qrm_displayUser");	
		wp_send_json ( $options );
		
	}
	static function saveReportOptions(){
		$options = json_decode ( file_get_contents ( "php://input" ) );
		
		update_option("qrm_siteKey", $options->siteKey);
		update_option("qrm_siteName", $options->siteName);
		update_option("qrm_siteID", $options->siteID);
		update_option("qrm_reportServerURL", $options->url);
		
	}
	static function saveDisplayUser(){
		$options = json_decode ( file_get_contents ( "php://input" ) );
		update_option("qrm_displayUser", $options->displayUser);	
	}
	static function saveIncident() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest"){
			wp_send_json(QRM::getGuestObject());
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

		update_post_meta ( $postID, "incidentdata", json_encode ( $incident,JSON_HEX_QUOT ) );
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
				add_post_meta ( $risk, "incident", intval($postID) );
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
		
		WPQRM_Model_Incident::replace($incident);

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
						
					if ($review->riskComments != null){
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
		
		if ($user_login == "guest"){
			wp_send_json(QRM::getGuestObject());
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
		
		if ($user_login == "guest"){
			wp_send_json(QRM::getGuestObject());
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

		update_post_meta ( $postID, "reviewdata", json_encode ( $review,JSON_HEX_QUOT ) );
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
				add_post_meta ( $risk, "review", intval($postID) );
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
		
		WPQRM_Model_Review::replace($review);

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
				
			if ($u->bAdmin || $u->bUser){
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
		
		if ($user_login == "guest"){
			wp_send_json(QRM::getGuestObject());
			return;
		}
		
		$audit = json_decode ( file_get_contents ( "php://input" ) );
		$riskID = $audit->riskID;

		$a = new stdObject ();
		$a->auditComment = $audit->auditComment;
		$a->auditDate = date ( "M j, Y" );
		$a->auditPerson = $current_user->ID;

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
				break;
			case 2 :
				$auditObj->auditIdentApp = $a;
				break;
			case 3 :
				break;
			case 4 :
				$auditObj->auditEvalRev = $a;
				break;
			case 5 :
				$auditObj->auditEvalApp = $a;
				break;
			case 6 :
				$auditObj->auditMit = $a;
				break;
			case 7 :
				$auditObj->auditMitRev = $a;
				break;
			case 8 :
				$auditObj->auditMitApp = $a;
				break;
		}

		update_post_meta ( $riskID, "audit", json_encode ( $auditObj ) );
		
		WPQRM_Model_Audit::replace($auditObj);

		wp_send_json ( json_decode ( get_post_meta ( $riskID, "audit", true ) ) );
	}
	static function getSiteUsers() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$user_query = new WP_User_Query ( array (
				'fields' => 'all'
		) );
		foreach ( $user_query->results as $result){
			$result->data->nickname = get_user_meta( $result->data->ID, "nickname" )[0];
			$result->data->first_name = get_user_meta( $result->data->ID, "first_name" )[0];
			$result->data->last_name = get_user_meta( $result->data->ID, "last_name" )[0];
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
		
		if ($user_login == "guest"){
			wp_send_json(QRM::getGuestObject());
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

		require_once plugin_dir_path ( __FILE__ ) . '/includes/qrm-sample.php';

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
		
		if ($user_login == "guest"){
			wp_send_json(QRM::getGuestObject());
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
			
			WPQRM_Model_Risk::replace($r);
			
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
	static function getRisk() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();

		$riskID = json_decode ( file_get_contents ( "php://input" ) );
		
		$json = get_post_meta ( $riskID, "riskdata", true );
		$risk = json_decode ( get_post_meta ( $riskID, "riskdata", true ) );
		
		// Make sure the user is authorised to get the risk
		$projectRiskManagerID = get_post_meta ( $risk->projectID, "projectRiskManagerID", true );
		$project = json_decode ( get_post_meta ( $risk->projectID, "projectdata", true ) );
		
		if (! ($current_user->ID == $projectRiskManagerID || in_array ( $current_user->ID, $project->ownersID ) || in_array ( $current_user->ID, $project->managersID ) || in_array ( $current_user->ID, $project->usersID ))) {
			wp_send_json ( array ("msg" => "You are not authorised to view this risk") );
		}

		$risk->comments = get_comments ( array ('post_id' => $riskID) );
		$risk->attachments = get_children ( array ("post_parent" => $riskID,"post_type" => "attachment") );
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
		
		if ($user_login == "guest"){
			wp_send_json(QRM::getGuestObject());
			return;
		}
		
		$risks = json_decode ( file_get_contents ( "php://input" ) );

		global $wpdb;
		foreach ( $risks as $risk ) {
			update_post_meta ( $risk->id, "rank", $risk->rank );
			$sql = sprintf( 'UPDATE %s SET rank = %%s WHERE id = %%s', $wpdb->prefix . 'qrm_risk');
			$wpdb->query( $wpdb->prepare( $sql, $risk->rank, $risk->id ));	
		}
		exit ();
	}
	static function getProjects() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $post;
		$args = array (
				'post_type' => 'riskproject',
				'posts_per_page' => -1,
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
		
		$ids = array();
		array_push($ids, $projectID);
		
		if($data->childProjects){
			$children = QRM::get_project_children($projectID);
			foreach ($children as $child){
				array_push($ids, $child->ID);
			}
		}

		global $post;
		$args = array (
				'post_type' => 'risk',
				'posts_per_page' => - 1,
				'meta_query' => array(
						array(
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
			if (! ($current_user->ID == $project->projectRiskManager || in_array ( $current_user->ID, $project->ownersID ) || in_array ( $current_user->ID, $project->managersID ) || in_array ( $current_user->ID, $project->usersID ))) {
				continue;
			}
			
			$risk->rank = get_post_meta ( $post->ID, "rank", true );
				
			array_push ( $risks, $risk );
		endwhile
		;

// 		$data = new Data ();
// 		$data->data = $risks;
		wp_send_json ( $risks );
	}
	static function get_project_children($parent_id){
		$children = array();
		$posts = get_posts( array( 'numberposts' => -1, 'post_status' => 'publish', 'post_type' => 'riskproject', 'post_parent' => $parent_id ));
		// now grab the grand children
		foreach( $posts as $child ){
			// recursion!! hurrah
			$gchildren = QRM::get_project_children($child->ID);
			// merge the grand children into the children array
			if( !empty($gchildren) ) {
				$children = array_merge($children, $gchildren);
			}
		}
		// merge in the direct descendants we found earlier
		$children = array_merge($children,$posts);
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
		
		if ($user_login == "guest"){
			wp_send_json(QRM::getGuestObject());
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
		
		//Include in reporting tables
		WPQRM_Model_Risk::replace($risk);

		return $risk->id;
	}
	static function saveRisk() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest"){
			wp_send_json(QRM::getGuestObject());
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
			if (! ($current_user->ID == $projectRiskManagerID || in_array ( $current_user->ID, $project->ownersID ) || in_array ( $current_user->ID, $project->managersID ) )) {
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
		update_post_meta ( $postID, "riskdata", json_encode ( $risk , JSON_HEX_QUOT) );

		// Key Data for searching etc
		update_post_meta ( $postID, "projectID", $risk->projectID );
		update_post_meta ( $postID, "riskProjectCode", $risk->riskProjectCode );
		update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
		update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
		update_post_meta ( $postID, "manager", get_user_by ( "id", $risk->manager )->data->display_name );
		update_post_meta ( $postID, "ownerID", $risk->owner );
		update_post_meta ( $postID, "managerID", $risk->manager );
		update_post_meta ( $postID, "project", $project->post_title );

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
		WPQRM_Model_Risk::replace($risk);
		wp_send_json ( $risk );
	}
	static function saveProject() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );


		global $user_identity, $user_email, $user_ID, $current_user, $user_login;
		get_currentuserinfo ();
		
		if ($user_login == "guest"){
			wp_send_json(QRM::getGuestObject());
			return;
		}
		
		$postdata = file_get_contents ( "php://input" );
		$project = json_decode ( $postdata );
		
		if ($project->parent_id != 0){
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
		update_post_meta ( $postID, "projectRiskManagerID", $project->projectRiskManager);
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
		
		QRM::updateChildProjects($postID);

		WPQRM_Model_Project::replace($project);
		QRM::getProjects ();
	}
	static function updateChildProjects($parentID){

		if ($parentID == null ){
			return;
		}
		
		$parent_project = json_decode ( get_post_meta ( $parentID, "projectdata", true ) );
		
		global $post;
		$the_query = new WP_Query ( 'post_type=riskproject&post_parent='.$parentID );
		while ( $the_query->have_posts () ) :
		$the_query->the_post ();
				$project = json_decode ( get_post_meta ( $post->ID, "projectdata", true ) );
				$project->matrix = $parent_project->matrix;
				update_post_meta ( $post->ID, "maxProb", $project->matrix->maxProb );
				update_post_meta ( $post->ID, "maxImpact", $project->matrix->maxImpact );
				update_post_meta ( $project->id, "projectdata", json_encode ( $project ) );
				QRM::updateChildProjects($project->id);
		endwhile
		;
		
	}
	static function reindexRiskCount(){
		
		
		$my_query = new WP_Query( 'post_type=riskproject&post_status=publish&posts_per_page=-1' );
		if ( $my_query->have_posts() ) {
			while ( $my_query->have_posts() ) {
				$my_query->the_post();
				
				$args = array (
						'post_type' => 'risk',
						'post_status' => 'publish',
						'posts_per_page' => 5000,
						'meta_query'  => array(
								array(
										'key'     => 'projectID',
										'value'   => $my_query->post->ID,
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
			#Update the tables used for reporting
			$sql = sprintf( 'UPDATE %s SET riskProjectCode = %%s WHERE id = %%s', $wpdb->prefix . 'qrm_risk');
			$wpdb->query( $wpdb->prepare( $sql, $risk->riskProjectCode, $post->ID ));
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
final class QRMReportData{
	static function incidentJSON($incidentIDs = array()) {
	
		global $post;
			
		$args = array( 'post_type' => 'incident',
				'post_per_page' => -1
		);
		if (sizeof($incidentIDs) > 0){
			$args['post__in'] = $incidentIDs;
		}
	
	
		$riskIDs = array();
	
		$the_query = new WP_Query ( $args );
		$incidents = array ();
		while ( $the_query->have_posts () ) :
		$the_query->the_post ();
		$incident = json_decode ( get_post_meta ( $post->ID, "incidentdata", true ) );
		$incident->comments = get_comments ( array ('post_id' => $post->ID) );
		if ($incident->risks != null )$riskIDs = array_merge($riskIDs, $incident->risks);
		array_push ( $incidents, $incident );
		endwhile
		;
	
		
		$risks = array ();
		if (sizeof ( $riskIDs ) > 0) {
			$args = array (
					'post_type' => 'risk',
					'post__in' => $riskIDs,
					'posts_per_page' => - 1 
			);
			
			$the_query = new WP_Query ( $args );
			while ( $the_query->have_posts () ) :
				$the_query->the_post ();
				$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
				// $risk->incidents = get_post_meta ( $post->ID, "incident" );
				$risk->projectID = get_post_meta ( $post->ID, "projectID", true );
				$risk->ID = $post->ID;
				if ($risk->primcat == 0) {
					$risk->primcat = new stdObject ();
				}
				if ($risk->seccat == 0) {
					$risk->seccat = new stdObject ();
				}
				array_push ( $risks, $risk );
			endwhile
			;
		}
	
		$export = new stdObject ();
		$export->risks = $risks;
		
		$export->incidents = $incidents;
	
	
		$users = array();
		$user_query = new WP_User_Query ( array ('fields' => "all"));
		foreach ($user_query->results as $user){
			$u = new stdObject();
			$u->id = $user->data->ID;
			$u->display_name = $user->data->display_name;
			$u->user_email = $user->data->user_email;
			array_push($users, $u);
		}
	
		$export->users = $users;
	
		return $export;
	}
	static function reviewJSON($reviewIDs = array()) {
	
		global $post;
			
		$args = array( 'post_type' => 'review',
				'post_per_page' => -1
		);
		if (sizeof($reviewIDs) > 0){
			$args['post__in'] = $reviewIDs;
		}
	
	
		$riskIDs = array();
	
		$the_query = new WP_Query ( $args );
		$reviews = array ();
		while ( $the_query->have_posts () ) :
		$the_query->the_post ();
		$review = json_decode ( get_post_meta ( $post->ID, "reviewdata", true ) );
		if ($review->risks != null )$riskIDs = array_merge($riskIDs, $review->risks);
		array_push ( $reviews, $review );
		endwhile
		;
	
			
		$args = array( 'post_type' => 'risk',
				'post__in' => $riskIDs,
				'posts_per_page' => -1
		);
	
		$risks = array ();
		
		if (sizeof ( $riskIDs ) > 0) {
			$the_query = new WP_Query ( $args );
			while ( $the_query->have_posts () ) :
				$the_query->the_post ();
				$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
				$risk->projectID = get_post_meta ( $post->ID, "projectID", true );
				$risk->ID = $post->ID;
				if ($risk->primcat == 0) {
					$risk->primcat = new stdObject ();
				}
				if ($risk->seccat == 0) {
					$risk->seccat = new stdObject ();
				}
				array_push ( $risks, $risk );
			endwhile
			;
		}
	
		$export = new stdObject ();
		$export->risks = $risks;
		$export->reviews = $reviews;
	
	
		$users = array();
		$user_query = new WP_User_Query ( array ('fields' => "all"));
		foreach ($user_query->results as $user){
			$u = new stdObject();
			$u->id = $user->data->ID;
			$u->display_name = $user->data->display_name;
			$u->user_email = $user->data->user_email;
			array_push($users, $u);
		}
	
		$export->users = $users;
	
		return $export;
	}
	
	static function getReportRiskJSON(){
		$config = json_decode ( file_get_contents ( "php://input" ) );
	
		$ids = array();
		array_push($ids, $config->projectID);
	
		if($config->childProjects){
			$children = QRM::get_project_children($config->projectID);
			foreach ($children as $child){
				array_push($ids, $child->ID);
			}
		}
	
		//Pass the project array and the risk ID array
		//commonJSON will apply the non null condition
		$export = QRM::commonJSON($ids,$config->risks, $config->basicsOnly);
		QRM::exportMetadata($export);
			
		if ($config->reportID != null) {
			
			$response = wp_remote_post ( $export->reportServerURL . "/report", array (
					'method' => 'POST',
					'timeout' => 60,
					'body' => array (
							'reportData' => json_encode ( $export ),
							'action' => "execute_report",
							'reportEmail' => $export->userEmail,
							'reportID' => $config->reportID,
							'sessionToken' => $export->sessionToken 
					) 
			) );
			if (is_wp_error ( $response )) {
				wp_send_json ( $export );
			} else {
				echo "OK";
				wp_die();
			}
		} else {
			wp_send_json ( $export );
		}
	}
	
	static function getReportIncidentJSON(){
		$config = json_decode ( file_get_contents ( "php://input" ) );
		$export = QRMReportData::incidentJSON($config->incidents);
		QRM::exportMetadata($export);
		if ($config->reportID != null) {
			$body = array (
							'reportData' => json_encode ( $export ),
							'action' => "execute_report",
							'reportEmail' => $export->userEmail,
							'reportID' => $config->reportID,
							'sessionToken' => $export->sessionToken
					) ;
			$response = wp_remote_post ( $export->reportServerURL . "/report", array (
					'method' => 'POST',
					'timeout' => 60,
					'body' => $body
			) );
			if (is_wp_error ( $response )) {
				wp_send_json ( $export );
			} else {
				echo "OK";
				wp_die();
			} 
		} else {
			wp_send_json ( $export );
 		}
	}
	static function getReportReviewJSON(){
		$config = json_decode ( file_get_contents ( "php://input" ) );
		
		$export = QRMReportData::reviewJSON($config->reviews);
		QRM::exportMetadata($export);
	if ($config->reportID != null) {
		$body = array (
				'reportData' => json_encode ( $export ),
				'action' => "execute_report",
				'reportEmail' => $export->userEmail,
				'reportID' => $config->reportID,
				'sessionToken' => $export->sessionToken
		) ;
			$response = wp_remote_post ( $export->reportServerURL . "/report", array (
					'method' => 'POST',
					'timeout' => 60,
					'body' => $body
			) );
			if (is_wp_error ( $response )) {
				wp_send_json ( $export );
			} else {
				echo "OK";
				wp_die();
			}
		} else {
			wp_send_json ( $export );
		}
	}
}
final class QRM_AutoUpdate{
	private $current_version;
	private $update_path;
	private $plugin_slug;
	private $slug;
	private $license_user;
	private $license_key;

	public function __construct( $current_version, $update_path, $plugin_slug, $license_user = '', $license_key = '' )
	{
		// Set the class public variables
		$this->current_version = $current_version;
		$this->update_path = $update_path;
		// Set the License
		$this->license_user = $license_user;
		$this->license_key = $license_key;
		// Set the Plugin Slug	
		$this->plugin_slug = $plugin_slug;
		list ($t1, $t2) = explode( '/', $plugin_slug );
		$this->slug = str_replace( '.php', '', $t2 );		
		// define the alternative API for updating checking
		add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_update' ) );
		// Define the alternative response for information checking
		add_filter( 'plugins_api', array( &$this, 'check_info' ), 10, 3 );
	}
	public function check_update( $transient )
	{
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		// Get the remote version
		$remote_version = $this->getRemote_version();
		// If a newer version is available, add the update
		if ( version_compare( $this->current_version, $remote_version->new_version, '<' ) ) {
			$obj = new stdClass();
			$obj->slug = $this->slug;
			$obj->new_version = $remote_version->new_version;
			$obj->url = $remote_version->url;
			$obj->plugin = $this->plugin_slug;
			$obj->package = $remote_version->package;
			$transient->response[$this->plugin_slug] = $obj;
		}
		return $transient;
	}
	public function check_info($false, $action, $arg)
	{
		if (isset($arg->slug) && $arg->slug === $this->slug) {
			$information = $this->getRemote_information();
			return $information;
		}
		return false;
	}
	public function getRemote_version()
	{
		$request = wp_remote_post ($this->update_path."&fn=version");
		if ( !is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			return unserialize( $request['body'] );
		}
		return false;
	}
	public function getRemote_information()
	{
		$request = wp_remote_post( $this->update_path."&fn=info", $params );
		if (!is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			return unserialize( $request['body'] );
		}
		return false;
	}
	public function getRemote_license()
	{
		$request = wp_remote_post( $this->update_path."&fn=license");
		if ( !is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			return unserialize( $request['body'] );
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
			add_filter('user_has_cap', array ($this,'qrm_prevent_riskproject_parent_deletion'), 10, 3 );
			add_filter('manage_riskproject_posts_columns', array ($this,'qrm_riskproject_table_head') );
			add_filter('manage_risk_posts_columns', array ($this,'qrm_risk_table_head') );

			add_action('manage_riskproject_posts_custom_column', array ($this,'qrm_riskproject_table_content'), 10, 2 );
			add_action('manage_risk_posts_custom_column', array ($this,'qrm_risk_table_content'), 10, 2 );
			
			add_action('admin_menu', array ($this,'qrm_admin_menu_config' ) );
			add_action( 'admin_init', array( $this, 'redirect_about_page' ), 1 );
			
			add_action('add_meta_boxes', array( $this, 'riskproject_meta_boxes' ) );
				
			add_filter('upload_mimes', array ($this,'add_custom_mime_types' ));
			add_filter('single_template', array ($this,'get_custom_post_type_template' ));
			add_action('plugins_loaded', array( 'PageTemplater', 'get_instance' ) );
			
			add_action('init', array( $this, 'register_types' ) );
			add_action('init', array ($this,'qrm_init_options' ));
			add_action('init', array ($this,'qrm_scripts_styles' ));
			add_action('init', array ($this,'qrm_init_user_cap' ));
			add_action('init', array ($this,'qrm_start_session' ));
				
			add_action('trashed_post', array ($this,'qrm_trashed_post' ));
			
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this,'qrm_add_plugin_action_links') );
				
			
			add_option("qrm_siteName", "Quay Risk Manager Site");
			add_option("qrm_reportServerURL", "http://report.quaysystems.com.au:8080");
			add_option("qrm_siteID", "Unregistered Site");
			add_option("qrm_siteKey", "Unregistered Site");
			add_option("qrm_displayUser", "userlogin");
				
			$this->activate_au();

				
			register_activation_hook ( __FILE__,  array ($this,'qrmplugin_activate' ));
		}
		public function qrm_start_session(){
			if(!session_id()) {
				session_start();
			}
		}
		public function activate_au() {
			$plugin_current_version = QRM_VERSION;
			$plugin_remote_path = 'http://www.quaysystems.com.au/wp-admin/admin-ajax.php?action=getUpdateInfo';
			$plugin_slug = plugin_basename( __FILE__ );
			new QRM_AutoUpdate ( $plugin_current_version, $plugin_remote_path, $plugin_slug);
		}
		public static function plugin_row_meta( $links, $file ) {
			if ( strpos( $file, 'qrm.php' ) !== false ) {
				$new_links = array(
							'<a href="http://www.quaysystems.com.au/docs" target="_blank">Docs</a>'
				);
				
				$links = array_merge( $links, $new_links );
			}
			
			return $links;
		}
		public function qrm_add_plugin_action_links($links) {
			//Add 'Settings' to plugin entry
			return array_merge ( array (
					'settings' => '<a href="' .admin_url( 'options-general.php?page=qrmadmin') .'">Settings</a>' 
			), $links );
		}
		public function riskproject_meta_boxes() {
			add_meta_box(
					'riskproject_fields',
					'Project Details',
					array( $this, 'render_riskproject_meta_boxes' ),
					'riskproject',
					'normal',
					'high'
			);
		}		
		public function qrm_trashed_post($postID){
			$post = get_post($postID);
			if ($post->post_type == "risk"){
				QRM::reindexRiskCount();
			}
		}
		public function render_riskproject_meta_boxes( $post ) {
		
			wp_enqueue_style ('font-awesome' );
			wp_enqueue_style ('ui-grid' );
			wp_enqueue_style ('qrm-angular');
			wp_enqueue_style ('qrm-style');
			wp_enqueue_style ('select');
			wp_enqueue_style ('select2');
			wp_enqueue_style ('selectize');
			wp_enqueue_style ('ngDialog');
			wp_enqueue_style ('ngDialogTheme');
			wp_enqueue_style ('ngNotify');
		
			wp_enqueue_script('qrm-boostrap');
			wp_enqueue_script('qrm-angular');
			wp_enqueue_script('qrm-projadmin');
			wp_enqueue_script('qrm-bootstraptpl');
			wp_enqueue_script('qrm-uigrid');
			wp_enqueue_script('qrm-d3');
			wp_enqueue_script('qrm-common');
			wp_enqueue_script('qrm-select');
			wp_enqueue_script('qrm-sanitize');
			wp_enqueue_script('qrm-ngDialog');
			wp_enqueue_script('qrm-ngNotify');
			wp_enqueue_script('qrm-services');
			
			echo "<script>";
			echo "projectID = ".$post->ID.";";
			echo "</script>";
			echo "<style>.form-table th {text-align: right}</style>";
			echo '<div ng-app="myApp" style="width: 100%; height: 100%" ng-controller="projectCtrl">';
			include 'includes/riskproject-widget.php';
			echo "</div>";

			}
		public function qrm_init_options() {
			add_option ( "qrm_objective_id", 1000 );
			add_option ( "qrm_category_id", 1000 );
		}
		public function qrm_init_user_cap(){
			add_role( 'risk_admin', 'Risk Administrator', array(
					'read' => true
			));
			
			$role = get_role( 'risk_admin' );
			$role->add_cap( 'risk_admin' );
			$role->add_cap( 'edit_posts' );
			$role->add_cap( 'edit_pages' );
			$role->add_cap( 'edit_others_posts' );
			$role->add_cap( 'edit_others_pages' );
			$role->add_cap( 'edit_private_posts' );
			$role->add_cap( 'edit_private_pages' );
			$role->add_cap( 'edit_published_posts' );
			$role->add_cap( 'edit_published_pages' );
			$role->add_cap( 'delete_pages' );
			$role->add_cap( 'delete_posts' );
			$role->add_cap( 'delete_others_posts' );
			$role->add_cap( 'delete_others_pages' );
			$role->add_cap( 'delete_published_posts' );
			$role->add_cap( 'delete_published_pages' );
				
			$role = get_role("administrator");
			$role->add_cap( 'risk_admin' );
				
		}
		public function qrmplugin_activate() {
			
			$pages = get_pages(array(
					'meta_key' => '_wp_page_template',
					'meta_value' => 'templates/qrm-type-template.php'
			));
			
			if (sizeof($pages) == 0) {
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
			
			$this->register_types ();
			flush_rewrite_rules ();
			
			set_transient ( 'qrm_about_page_activated', 1, 30 );
			
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$table_name = $wpdb->prefix . 'qrm_risk';
			
			
			$sql = "CREATE TABLE $table_name (
			 id INT(11) NOT NULL,
			 cause TEXT,
			 consequence TEXT,
			 currentImpact DOUBLE,
			 currentProb DOUBLE,
			 currentTolerance INT(11),
			 description TEXT,
			 end VARCHAR(255),
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
			 matImage LONGBLOB,
			 owner INT(11),
			 rank INT(11) NOT NULL DEFAULT 0,
			 postLikeImage LONGBLOB,
			 preLikeImage LONGBLOB,
			 primcatID INT(11),
			 projectID INT(11), 
			 riskProjectCode VARCHAR(255) DEFAULT NULL,
			 seccatID INT(11),
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
			 useCalContingency TINYINT NOT NULL DEFAULT 0, 
			 useCalProb TINYINT NOT NULL DEFAULT 0,
			 auditIdentDate VARCHAR(255) DEFAULT NULL,
			 auditIdentComment TEXT DEFAULT NULL,
			 auditIdentPersonID INT(11) DEFAULT NULL,
			 auditIdentRevDate VARCHAR(255) DEFAULT NULL,
			 auditIdentRevComment TEXT DEFAULT NULL,
			 auditIdentRevPersonID INT(11) DEFAULT NULL,
			 auditIdentAppDate VARCHAR(255) DEFAULT NULL,
			 auditIdentAppComment TEXT DEFAULT NULL,
			 auditIdentAppPersonID INT(11) DEFAULT NULL,
			 auditEvalDate VARCHAR(255) DEFAULT NULL,
			 auditEvalComment TEXT DEFAULT NULL,
			 auditEvalPersonID INT(11) DEFAULT NULL,
			 auditEvalRevDate VARCHAR(255) DEFAULT NULL,
			 auditEvalRevComment TEXT DEFAULT NULL,
			 auditEvalRevPersonID INT(11) DEFAULT NULL,
			 auditEvalAppDate VARCHAR(255) DEFAULT NULL,
			 auditEvalAppComment TEXT DEFAULT NULL,
			 auditEvalAppPersonID INT(11) DEFAULT NULL,
			 auditMitDate VARCHAR(255) DEFAULT NULL,
			 auditMitComment TEXT DEFAULT NULL,
			 auditMitPersonID INT(11) DEFAULT NULL,
			 auditMitRevDate VARCHAR(255) DEFAULT NULL,
			 auditMitRevComment TEXT DEFAULT NULL,
			 auditMitRevPersonID INT(11) DEFAULT NULL,
			 auditMitAppDate VARCHAR(255) DEFAULT NULL,
			 auditMitAppComment TEXT DEFAULT NULL,
			 auditMitAppPersonID INT(11) DEFAULT NULL,
			 mitPlanSummary TEXT DEFAULT NULL,
			 mitPlanSummaryUpdate TEXT DEFAULT NULL,
			 respPlanSummary TEXT DEFAULT NULL,
			 respPlanSummaryUpdate TEXT DEFAULT NULL,		 
			 PRIMARY KEY (id) ) $charset_collate;";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_controls';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			riskID bigint(20) DEFAULT NULL,
			description TEXT,
			effectiveness TEXT,
			contribution TEXT,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_mitplan';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			riskID bigint(20) DEFAULT NULL,
			description TEXT,
			cost DOUBLE DEFAULT NULL,
			complete DOUBLE DEFAULT NULL,
			due VARCHAR(255) DEFAULT NULL,
			person INT(11),
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_respplan';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			riskID bigint(20) DEFAULT NULL,
			description TEXT,
			cost DOUBLE,
			person INT(11),
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_project';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
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
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_projectowners';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			projectID INT(11) NOT NULL,
			ownerID INT(11) NOT NULL,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
				
			$table_name = $wpdb->prefix . 'qrm_projectmanagers';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			projectID INT(11) NOT NULL,
			managerID INT(11) NOT NULL,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_projectusers';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			projectID INT(11) NOT NULL,
			userID INT(11) NOT NULL,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_objective';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			projectID INT(11) NOT NULL,
			parentID INT(11) DEFAULT NULL,
			title TEXT,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
	
			
			$table_name = $wpdb->prefix . 'qrm_incident';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
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
			reputation TINYINT NOT NULL DEFAULT 0,
			spec TINYINT NOT NULL DEFAULT 0,
			evaluated TINYINT NOT NULL DEFAULT 0,
			resolved TINYINT NOT NULL DEFAULT 0,
			time TINYINT NOT NULL DEFAULT 0,
			identified TINYINT NOT NULL DEFAULT 0,
			reportedby INT(11),
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_incidentrisks';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			incidentID INT(11) NOT NULL,
			riskID INT(11) NOT NULL,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
				
			
			$table_name = $wpdb->prefix . 'qrm_category';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			projectID INT(11) NOT NULL,
			parentID INT(11) DEFAULT NULL,
			primCat TINYINT NOT NULL DEFAULT 0,
			title TEXT,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_review';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title TEXT,
			description TEXT,
			schedDate VARCHAR(255) DEFAULT NULL,	
			actualDate VARCHAR(255) DEFAULT NULL,
			reviewCode VARCHAR(255) DEFAULT NULL,
			responsible INT(11),
			notes TEXT,
			complete TINYINT NOT NULL DEFAULT 0,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_reviewrisks';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			reviewID INT(11) NOT NULL,
			riskID INT(11) NOT NULL,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
				
			$table_name = $wpdb->prefix . 'qrm_reviewriskcomments';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			reviewID INT(11) NOT NULL,
			riskID INT(11) NOT NULL,
			comment TEXT,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
			
			$table_name = $wpdb->prefix . 'qrm_reviewcomments';
			$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			reviewID INT(11) NOT NULL,
			commentID INT(11) NOT NULL,
			PRIMARY KEY  (id) ) $charset_collate;";
			dbDelta( $sql );
				
				
							
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
			add_action ( "wp_ajax_getProject", array (QRM,"getProject" ) );
			add_action ( "wp_ajax_getAllRisks", array (QRM,"getAllRisks") );
			add_action ( "wp_ajax_getProjects", array (QRM,"getProjects") );
			add_action ( "wp_ajax_getSiteUsersCap", array (QRM,"getSiteUsersCap") );
			add_action ( "wp_ajax_getSiteUsers", array (QRM,"getSiteUsers" ) );
			add_action ( "wp_ajax_saveSiteUsers", array (QRM,"saveSiteUsers") );
			add_action ( "wp_ajax_saveProject", array (QRM,"saveProject" ) );
			add_action ( "wp_ajax_getAllProjectRisks", array (QRM,"getAllProjectRisks") );
			add_action ( "wp_ajax_getRisk", array (QRM,"getRisk" ) );
			add_action ( "wp_ajax_saveRisk", array (QRM,"saveRisk") );
			add_action ( "wp_ajax_updateRisksRelMatrix", array (QRM,"updateRisksRelMatrix") );
			add_action ( "wp_ajax_getAttachments", array (QRM,"getAttachments" ) );
			add_action ( "wp_ajax_uploadFile", array (QRM,"uploadFile" ) );
			add_action ( "wp_ajax_uploadImport", array (QRM,"uploadImport") );
			add_action ( "wp_ajax_getCurrentUser", array (QRM,"getCurrentUser") );
			add_action ( "wp_ajax_saveRankOrder", array (QRM,"saveRankOrder" ) );
			add_action ( "wp_ajax_registerAudit", array (QRM,"registerAudit" ) );
			add_action ( "wp_ajax_getAllIncidents", array (QRM,"getAllIncidents") );
			add_action ( "wp_ajax_getIncident", array (QRM,"getIncident" ) );
			add_action ( "wp_ajax_saveIncident", array (QRM,"saveIncident") );
			add_action ( "wp_ajax_addGeneralComment", array (QRM,"addGeneralComment") );
			add_action ( "wp_ajax_getAllReviews", array (QRM,"getAllReviews" ) );
			add_action ( "wp_ajax_getReview", array (QRM,"getReview" ) );
			add_action ( "wp_ajax_saveReview", array (QRM,"saveReview") );
			add_action ( "wp_ajax_nopriv_login", array (QRM,"login" ) );
			add_action ( "wp_ajax_login", array (QRM,"login" ) );
			add_action ( "wp_ajax_logout", array (QRM,"logout") );
			add_action ( "wp_ajax_checkSession", array (QRM,"checkSession") );
			add_action ( "wp_ajax_newPushDown", array (QRM,"newPushDown" ) );
			add_action ( "wp_ajax_installSample", array (QRM,"installSample") );
			add_action ( "wp_ajax_installSampleProjects", array (QRM,"installSampleProjects") );
			add_action ( "wp_ajax_removeSample", array (QRM,"removeSample" ) );
			add_action ( "wp_ajax_downloadJSON", array (QRM,"downloadJSON" ) );
			add_action ( "wp_ajax_getJSON", array (QRM,"downloadJSON" ) );
			add_action ( "wp_ajax_getReportRiskJSON", array(QRMReportData, "getReportRiskJSON"));
			add_action ( "wp_ajax_getReportIncidentJSON", array(QRMReportData, "getReportIncidentJSON"));
			add_action ( "wp_ajax_getReportReviewJSON", array(QRMReportData, "getReportReviewJSON"));
			add_action ( "wp_ajax_getReportOptions", array(QRM, "getReportOptions"));
			add_action ( "wp_ajax_saveReportOptions", array(QRM, "saveReportOptions"));
			add_action ( "wp_ajax_getServerMeta", array(QRM, "getServerMeta"));				
			add_action ( "wp_ajax_createDummyRiskEntry", array(QRM, "createDummyRiskEntry"));				
			add_action ( "wp_ajax_createDummyRiskEntryMultiple", array(QRM, "createDummyRiskEntryMultiple"));				
			add_action ( "wp_ajax_reindexRiskCount", array(QRM, "reindexRiskCount"));	
			add_action ( "wp_ajax_saveDisplayUser", array(QRM, "saveDisplayUser"));
			add_action ( "wp_ajax_getDisplayUser", array(QRM, "getDisplayUser"));
				
			
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
					if ($num_posts > 0){
						$allcaps [$caps [0]] = false;
					} else {
						$allcaps [$caps [0]] = true;;
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
			add_submenu_page ( 'options-general.php', 'Quay Risk Manager','Quay Risk Manager', 'manage_options', 'qrmadmin',array($this, qrmadminpage) );
			remove_meta_box ( 'pageparentdiv', 'riskproject', 'normal' );
			remove_meta_box ( 'pageparentdiv', 'riskproject', 'side' );
		}
		public function redirect_about_page() {
			
			//Redirect to QRM Setting Page when first activated
			
			// only do this if the user can activate plugins
			if ( ! current_user_can( 'manage_options' ) )
				return;
		
			// don't do anything if the transient isn't set
			if ( ! get_transient( 'qrm_about_page_activated' ) )
				return;
		
			delete_transient( 'qrm_about_page_activated' );
			wp_safe_redirect( admin_url( 'options-general.php?page=qrmadmin') );
			exit;
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
			
			wp_register_style ( 'q1', plugin_dir_url (__FILE__)."includes/qrmmainapp/font-awesome/css/font-awesome.css");
			wp_register_style ( 'q2', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/bootstrap.min.css");
			wp_register_style ( 'q3', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/dropzone/dropzone.css");
			wp_register_style ( 'q4', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/ui-grid/ui-grid-unstable.css");
			wp_register_style ( 'q5', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/angular-notify/angular-notify.min.css");
			wp_register_style ( 'q6', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/iCheck/custom.css");
			wp_register_style ( 'q7', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/ngNotify/ng-notify.min.css");
			wp_register_style ( 'q8', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/ngDialog/ngDialog.min.css");
			wp_register_style ( 'q9', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/ngDialog/ngDialog-theme-default.min.css");
			wp_register_style ( 'q10', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/select/select.css");
			wp_register_style ( 'q11', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/select2.css");
			wp_register_style ( 'q12', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/selectize.default.css");
			wp_register_style ( 'q13', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/textAngular/textAngular.css");
			wp_register_style ( 'q14', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/loading-bar/loading-bar.min.css");
			wp_register_style ( 'q15', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/plugins/nv/nv.d3.min.css");
			wp_register_style ( 'q16', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/daterangepicker-bs3.css");
			wp_register_style ( 'q17', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/animate.css");
			wp_register_style ( 'q18', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/style.css");
			wp_register_style ( 'q19', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/qrm_angular.css");
			wp_register_style ( 'q20', plugin_dir_url (__FILE__)."includes/qrmmainapp/css/qrm_styles.css");
			
			// qrm-type-template scripts
			
			wp_register_script ( 's3', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/bootstrap/bootstrap.min.js");
			wp_register_script ( 's4', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/angular/angular.min.js");
			wp_register_script ( 's5', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/angular/angular-animate.min.js");
			wp_register_script ( 's6', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/oclazyload/dist/ocLazyLoad.min.js");
			wp_register_script ( 's7', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/ui-router/angular-ui-router.min.js");
			wp_register_script ( 's8', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/bootstrap/ui-bootstrap-tpls-0.12.0.min.js");
			wp_register_script ( 's9', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/angular-idle/angular-idle.js");
			wp_register_script ( 's10', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/ui-grid/ui-grid-unstable.js");
			wp_register_script ( 's11', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/iCheck/icheck.min.js");
			wp_register_script ( 's12', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/angular-notify/angular-notify.min.js");
			wp_register_script ( 's13', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/dropzone/dropzone.js");
			wp_register_script ( 's14', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/moment.js");
			wp_register_script ( 's15', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/ngDialog/ngDialog.min.js");
			wp_register_script ( 's16', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/textAngular/textAngular.min.js");
			wp_register_script ( 's17', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/textAngular/textAngular-rangy.min.js");
			wp_register_script ( 's18', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/textAngular/textAngular-sanitize.min.js");
			wp_register_script ( 's19', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/d3/d3.min.js");
			wp_register_script ( 's20', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/nv/nv.d3.js");
			wp_register_script ( 's21', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/qrm-common.js");
			wp_register_script ( 's22', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/services.js");
			wp_register_script ( 's23', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/ngNotify/ng-notify.min.js");
			wp_register_script ( 's24', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/select/select.min.js");
			wp_register_script ( 's25', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/sanitize/angular-sanitize.min.js");
			wp_register_script ( 's26', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/plugins/loading-bar/loading-bar.min.js");
			wp_register_script ( 's27', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/app.js");
			wp_register_script ( 's28', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/config.js");
			wp_register_script ( 's29', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/directives.js");
			wp_register_script ( 's30', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/daterangepicker.js");
			wp_register_script ( 's31', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/controllers.js");
			wp_register_script ( 's31m', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/controllers.min.js");
			wp_register_script ( 's32', plugin_dir_url ( __FILE__ ) ."/includes/qrmmainapp/js/qrm.min.js");
				
		
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
			wp_register_script ( 'qrm-common', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/qrm-common.js', array ('qrm-d3'), "", true );
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
			$labels = array(
					'name'               => __( 'Risk Projects', 'riskproject-post-type' ),
					'singular_name'      => __( 'Risk Project', 'riskproject-post-type' ),
					'add_new'            => __( 'Add Risk Project', 'riskproject-post-type' ),
					'add_new_item'       => __( 'Add Risk Project', 'riskproject-post-type' ),
					'edit_item'          => __( 'Edit Risk Project', 'riskproject-post-type' ),
					'new_item'           => __( 'New Risk Project', 'riskproject-post-type' ),
					'view_item'          => __( 'View Risk Project', 'riskproject-post-type' ),
					'search_items'       => __( 'Search Risk Project', 'riskproject-post-type' ),
					'not_found'          => __( 'No risk projects found', 'riskproject-post-type' ),
					'not_found_in_trash' => __( 'No risk projects in the trash', 'riskproject-post-type' ),	
			);
		
			$supports = array(
					'page-attributes',
		
			);
		
			$args = array(
					'labels'          => $labels,
					'supports'        => $supports,
					'public'          => true,
					'capability_type' => 'post',
					'rewrite'         => array( 'slug' => 'riskproject', ), // Permalinks format
					'menu_position'   => 30,
					'hierarchical'    => true,
					'show_ui'         => true,
					'show_in_menu'    => true,
					'menu_icon'       => 'dashicons-portfolio'
			);
		
			$args = apply_filters( 'riskproject_post_type_args', $args );
			register_post_type( 'riskproject', $args );
			/*
			 * Risk Post Type
			*/
		
			$labels = array(
					'name'               => __( 'Risks', 'risk-post-type' ),
					'singular_name'      => __( 'Risk', 'risk-post-type' ),
					'search_items'       => __( 'Search Risks', 'risk-post-type' ),
					'not_found'          => __( 'No risks found', 'risk-post-type' ),
					'not_found_in_trash' => __( 'No risks in the trash', 'risk-post-type' )
			);
		
			$supports = array(
					'comments',
					'title'
			);
		
			$args = array(
					'labels'          => $labels,
					'supports'        => $supports,
					'public'          => true,
					'capability_type' => 'post',
					'rewrite'         => array( 'slug' => 'risk' ), // Permalinks format
					'menu_position'   => 30,
					'menu_icon'       => 'dashicons-sos',
//					'show_in_menu'		 => 'edit.php?post_type=riskproject',
					'show_in_menu'    => true,
					'capabilities' => array(
							'create_posts' => false, // Removes support for the "Add New" function
					),
					'map_meta_cap' => true  // Allows editting and trashing which above disables
			);
		
			$args = apply_filters( 'risk_post_type_args', $args );
			register_post_type( 'risk', $args );
		
		
		
			/*
			 * Incident Post Type
			*/
		
			$labels = array(
					'name'               => __( 'Risk Incidents', 'incident-post-type' ),
					'singular_name'      => __( 'Risk Incident', 'incident-post-type' ),
					'search_items'       => __( 'Search Incidents', 'incident-post-type' ),
					'not_found'          => __( 'No incidents found', 'incident-post-type' ),
					'not_found_in_trash' => __( 'No incidents in the trash', 'incident-post-type' )
			);
		
			$supports = array(
					'comments',
					'title'
			);
		
			$args = array(
					'labels'          => $labels,
					'supports'        => $supports,
					'public'          => true,
					'capability_type' => 'post',
					'rewrite'         => array( 'slug' => 'incident'), // Permalinks format
					'menu_position'   => 30,
					'menu_icon'       => 'dashicons-sos',
					'show_in_menu'	  => true,
					'capabilities' => array(
							'create_posts' => false, // Removes support for the "Add New" function
					),
					'map_meta_cap' => true  // Allows editting and trashing which above disables
			);
		
			$args = apply_filters( 'incident_post_type_args', $args );
			register_post_type( 'incident', $args );
		
			/*
			 * Review Post Type
			*/
		
			$labels = array(
					'name'               => __( 'Risk Reviews', 'review-post-type' ),
					'singular_name'      => __( 'Risk Review', 'review-post-type' ),
					'search_items'       => __( 'Search Reviews', 'review-post-type' ),
					'not_found'          => __( 'No reviews found', 'review-post-type' ),
					'not_found_in_trash' => __( 'No reviews in the trash', 'review-post-type' )
			);
		
			$supports = array(
					'comments',
					'title'
			);
		
			$args = array(
					'labels'          => $labels,
					'supports'        => $supports,
					'public'          => true,
					'capability_type' => 'post',
					'rewrite'         => array( 'slug' => 'review'), // Permalinks format
					'menu_position'   => 30,
					'menu_icon'       => 'dashicons-sos',
//					'show_in_menu'		 => 'edit.php?post_type=riskproject',
					'show_in_menu'		=> true,
					'capabilities' => array(
					'create_posts' => false, // Removes support for the "Add New" function
					),
					'map_meta_cap' => true  // Allows editting and trashing which above disables
			);
		
			$args = apply_filters( 'review_post_type_args', $args );
			register_post_type( 'review', $args );
		}
		
		public function qrmadminpage(){
			
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
			wp_enqueue_script('qrm-sanitize');
			wp_enqueue_script ( 'qrm-dropzone' );
			
			?>

<script type="text/javascript">
					var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
			</script>
<div ng-app="myApp" style="width: 100%; height: 100%">
	<div class="container-fluid">
		<div class="row">
			<div class="col-sm-12 col-md-8 col-lg-8">
				<h2 style="font-weight: 600">Quay Risk Manager</h2>
				<h3>Welcome to Quay Risk Manager</h3>
				<p>Quay Risk Manager (QRM) helps you to manage your portfolio of
					risk</p>
				<p>
					Visit Quay Systems at <a href="http://www.quaysystemm.com.au">www.quaysystemm.com.au</a>
					for tutorials, documentation and support forums on the use and
					managment of Quay Risk Manager. You will also find links on the
					site to other products and services offered by Quay Systems,
					including templates for risk projects utilising industry frameworks
				</p>

				<h4>Getting Started:</h4>
				<div style="padding-left: 20px">
					<p>Users access to the QRM is limited to the users configured in
						the user access table. Select the user who will have access to the
						Quay Risk Manager</p>
					<p>
						Risks are arranged into "Risk Projects" which can be arranged into
						a heirarcical order. Use the "Risk Project" item in the Dashboard
						menu to add a new risk project or select &nbsp; <a
							href="<?php echo admin_url( 'post-new.php?post_type=riskproject') ?>">Add
							New Project</a>
					</p>
					<p>Quay Risk Manager is accessed by via the "Quay Risk Manager"
						page</p>
				</div>

				<div ng-controller="userCtrl">
					<h4 style="margin-top: 20px">User Access Table</h4>
					<div style="width: 100%" id="userGrid" ui-grid="gridOptions"
						ui-grid-auto-resize ng-style="getTableHeight()" class="userGrid"></div>
					<p>Assign the WordPress role "Risk Administrator" to users you want
						to allow to create projects and administer risks, incident and
						reviews</p>
					<div style="text-align: right; margin-top: 15px">
						<button type="button" class="btn btn-w-m btn-sm btn-primary"
							ng-click="saveChanges()">Save Changes</button>
						<button type="button" style="margin-left: 10px"
							class="btn btn-w-m btn-sm btn-warning" ng-click="cancelChanges()">Cancel</button>
					</div>

				</div>
				
				<table class="qrm-settings">
				<tr><td class="qrm-settings">
				

				<div style="margin-top: 15px" >
								<div style="text-align: right; margin-top: 15px" ng-controller="userNameCtrl">
					                    <div class="form-group" style="text-align: left;">
                        <label class="control-label">Select the field of the user name to display: </label>
                        <div>
                            <div class="checkbox">
                                <label>
                                    <input icheck type="radio" name="status" value="userdisplayname"  ng-model="status.val"> Display Name </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input icheck type="radio" name="status" value="userlogin"  ng-model="status.val"> User Login </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input icheck type="radio" name="status" value="usernicename" ng-model="status.val"> User Nice Name </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input icheck type="radio" name="status" value="useremail"  ng-model="status.val"> User Email </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input icheck type="radio" name="status" value="usernickname"  ng-model="status.val"> Nickname </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input icheck type="radio" name="status" value="userfirstname"  ng-model="status.val"> Fisrt Name </label>
                            </div>
                           <div class="checkbox">
                                <label>
                                    <input icheck type="radio" name="status" value="userlastname" ng-model="status.val"> Last Name </label>
                            </div>
                         </div>
                    </div>
					<button type="button" style="margin-left: 10px"
						class="btn btn-w-m btn-sm btn-primary" ng-click="setUserName()">Save</button>
				</div>
				</div>
				</td></tr>
				<tr><td class="qrm-settings">

				<h4>Sample Data</h4>
				<p>Once users have been enabled to use the system, sample data can
					be installed</p>
				<div style="margin-top: 15px" ng-controller="sampleCtrl">
					<table>
						<tr>
							<td colspan=2 style="padding-bottom: 5px">Sample Risks per
								Project:</td>
						</tr>
						<tr>
							<td>Minimum</td>
							<td><input style="width: 80px; margin-left: 15px"
								class="form-control" type="number" name="input" ng-model="min"
								min="5" max="10"></td>
						</tr>
						<tr>
							<td>Maximum</td>
							<td><input style="width: 80px; margin-left: 15px"
								class="form-control" type="number" name="input" ng-model="max"
								min="10" max="20"></td>
							<td style="padding-left: 30px"><button type="button"
									class="btn btn-w-m btn-sm btn-primary"
									ng-click="installSampleProjects()">Install Sample Data</button></td>
						</tr>
					</table>

				</div>
</td></tr>
<tr><td class="qrm-settings">

				<h4 style="margin-top: 15px">Clear QRM Data</h4>
				<p>All the Quay Risk Manager Data can be removed from the site
					(Caution!)</p>
				<div style="text-align: right; margin-top: 15px"
					ng-controller="sampleCtrl">
					<button type="button" style="margin-left: 10px"
						class="btn btn-w-m btn-sm btn-danger" ng-click="removeAllData()">Remove
						All QRM Data</button>
				</div>
</td></tr>
<tr><td class="qrm-settings">
				<div style="margin-top: 20px" ng-controller="sampleCtrl as samp">
					<div style="float: left; width: 250px; text-align: -webkit-center">
						<h4>Data Export</h4>
						<button type="button" class="btn btn-w-m btn-sm btn-primary"
							ng-click="samp.downloadJSON()">Export Data</button>
						<p style="margin-top: 10px">The data from QRM will be dowloaded in
							a single file in a form suitable for importation to another QRM
							instance</p>
					</div>

					<div
						style="width: 300px; float: right; margin-left: 10px; text-align: -webkit-center">
						<h4>Data Import</h4>
						<div dropzone="dropzoneConfigAdmin" class="dropzone dz-clickable"
							style="width: 300px; padding: 15px 15px; margin: 2px">
							<div class="dz-message">
								Drop import file here or click to select.<br />Files must have
								been exported from another instance of QRM
							</div>
						</div>
						<div style="text-align: -webkit-right">
							<button type="button" style="margin-top: 5px"
								class="btn btn-w-m btn-sm btn-primary"
								ng-click="samp.uploadImport()"
								ng-disabled="samp.disableAttachmentButon">Upload & Import</button>
							<button type="button" style="margin-top: 5px; margin-left: 10px"
								class="btn btn-w-m btn-sm btn-warning"
								ng-click="samp.cancelUpload()"
								ng-disabled="samp.disableAttachmentButon">Cancel</button>
						</div>
					</div>
				</div>
				</td></tr>
<tr><td class="qrm-settings">
				<div style="margin-top: 20px" ng-controller="repCtrl as rep">
					<div style="float: left; clear: both">
						<div>
							<h4>Report Generation</h4>
							<p>
								Quay Risk Manager uses a remote web service to generate reports
								in PDF Format<br /> You can produce reports without registering
								for this service, but they will include a watermark<br />
								Contact Quay Systems at <a href="http://www.quaysystems.com.au">http://www.quaysystems.com.au</a>
								to register for the service without water marks
							</p>
							<table style="width: 600px; border-collapse: collapse">
								<tr valign="top">
									<th
										style="width: 150px; padding-top: 0.5em; padding-bottom: 0.5em">Report
										Server URL</th>
									<td><input ng-model="url" style="width: 100%" required></td>
								</tr>
								<tr valign="top">
									<th
										style="width: 150px; padding-top: 0.5em; padding-bottom: 0.5em">Site
										Name</th>
									<td><input ng-model="siteName" style="width: 100%" required></td>
								</tr>
								<tr valign="top">
									<th
										style="width: 150px; padding-top: 0.5em; padding-bottom: 0.5em">Site
										ID</th>
									<td><input ng-model="siteID" style="width: 100%"></td>
								</tr>
								<tr valign="top">
									<th
										style="width: 150px; padding-top: 0.5em; padding-bottom: 0.5em">Site
										Key</th>
									<td><input ng-model="siteKey" style="width: 100%"></td>
								</tr>
								<tr>
									<th></th>
									<td align="right" style="padding-top: 0.75em;"><button
											type="button" class="btn btn-w-m btn-sm btn-primary"
											ng-click="saveChanges()">Save Changes</button></td>
							
							</table>
						</div>
					</div>
				</div>
				</td></tr>
				</table>
			</div>
		</div>
	</div>
</div>


<!-- This is the template used by Dropzone, won't be displayed -->
<div id="preview-template" style="display: none;">

	<div class="dz-preview dz-file-preview" style="margin: 0px">
		<div class="dz-image">
			<img data-dz-thumbnail />
		</div>

		<div class="dz-details">
			<div class="dz-size">
				<span data-dz-size></span>
			</div>
			<div class="dz-filename">
				<span data-dz-name></span>
			</div>
		</div>
		<div class="dz-progress">
			<span class="dz-upload" data-dz-uploadprogress></span>
		</div>
		<div class="dz-error-message">
			<span data-dz-errormessage></span>
		</div>
		<div class="dz-success-mark">

			<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1"
				xmlns="http://www.w3.org/2000/svg"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
			                <!-- Generator: Sketch 3.2.1 (9971) - http://www.bohemiancoding.com/sketch -->
			                <title>Check</title>
			                <desc>Created with Sketch.</desc>
			                <defs></defs>
			                <g id="Page-1" stroke="none" stroke-width="1"
					fill="none" fill-rule="evenodd" sketch:type="MSPage">
			                    <path
					d="M23.5,31.8431458 L17.5852419,25.9283877 C16.0248253,24.3679711 13.4910294,24.366835 11.9289322,25.9289322 C10.3700136,27.4878508 10.3665912,30.0234455 11.9283877,31.5852419 L20.4147581,40.0716123 C20.5133999,40.1702541 20.6159315,40.2626649 20.7218615,40.3488435 C22.2835669,41.8725651 24.794234,41.8626202 26.3461564,40.3106978 L43.3106978,23.3461564 C44.8771021,21.7797521 44.8758057,19.2483887 43.3137085,17.6862915 C41.7547899,16.1273729 39.2176035,16.1255422 37.6538436,17.6893022 L23.5,31.8431458 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"
					id="Oval-2" stroke-opacity="0.198794158" stroke="#747474"
					fill-opacity="0.816519475" fill="#FFFFFF"
					sketch:type="MSShapeGroup"></path>
			                </g>
			            </svg>

		</div>
		<div class="dz-error-mark">

			<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1"
				xmlns="http://www.w3.org/2000/svg"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
			                <!-- Generator: Sketch 3.2.1 (9971) - http://www.bohemiancoding.com/sketch -->
			                <title>error</title>
			                <desc>Created with Sketch.</desc>
			                <defs></defs>
			                <g id="Page-1" stroke="none" stroke-width="1"
					fill="none" fill-rule="evenodd" sketch:type="MSPage">
			                    <g id="Check-+-Oval-2" sketch:type="MSLayerGroup"
					stroke="#747474" stroke-opacity="0.198794158" fill="#FFFFFF"
					fill-opacity="0.816519475">
			                        <path
					d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"
					id="Oval-2" sketch:type="MSShapeGroup"></path>
			                    </g>
			                </g>
			            </svg>

		</div>
	</div>
</div>
<?php 
		}
}
function QRMMaster() {
	return QuayRiskManager::instance ();
}

$GLOBALS['quayriskmanager'] = QRMMaster();
