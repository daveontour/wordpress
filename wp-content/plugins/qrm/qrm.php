<?php
/*** 
 * Plugin Name: Quay Systems Risk Manager 
 * Description: Quay Risk Manager 
 * Version: 1.0.0
 * Author: Dave Burton
 * License: Commercial
 */

// Register Custom Post Type
if (! defined ( 'WPINC' )) {
	die ();
}

defined ( 'ABSPATH' ) or die ( 'No script kiddies please!' );

class Project{
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
class Risk {

	public $startDate;
	public $endDate;

	public $riskProjectCode;
	public $consequences;
	public $causes;
	public $description;
	public $title;

	public $riskOwner;    //metadata
	public $riskManager;  //metadata
	public $riskManager2;

	public $probInt;     //metadata
	public $impactInt;   //metadata
	public $probDouble;
	public $impactDouble;
	public $probReal;
	public $tolerance;   //metadata
	public $costImpact;

	public $probIntPost;     //metadata
	public $impactIntPost;   //metadata
	public $probDoublePost;
	public $impactDoublePost;
	public $probRealPost;
	public $tolerancePost;  //metadata
	public $costImpactPost;

	public $calcContingencyCost;
	public $estimatedContingencyCost;

	public $calcRemediationCost;
	public $estimatedRemediationCost;

	public $primCategory;  //metadata
	public $secCategory;   //metadata

	public $bTreated = FALSE;

	public $bTreatAvoidence = FALSE;
	public $bTreatTransfer = FALSE;
	public $bTreatMinimisation = FALSE;
	public $bTreatAccept = FALSE;

	public $bImpSafety = FALSE;
	public $bImpCost = FALSE;
	public $bImpTime = FALSE;
	public $bImpSpec = FALSE;
	public $bImpEnviron = FALSE;

	public $mitigationPlanID;

	public $comments;
	public $attachments;
	public $objectives;


	public static function postSave($post_id) {
		;
	}

}
class SmallRisk {
	public $title;
	public $id;
	public $owner;
	public $manager;
	public $description;
	public $currentTolerance;
	public $currentProb;
	public $currentImpact;
	public $riskProjectCode;
	public $rank;
}
class Data {
	public $data;
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
class PageTemplater {
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
class QRM {
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
		$export->reportServerURL = get_option("qrm_reportServerURL");;
		
	}
	static function getServerMeta(){
		$export = new stdObject();
		QRM::exportMetadata($export);
		wp_send_json($export);
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
		$export = QRM::commonJSON($ids,$config->riskIDs);
		QRM::exportMetadata($export);
		
		//Remove risks not included in request
		// Note - need to reindex after filter so a array is returned
		
// 		if (isset($config->projectID) && $config->projectID != null){
// 		$export->risks = array_values(array_filter($export->risks, function ($risk) use ($ids) {
// 			return in_array($risk->projectID,$ids);
// 		}));
// 		}

		
// 		if (isset($config->risks) && sizeof($config->risks) > 0 ){
// 			$export->risks = array_values(array_filter($export->risks, function ($risk) use ($config) {
// 				return in_array($risk->ID, $config->risks);
// 			}));
// 		}
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

		echo json_encode ( $export );

		exit ( 0 );
	}
	static function commonJSON($projectIDs = array(), $riskIDs = array()) {

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
			$risk->audit = json_decode ( get_post_meta ( $post->ID, "audit", true ) );
			$risk->incidents = get_post_meta ( $post->ID, "incident" );
			$risk->reviews = get_post_meta ( $post->ID, "review" );
			$risk->projectID = get_post_meta($post->ID, projectID,true);
			$risk->ID = $post->ID;
			$risk->comments = get_comments ( array (
					'post_id' => $post->ID
			) );
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

		$export = new stdObject ();
		$export->projects = $projects;
		$export->risks = $risks;
		$export->incidents = $incidents;
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

	static function installSample() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		require plugin_dir_path ( __FILE__ ) . '/qrm-sample.php';
		wp_send_json ( array (
				"msg" => QRMSample::installSample ()
		) );
	}
	static function removeSample() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		require plugin_dir_path ( __FILE__ ) . '/qrm-sample.php';
		wp_send_json ( array (
				"msg" => QRMSample::removeSample ()
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
	static function saveReportOptions(){
		$options = json_decode ( file_get_contents ( "php://input" ) );
		
		update_option("qrm_siteKey", $options->siteKey);
		update_option("qrm_siteName", $options->siteName);
		update_option("qrm_siteID", $options->siteID);
		update_option("qrm_reportServerURL", $options->url);
		
	}
	static function saveIncident() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();

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

		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();

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
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();

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
				
			array_push ( $userSummary, $u );
		}

		wp_send_json ( $userSummary );
	}
	static function registerAudit() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();

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

		wp_send_json ( json_decode ( get_post_meta ( $riskID, "audit", true ) ) );
	}
	static function getSiteUsers() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$user_query = new WP_User_Query ( array (
				'fields' => 'all'
		) );
		$data = new Data ();
		$data->data = $user_query->results;
		echo json_encode ( $data, JSON_PRETTY_PRINT );
		exit ();
	}
	static function uploadFile() {
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

		require_once plugin_dir_path ( __FILE__ ) . '/qrm-sample.php';

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
		$projectRiskManager = get_post_meta ( $risk->projectID, "projectRiskManager", true );
		$project = json_decode ( get_post_meta ( $risk->projectID, "projectdata", true ) );
		if (! ($current_user->ID == $projectRiskManager || in_array ( $current_user->ID, $project->ownersID ) || in_array ( $current_user->ID, $project->managersID ) || in_array ( $current_user->ID, $project->usersID ))) {
			wp_send_json ( array ("msg" => "You are not authorised to view this risk") );
			exit ( 0 );
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
		$risks = json_decode ( file_get_contents ( "php://input" ) );

		foreach ( $risks as $risk ) {
			update_post_meta ( $risk->id, "rank", $risk->rank );
		}
		exit ();
	}
	static function getProjects() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $post;
		$args = array (
				'post_type' => 'riskproject',
				'posts_per_page' => - 1
		);
		$the_query = new WP_Query ( $args );
		$projects = array ();

		while ( $the_query->have_posts () ) :
		$the_query->the_post ();
		$project = json_decode ( get_post_meta ( $post->ID, "projectdata", true ) );
		array_push ( $projects, $project );
		endwhile
		;

		$data = new Data ();
		$data->data = $projects;
		wp_send_json ( $data );
	}
	static function getProject() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		$projectID = json_decode ( file_get_contents ( "php://input" ) );
		$project = json_decode ( get_post_meta ( $projectID, "projectdata", true ) );
		$data = new Data ();
		$data->data = $project;
		wp_send_json ( $data );
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
			
		// echo var_dump($post);
			
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

		$data = new Data ();
		$data->data = $risks;
		wp_send_json ( $data );
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

		return $risk->id;
	}
	static function saveRisk() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();

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
			$projectRiskManager = get_post_meta ( $risk->projectID, "projectRiskManager", true );
			$owner = get_post_meta ( $risk->id, "owner", true );
			$manager = get_post_meta ( $risk->id, "manager", true );
			if (! ($current_user->ID == $projectRiskManager || in_array ( $current_user->ID, $project->ownersID ) || in_array ( $current_user->ID, $project->managersID ) )) {
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
		$risk->audit = json_decode ( get_post_meta ( $riskID, "audit", true ) );
		wp_send_json ( $risk );
	}
	static function saveProject() {
		if (! QRM::qrmUser ())
			wp_die ( - 3 );
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();

		$postdata = file_get_contents ( "php://input" );
		$project = json_decode ( $postdata );

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
		update_post_meta ( $postID, "projectCode", $project->projectCode );
		update_post_meta ( $postID, "projectTitle", $project->title );
		update_post_meta ( $postID, "maxProb", $project->matrix->maxProb );
		update_post_meta ( $postID, "maxImpactb", $project->matrix->maxImpact );

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

		// Return all the projects
		QRM::getProjects ();
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

if (! class_exists ( 'QuayRiskManager' )) :
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
			add_action('add_meta_boxes', array( $this, 'riskproject_meta_boxes' ) );
				
			add_filter('upload_mimes', array ($this,'add_custom_mime_types' ));
			add_filter('single_template', array ($this,'get_custom_post_type_template' ));
			add_action('plugins_loaded', array( 'PageTemplater', 'get_instance' ) );
			
			add_action('init', array( $this, 'register_types' ) );
			add_action('init', array ($this,'qrm_init_options' ));
			add_action('init', array ($this,'qrm_scripts_styles' ));
			add_action('init', array ($this,'qrm_init_user_cap' ));
			
			
			add_option("qrm_siteKey", "3182129");
			add_option("qrm_siteName", "Quay Risk Manager Site");
			add_option("qrm_siteID", "Quay Risk Manager Site");
			add_option("qrm_reportServerURL", "http://qrm.quaysystems.com.au:8080/report");
				
			register_activation_hook ( __FILE__,  array ($this,'qrmplugin_activate' ));
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
		
			wp_enqueue_script('qrm-jquery');
			wp_enqueue_script('qrm-jqueryui');
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
			?>
<script>
						projectID = <?php echo $post->ID; ?>;
				 	</script>
<style>
.form-table th {
	text-align: right
}
</style>
<div ng-app="myApp" style="width: 100%; height: 100%"
	ng-controller="projectCtrl">
			            <?php include 'riskproject-widget.php';?>
			  	 </div>
<?php 
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
			$role->add_cap('publish_pages');
			$role->add_cap('publish_posts');
			$role->add_cap( 'delete_pages' );
			$role->add_cap( 'delete_posts' );
			$role->add_cap( 'delete_others_posts' );
			$role->add_cap( 'delete_others_pages' );
			$role->add_cap( 'delete_private_posts' );
			$role->add_cap( 'delete_private_pages' );
			$role->add_cap( 'delete_published_posts' );
			$role->add_cap( 'delete_published_pages' );
			$role->add_cap( 'manage_options' );
			$role->add_cap( 'manage_links' );
			$role->add_cap( 'manage_categories' );
				
			$role = get_role("administrator");
			$role->add_cap( 'risk_admin' );
				
		}
		public function qrmplugin_activate() {
			// Create the page to access the application
			$postdata = array (
					'post_parent' => 0,
					'post_status' => 'publish',
					'post_title' => 'Quay Risk Manager',
					'post_name' => 'riskmanager', /* the slug */
					'page_template' => 'templates/qrm-type-template.php',
					'post_type' => 'page' 
			);
			$pageID = wp_insert_post ( $postdata );
			update_post_meta ( $pageID, '_wp_page_template', 'templates/qrm-type-template.php' );
		}
		
		public  function get_custom_post_type_template($single_template) {
			// Template for viewing risk or projects
			// Template loads the QRM app
			global $post;
		
			if ($post->post_type == 'risk') {
				$single_template = dirname ( __FILE__ ) . '/templates/risk-type-template.php';
			}
			if ($post->post_type == 'riskproject') {
				$single_template = dirname ( __FILE__ ) . '/templates/riskproject-type-template.php';
			}
			if ($post->post_type == 'incident') {
				$single_template = dirname ( __FILE__ ) . '/templates/incident-type-template.php';
			}
			if ($post->post_type == 'review') {
				$single_template = dirname ( __FILE__ ) . '/templates/review-type-template.php';
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
			add_action ( "wp_ajax_removeSample", array (QRM,"removeSample" ) );
			add_action ( "wp_ajax_downloadJSON", array (QRM,"downloadJSON" ) );
			add_action ( "wp_ajax_getJSON", array (QRM,"downloadJSON" ) );
			add_action ( "wp_ajax_getReportRiskJSON", array(QRM, "getReportRiskJSON"));
			add_action ( "wp_ajax_getReportOptions", array(QRM, "getReportOptions"));
			add_action ( "wp_ajax_saveReportOptions", array(QRM, "saveReportOptions"));
			add_action ( "wp_ajax_getServerMeta", array(QRM, "getServerMeta"));
				
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
					$query = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = %s AND post_parent = %s";
					$num_posts = $wpdb->get_var ( $wpdb->prepare ( $query, $post->post_type, $post->ID ) );
					if ($num_posts > 0)
						$allcaps [$caps [0]] = false;
						
						// Prevent deletion if there are still risks
					$query = "SELECT COUNT(*) FROM wp_postmeta  JOIN wp_posts ON wp_postmeta.post_id = wp_posts.ID  AND wp_posts.post_type = 'risk' WHERE meta_key = 'projectID' AND meta_value = %s";
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
			add_menu_page ( 'Quay Risk Risk Manager', 'QRM Risk Manager', 'manage_options', plugin_dir_path ( __FILE__ ) . 'admin.php', '', 'dashicons-smiley', "20.9" );
			remove_meta_box ( 'pageparentdiv', 'riskproject', 'normal' );
			remove_meta_box ( 'pageparentdiv', 'riskproject', 'side' );
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
		
			wp_register_script ( 'qrm-jquery', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/jquery/jquery-2.1.1.min.js', array (), "", true );
			wp_register_script ( 'qrm-jqueryui', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/jquery-ui/jquery-ui.js', array (), "", true );
			wp_register_script ( 'qrm-bootstrap', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/bootstrap/bootstrap.min.js', array (), "", true );
			wp_register_script ( 'qrm-metis', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/metisMenu/jquery.metisMenu.js', array (), "", true );
			wp_register_script ( 'qrm-slimscroll', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/slimscroll/jquery.slimscroll.min.js', array (), "", true );
			wp_register_script ( 'qrm-pace', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/plugins/pace/pace.min.js', array (), "", true );
			wp_register_script ( 'qrm-inspinia', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/inspinia.js', array (
					'qrm-jquery'
			), "", true );
			wp_register_script ( 'qrm-angular', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/angular/angular.min.js', array (), "", true );
			wp_register_script ( 'qrm-projadmin', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/projectadmin.js', array (
					'qrm-jquery',
					'qrm-angular',
					'qrm-common',
					'qrm-services'
			), "", true );
			wp_register_script ( 'qrm-mainadmin', plugin_dir_url ( __FILE__ ) . 'includes/qrmmainapp/js/mainadmin.js', array (
					'qrm-jquery',
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
					'revisions',
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
					'menu_icon'       => 'dashicons-id',
					'menu_position'   => 21
			);
		
			$args = apply_filters( 'riskproject_post_type_args', $args );
			register_post_type( 'riskproject', $args );
			/*
			 * Risk Post Type
			*/
		
			$labels = array(
					'name'               => __( 'Risks', 'risk-post-type' ),
					'singular_name'      => __( 'Risk', 'risk-post-type' ),
					'add_new'            => __( 'Add Risk', 'risk-post-type' ),
					'add_new_item'       => __( 'Add Risk', 'risk-post-type' ),
					'edit_item'          => __( 'Edit Risk', 'risk-post-type' ),
					'new_item'           => __( 'New Risk', 'risk-post-type' ),
					'view_item'          => __( 'View Risk', 'risk-post-type' ),
					'search_items'       => __( 'Search Risk', 'risk-post-type' ),
					'not_found'          => __( 'No risks found', 'risk-post-type' ),
					'not_found_in_trash' => __( 'No risks in the trash', 'risk-post-type' )
			);
		
			$supports = array(
					'revisions',
					'comments',
					'title'
			);
		
			$args = array(
					'labels'          => $labels,
					'supports'        => $supports,
					'public'          => true,
					'capability_type' => 'post',
					'rewrite'         => array( 'slug' => 'risk' ), // Permalinks format
					'menu_position'   => 22,
					'menu_icon'       => 'dashicons-id',
					'show_in_menu'		 => 'edit.php?post_type=riskproject',
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
					'add_new'            => __( 'Add Incident', 'incident-post-type' ),
					'add_new_item'       => __( 'Add Incident', 'incident-post-type' ),
					'edit_item'          => __( 'Edit Incident', 'incident-post-type' ),
					'new_item'           => __( 'New Incident', 'incident-post-type' ),
					'view_item'          => __( 'View Incident', 'incident-post-type' ),
					'search_items'       => __( 'Search Incidnet', 'incident-post-type' ),
					'not_found'          => __( 'No incidents found', 'incident-post-type' ),
					'not_found_in_trash' => __( 'No incidents in the trash', 'incident-post-type' )
			);
		
			$supports = array(
					'revisions',
					'comments',
					'title'
			);
		
			$args = array(
					'labels'          => $labels,
					'supports'        => $supports,
					'public'          => true,
					'capability_type' => 'post',
					'rewrite'         => array( 'slug' => 'incident'), // Permalinks format
					'menu_position'   => 22,
					'menu_icon'       => 'dashicons-id',
					'show_in_menu'		 => 'edit.php?post_type=riskproject',
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
					'add_new'            => __( 'Add Review', 'review-post-type' ),
					'add_new_item'       => __( 'Add Review', 'review-post-type' ),
					'edit_item'          => __( 'Edit Review', 'review-post-type' ),
					'new_item'           => __( 'New Review', 'review-post-type' ),
					'view_item'          => __( 'View Review', 'review-post-type' ),
					'search_items'       => __( 'Search Review', 'review-post-type' ),
					'not_found'          => __( 'No reviews found', 'review-post-type' ),
					'not_found_in_trash' => __( 'No reviews in the trash', 'review-post-type' )
			);
		
			$supports = array(
					'revisions',
					'comments',
					'title'
			);
		
			$args = array(
					'labels'          => $labels,
					'supports'        => $supports,
					'public'          => true,
					'capability_type' => 'post',
					'rewrite'         => array( 'slug' => 'review'), // Permalinks format
					'menu_position'   => 22,
					'menu_icon'       => 'dashicons-id',
					'show_in_menu'		 => 'edit.php?post_type=riskproject',
					'capabilities' => array(
							'create_posts' => false, // Removes support for the "Add New" function
					),
					'map_meta_cap' => true  // Allows editting and trashing which above disables
			);
		
			$args = apply_filters( 'review_post_type_args', $args );
			register_post_type( 'review', $args );
		}
}
endif;

function QRMMaster() {
	return QuayRiskManager::instance ();
}

$GLOBALS['quayriskmanager'] = QRMMaster();
