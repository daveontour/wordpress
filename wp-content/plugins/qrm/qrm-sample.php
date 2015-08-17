<?php
class QRMSample {
	
	static  function installImport($filename){
		$import = json_decode ( file_get_contents ($filename ) );
		if (QRMSample::processImport($import,false)){
			return "Imported Successfully";
		}
	}
	
	static  function installSample(){
		$import = json_decode ( file_get_contents ( __DIR__ . "/QRMData.json" ) );
		if (QRMSample::processImport($import, true)){
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
		$incidentIDMap[0] = 0;
		$reviewIDMap[0] = 0;
		
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		
		
		foreach ( $import->projects as $project ) {
			
			if ($sample)$project->title = $project->title . "**";
			$project->riskProjectManager = $current_user->ID;
			$project->ownersID = array($current_user->ID);
			$project->mangersID = array($current_user->ID);
			$project->usersID = array($current_user->ID);
			
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
			update_post_meta ( $postID, "sampleqrmdata", $sample );
		}
		
		foreach ( $projIDMap as $oldValue => $newValue ) {
			$project = json_decode ( get_post_meta ( $newValue, "projectdata", true ) );
			if ($project->parent_id != 0)$project->parent_id = $projIDMap [$project->parent_id];
			update_post_meta ( $newValue, "projectdata", json_encode ( $project ) );
			wp_update_post ( array (
					'ID' => $newValue,
					'post_parent' => $project->parent_id 
			) );
		}
		
		$rs = file_get_contents ( __DIR__ . "/risks.json" );
		$risks = json_decode ( $rs );
		
		foreach ( $import->risks as $risk ) {
			
			if ($sample)$risk->title = $risk->title . "**";
			$risk->manager = $current_user->ID;
			$risk->owner = $current_user->ID;
			$risk->projectID = $projIDMap [$risk->projectID];
			
			if ($risk->primcat != null) {
				$risk->primcat->id = $catIDMap [$risk->primcat->id];
				$risk->primcat->parentID = $catIDMap [$risk->primcat->parentID];
				$risk->primcat->projectID = $projIDMap [$risk->primat->projectID];
			}
			if ($risk->seccat != null) {
				$risk->seccat->id = $catIDMap [$risk->seccat->id];
				$risk->seccat->parentID = $catIDMap [$risk->seccat->parentID];
				$risk->seccat->projectID = $projIDMap [$risk->seccat->projectID];
			}
			
			if ($risk->objectives != null) {
				$newObjectiveObject = new stdObject ();
				foreach ( $risk->objectives as $key => $value ) {
					$newObjectiveObject->$objIDMap[$key] = true;
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
			update_post_meta ( $postID, "sampleqrmdata", $sample );
			update_post_meta ( $postID, "audit", json_encode($risk->audit) );
			update_post_meta ( $postID, "projectID", $risk->projectID );
			update_post_meta ( $postID, "riskProjectCode", $risk->riskProjectCode );
			update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
			update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
			update_post_meta ( $postID, "manager", get_user_by ( "id", $risk->manager )->data->display_name );
			update_post_meta ( $postID, "ownerID", $risk->owner);
			update_post_meta ( $postID, "managerID", $risk->manager);
			update_post_meta ( $postID, "project", $project->post_title );
			
			if ($risk->reviews != null){
				foreach ($risk->reviews as $reviewID){
					add_post_meta($postID, 'review', $reviewID);
				}
			}
			if ($risk->incidents != null){
				foreach ($risk->incidents as $incidentID){
					add_post_meta($postID, 'incident', $incidentID);
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
			if ($sample)$review->title = $review->title."**";
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
			update_post_meta ( $postID, "sampleqrmdata", $sample );
			
			wp_update_post ( array (
			'ID' => $review->id,
			'post_title' => $review->reviewCode . " - " . $review->title,
			'post_type' => 'review'
					) );
			
			if ($review->risks != null){
				$newRiskArray = array();
				foreach ( $review->risks as $riskID ) {
					array_push($newRiskArray, $riskIDMap[$riskID]);
				}
				$review->risks = $newRiskArray;
			}
			
			if ($review->riskComments != null){
				foreach ($review->riskComments as $comment){
					$comment->riskID = $riskIDMap[$comment->riskID];
				}
			}
			
			update_post_meta ( $postID, "reviewdata", json_encode ( $review ) );
			update_post_meta ( $postID, "reviewtitle", $review->reviewCode . " - " . $review->title );
		}
		
		//Fix up the risk references to the reviews
		foreach ( $reviewIDMap as $oldID => $newID ) {
			$args = array (
					'posts_per_page' => - 1,
					'meta_key' => 'review',
					'meta_value' => $oldID,
					'post_type' => 'risk'
			);
			foreach ( get_posts ( $args ) as $post ) {
				update_post_meta($post->ID, 'review', intval($newID), $oldID);
			}
		}		
		
		foreach ( $import->incidents as $incident ) {

			$incident->reportedby = $current_user->ID;
			if ($sample)$incident->title = $incident->title."**";
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
			update_post_meta ( $postID, "sampleqrmdata", $sample );
				
			wp_update_post ( array (
			'ID' => $incident->id,
			'post_title' => $incident->incidentCode . " - " . $incident->title,
			'post_type' => 'incident'
					) );
				
			if ($incident->risks != null){
				$newRiskArray = array();
				foreach ( $incident->risks as $riskID ) {
					array_push($newRiskArray, $riskIDMap[$riskID]);
				}
				$incident->risks = $newRiskArray;
			}
				
			update_post_meta ( $postID, "incidentdata", json_encode ( $incident ) );
			update_post_meta ( $postID, "incidenttitle", $incident->incidentCode . " - " . $incident->title );
				
		}
				//Fix up the risk references to the incident
		foreach ( $incidentIDMap as $oldID => $newID ) {
			$args = array (
					'posts_per_page' => - 1,
					'meta_key' => 'incident',
					'meta_value' => $oldID,
					'post_type' => 'risk'
			);
			foreach ( get_posts ( $args ) as $post ) {
				update_post_meta($post->ID, 'incident', intval($newID), $oldID);
			}
		}	
		return true;
	}
	static function removeSample($all = false) {
		
		$args = array (
				'posts_per_page' => - 1,
				'post_type' => 'riskproject' 
		);
		
		if (!$all){
			$args['meta_key'] = "sampleqrmdata";
			$args['meta_value'] = true;
		}
				
		$args ['post_type'] = "risk";
		foreach ( get_posts ( $args ) as $post ) {
			wp_delete_post ( $post->ID, true );
		}
		
		$args ['post_type'] = "review";
		foreach ( get_posts ( $args ) as $post ) {
			wp_delete_post ( $post->ID, true );
		}
		$args ['post_type'] = "incident";
		foreach ( get_posts ( $args ) as $post ) {
			wp_delete_post ( $post->ID, true );
		}
		$args ['post_type'] = "riskproject";
		foreach ( get_posts ( $args ) as $post ) {
			wp_delete_post ( $post->ID, true );
		}
		return "Sample Data Removed";
	}
}