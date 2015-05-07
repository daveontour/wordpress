<?php ?>

<div style="width: 100%">
	<h2>New Risk Project</h2>

	<div class="row">
		<div class="col-lg-6">
			<form class="form-horizontal">

				<div class="form-group">
					<label class="col-xs-4 col-sm-3 control-label">Title</label>
					<div class="col-xs-8 col-sm-9">
						<input class="form-control" ng-model="proj.title" required>
					</div>
				</div>

				<div class="form-group">
					<label class="col-xs-4 col-sm-3 control-label">Description</label>
					<div class="col-xs-8 col-sm-9">
						<textarea class="form-control" style="height: 100px"
							ng-model="proj.description"></textarea>
					</div>
				</div>

				<div class="form-group">
					<label class="col-xs-4 col-sm-3 control-label">Project Risk Manager</label>
					<div class="col-xs-8 col-sm-9">
						<select ng-model="proj.projectRiskManager"
							ng-options="person.data.display_name for person in ref.riskProjectManagers track by person.ID"
							class="form-control"></select>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label">&nbsp;</label>
					<div class="col-sm-9">
						<label class="checkbox-inline" style="padding-left: 0px"> <input
							icheck type="checkbox" ng-model="proj.useAdvancedLiklihood">
							Enable Advanced Likelihood
						</label>
					</div>

					<label class="col-sm-3 control-label">&nbsp;</label>
					<div class="col-sm-9">
						<label class="checkbox-inline" style="padding-left: 0px"> <input
							icheck type="checkbox" ng-model="proj.useAdvancedConsequences">
							Enable Quantitative Consequences
						</label>
					</div>
				</div>


			</form>

			<label class="col-xs-4 col-sm-3 control-label">Tolerance Matrix</label>
			<div class="col-sm-9" style="text-align: right">
			<label class="col-sm-6 control-label">Max. Probability</label>
			<div class="col-sm-6">
				<input class="form-control" type="number" name="input"
					ng-model="proj.matrix.maxProb" ng-change="matrixChange()" min="2"
					max="8">
			</div>
			<label class="col-sm-6 control-label">Max. Impact</label>
			<div class="col-sm-6">
				<input class="form-control" type="number" name="input"
					ng-model="proj.matrix.maxImpact" ng-change="matrixChange()" min="2"
					max="8">
			</div>
						<div class="col-sm-12">
				<div style="width: 180px; height: 180px;margin-left:auto; margin-right:auto;margin-top:25px" id="svgDIV"></div>
				<div style="width: 100%;text-align:center;margin-top:5px">Click on cells to change tolerance</div>
				</div>

</div>
		</div>
		<div class="col-lg-6">
			<div>
				<label class="col-lg-12 control-label">Risk Owners</label>
			</div>
			<div style="width: 100%; margin-top: 25px" id="ownerGrid"
				ui-grid="gridOwnerOptions" ui-grid-auto-resize class="userGrid"></div>
			<div style="margin-top: 10px">
				<label class="col-lg-12 control-label">Risk Managers</label>
			</div>
			<div style="width: 100%; margin-top: 35px" id="managerGrid"
				ui-grid="gridManagerOptions" ui-grid-auto-resize class="userGrid"></div>
			<div style="margin-top: 10px">
				<label class="col-lg-12 control-label">Risk Users</label>
			</div>
			<div style="width: 100%; margin-top: 35px" id="userGrid"
				ui-grid="gridUserOptions" ui-grid-auto-resize class="userGrid"></div>
		</div>
	</div>


	<div style="float: right; margin-top: 15px">
		<button type="button" class="btn btn-w-m btn-primary"
			ng-click="alert(JSON.stringify(proj))">Save Changes</button>
		<button type="button" style="margin-left: 10px"
			class="btn btn-w-m btn-warning" ng-click="cancelChanges()">Cancel</button>
	</div>
</div>