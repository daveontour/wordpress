<?php 

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
?>