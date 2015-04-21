<?php



class Risk_Post_Type {


	const VERSION = '0.1.0';
	const PLUGIN_SLUG = 'risk-post-type';
	protected $registration_handler;

	public function __construct( $registration_handler ) {

		$this->registration_handler = $registration_handler;

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );;

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since 0.1.0
	 */
	public function activate() {
		$this->registration_handler->register();
		flush_rewrite_rules();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since 0.1.0
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 0.1.0
	 */
	public function load_plugin_textdomain() {
		$domain = self::PLUGIN_SLUG;
		load_plugin_textdomain( $domain, FALSE, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages' );
	}
}