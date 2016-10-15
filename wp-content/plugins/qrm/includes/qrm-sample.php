<?php

require_once (plugin_dir_path ( __FILE__ ) . '../qrm-db.php');

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
		$m = new stdClass();
	
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
	
		switch (intval(floor($qprob))) {
			case 1:
				$lowerlimit = 0.0;
				$upperlimit = $mat->probVal1;
				break;
			case 2:
				$lowerlimit = $mat->probVal1;
				$upperlimit = $mat->probVal2;
				break;
			case 3:
				$lowerlimit = $mat->probVal2;
				$upperlimit = $mat->probVal3;
				break;
			case 4:
				$lowerlimit = $mat->probVal3;
				$upperlimit = $mat->probVal4;
				break;
			case 5:
				$lowerlimit = $mat->probVal4;
				$upperlimit = $mat->probVal5;
				break;
			case 6:
				$lowerlimit = $mat->probVal5;
				$upperlimit = $mat->probVal6;
				break;
			case 7:
				$lowerlimit = $mat->probVal6;
				$upperlimit = $mat->probVal7;
				break;
			case 8:
				$lowerlimit = $mat->probVal7;
				$upperlimit = $mat->probVal8;
				break;
		}
	
		$prob = $lowerlimit + ($upperlimit - $lowerlimit) * ($qprob - floor($qprob));
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
			
			if ($sample)
				$project->title = $project->title . "**";
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
			update_post_meta ( $postID, "sampleqrmdata", $sample );
		}
		
		foreach ( $projIDMap as $oldValue => $newValue ) {
			$project = json_decode ( get_post_meta ( $newValue, "projectdata", true ) );
			if ($project->parent_id != 0)
				$project->parent_id = $projIDMap [$project->parent_id];
			update_post_meta ( $newValue, "projectdata", json_encode ( $project ) );
			wp_update_post ( array (
					'ID' => $newValue,
					'post_parent' => $project->parent_id 
			) );
		}
		
		$rs = file_get_contents ( __DIR__ . "/risks.json" );
		$risks = json_decode ( $rs );
		
		foreach ( $import->risks as $risk ) {
			
			if ($sample)
				$risk->title = $risk->title . "**";
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
			update_post_meta ( $postID, "sampleqrmdata", $sample );
			update_post_meta ( $postID, "audit", json_encode ( $risk->audit ) );
			update_post_meta ( $postID, "projectID", $risk->projectID );
			update_post_meta ( $postID, "riskProjectCode", $risk->riskProjectCode );
			update_post_meta ( $postID, "riskProjectTitle", get_post_meta ( $risk->projectID, "projectTitle", true ) );
			update_post_meta ( $postID, "owner", get_user_by ( "id", $risk->owner )->data->display_name );
			update_post_meta ( $postID, "manager", get_user_by ( "id", $risk->manager )->data->display_name );
			update_post_meta ( $postID, "ownerID", $risk->owner );
			update_post_meta ( $postID, "managerID", $risk->manager );
			update_post_meta ( $postID, "project", $project->post_title );
			
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
			update_post_meta ( $postID, "sampleqrmdata", $sample );
			
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
			update_post_meta ( $postID, "sampleqrmdata", $sample );
			
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
	static function removeSample($all = false) {
		$args = array (
				'posts_per_page' => - 1,
				'post_type' => 'riskproject' 
		);
		
		if (! $all) {
			$args ['meta_key'] = "sampleqrmdata";
			$args ['meta_value'] = true;
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
		return ($all)?"All Quay Risk Manager Data Removed":"Sample Quay Risk Manager Data Removed";
	}
	static function make_seed(){
		list($usec, $sec) = explode(' ', microtime());
		return (float) $sec + ((float) $usec * 100000);
	}

	static function createDummyRiskEntryMultiple($topParent = null, $min, $max) {
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
		
		srand(QRMSample::make_seed());
	
		$risk = json_decode ( QRMSample::$emptyRiskJSON );
		while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$project = json_decode ( get_post_meta ( $post->ID, "projectdata", true ) );
			$risk->projectID = $post->ID;
			$idx = rand ( $min, $max );
			for($i = 0; $i < $idx; $i ++) {
				QRMSample::createDummyRiskEntryCommon ($risk, $project, $topParent );
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
		
		return QRMSample::createDummyRiskEntryCommon($risk, $project);
	
	}
	static function createDummyRiskEntryCommon($risk, $project, $topParent = null) {
		srand(QRMSample::make_seed());
		$lorem = new LoremIpsumGenerator ();
		$now = mktime ();
		$month = 60 * 60 * 24 * 30;
		$day = 60 * 60 * 24;
		$past = (rand ( 0, 1 ) == 1) ? 1 : - 1;
		$start = $now + $past * rand ( 0, 300 ) * $day;
		
		
		$risk->owner = $project->ownersID [array_rand ( $project->ownersID, 1 )];
		$risk->manager = $project->managersID [array_rand ( $project->managersID, 1 )];	
		
		if ($risk->owner == null){
			$risk->owner = $project->projectRiskManager;
		}
		if ($risk->manager == null){
			$risk->manager = $project->projectRiskManager;
		}
		$risk->title = $lorem->getContent ( rand ( 6, 15 ), "plain", false );
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
 		$risk->inherentAbsProb = QRMSample::probFromMatrix($risk->inherentProb, $project->matrix);
 		$risk->treatedAbsProb = QRMSample::probFromMatrix($risk->treatedProb, $project->matrix);
		
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
		
		if ($topParent != null){
			$project->categories = array_merge($project->categories, $topParent->categories);
		}
		$primCats = array_filter ( $project->categories, function ($cat) {
			return $cat->primCat;
		} );
		
		if (count ( $primCats ) > 0) {
			$risk->primcat = $primCats [array_rand ( $primCats, 1 )];
			$secCats = array_filter ( $project->categories, function ($cat) use ($risk) {
				if ($cat->primCat) return false;
				if ($cat->parentID == $risk->primcat->id) return true;
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
		
		WPQRM_Model_Risk::replace($risk);
		
		return $risk->riskProjectCode;
	}
	/**
	 * @return string
	 */
	static function createSampleProjects(){
	
		global $user_identity, $user_email, $user_ID, $current_user;
		get_currentuserinfo ();
		
		
		$minmax = json_decode ( file_get_contents ( "php://input" ) );
	
		$user_query = new WP_User_Query ( array ('fields' => 'all', ) );
		$userSummary = array ();
		foreach ( $user_query->results as $user ) {
			if ($user->caps["risk_admin"] == true || $user->caps["risk_user"] == true){
				array_push ( $userSummary, $user->ID );
			}
		}
	
		$cat = array();
		array_push($cat, json_decode('{"title": "Vendor", "id": -1, "primCat": true, "parentID": 0, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Performance", "id": -2, "primCat": false, "parentID": -1, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Value", "id": -3, "primCat": false, "parentID": -1, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Billing", "id": -4, "primCat": false, "parentID": -1, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Delivery", "id": -5, "primCat": false, "parentID": -1, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Regulatory", "id": -6, "primCat": true, "parentID": 0, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Federal", "id": -7, "primCat": false, "parentID": -6, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "State", "id": -8, "primCat": false, "parentID": -6, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "ASIC", "id": -9, "primCat": false, "parentID": -6, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Environmental", "id": -10, "primCat": false, "parentID": -6, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Customer", "id": -11, "primCat": true, "parentID": 0, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Satisfaction", "id": -12, "primCat": false, "parentID": -11, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Relationship", "id": -13, "primCat": false, "parentID": -11, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Billing", "id": -14, "primCat": false, "parentID": -11, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Creit", "id": -15, "primCat": false, "parentID": -11, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Employee", "id": -16, "primCat": true, "parentID": 0, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Satisfaction", "id": -17, "primCat": false, "parentID": -16, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Relationship", "id": -18, "primCat": false, "parentID": -16, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Award", "id": -19, "primCat": false, "parentID": -16, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Productivity", "id": -20, "primCat": false, "parentID": -16, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Financial", "id": -21, "primCat": true, "parentID": 0, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Cashflow", "id": -22, "primCat": false, "parentID": -21, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Treasury", "id": -23, "primCat": false, "parentID": -21, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Transperancy", "id": -24, "primCat": false, "parentID": -21, "projectID": -1}'));
		array_push($cat, json_decode('{"title": "Viability", "id": -25, "primCat": false, "parentID": -21, "projectID": -1}'));
	
		
		$p1 = QRMSample::singleProject("Quay Systems", "QS", $userSummary, 0, $cat);		
 		QRMSample::singleProject("Board of Directors", "BOD", $userSummary, $p1->id);
		QRMSample::singleProject("Executive", "EXECE", $userSummary, $p1->id);
		$it = QRMSample::singleProject("Information Technology", "IT", $userSummary, $p1->id);
		QRMSample::singleProject("Information Technology Security", "ITSEC", $userSummary, $it->id);
		$itops = QRMSample::singleProject("Information Technology Operations", "ITOPS", $userSummary, $it->id);
		QRMSample::singleProject("Information Technology End User", "ITEUC", $userSummary, $itops->id);
		QRMSample::singleProject("Information Technology Data Center", "ITDC", $userSummary, $itops->id);
		QRMSample::singleProject("Information Technology Network", "ITNW", $userSummary, $itops->id);
		
		QRMSample::singleProject("Sales", "SALE", $userSummary, $p1->id);
		QRMSample::singleProject("Marketing", "MARK", $userSummary, $p1->id);
		QRMSample::singleProject("Business Services", "BIZ", $userSummary, $p1->id);
		QRMSample::singleProject("Human Resources", "HR", $userSummary, $p1->id);
		QRMSample::singleProject("Manufacturing", "MAN", $userSummary, $p1->id);
		QRMSample::singleProject("Customer Support", "CUS", $userSummary, $p1->id);
		
		QRMSample::createDummyRiskEntryMultiple($p1,$minmax[0],$minmax[1]);
			
		return "Sample Data Installed";
	}
	
	static function singleProject($title, $id, $users, $parent = 0, $cat = null){
	
		global $user_identity, $user_email, $user_ID, $current_user;
		
		$p = QRMSample::getSampleProject();
		$p->parent_id = $parent;
		$p->title = $title;
		$p->projectCode = $id;
		$p->projectRiskManager = $current_user->ID;
		$p->ownersID = $users;
		$p->managersID = $users;
		if ($cat != null){
			$p->categories = $cat;
		}
		$p = QRMSample::saveSampleProject($p);
		WPQRM_Model_Project::replace($p);	
		return $p;
	}
	static function saveSampleProject($project){
	
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
class LoremIpsumGenerator {
	/**
	 * Copyright (c) 2009, Mathew Tinsley (tinsley@tinsology.net)
	 * All rights reserved.
	 *
	 * Redistribution and use in source and binary forms, with or without
	 * modification, are permitted provided that the following conditions are met:
	 * * Redistributions of source code must retain the above copyright
	 * notice, this list of conditions and the following disclaimer.
	 * * Redistributions in binary form must reproduce the above copyright
	 * notice, this list of conditions and the following disclaimer in the
	 * documentation and/or other materials provided with the distribution.
	 * * Neither the name of the organization nor the
	 * names of its contributors may be used to endorse or promote products
	 * derived from this software without specific prior written permission.
	 *
	 * THIS SOFTWARE IS PROVIDED BY MATHEW TINSLEY ''AS IS'' AND ANY
	 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	 * DISCLAIMED. IN NO EVENT SHALL <copyright holder> BE LIABLE FOR ANY
	 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	 */
	private $words, $wordsPerParagraph, $wordsPerSentence;
	function __construct($wordsPer = 100) {
		$this->wordsPerParagraph = $wordsPer;
		$this->wordsPerSentence = 24.460;
		$this->words = array (
				'lorem',
				'ipsum',
				'dolor',
				'sit',
				'amet',
				'consectetur',
				'adipiscing',
				'elit',
				'curabitur',
				'vel',
				'hendrerit',
				'libero',
				'eleifend',
				'blandit',
				'nunc',
				'ornare',
				'odio',
				'ut',
				'orci',
				'gravida',
				'imperdiet',
				'nullam',
				'purus',
				'lacinia',
				'a',
				'pretium',
				'quis',
				'congue',
				'praesent',
				'sagittis',
				'laoreet',
				'auctor',
				'mauris',
				'non',
				'velit',
				'eros',
				'dictum',
				'proin',
				'accumsan',
				'sapien',
				'nec',
				'massa',
				'volutpat',
				'venenatis',
				'sed',
				'eu',
				'molestie',
				'lacus',
				'quisque',
				'porttitor',
				'ligula',
				'dui',
				'mollis',
				'tempus',
				'at',
				'magna',
				'vestibulum',
				'turpis',
				'ac',
				'diam',
				'tincidunt',
				'id',
				'condimentum',
				'enim',
				'sodales',
				'in',
				'hac',
				'habitasse',
				'platea',
				'dictumst',
				'aenean',
				'neque',
				'fusce',
				'augue',
				'leo',
				'eget',
				'semper',
				'mattis',
				'tortor',
				'scelerisque',
				'nulla',
				'interdum',
				'tellus',
				'malesuada',
				'rhoncus',
				'porta',
				'sem',
				'aliquet',
				'et',
				'nam',
				'suspendisse',
				'potenti',
				'vivamus',
				'luctus',
				'fringilla',
				'erat',
				'donec',
				'justo',
				'vehicula',
				'ultricies',
				'varius',
				'ante',
				'primis',
				'faucibus',
				'ultrices',
				'posuere',
				'cubilia',
				'curae',
				'etiam',
				'cursus',
				'aliquam',
				'quam',
				'dapibus',
				'nisl',
				'feugiat',
				'egestas',
				'class',
				'aptent',
				'taciti',
				'sociosqu',
				'ad',
				'litora',
				'torquent',
				'per',
				'conubia',
				'nostra',
				'inceptos',
				'himenaeos',
				'phasellus',
				'nibh',
				'pulvinar',
				'vitae',
				'urna',
				'iaculis',
				'lobortis',
				'nisi',
				'viverra',
				'arcu',
				'morbi',
				'pellentesque',
				'metus',
				'commodo',
				'ut',
				'facilisis',
				'felis',
				'tristique',
				'ullamcorper',
				'placerat',
				'aenean',
				'convallis',
				'sollicitudin',
				'integer',
				'rutrum',
				'duis',
				'est',
				'etiam',
				'bibendum',
				'donec',
				'pharetra',
				'vulputate',
				'maecenas',
				'mi',
				'fermentum',
				'consequat',
				'suscipit',
				'aliquam',
				'habitant',
				'senectus',
				'netus',
				'fames',
				'quisque',
				'euismod',
				'curabitur',
				'lectus',
				'elementum',
				'tempor',
				'risus',
				'cras' 
		);
	}
	function getContent($count, $format = 'html', $loremipsum = true) {
		$format = strtolower ( $format );
		
		if ($count <= 0)
			return '';
		
		switch ($format) {
			case 'txt' :
				return $this->getText ( $count, $loremipsum );
			case 'plain' :
				return $this->getPlain ( $count, $loremipsum );
			default :
				return $this->getHTML ( $count, $loremipsum );
		}
	}
	private function getWords(&$arr, $count, $loremipsum) {
		$i = 0;
		if ($loremipsum) {
			$i = 2;
			$arr [0] = 'lorem';
			$arr [1] = 'ipsum';
		}
		
		for($i; $i < $count; $i ++) {
			$index = array_rand ( $this->words );
			$word = $this->words [$index];
			// echo $index . '=>' . $word . '<br />';
			
			if ($i > 0 && $arr [$i - 1] == $word)
				$i --;
			else
				$arr [$i] = $word;
		}
	}
	private function getPlain($count, $loremipsum, $returnStr = true) {
		$words = array ();
		$this->getWords ( $words, $count, $loremipsum );
		// print_r($words);
		
		$delta = $count;
		$curr = 0;
		$sentences = array ();
		while ( $delta > 0 ) {
			$senSize = $this->gaussianSentence ();
			// echo $curr . '<br />';
			if (($delta - $senSize) < 4)
				$senSize = $delta;
			
			$delta -= $senSize;
			
			$sentence = array ();
			for($i = $curr; $i < ($curr + $senSize); $i ++)
				$sentence [] = $words [$i];
			
			$this->punctuate ( $sentence );
			$curr = $curr + $senSize;
			$sentences [] = $sentence;
		}
		
		if ($returnStr) {
			$output = '';
			foreach ( $sentences as $s )
				foreach ( $s as $w )
					$output .= $w . ' ';
			
			return $output;
		} else
			return $sentences;
	}
	private function getText($count, $loremipsum) {
		$sentences = $this->getPlain ( $count, $loremipsum, false );
		$paragraphs = $this->getParagraphArr ( $sentences );
		
		$paragraphStr = array ();
		foreach ( $paragraphs as $p ) {
			$paragraphStr [] = $this->paragraphToString ( $p );
		}
		
		$paragraphStr [0] = "\t" . $paragraphStr [0];
		return implode ( "\n\n\t", $paragraphStr );
	}
	private function getParagraphArr($sentences) {
		$wordsPer = $this->wordsPerParagraph;
		$sentenceAvg = $this->wordsPerSentence;
		$total = count ( $sentences );
		
		$paragraphs = array ();
		$pCount = 0;
		$currCount = 0;
		$curr = array ();
		
		for($i = 0; $i < $total; $i ++) {
			$s = $sentences [$i];
			$currCount += count ( $s );
			$curr [] = $s;
			if ($currCount >= ($wordsPer - round ( $sentenceAvg / 2.00 )) || $i == $total - 1) {
				$currCount = 0;
				$paragraphs [] = $curr;
				$curr = array ();
				// print_r($paragraphs);
			}
			// print_r($paragraphs);
		}
		
		return $paragraphs;
	}
	private function getHTML($count, $loremipsum) {
		$sentences = $this->getPlain ( $count, $loremipsum, false );
		$paragraphs = $this->getParagraphArr ( $sentences );
		// print_r($paragraphs);
		
		$paragraphStr = array ();
		foreach ( $paragraphs as $p ) {
			$paragraphStr [] = "<p>" . $this->paragraphToString ( $p, true ) . '</p>';
		}
		
		// add new lines for the sake of clean code
		return implode ( " ", $paragraphStr );
	}
	private function paragraphToString($paragraph, $htmlCleanCode = false) {
		$paragraphStr = '';
		foreach ( $paragraph as $sentence ) {
			foreach ( $sentence as $word )
				$paragraphStr .= $word . ' ';
			
			if ($htmlCleanCode)
				$paragraphStr .= " ";
		}
		return $paragraphStr;
	}
	
	/*
	 * Inserts commas and periods in the given
	 * word array.
	 */
	private function punctuate(& $sentence) {
		$count = count ( $sentence );
		$sentence [$count - 1] = $sentence [$count - 1] . '.';
		
		if ($count < 4)
			return $sentence;
		
		$commas = $this->numberOfCommas ( $count );
		
		for($i = 1; $i <= $commas; $i ++) {
			$index = ( int ) round ( $i * $count / ($commas + 1) );
			
			if ($index < ($count - 1) && $index > 0) {
				$sentence [$index] = $sentence [$index] . ',';
			}
		}
	}
	
	/*
	 * Determines the number of commas for a
	 * sentence of the given length. Average and
	 * standard deviation are determined superficially
	 */
	private function numberOfCommas($len) {
		$avg = ( float ) log ( $len, 6 );
		$stdDev = ( float ) $avg / 6.000;
		
		return ( int ) round ( $this->gauss_ms ( $avg, $stdDev ) );
	}
	
	/*
	 * Returns a number on a gaussian distribution
	 * based on the average word length of an english
	 * sentence.
	 * Statistics Source:
	 * http://hearle.nahoo.net/Academic/Maths/Sentence.html
	 * Average: 24.46
	 * Standard Deviation: 5.08
	 */
	private function gaussianSentence() {
		$avg = ( float ) 24.460;
		$stdDev = ( float ) 5.080;
		
		return ( int ) round ( $this->gauss_ms ( $avg, $stdDev ) );
	}
	
	/*
	 * The following three functions are used to
	 * compute numbers with a guassian distrobution
	 * Source:
	 * http://us.php.net/manual/en/function.rand.php#53784
	 */
	private function gauss() { // N(0,1)
	  // returns random number with normal distribution:
	  // mean=0
	  // std dev=1
	  
		// auxilary vars
		$x = $this->random_0_1 ();
		$y = $this->random_0_1 ();
		
		// two independent variables with normal distribution N(0,1)
		$u = sqrt ( - 2 * log ( $x ) ) * cos ( 2 * pi () * $y );
		$v = sqrt ( - 2 * log ( $x ) ) * sin ( 2 * pi () * $y );
		
		// i will return only one, couse only one needed
		return $u;
	}
	private function gauss_ms($m = 0.0, $s = 1.0) {
		return $this->gauss () * $s + $m;
	}
	private function random_0_1() {
		return ( float ) rand () / ( float ) getrandmax ();
	}
}