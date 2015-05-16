<?php ?>

<div style="width: 100%">
	<h2>{{proj.title}}</h2>

	<div class="row">
		<div class="col-lg-12">
			<tabset> <tab heading="Project Information"  active="firstActive">
			<form class="form-horizontal" style="margin-top: 5px">
				<div class="form-group">
					<label class="col-xs-4 col-sm-2 control-label">Title</label>
					<div class="col-xs-8 col-sm-8">
						<input class="form-control" ng-model="proj.title" required>
					</div>
				</div>
				<div class="form-group">
					<label class="col-xs-4 col-sm-2 control-label">Project Code</label>
					<div class="col-xs-4 col-sm-2">
						<input class="form-control" ng-model="proj.projectCode" required>
					</div>
				</div>
				<div class="form-group">
					<label class="col-xs-4 col-sm-2 control-label">Description</label>
					<div class="col-xs-8 col-sm-8">
						<textarea class="form-control" style="height: 100px"
							ng-model="proj.description"></textarea>
					</div>
				</div>

				<div class="form-group">
					<label class="col-xs-4 col-sm-2 control-label">Project Risk Manager</label>
					<div class="col-xs-8 col-sm-3">
												
												<ui-select ng-model="proj.projectRiskManager" theme="bootstrap"> 
						<ui-select-match allow-clear=false placeholder="Select Project Risk Manager...">{{$select.selected.display_name}}</ui-select-match>
						<ui-select-choices	repeat="person.ID as person in ref.riskProjectManagers">
						<div>
							<span ng-bind-html="person.display_name"></span>
						</div>
						</ui-select-choices> </ui-select>	
							
					</div>
				</div>

				<div class="form-group">
					<label class="col-xs-4 col-sm-2 control-label">Parent Project</label>
					<div class="col-xs-8 col-sm-3">
						<ui-select ng-model="proj.parent_id" theme="bootstrap" on-select=""> 
						<ui-select-match allow-clear=true placeholder="Select parent project...">{{$select.selected.title}}</ui-select-match>
						<ui-select-choices	repeat="project.id as project in sortedParents">
						<div ng-style="rowStyle(project)">
							<span ng-bind-html="project.title" ng-click="projectSelect(project)"></span>
						</div>
						</ui-select-choices> </ui-select>
					</div>
				</div>

				<div class="form-group">
					<label class="col-xs-4 col-sm-2 control-label">&nbsp;</label>
					<div class="col-sm-8">
						<label class="checkbox-inline" style="padding-left: 0px"> <input
							icheck type="checkbox" ng-model="proj.useAdvancedConsequences">
							Enable Quantitative Consequences
						</label>
					</div>
				</div>
			</form>
			</tab> <tab heading="Tolerance Matrix" select="resetFirst()">

			<form class="form-horizontal" style="margin-top: 5px">

				<div class="pull-left" style="width: 220px;margin-left:10px;margin-top:10px">
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


					<div class="pull-left" style="width: 220px;margin-left:10px;margin-top:10px">

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
			</form>
			</tab> <tab heading="Risk Owners, Managers and Users"  select="resetFirst()">
			<div class="row" style="margin-top: 5px">
				<div class="col-lg-6">
					<label class="pull-left control-label">Risk Owners</label>
					<div style="clear: both; margin-top: 5px" id="ownerGrid"
						ui-grid="gridOwnerOptions" ui-grid-auto-resize class="userGrid"></div>
				</div>
				<div class="col-lg-6">
					<label class="pull-left control-label">Risk Managers</label>
					<div style="clear: both; margin-top: 5px" id="managerGrid"
						ui-grid="gridManagerOptions" ui-grid-auto-resize class="userGrid"></div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-6" style="margin-top: 5px">
					<label class="pull-left control-label">Risk Users</label>
					<div style="clear: both; margin-top: 5px" id="userGrid"
						ui-grid="gridUserOptions" ui-grid-auto-resize class="userGrid"></div>
				</div>
			</div>
			</tab>
			 <tab heading="Project Objectives"  select="resetFirst()"> 
			 <treecontrol
				style="margin-top:5px"
				class="tree-light col-xs-12 col-sm-12 col-lg-6"
				tree-model="projectObjectives" options="treeObjectiveOptions"
				expanded-nodes="expandedObjectives"
				selected-node="ref.selectedObjective"> {{node.title}}
			<div class="pull-right">
				<i class="fa fa-edit" style="cursor: pointer; color: green;"
					ng-show="node.projectID == proj.id" ng-click="editObjective(node)"></i>&nbsp;&nbsp;<i
					class="fa fa-trash" style="color: red; cursor: pointer"
					ng-show="node.projectID == proj.id"
					ng-click="deleteObjective(node)"></i> <small
					ng-show="node.projectID != proj.id">Parent</small>
			</div>
			</treecontrol>

			<div class="col-xs-12 col-sm-6 col-lg-4" style="clear: both">
				<input type="text" style="width: 100%" ng-model="ref.objectiveText">
			</div>
			<div class="col-xs-12 col-sm-6 col-lg-4" style="margin-top: 4px">
				<button type="button" class="btn btn-w-m btn-xs btn-primary"
					ng-click="addObjective(true)">Add Objective</button>
				<button type="button" class="btn btn-w-m btn-xs btn-primary"
					ng-click="addObjective(false)">Add Sub Objective</button>
			</div>

			</tab>
			 <tab heading="Risk Categories"  select="resetFirst()">
			<form class="form-horizontal" style="margin-top: 5px">
				<div class="row" style="margin-top: 5px">
					<div class="col-lg-6">
						<div style="margin-top:10px" id="primCatGrid" ui-grid="primGridOptions"
							ui-grid-auto-resize class="userGrid"></div>
						<div style="margin-top: 5px">
							<input style="margin-right: 5px" ng-model="ref.catText"
								name="catText">
							<button type="button" class="btn btn-xs btn-primary"
								ng-click="addCat(true)">Add New Primary</button>
						</div>
					</div>


					<div class="col-lg-6">
						<div style="margin-top:10px" id="secCatGrid" ui-grid="secGridOptions" ui-grid-auto-resize
							class="userGrid"></div>
						<div style="margin-top: 5px">
							<input style="margin-right: 5px" ng-disabled="primCatID==0"
								ng-model="ref.catSubText">
							<button ng-disabled="primCatID==0" type="button"
								class="btn btn-xs btn-primary" ng-click="addCat(false)">Add New
								Secondary</button>
						</div>
					</div>
				</div>
			</form>
			</tab> </tabset>
		</div>
	</div>
</div>
<div style="float: right; margin-top: 15px">
	<button type="button" class="btn btn-w-m btn-primary"
		ng-click="saveChanges()">Save Changes</button>
	<button type="button" style="margin-left: 10px"
		class="btn btn-w-m btn-warning" ng-click="cancelChanges()">Cancel</button>
</div>
</div>


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

