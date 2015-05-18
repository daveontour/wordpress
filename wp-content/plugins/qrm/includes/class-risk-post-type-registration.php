<?php

class Risk_Post_Type_Registrations {

	public function init() {
		// Add the team post type and taxonomies
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		$this->register_post_type();
	}

	protected function register_post_type() {
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
			'not_found_in_trash' => __( 'No risks in the trash', 'risk-post-type' ),
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
			'rewrite'         => array( 'slug' => 'risk', ), // Permalinks format
			'menu_position'   => 30,
			'menu_icon'       => 'dashicons-id',
		);

		$args = apply_filters( 'risk_post_type_args', $args );
		register_post_type( 'risk', $args );
		
		
		$labels = array(
				'name'               => __( 'Risk Project', 'riskproject-post-type' ),
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
				'title',
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
		);
		
		$args = apply_filters( 'riskproject_post_type_args', $args );
		register_post_type( 'riskproject', $args );
	}

}