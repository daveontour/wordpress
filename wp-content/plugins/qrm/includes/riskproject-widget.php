<?php ?>

 <style>
    .select2 > .select2-choice.ui-select-match {
      /* Because of the inclusion of Bootstrap */
      height: 29px;
    }

  </style>

<table class="form-table">
	<tr valign="top">
		<th scope="row">Project Title</th>
		<td><input ng-model="proj.title" style="width:100%" required></td>
	</tr>
		<tr valign="top">
		<th scope="row">Project Description</th>
		<td><textarea ng-model="proj.description" rows=4 style="width:100%" required></textarea></td>
	</tr>
		<tr valign="top">
		<th scope="row">Project Code</th>
		<td><input ng-model="proj.projectCode" required></td>
	</tr>
	<tr valign="top">
		<th scope="row">Risk Project Manager</th>
		<td><ui-select ng-model="proj.projectRiskManager" theme="select2" search-enabled=false style="width:350px"> <ui-select-match
				allow-clear=false placeholder="Select Project Risk Manager...">{{$select.selected.display_name}}</ui-select-match>
			<ui-select-choices
				repeat="person.ID as person in ref.riskProjectManagers">
			<div>
				<span ng-bind-html="person.display_name"></span>
			</div>
			</ui-select-choices> </ui-select></td>
	</tr>
	<tr valign="top">
		<th scope="row">Parent Project</th>
		<td><ui-select ng-model="proj.parent_id" theme="select2"  search-enabled=false
				on-select="projectSelect($item, $model)" style="width:350px"> <ui-select-match
				allow-clear=true placeholder="Select parent project...">{{$select.selected.title}}</ui-select-match>
			<ui-select-choices repeat="project.id as project in sortedParents track by project.id">
			<div ng-style="rowStyle(project)">
				<span ng-bind-html="project.title"></span>
			</div>
			</ui-select-choices> </ui-select></td>
	</tr>
	<tr valign="top">
		<th scope="row"></th>
		<td><label class="checkbox-inline" style="padding-left: 0px"> <input
				 type="checkbox" ng-model="proj.useAdvancedConsequences">
				Enable Quantitative Consequences
		</label></td>
	</tr>
	<tr valign="top">
		<th scope="row">Tolerance Matrix</th>
		<td>
			<div
				style="width: 220px; margin-left: 10px; margin-top: 10px; float: left">
				<div style="width: 100%">
					<input style="width: 80px; clear: both; margin-left: 15px"
						class="form-control pull-right" type="number" name="input"
						ng-model="proj.matrix.maxProb" ng-change="matrixChange()" min="2"
						max="8"> <label class="control-label pull-right">Max. Prob</label>

					<input style="width: 80px; clear: both; margin-left: 15px"
						class="form-control pull-right" type="number" name="input"
						ng-model="proj.matrix.maxImpact" ng-change="matrixChange()"
						min="2" max="8">
				</div>
				<label class="control-label pull-right">Max. Impact</label>

				<div style="width: 220px; height: 220px; margin-top: 80px"
					id="svgDIV"></div>


			</div>


			<div
				style="width: 220px; margin-left: 10px; margin-top: 10px; float: left">

				<div ng-show="proj.matrix.maxProb > 7">
					<input style="width: 80px; margin-left: 15px"
						class="form-control pull-right" type="number" name="input"
						ng-model="proj.matrix.probVal8" ng-change="matrixChange()"
						max="100"> <label class="pull-right">P8 Percentage</label>
				</div>

				<div ng-show="proj.matrix.maxProb > 6">
					<input style="width: 80px; margin-left: 15px; clear: both"
						class="form-control pull-right" type="number" name="input"
						ng-model="proj.matrix.probVal7" ng-change="matrixChange()"
						max="100"> <label class="pull-right">P7 Percentage</label>
				</div>

				<div ng-show="proj.matrix.maxProb > 5">
					<input style="width: 80px; margin-left: 15px; clear: both"
						class="form-control pull-right" type="number" name="input"
						ng-model="proj.matrix.probVal6" ng-change="matrixChange()"
						max="100"> <label class="pull-right">P6 Percentage</label>
				</div>

				<div ng-show="proj.matrix.maxProb > 4">
					<input style="width: 80px; margin-left: 15px; clear: both"
						class="form-control pull-right" type="number" name="input"
						ng-model="proj.matrix.probVal5" ng-change="matrixChange()"
						max="100"> <label class="pull-right">P5 Percentage</label>
				</div>

				<div ng-show="proj.matrix.maxProb > 3">
					<input style="width: 80px; margin-left: 15px; clear: both"
						class="form-control pull-right" type="number" name="input"
						ng-model="proj.matrix.probVal4" ng-change="matrixChange()"
						max="100"> <label class="pull-right">P4 Percentage</label>
				</div>

				<div ng-show="proj.matrix.maxProb > 2">
					<input style="width: 80px; margin-left: 15px; clear: both"
						class="form-control pull-right" type="number" name="input"
						ng-model="proj.matrix.probVal3" ng-change="matrixChange()"
						max="100"> <label class="pull-right">P3 Percentage</label>
				</div>

				<div>
					<input style="width: 80px; margin-left: 15px; clear: both"
						class="form-control pull-right" type="number" name="input"
						ng-model="proj.matrix.probVal2" ng-change="matrixChange()"
						max="100"> <label class="pull-right">P2 Percentage</label>
				</div>

				<div>
					<input style="width: 80px; margin-left: 15px; clear: both"
						class="form-control pull-right" type="number" name="input"
						ng-model="proj.matrix.probVal1" ng-change="matrixChange()"
						max="100"> <label class="pull-right">P1 Percentage</label>
				</div>


			</div>
		</td>
	</tr>
	<tr>
	<th scope="row">Risk Owners</th>
	<td><div style="clear: both; margin-top: 5px" id="ownerGrid"
						ui-grid="gridOwnerOptions" ui-grid-auto-resize class="userGrid"></div></td>
	</tr>
	<tr>
	<th scope="row">Risk Managers</th>
	<td><div style="clear: both; margin-top: 5px" id="managerGrid"
						ui-grid="gridManagerOptions" ui-grid-auto-resize class="userGrid"></td>
	</tr>	<tr>
	<th scope="row">Risk Users</th>
	<td><div style="clear: both; margin-top: 5px" id="userGrid"
						ui-grid="gridUserOptions" ui-grid-auto-resize class="userGrid"></td>
	</tr>
	<tr>
	<th scope="row">Risk Categories</th>
	<td>					<div class="col-lg-6">
						<div style="margin-top: 10px" id="primCatGrid"
							ui-grid="gridPrimCatOptions" ui-grid-auto-resize class="userGrid"></div>
						<div style="margin-top: 5px">
							<input style="margin-right: 5px" ng-model="ref.catText"
								name="catText">
							<button type="button" class="btn btn-xs btn-primary"
								ng-click="addCat(true)">Add New Primary</button>
						</div>
					</div>


					<div class="col-lg-6">
						<div style="margin-top: 10px" id="secCatGrid"
							ui-grid="gridSecCatOptions" ui-grid-auto-resize class="userGrid"></div>
						<div style="margin-top: 5px">
							<input style="margin-right: 5px" ng-disabled="primCatID==0"
								ng-model="ref.catSubText">
							<button ng-disabled="primCatID==0" type="button"
								class="btn btn-xs btn-primary" ng-click="addCat(false)">Add New
								Secondary</button>
						</div>
					</div>
				</div>
	</td>
	</tr>
		<tr>
	<th scope="row">Project Objectives</th>
	<td>			<div class="row" style="margin-top: 5px">
				
				
				<div class="col-lg-12" style="margin-top: 10px"
					ui-grid="gridObjectiveOptions" ui-grid-auto-resize
					ui-grid-tree-view class="userGrid"></div>


				</div>
				<div class="row" style="margin-top: 5px">
								<div class="col-xs-12 col-sm-6 col-lg-4" style="clear: both">
					<input type="text" style="width: 100%" ng-model="ref.objectiveText">
				</div>
				<div class="col-xs-12 col-sm-6 col-lg-4" style="margin-top: 4px">
					<button type="button" class="btn btn-w-m btn-xs btn-primary"
						ng-click="addObjective(true)">Add Objective</button>
					<button type="button" class="btn btn-w-m btn-xs btn-primary"
						ng-click="addObjective(false)">Add Sub Objective</button>
				</div>
			</div>
	</td>
	</tr>
</table>

<script>
jQuery(document).ready(function(){
	jQuery("#post").on('submit', function(event){
		QRM.projCtrl.saveProject();
		event.preventDefault();
	})
});
	
</script>

    <script type="text/ng-template" id="deleteObjectiveModalDialogId">
        <div class="ngdialog-message">
            <h3>Delete Objective</h3>
            <p>Please confirm that you wish to delete the objective <i>{{dialogObjective.title}}</i></p>
        </div>
        <div class="ngdialog-buttons">
             <button type="button" class="ngdialog-button ngdialog-button-secondary" ng-click="closeThisDialog('button')">Cancel</button>
            <button type="button" class="ngdialog-button ngdialog-button-primary" ng-click="confirm(confirmValue)">Confirm</button>
       </div>
    </script>
    <script type="text/ng-template" id="editObjectiveModalDialogId">
        <div class="ngdialog-message">
            <h3>Edit Objective</h3>
            <p><input ng-model="dialogObjective.title" style="width:100%"></p>
        </div>
        <div class="ngdialog-buttons">
             <button type="button" class="ngdialog-button ngdialog-button-secondary" ng-click="closeThisDialog('button')">Cancel</button>
            <button type="button" class="ngdialog-button ngdialog-button-primary" ng-click="confirm(confirmValue)">Save</button>
       </div>
    </script>
        <script type="text/ng-template" id="deleteCategoryModalDialogId">
        <div class="ngdialog-message">
            <h3>Delete Category</h3>
            <p>Please confirm that you wish to delete the category <i>{{dialogCategory.title}}</i></p>
            <p>If it is a primary category, the associated seconday categories will also be deleted</p>
        </div>
        <div class="ngdialog-buttons">
            <button type="button" class="ngdialog-button ngdialog-button-secondary" ng-click="closeThisDialog('button')">Cancel</button>
            <button type="button" class="ngdialog-button ngdialog-button-primary" ng-click="confirm(confirmValue)">Confirm</button>
        </div>
    </script>
        <script type="text/ng-template" id="editCategoryModalDialogId">
        <div class="ngdialog-message">
            <h3>Edit Category</h3>
            <p><input ng-model="dialogCategory.title" style="width:100%"></p>
        </div>
        <div class="ngdialog-buttons">
             <button type="button" class="ngdialog-button ngdialog-button-secondary" ng-click="closeThisDialog('button')">Cancel</button>
            <button type="button" class="ngdialog-button ngdialog-button-primary" ng-click="confirm(confirmValue)">Save</button>
       </div>
    </script>
<script type="text/ng-template" id="myModalContentCat.html">
        <div class="inmodal">
            <div class="modal-header">
                <h4 class="modal-title">{{vm.title}}</h4>
            </div>
            <div class="modal-body">
                <input ng-model="vm.catTitle" style="width:100%;" />
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" ng-click="vm.ok()">OK</button>
                <button class="btn btn-warning" ng-click="vm.cancel()">Cancel</button>
            </div>
        </div>
    </script>

<script type="text/ng-template" id="myModalContentConfirm.html">
        <div class="inmodal">
            <div class="modal-header">
                <h2>Please Confirm</h2>
            </div>
            <div class="modal-body">
                {{vm.title}}
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" ng-click="vm.ok()">Yes</button>
                <button class="btn btn-warning" ng-click="vm.cancel()">No</button>
            </div>
        </div>
    </script>
