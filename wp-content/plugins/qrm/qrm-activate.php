<?php
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
		
		require_once 'qrm-sample.php';
		require_once 'qrm-db.php';
		
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

?>