<?php

class Risk_Post_Type_Metaboxes {

	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'riskproject_meta_boxes' ) );
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
 		wp_enqueue_style ('qrm-angular');
		wp_enqueue_style ('qrm-style');
  		wp_enqueue_style ('select');
 		wp_enqueue_style ('select2');
  		wp_enqueue_style ('selectize');
  		wp_enqueue_style ('ngDialog');
  		wp_enqueue_style ('ngDialogTheme');
  		wp_enqueue_style ('ngNotify');
  		
		wp_enqueue_script('qrm-jquery');
		wp_enqueue_script('qrm-jqueryui');
		wp_enqueue_script('qrm-boostrap');
 		wp_enqueue_script('qrm-angular');
 		wp_enqueue_script('qrm-projadmin');
	 	wp_enqueue_script('qrm-bootstraptpl');
	 	wp_enqueue_script('qrm-uigrid');
	 	wp_enqueue_script('qrm-d3');
	 	wp_enqueue_script('qrm-common');
	 	wp_enqueue_script('qrm-select');
	 	wp_enqueue_script('qrm-sanitize');
	 	wp_enqueue_script('qrm-ngDialog');
	 	wp_enqueue_script('qrm-ngNotify');
	 	wp_enqueue_script('qrm-services');
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
}