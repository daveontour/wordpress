<?php
/**
 * Team Post Type
 *
 * @package   Team_Post_Type
 * @license   GPL-2.0+
 */

/**
 * Register metaboxes.
 *
 * @package Team_Post_Type
 */
class Risk_Post_Type_Metaboxes {

	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'risk_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ),  10, 2 );
	}

	/**
	 * Register the metaboxes to be used for the team post type
	 *
	 * @since 0.1.0
	 */
	public function risk_meta_boxes() {
		add_meta_box(
			'risk_fields',
			'Risk Fields',
			array( $this, 'render_meta_boxes' ),
			'risk',
			'normal',
			'high'
		);
	}

   /**
	* The HTML for the fields
	*
	* @since 0.1.0
	*/
	function render_meta_boxes( $post ) {
		
		$handle_d3 = "d3.v3.min.js";
		if (!wp_script_is($handle_d3, $list = 'enqueued')){
			wp_enqueue_script( $handle_d3, "http://d3js.org/d3.v3.min.js", array(), null);
		}
		$handle_qrm = "qrm-common.js";
		if (!wp_script_is($handle_qrm, $list = 'enqueued')){
			wp_enqueue_script( $handle_qrm, plugin_dir_url ( __FILE__ ). "js/qrm-common.js", array(), null);
		}
		$handle_qrm_style = "qrm_styles.css";
		if (!wp_style_is($handle_qrm_style, $list = 'enqueued')){
			wp_enqueue_style( $handle_qrm_style, plugin_dir_url ( __FILE__ ). "style/qrm_styles.css", array(), null);
		}	

		wp_enqueue_script('jquery-ui-selectmenu');
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_style('smooth_theme', "//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css");
		$meta = get_post_custom( $post->ID );
		
		$risk = ! isset( $meta['risk'][0] ) ? new Risk() :unserialize($meta['risk'][0]);

		wp_nonce_field( basename( __FILE__ ), 'risk_fields' ); ?>

		
		<div id="tabs">
		  <ul>
		    <li><a href="#tabs-1">Risk</a></li>
		    <li><a href="#tabs-2">Dates</a></li>
		    <li><a href="#tabs-3">Probability</a></li>
		  </ul>
		  <div id="tabs-1"><?php include 'risk-admin-core-widget.php';	?></div>
		  <div id="tabs-2"><?php include 'risk-admin-date-widget.php';	?></div>
		  <div id="tabs-3"><?php include 'risk-admin-prob-widget.php';	?></div>
		</div>
		<script>
			jQuery(document).ready(function() {
				jQuery('#tabs').tabs();
			});
		</script>
	<?php 
}


	function save_meta_boxes( $post_id ) {

		global $post;

		// Verify nonce
		if ( !isset( $_POST['risk_fields'] ) || !wp_verify_nonce( $_POST['risk_fields'], basename(__FILE__) ) ) {
			return $post_id;
		}
		// Check Autosave
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ( defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) ) {
			return $post_id;
		}
		// Don't save if only a revision
		if ( isset( $post->post_type ) && $post->post_type == 'revision' ) {
			return $post_id;
		}
		// Check permissions
		if ( !current_user_can( 'edit_post', $post->ID ) ) {
			return $post_id;
		}
		$meta['start_date'] = ( isset( $_POST['start_date'] ) ? esc_textarea( $_POST['start_date'] ) : '' );
		$meta['end_date'] = ( isset( $_POST['end_date'] ) ? esc_textarea( $_POST['end_date'] ) : '' );
		
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post->ID, $key, $value );
		}
		
		//Get the risdk for updating
		$meta2 = get_post_custom( $post->ID );		
		$risk = ! isset( $meta2['risk'][0] ) ? new Risk() :unserialize($meta2['risk'][0]);
		
		$risk->endDate = $meta['end_date'];
		$risk->startDate = $meta['start_date'];
		$risk->causes = ( isset( $_POST['causes'] ) ? esc_textarea( $_POST['causes'] ) : '' );
		$risk->consequences = ( isset( $_POST['consequences'] ) ? esc_textarea( $_POST['consequences'] ) : '' );
		
		update_post_meta($post->ID, 'risk', $risk);
	}

}