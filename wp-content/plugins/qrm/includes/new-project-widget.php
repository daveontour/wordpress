<?php ?>

<div style="width: 100%">
	<h2>{{proj.title}}</h2>

	<div class="row">
		<div class="col-lg-12">
			<tabset>
				<tab heading="Project Information">
				<form class="form-horizontal" style="margin-top:5px">
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
						<select ng-model="proj.projectRiskManager"
							ng-options="person.display_name for person in ref.riskProjectManagers track by person.ID"
							class="form-control"></select>
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
				</tab>
				<tab heading="Tolerance Matrix">
				<form class="form-horizontal" style="margin-top:5px">
				 <label class="col-xs-4 col-sm-3 control-label">Max.
					Prob</label>
				<div class="col-sm-3">
					<input class="form-control" type="number" name="input"
						ng-model="proj.matrix.maxProb" ng-change="matrixChange()" min="2"
						max="8">
				</div>

				<label class="col-sm-3 control-label" style="clear: both">Max.
					Impact</label>
				<div class="col-sm-3">
					<input class="form-control" type="number" name="input"
						ng-model="proj.matrix.maxImpact" ng-change="matrixChange()"
						min="2" max="8">
				</div>

				<div class="col-lg-12" style="vertical-align: middle">

					<div class="col-sm-6" style="clear: both">
						<div
							style="width: 220px; height: 220px; margin-left: auto; margin-right: auto; margin-top: 25px"
							id="svgDIV"></div>
						<div
							style="width: 100%; text-align: center; margin-top: 5px; margin-bottom: 20px">Click
							on cells to change tolerance</div>
					</div>
					<div class="col-sm-6">
						<div ng-show="proj.matrix.maxProb > 7">
							<label class="col-sm-6 control-label" style="font-size: 8pt">P8
								Percentage</label>
							<div class="col-sm-6">
								<input class="form-control" type="number" name="input"
									ng-model="proj.matrix.probVal8" ng-change="matrixChange()"
									max="100">
							</div>
						</div>
						<div ng-show="proj.matrix.maxProb > 6">
							<label class="col-sm-6 control-label"
								style="clear: both; font-size: 8pt">P7 Percentage</label>
							<div class="col-sm-6">
								<input class="form-control" type="number" name="input"
									ng-model="proj.matrix.probVal7" ng-change="matrixChange()"
									max="100">
							</div>
						</div>

						<div ng-show="proj.matrix.maxProb > 5">
							<label class="col-sm-6 control-label"
								style="clear: both; font-size: 8pt">P6 Percentage</label>
							<div class="col-sm-6">
								<input class="form-control" type="number" name="input"
									ng-model="proj.matrix.probVal6" ng-change="matrixChange()"
									" max="100">
							</div>
						</div>
						<div ng-show="proj.matrix.maxProb > 4">
							<label class="col-sm-6 control-label"
								style="clear: both; font-size: 8pt">P5 Percentage</label>
							<div class="col-sm-6">
								<input class="form-control" type="number" name="input"
									ng-model="proj.matrix.probVal5" ng-change="matrixChange()"
									max="100">
							</div>
						</div>
						<div ng-show="proj.matrix.maxProb > 3">
							<label class="col-sm-6 control-label"
								style="clear: both; font-size: 8pt">P4 Percentage</label>
							<div class="col-sm-6">
								<input class="form-control" type="number" name="input"
									ng-model="proj.matrix.probVal4" ng-change="matrixChange()"
									max="100">
							</div>
						</div>
						<div ng-show="proj.matrix.maxProb > 2">
							<label class="col-sm-6 control-label"
								style="clear: both; font-size: 8pt">P3 Percentage</label>
							<div class="col-sm-6">
								<input class="form-control" type="number" name="input"
									ng-model="proj.matrix.probVal3" ng-change="matrixChange()"
									max="100">
							</div>
						</div>
						<div>
							<label class="col-sm-6 control-label"
								style="clear: both; font-size: 8pt">P2 Percentage</label>
							<div class="col-sm-6">
								<input class="form-control" type="number" name="input"
									ng-model="proj.matrix.probVal2" ng-change="matrixChange()"
									max="100">
							</div>
						</div>

						<div>
							<label class="col-sm-6 control-label"
								style="clear: both; font-size: 8pt">P1 Percentage</label>
							<div class="col-sm-6">
								<input class="form-control" type="number" name="input"
									ng-model="proj.matrix.probVal1" ng-change="matrixChange()"
									max="100">
							</div>
						</div>
					</div>

				</div>
</form>
				</tab>
				<tab heading="Parent Project">
				 <treecontrol class="tree-light"
					tree-model="projects" options="treeOptions"
					on-selection="projectSelect(node)" expanded-nodes="projectsLinear"
					selected-node="selectedProject"> {{node.title}} </treecontrol>
				<div
					style="width: 100%; text-align: center; margin-top: 5px; margin-bottom: 20px">Select
					tht project itself to make it a top level project</div>
				</tab>
				
				
				<tab heading="Risk Owners, Managers and Users">
				<div class="row" style="margin-top:5px">
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
					<div class="col-lg-6" style="margin-top:5px">
						<label class="pull-left control-label">Risk Users</label>
						<div style="clear: both; margin-top: 5px" id="userGrid"
							ui-grid="gridUserOptions" ui-grid-auto-resize class="userGrid"></div>
					</div>
				</div>
				</tab>
				
				
				<tab heading="Project Objectives">
				 <treecontrol style="margin-top:5px" class="tree-light col-xs-12 col-sm-12 col-lg-6"
					tree-model="projectObjectives" options="treeObjectiveOptions"
					expanded-nodes="projectLinearObjectives"
					selected-node="ref.selectedObjective">
					{{node.title}} 
					<div class="pull-right">
					<i class="fa fa-edit" style="cursor:pointer;color:green;" ng-show="node.projectID == proj.id" ng-click="editObjective(node)"></i>&nbsp;&nbsp;<i class="fa fa-trash" style=";color:red;cursor:pointer" ng-show="node.projectID == proj.id" ng-click="deleteObjective(node)"></i>
					<small ng-show="node.projectID != proj.id">Parent</small>
					</div>
					</treecontrol>

				<div class="col-xs-12 col-sm-6 col-lg-4" style="clear:both">
					<input type="text" style="width: 100%" ng-model="ref.objectiveText">
				</div>
				<div class="col-xs-12 col-sm-6 col-lg-4" style="margin-top: 4px">
					<button type="button" class="btn btn-w-m btn-xs btn-primary"
						ng-click="addObjective(true)">Add Objective</button>
					<button type="button" class="btn btn-w-m btn-xs btn-primary"
						ng-click="addObjective(false)">Add Sub Objective</button>
				</div>

				</tab>
				
				
				<tab heading="Risk Categories">
				<form class="form-horizontal" style="margin-top:5px">
				<div class="row" style="margin-top:5px">
					<div class="col-lg-6">
						<div  id="primCatGrid" ui-grid="primGridOptions" ui-grid-auto-resize class="userGrid"></div>
						<div style="margin-top:5px"><input style="margin-right:5px" ng-model="ref.catText" name="catText">
						<button type="button" class="btn btn-xs btn-primary" ng-click="addCat(true)">Add New Primary</button>
						</div>
					</div>
				
				
					<div class="col-lg-6">
						<div  id="secCatGrid" ui-grid="secGridOptions" ui-grid-auto-resize class="userGrid"></div>
						<div style="margin-top:5px"><input style="margin-right:5px" ng-disabled="primCatID==0" ng-model="ref.catSubText">
						<button ng-disabled="primCatID==0" type="button" class="btn btn-xs btn-primary" ng-click="addCat(false)">Add New Secondary</button>
						</div>
					</div>
				</div>
				</form>
				</tab>
			</tabset>
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

