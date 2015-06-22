<?php
wp_enqueue_style ( 'bootstrap' );
wp_enqueue_style ( 'animate' );
wp_enqueue_style ( 'ui-grid' );
wp_enqueue_style ( 'notify' );
wp_enqueue_style ( 'style' );
wp_enqueue_style ( 'qrm-angular' );
wp_enqueue_style ( 'qrm-style' );

wp_enqueue_script ( 'qrm-jquery' );
wp_enqueue_script ( 'qrm-jqueryui' );
wp_enqueue_script ( 'qrm-bootstrap' );
wp_enqueue_script ( 'qrm-angular' );
wp_enqueue_script ( 'qrm-bootstraptpl' );
wp_enqueue_script ( 'qrm-uigrid' );
wp_enqueue_script ( 'qrm-common' );
wp_enqueue_script ( 'qrm-ngDialog' );
wp_enqueue_script ( 'qrm-ngNotify' );
wp_enqueue_script ( 'qrm-mainadmin' );

?>
<style>
#wpcontent {
	height: 100%;
	padding-left: 0px;
}
</style>

<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
</script>
<div ng-app="myApp" style="width: 100%; height: 100%">
	<div>
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12 col-md-12 col-lg-12">
					<h2 style="font-weight: 600">Quay Risk Manager</h2>
				</div>

				<div class="col-sm-12 col-md-6 col-lg-4">
					<p>Welcome to Quay Risk Manager</p>
					<h4>Getting Started:</h4>
					<div style="padding-left: 20px">

						<p>Users access to the QRM is limited to the users configured in
							the user access table. Select the user who will have access to
							the Quay Risk Manager</p>
						<p>Risks are arranged into "Risk Projects" which can be arranged
							into a heirarcical order. Use the "Risk Project" item in the
							Dashboard menu to add a new risk project</p>
						<p>Quay Risk Manager is accessed by either:
						
						
						<ol style="margin-top: 10px; padding-left: 15px">
							<li>Selecting "View" from the list of Projects, Risks, Incident
								or Reviews</li>
							<li>Via the "Quay Risk Manager" page
						
						</ol>
						</p>

						<p>Once users have been enabled to use the system, sample data can
							be installed or removed
						
						
						<div style="text-align: right; margin-top: 15px"
							ng-controller="sampleCtrl">
							<button type="button" class="btn btn-w-m btn-sm btn-primary"
								ng-click="installSample()">Install Sample Data</button>
							<button type="button" style="margin-left: 10px"
								class="btn btn-w-m btn-sm btn-warning" ng-click="removeSample()">Remove
								Sample Data</button>
						</div>
						</p>

						<div>
							<h4>Support</h4>
							<div style="padding-left: 20px">
								<p>Email: support@quaysystems.com.au</p>
							</div>
						</div>
						<div>
							<h4>Notes</h4>
							<div style="padding-left: 20px">
								<p>Risk Projects are added and edited via the Dashboard</p>
								<p>Risk, Reviews and Incidents can only be added via the QRM
									system</p>
								<p>Projects can only be deleted if they have no child projects
									and no risks assigned to them</p>
							</div>
						</div>
					</div>

				</div>

				<div class="col-sm-12 col-md-6 col-lg-8" ng-controller="userCtrl">
					<h4>Quay Risk Manager User Access Table</h4>
					<div style="width: 100%" id="userGrid" ui-grid="gridOptions"
						ui-grid-auto-resize ng-style="getTableHeight()" class="userGrid"></div>
					<div style="text-align: right; margin-top: 15px">
						<button type="button" class="btn btn-w-m btn-sm btn-primary"
							ng-click="saveChanges()">Save Changes</button>
						<button type="button" style="margin-left: 10px"
							class="btn btn-w-m btn-sm btn-warning" ng-click="cancelChanges()">Cancel</button>
					</div>
					<div style="">
						<h4>Export Data</h4>

						<div style="margin-top: 15px" ng-controller="sampleCtrl">
							<button type="button" class="btn btn-w-m btn-sm btn-primary"
								ng-click="downloadJSON()">Export JSON</button>
							<button type="button" style="margin-left: 10px"
								class="btn btn-w-m btn-sm btn-primary" ng-click="exportXML()">Export
								XML</button>
						</div>
						</p>
					</div>
					<div style="">
						<h4>Import Data</h4>
						<div style="margin-top: 15px" ng-controller="sampleCtrl">
							<button type="button" class="btn btn-w-m btn-sm btn-primary"
								ng-click="importJSON()">Import JSON</button>
							<button type="button" style="margin-left: 10px"
								class="btn btn-w-m btn-sm btn-primary" ng-click="importXML()">Import
								XML</button>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>

