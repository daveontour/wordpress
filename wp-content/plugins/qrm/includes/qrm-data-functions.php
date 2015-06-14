<?php
class QRM {
	static function router($fn) {
		
		// First do a security check
		$caps = wp_get_current_user ()->caps;
		
		if (! in_array ( "risk_owner", $caps ) && ! in_array ( "risk_manager", $caps ) && ! in_array ( "risk_project_manager", $caps ) && ! in_array ( "risk_user", $caps ) && ! in_array ( "risk_admin", $caps ) && ! in_array ( "administrator", $caps )) // Fail Safe for the site admins have always got access
{
			http_response_code ( 400 );
			echo '{"error":true,"msg":"Not Authorised"}';
			exit ();
		}
		// Pass to the specific function
		switch ($fn) {
			
			case "saveRisk" :
				QRM::saveRisk ();
				break;
			case "getRisk" :
				QRM::getRisk ();
				break;
			case "getAllRisks" :
				QRM::getAllRisks ();
				break;
			case "addComment" :
				QRM::addComments ();
				break;
			case "uploadFile" :
				QRM::uploadFile ();
				break;
			case "getRiskAttachments" :
				QRM::getRiskAttachments ();
				break;
			case "updateRisksRelMatrix" :
				QRM::updateRisksRelMatrix ();
				break;
			case "getSiteUsers" :
				QRM::getSiteUsers ();
				break;
			case "getSiteUsersCap" :
				QRM::getSiteUsersCap ();
				break;
			case "getProjects" :
				QRM::getProjects ();
				break;
			case "getProject" :
				QRM::getProject ();
				break;
			case "saveSiteUsers" :
				QRM::saveSiteUsers ();
			case "saveProject" :
				QRM::saveProject ();
				break;
			
			default :
				wp_die ( $wp->query_vars ['qrmfn'] );
		}
	}
	
	static function getAllIncidents(){
		
	}
	static function getIncident(){
		
	}
	static function saveIncident(){
		
	}
	static function getAllReviews(){
		
	}
	static function getReview(){
		
	}
	static function saveReview(){
		
	}	
	static function getCurrentUser() {
		wp_send_json ( wp_get_current_user () );
	}
	static function getSiteUsersCap() {
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
			$u->bProjMgr = $user->has_cap ( "risk_project_manager" );
			$u->bOwner = $user->has_cap ( "risk_owner" );
			$u->bManager = $user->has_cap ( "risk_manager" );
			$u->bUser = $user->has_cap ( "risk_user" );
			
			array_push ( $userSummary, $u );
		}
		
		wp_send_json ( $userSummary );
	}
	static function registerAudit() {
		
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		
		$audit = json_decode ( file_get_contents ( "php://input" ) );
		$riskID = $audit->riskID;
		
		$a = new stdObject ();
		$a->auditComment = $audit->auditComment;
		$a->auditDate = date ( "M j, Y" );
		$a->auditPerson = $current_user->ID;
		
		$auditObj = json_decode ( get_post_meta ( $riskID, "audit", true ) );
		if ($auditObj == null){
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
		$user_query = new WP_User_Query ( array (
				'fields' => 'all' 
		) );
		$data = new Data ();
		$data->data = $user_query->results;
		echo json_encode ( $data, JSON_PRETTY_PRINT );
		exit ();
	}
	static function uploadFile() {
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
			$parent_post_id = $_POST ["riskID"];
			
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
	static function updateRisksRelMatrix() {
		$risks = json_decode ( file_get_contents ( "php://input" ) );
		
		foreach ( $risks as $risk ) {
			$r = json_decode ( get_post_meta ( $risk->riskID, "riskdata", true ) );
			$r->inherentProb = $risk->newInherentProb;
			$r->inherentImpact = $risk->newInherentImpact;
			$r->treatedProb = $risk->newTreatedProb;
			$r->treatedImpact = $risk->newTreatedImpact;
			
			update_post_meta ( $risk->riskID, "riskdata", json_encode ( $r ) );
		}
		
		exit ();
	}
	static function saveSiteUsers() {
		$users = json_decode ( file_get_contents ( "php://input" ) );
		
		if ($users == null) {
			QRM::getSiteUsers ();
			return;
		}
		foreach ( $users as $u ) {
			
			if (array_key_exists ( "dirty", $u )) {
				
				$wpUser = get_user_by ( "id", $u->ID );
				$wpUser->remove_cap ( "risk_admin" );
				$wpUser->remove_cap ( "risk_project_manager" );
				$wpUser->remove_cap ( "risk_owner" );
				$wpUser->remove_cap ( "risk_manager" );
				$wpUser->remove_cap ( "risk_user" );
				
				if (isset ( $u->caps->risk_admin ) && $u->caps->risk_admin == true) {
					$wpUser->add_cap ( "risk_admin" );
				}
				if (isset ( $u->caps->risk_project_manager ) && $u->caps->risk_project_manager == true) {
					$wpUser->add_cap ( "risk_project_manager" );
				}
				if (isset ( $u->caps->risk_owner ) && $u->caps->risk_owner == true) {
					$wpUser->add_cap ( "risk_owner" );
				}
				if (isset ( $u->caps->risk_manager ) && $u->caps->risk_manager == true) {
					$wpUser->add_cap ( "risk_manager" );
				}
				if (isset ( $u->caps->risk_user ) && $u->caps->risk_user == true) {
					$wpUser->add_cap ( "risk_user" );
				}
			}
		}
		QRM::getSiteUsers ();
	}
	static function addComment() {
		$comment = json_decode ( file_get_contents ( "php://input" ) );
		$time = current_time ( 'mysql' );
		
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		
		$data = array (
				'comment_post_ID' => $comment->riskID,
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
		
		$emptyRisk = new Risk ();
		$emptyRisk->comments = get_comments ( array (
				'post_id' => $comment->riskID 
		) );
		wp_send_json ( $emptyRisk );
	}
	static function getRiskAttachments() {
		$riskID = json_decode ( file_get_contents ( "php://input" ) );
		$attachments = get_children ( array (
				"post_parent" => $riskID,
				"post_type" => "attachment" 
		) );
		wp_send_json ( $attachments );
	}
	static function getRisk() {
		$riskID = json_decode ( file_get_contents ( "php://input" ) );
		$risk = json_decode ( get_post_meta ( $riskID, "riskdata", true ) );
		$risk->comments = get_comments ( array (
				'post_id' => $riskID 
		) );
		$risk->attachments = get_children ( array (
				"post_parent" => $riskID,
				"post_type" => "attachment" 
		) );
		$risk->audit = json_decode ( get_post_meta ( $riskID, "audit", true ) );
		wp_send_json ( $risk );
	}
	static function saveRankOrder() {
		$risks = json_decode ( file_get_contents ( "php://input" ) );
		
		foreach ( $risks as $risk ) {
			update_post_meta ( $risk->id, "rank", $risk->rank );
		}
		
		exit ();
	}
	static function getProjects() {
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
			$project->rankOrder = get_post_meta ( $post->ID, "rankOrder", true );
			array_push ( $projects, $project );
		endwhile
		;
		
		$data = new Data ();
		$data->data = $projects;
		wp_send_json ( $data );
	}
	static function getProject() {
		$projectID = json_decode ( file_get_contents ( "php://input" ) );
		$project = json_decode ( get_post_meta ( $projectID, "projectdata", true ) );
		$data = new Data ();
		$data->data = $project;
		wp_send_json ( $data );
	}
	static function getAllProjectRisks() {
		$projectID = json_decode ( file_get_contents ( "php://input" ) );
		if ($projectID == null) {
			wp_send_json ( array () );
		}
		global $post;
		$args = array (
				'post_type' => 'risk',
				'posts_per_page' => - 1,
				'meta_key' => 'projectID',
				'meta_value' => $projectID 
		);
		
		$the_query = new WP_Query ( $args );
		$risks = array ();
		
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			
			$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
			
			// echo var_dump($post);
			
			$r = new SmallRisk ();
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
			$r->riskProjectCode = $risk->riskProjectCode;
			$risk->rank = get_post_meta ( $post->ID, "rank", true );
			
			array_push ( $risks, $risk );
		endwhile
		;
		
		$data = new Data ();
		$data->data = $risks;
		wp_send_json ( $data );
	}
	static function saveRisk() {
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
				
		$postdata = file_get_contents ( "php://input" );
		$risk = json_decode ( $postdata );
		
		$project = json_decode ( get_post_meta ( $risk->projectID, "projectdata", true ) );
		
		if ($risk->manager == -1 || $risk->manager == ""){
			if (in_array($current_user->ID, $project->managersID)){
				$risk->manager = $current_user->ID;
			} else {
				$risk->manager = $project->projectRiskManager;
			}
		}
		if ($risk->owner == -1 || $risk->owner == ""){
			if (in_array($current_user->ID, $project->ownersID)){
				$risk->owner = $current_user->ID;
			} else {
				$risk->owner = $project->projectRiskManager;
			}
		}		
		$postID = null;
		
		if (! empty ( $risk->id )) {
			// Update the existing post
			$post ['ID'] = $risk->id;
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
			
			update_post_meta ( $postID, "audit", json_encode ( $auditObj ) );
		}
		$risk->riskProjectCode = get_post_meta ( $risk->projectID, "projectCode", true ) . $postID;
		// The Bulk of the data is held in the post's meta data
		update_post_meta ( $postID, "riskdata", json_encode ( $risk ) );
		
		// Key Data for searching etc
		update_post_meta ( $postID, "projectID", $risk->projectID );
		update_post_meta ( $postID, "risProjectCode", $risk->riskProjectCode );
		update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
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
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		
		$postdata = file_get_contents ( "php://input" );
		$project = json_decode ( $postdata );
		
		$postID = null;
		
		if (! empty ( $project->id ) && $project->id > 0) {
			// Update the existing post
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
		update_post_meta ( $postID, "projectriskmanager", get_user_by ( "id", $project->projectRiskManager )->display_name );
		update_post_meta ( $postID, "projectCode", $project->projectCode );
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
}
