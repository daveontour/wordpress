<?php
class QRMSample {
	static function installSample() {
		// return "Install sample";
		$projects = json_decode ( file_get_contents ( __DIR__ . "/projects.json" ) );
		
		$projIDMap = array ();
		$objIDMap = array ();
		$catIDMap = array ();
		$riskIDMap = array ();
		
		$projIDMap [0] = 0;
		$objIDMap [0] = 0;
		$riskIDMap [0] = 0;
		$catIDMap [0] = 0;
		
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		
		foreach ( $projects as $p ) {
			
			$project = json_decode ( $p->meta_value );
			$project->title = $project->title . " (sample)";
			$project->riskProjectManager = $current_user->ID;
			array_push ( $project->ownersID, $current_user->ID );
			array_push ( $project->managersID, $current_user->ID );
			array_push ( $project->usersID, $current_user->ID );
			
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
			update_post_meta ( $postID, "projectriskmanager", get_user_by ( "id", $project->projectRiskManager )->display_name );
			update_post_meta ( $postID, "projectCode", $project->projectCode );
			update_post_meta ( $postID, "projectTitle", $project->title );
			update_post_meta ( $postID, "maxProb", $project->matrix->maxProb );
			update_post_meta ( $postID, "maxImpactb", $project->matrix->maxImpact );
			update_post_meta ( $postID, "sampleqrmdata", true );
		}
		
		foreach ( $projIDMap as $oldValue => $newValue ) {
			$project = json_decode ( get_post_meta ( $newValue, "projectdata", true ) );
			$project->parent_id = $projIDMap [$project->parent_id];
			update_post_meta ( $newValue, "projectdata", json_encode ( $project ) );
			wp_update_post ( array (
					'ID' => $newValue,
					'post_parent' => $project->parent_id 
			) );
		}
		
		$rs = file_get_contents ( __DIR__ . "/risks.json" );
		echo var_dump ( $rs );
		$risks = json_decode ( $rs );
		echo var_dump ( $risks );
		
		foreach ( $risks as $r ) {
			
			$risk = json_decode ( $r->meta_value );
			$risk->title = $risk->title . " (sample)";
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
			update_post_meta ( $postID, "sampleqrmdata", true );
			
			// Fill in all the other meta data
			// Key Data for searching etc
			update_post_meta ( $postID, "projectID", $risk->projectID );
			update_post_meta ( $postID, "riskProjectCode", $risk->riskProjectCode );
			update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
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
		}
		
		$reviews = json_decode ( file_get_contents ( __DIR__ . "/reviews.json" ) );
		foreach ( $reviews as $r ) {
			$review = json_decode ( $r->meta_value );
			$review->responsible = $current_user->ID;
			$review->title = $review->title." (sample)";
			$postID = wp_insert_post ( array (
					'post_content' => $review->description,
					'post_title' => $review->title,
					'post_status' => 'publish',
					'post_type' => 'review',
					'post_author' => $current_user->ID
			) );
			$review->id = $postID;
			$review->reviewCode = "REVIEW-" . $review->id;
			update_post_meta ( $postID, "sampleqrmdata", true );
			
			wp_update_post ( array (
			'ID' => $review->id,
			'post_title' => $review->reviewCode . " - " . $review->title." (sample)",
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
		}
		
		
		$incidents = json_decode ( file_get_contents ( __DIR__ . "/incidents.json" ) );
		foreach ( $incidents as $r ) {
			$incident = json_decode ( $r->meta_value );
			$incident->reportedby = $current_user->ID;
			$incident->title = $incident->title." (sample)";
			$postID = wp_insert_post ( array (
					'post_content' => $incident->description,
					'post_title' => $incident->title,
					'post_status' => 'publish',
					'post_type' => 'incident',
					'post_author' => $current_user->ID
			) );
			$incident->id = $postID;
			$incident->incidentCode = "INCIDENT-" . $incident->id;
			update_post_meta ( $postID, "sampleqrmdata", true );
				
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
		}
	}
	static function removeSample() {
		$args = array (
				'posts_per_page' => - 1,
				'meta_key' => 'sampleqrmdata',
				'meta_value' => true,
				'post_type' => 'riskproject' 
		);
		

		
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
	}
}