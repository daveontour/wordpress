<?php 

final class QRMAutoUpdate {
	private $current_version;
	private $update_path;
	private $plugin_slug;
	private $slug;
	private $license_user;
	private $license_key;
	public function __construct($current_version, $update_path, $plugin_slug, $license_user = '', $license_key = '') {
		// Set the class public variables
		$this->current_version = $current_version;
		$this->update_path = $update_path;
		// Set the License
		$this->license_user = $license_user;
		$this->license_key = $license_key;
		// Set the Plugin Slug
		$this->plugin_slug = $plugin_slug;
		list ( $t1, $t2 ) = explode ( '/', $plugin_slug );
		$this->slug = str_replace ( '.php', '', $t2 );
		// define the alternative API for updating checking
		add_filter ( 'pre_set_site_transient_update_plugins', array (
				&$this,
				'check_update'
		) );
		// Define the alternative response for information checking
		add_filter ( 'plugins_api', array (
				&$this,
				'check_info'
		), 10, 3 );
	}
	public function check_update($transient) {
		if (empty ( $transient->checked )) {
			return $transient;
		}
		// Get the remote version
		$remote_version = $this->getRemote_version ();
		if (isset ( $remote_version )) {
			// If a newer version is available, add the update
			if (version_compare ( $this->current_version, $remote_version->new_version, '<' )) {
				$obj = new stdClass ();
				$obj->slug = $this->slug;
				$obj->new_version = $remote_version->new_version;
				$obj->url = $remote_version->url;
				$obj->plugin = $this->plugin_slug;
				$obj->package = $remote_version->package;
				$transient->response [$this->plugin_slug] = $obj;
			}
		}
		return $transient;
	}
	public function check_info($false, $action, $arg) {
		if (isset ( $arg->slug ) && $arg->slug === $this->slug) {
			$information = $this->getRemote_information ();
			return $information;
		}
		return false;
	}
	public function getRemote_version() {
		$request = wp_remote_post ( $this->update_path . "&fn=version" );
		if (! is_wp_error ( $request ) || wp_remote_retrieve_response_code ( $request ) === 200) {
			return unserialize ( $request ['body'] );
		}
		return false;
	}
	public function getRemote_information() {
		$request = wp_remote_post ( $this->update_path . "&fn=info", $params );
		if (! is_wp_error ( $request ) || wp_remote_retrieve_response_code ( $request ) === 200) {
			return unserialize ( $request ['body'] );
		}
		return false;
	}
	public function getRemote_license() {
		$request = wp_remote_post ( $this->update_path . "&fn=license" );
		if (! is_wp_error ( $request ) || wp_remote_retrieve_response_code ( $request ) === 200) {
			return unserialize ( $request ['body'] );
		}
		return false;
	}
}


?>