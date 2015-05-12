<?php ?>

<div style="width: 100%">
	<h2>New Risk Project</h2>

	<div class="row">
		<div class="col-lg-6">
			<form class="form-horizontal">
				<div class="panel panel-success">
					<div class="panel-heading">Project Information</div>
					<div class="panel-body">
						<div class="form-group">
							<label class="col-xs-4 col-sm-3 control-label">Title</label>
							<div class="col-xs-8 col-sm-9">
								<input class="form-control" ng-model="proj.title" required>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-4 col-sm-3 control-label">Project Code</label>
							<div class="col-xs-8 col-sm-3">
								<input class="form-control" ng-model="proj.projectCode" required>
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
							<label class="col-xs-4 col-sm-3 control-label">Project Risk
								Manager</label>
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
									icheck type="checkbox" ng-model="proj.useAdvancedConsequences">
									Enable Quantitative Consequences
								</label>
							</div>
						</div>
					</div>
				</div>
				<div class="panel panel-success">
					<div class="panel-heading">Tolerance Matrix</div>
					<div class="panel-body">

						<label class="col-xs-4 col-sm-3 control-label">Max. Prob</label>
						<div class="col-sm-3">
							<input class="form-control" type="number" name="input"
								ng-model="proj.matrix.maxProb" ng-change="matrixChange()"
								min="2" max="8">
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

					</div>
				</div>
				<div class="panel panel-success">
					<div class="panel-heading">Parent Risk Project</div>
					<div class="panel-body">
						<treecontrol class="tree-light" tree-model="projects"
							options="treeOptions" on-selection="projectSelect(node)" expanded-nodes="projectsLinear"
							selected-node="selectedProject"> {{node.title}} </treecontrol>
							<div
									style="width: 100%; text-align: center; margin-top: 5px; margin-bottom: 20px">Select tht project itself to make it a top level project</div>
							
					</div>
				</div>
			</form>

		</div>
		<div class="col-lg-6">
			<div class="panel panel-success">
				<div class="panel-heading">Risk Onwers, Manager and Users</div>
				<div class="panel-body">
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

			<div class="panel panel-success">
				<div class="panel-heading">Project Objectives</div>
				<div class="panel-body">


					<div class="panel-body">
						<treecontrol class="tree-light" tree-model="projectObjectives"
							options="treeObjectiveOptions" expanded-nodes="projectLinearObjectives"
							selected-node="selectedObjective"> {{node.title}} </treecontrol>

						<div class="col-lg-12">
							<input type="text" style="width: 100%" ng-model="objectiveText">
						</div>
						<div class="col-lg-12" style="margin-top: 4px">
							<button type="button" class="btn btn-w-m btn-xs btn-primary"
								ng-click="addObjective(true)">Add Objective</button>
							<button type="button" class="btn btn-w-m btn-xs btn-primary"
								ng-click="addObjective(false)">Add Sub Objective</button>
						</div>
					</div>
				</div>
</div>
				<div class="panel panel-success">
					<div class="panel-heading">Risk Categories</div>
					<div class="panel-body">
					
					 	<div style="width:100%" id="primCatGrid" ui-grid="primGridOptions" ui-grid-auto-resize  class="userGrid"></div>
					 							<div class="col-lg-12">
							<input type="text" style="width: 80%" ng-model="catText"><button type="button" class="btn btn-xs btn-primary"
								ng-click="addCat(true)">Add New Primary</button>
						</div>
					 	<div style="margin-top:35px;width:100%" id="secCatGrid" ui-grid="secGridOptions" ui-grid-auto-resize class="userGrid"></div>
<input type="text" style="width: 80%" ng-model="catText"><button type="button" class="btn btn-xs btn-primary"
								ng-click="addCat(false)">Add New Secondary</button>
						</div>					

					</div>
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

