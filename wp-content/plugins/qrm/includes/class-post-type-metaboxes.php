<?php

class Risk_Post_Type_Metaboxes {

	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'riskproject_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ),  10, 2 );
	}


	public function riskproject_meta_boxes() {
		add_meta_box(
		'riskproject_fields',
		'Project Details',
		array( $this, 'render_riskproject_meta_boxes' ),
		'riskproject',
		'normal',
		'high'
				);
	}	

	function render_riskproject_meta_boxes( $post ) {
		
		wp_enqueue_style ('font-awesome' );
		wp_enqueue_style ('ui-grid' );
		wp_enqueue_style ('notify');
 		wp_enqueue_style ('qrm-angular');
		wp_enqueue_style ('qrm-style');
  		wp_enqueue_style ('select');
 		wp_enqueue_style ('select2');
  		wp_enqueue_style ('selectize');
 		
		wp_enqueue_script('qrm-jquery');
		wp_enqueue_script('qrm-jqueryui');
//		wp_enqueue_script('qrm-boostrap');
 		wp_enqueue_script('qrm-angular');
 		wp_enqueue_script('qrm-projadmin');
	 	wp_enqueue_script('qrm-bootstraptpl');
	 	wp_enqueue_script('qrm-uigrid');
	 	wp_enqueue_script('qrm-notify');
	 	wp_enqueue_script('qrm-d3');
	 	wp_enqueue_script('qrm-common');
	 	wp_enqueue_script('qrm-select');
	 	wp_enqueue_script('qrm-sanitize');
	 	
	 	?>
	 	
	 	<script>
			projectID = <?php echo $post->ID; ?>;
	 	</script>
	 	<style>
	 	.form-table th {
	 		text-align:right
	 	}
	 	</style>
	 	
   <div ng-app="myApp" style="width:100%;height:100%" ng-controller="projectCtrl">
            <?php include 'riskproject-widget.php';?>
   </div>
 	 	
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