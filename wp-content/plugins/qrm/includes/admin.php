<?php
wp_enqueue_style ( 'bootstrap' );
wp_enqueue_style ( 'animate' );
wp_enqueue_style ( 'ui-grid' );
wp_enqueue_style ( 'notify' );
wp_enqueue_style ( 'style' );
wp_enqueue_style ( 'qrm-angular' );
wp_enqueue_style ( 'qrm-style' );
wp_enqueue_style ( 'dropzone' );

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
wp_enqueue_script ( 'qrm-dropzone' );

?>


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

				<div class="col-sm-12 col-md-6 col-lg-6">
					<p>Welcome to Quay Risk Manager</p>
					<h4>Getting Started:</h4>
					<div style="padding-left: 20px">

						<p>Users access to the QRM is limited to the users configured in
							the user access table. Select the user who will have access to
							the Quay Risk Manager</p>
						<p>Risks are arranged into "Risk Projects" which can be arranged
							into a heirarcical order. Use the "Risk Project" item in the
							Dashboard menu to add a new risk project</p>
						Quay Risk Manager is accessed by either:

						<ol style="margin-top: 10px; padding-left: 15px">
							<li>Selecting "View" from the list of Projects, Risks, Incident
								or Reviews</li>
							<li>Via the "Quay Risk Manager" page</li>
						</ol>

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
					<div ng-controller="userCtrl">
						<h4 style="margin-top: 20px">Quay Risk Manager User Access Table</h4>
						<div style="width: 100%" id="userGrid" ui-grid="gridOptions"
							ui-grid-auto-resize ng-style="getTableHeight()" class="userGrid"></div>
						<div style="text-align: right; margin-top: 15px">
							<button type="button" class="btn btn-w-m btn-sm btn-primary"
								ng-click="saveChanges()">Save Changes</button>
							<button type="button" style="margin-left: 10px"
								class="btn btn-w-m btn-sm btn-warning"
								ng-click="cancelChanges()">Cancel</button>
						</div>
					</div>

					<h4>Sample Data</h4>
					<p>Once users have been enabled to use the system, sample data can
						be installed or removed</p>

					<div style="text-align: right; margin-top: 15px"
						ng-controller="sampleCtrl">
						<button type="button" class="btn btn-w-m btn-sm btn-primary"
							ng-click="installSampleProjects()">Install Sample Data</button>
						<button type="button" style="margin-left: 10px"
							class="btn btn-w-m btn-sm btn-danger" ng-click="removeSample()">Remove
							Sample Data</button>
					</div>

					
						<h4>Clear QRM Data</h4>
						<p>All the Quay Risk Manager Data can be removed from the site
							(Caution!)</p>
							<div style="text-align: right; margin-top: 15px"
						ng-controller="sampleCtrl">
						<button type="button" style="margin-left: 10px"
							class="btn btn-w-m btn-sm btn-danger" ng-click="removeAllData()">Remove
							All QRM Data</button>

					</div>

						<h4>Re-index QRM Data</h4>
						<p>If the project count of risks appear to be inconsistent, the count can be reindexed. Projects which are recorded with non zero counts of risks cannot be delete. Re-index corrects the count of risks for each project.</p>
							<div style="text-align: right; margin-top: 15px"
						ng-controller="sampleCtrl">
						<button type="button" style="margin-left: 10px"
							class="btn btn-w-m btn-sm btn-danger" ng-click="reindexRiskCount()">Re-index Data</button>

					</div>


					<div style="margin-top: 20px" ng-controller="sampleCtrl as samp">
						<div>
							<div>
								<div
									style="float: left; width: 250px; text-align: -webkit-center">
									<h4>Data Export</h4>
									<button type="button" class="btn btn-w-m btn-sm btn-primary"
										ng-click="samp.downloadJSON()">Export Data</button>
									<p style="margin-top: 10px">The data from QRM will be dowloaded
										in a single file in a form suitable for importation to another
										QRM instance</p>
								</div>

								<div
									style="width: 300px; float: right; margin-left: 10px; text-align: -webkit-center">
									<h4>Data Import</h4>
									<div dropzone="dropzoneConfig" class="dropzone dz-clickable"
										style="width: 300px; padding: 15px 15px; margin: 2px">
										<div class="dz-message">
											Drop import file here or click to select.<br />Files must
											have been exported from another instance of QRM
										</div>
									</div>
									<div>
										<div style="text-align: -webkit-right">
											<button type="button" style="margin-top: 5px"
												class="btn btn-w-m btn-sm btn-primary"
												ng-click="samp.uploadImport()"
												ng-disabled="samp.disableAttachmentButon">Upload & Import</button>
											<button type="button"
												style="margin-top: 5px; margin-left: 10px"
												class="btn btn-w-m btn-sm btn-warning"
												ng-click="samp.cancelUpload()"
												ng-disabled="samp.disableAttachmentButon">Cancel</button>

										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div style="margin-top: 20px" ng-controller="repCtrl as rep">
						<div style="float: left; clear: both">
							<div>
								<h4>Report Generation</h4>
								<p>
									Quay Risk Manager uses a remote web service to generate reports
									in PDF Format<br /> You can produce reports without registering
									for this service, but they will include a watermark<br />
									Contact Quay Systems at <a href="http://www.quaysystems.com.au">http://www.quaysystems.com.au</a>
									to register for the service without water marks
								</p>
								<table style="width: 600px; border-collapse: collapse">
									<tr valign="top">
										<th
											style="width: 150px; padding-top: 0.5em; padding-bottom: 0.5em">Report
											Server URL</th>
										<td><input ng-model="url" style="width: 100%" required></td>
									</tr>
									<tr valign="top">
										<th
											style="width: 150px; padding-top: 0.5em; padding-bottom: 0.5em">Site
											Name</th>
										<td><input ng-model="siteName" style="width: 100%" required></td>
									</tr>
									<tr valign="top">
										<th
											style="width: 150px; padding-top: 0.5em; padding-bottom: 0.5em">Site
											ID</th>
										<td><input ng-model="siteID" style="width: 100%"></td>
									</tr>
									<tr valign="top">
										<th
											style="width: 150px; padding-top: 0.5em; padding-bottom: 0.5em">Site
											Key</th>
										<td><input ng-model="siteKey" style="width: 100%"></td>
									</tr>
									<tr>
										<th></th>
										<td align="right" style="padding-top: 0.75em;"><button
												type="button" class="btn btn-w-m btn-sm btn-primary"
												ng-click="saveChanges()">Save Changes</button></td>
								
								</table>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<!-- This is the template used by Dropzone, won't be displayed -->
<div id="preview-template" style="display: none;">

	<div class="dz-preview dz-file-preview" style="margin: 0px">
		<div class="dz-image">
			<img data-dz-thumbnail />
		</div>

		<div class="dz-details">
			<div class="dz-size">
				<span data-dz-size></span>
			</div>
			<div class="dz-filename">
				<span data-dz-name></span>
			</div>
		</div>
		<div class="dz-progress">
			<span class="dz-upload" data-dz-uploadprogress></span>
		</div>
		<div class="dz-error-message">
			<span data-dz-errormessage></span>
		</div>
		<div class="dz-success-mark">

			<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1"
				xmlns="http://www.w3.org/2000/svg"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
                <!-- Generator: Sketch 3.2.1 (9971) - http://www.bohemiancoding.com/sketch -->
                <title>Check</title>
                <desc>Created with Sketch.</desc>
                <defs></defs>
                <g id="Page-1" stroke="none" stroke-width="1"
					fill="none" fill-rule="evenodd" sketch:type="MSPage">
                    <path
					d="M23.5,31.8431458 L17.5852419,25.9283877 C16.0248253,24.3679711 13.4910294,24.366835 11.9289322,25.9289322 C10.3700136,27.4878508 10.3665912,30.0234455 11.9283877,31.5852419 L20.4147581,40.0716123 C20.5133999,40.1702541 20.6159315,40.2626649 20.7218615,40.3488435 C22.2835669,41.8725651 24.794234,41.8626202 26.3461564,40.3106978 L43.3106978,23.3461564 C44.8771021,21.7797521 44.8758057,19.2483887 43.3137085,17.6862915 C41.7547899,16.1273729 39.2176035,16.1255422 37.6538436,17.6893022 L23.5,31.8431458 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"
					id="Oval-2" stroke-opacity="0.198794158" stroke="#747474"
					fill-opacity="0.816519475" fill="#FFFFFF"
					sketch:type="MSShapeGroup"></path>
                </g>
            </svg>

		</div>
		<div class="dz-error-mark">

			<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1"
				xmlns="http://www.w3.org/2000/svg"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
                <!-- Generator: Sketch 3.2.1 (9971) - http://www.bohemiancoding.com/sketch -->
                <title>error</title>
                <desc>Created with Sketch.</desc>
                <defs></defs>
                <g id="Page-1" stroke="none" stroke-width="1"
					fill="none" fill-rule="evenodd" sketch:type="MSPage">
                    <g id="Check-+-Oval-2" sketch:type="MSLayerGroup"
					stroke="#747474" stroke-opacity="0.198794158" fill="#FFFFFF"
					fill-opacity="0.816519475">
                        <path
					d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"
					id="Oval-2" sketch:type="MSShapeGroup"></path>
                    </g>
                </g>
            </svg>

		</div>
	</div>
</div>

