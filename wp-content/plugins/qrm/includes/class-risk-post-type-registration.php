<?php
/**
 * Team Post Type
 *
 * @package   Risk_Post_Type
 * @license   GPL-2.0+
 */

/**
 * Register post types and taxonomies.
 *
 * @package Team_Post_Type
 */
class Risk_Post_Type_Registrations {

	public $post_type = 'risk';

	public function init() {
		// Add the team post type and taxonomies
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		$this->register_post_type();
		$this->register_custom_taxonomy();
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
		register_post_type( $this->post_type, $args );
		
		
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
				'editor'
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
	
	// Register Custom Taxonomy
	function register_custom_taxonomy() {
	
		$labels = array(
				'name'                       => __( 'Risk Categories', 'text_domain' ),
				'singular_name'              => __( 'Category', 'text_domain' ),
				'menu_name'                  => __( 'Categories', 'text_domain' ),
				'all_items'                  => __( 'All Items', 'text_domain' ),
				'parent_item'                => __( 'Parent Item', 'text_domain' ),
				'parent_item_colon'          => __( 'Parent Item:', 'text_domain' ),
				'new_item_name'              => __( 'New Risk Category Name', 'text_domain' ),
				'add_new_item'               => __( 'Add Risk Category', 'text_domain' ),
				'edit_item'                  => __( 'Edit Risk Category', 'text_domain' ),
				'update_item'                => __( 'Update Risk Category', 'text_domain' ),
				'separate_items_with_commas' => __( 'Separate items with commas', 'text_domain' ),
				'search_items'               => __( 'Search Items', 'text_domain' ),
				'add_or_remove_items'        => __( 'Add or remove items', 'text_domain' ),
				'choose_from_most_used'      => __( 'Choose from the most used items', 'text_domain' ),
				'not_found'                  => __( 'Not Found', 'text_domain' ),
		);
		$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => true,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_in_nav_menus'          => true,
				'show_tagcloud'              => true,
		);
		register_taxonomy( 'qrmcategory', array( 'riskproject' ), $args );	
	
	}
}