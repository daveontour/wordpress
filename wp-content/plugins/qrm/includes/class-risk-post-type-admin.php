<?php

class Risk_Post_Type_Admin {

	protected $registration_handler;

	public function __construct( $registration_handler ) {
		$this->registration_handler = $registration_handler;
	}

	public function init() {

		// Show post counts in the dashboard
		add_action( 'right_now_content_table_end', array( $this, 'add_rightnow_counts' ) );
		add_action( 'dashboard_glance_items', array( $this, 'add_glance_counts' ) );

	}

	/**
	 * Add counts to "At a Glance" dashboard widget in WP 3.8+
	 *
	 * @since 0.1.0
	 */
	public function add_glance_counts() {
		$glancer = new Dashboard_Glancer;
		$glancer->add( $this->registration_handler->post_type, array( 'publish', 'pending' ) );
	}
}