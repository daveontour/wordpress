<?php ?>

<div style="width: 100%">
	<h2>Risk Projects</h2>

	<div class="row">
		<div class="col-lg-4">
				<div class="panel panel-success">
					<div class="panel-heading">Select Risk Projects</div>
					<div class="panel-body">
					<treecontrol class="tree-light"   
					tree-model="projects" 
					options="treeOptions"
					expanded-nodes="projects"  
					on-selection="projectSelect(node)"  
					selected-node="selectedProject">
   						{{node.title}} - {{node.id}}
					</treecontrol>
					</div>
				</div>
		</div>
				<div class="col-lg-8">
				<div class="panel panel-success">
					<div class="panel-heading">Projects Details</div>
					<div class="panel-body">
    <div style="float:right;margin-top:15px">
         <button type="button" class="btn btn-w-m btn-primary" ng-click="editProject()">Edit Project</button> 
          
     </div>
					</div>
				</div>
		</div>
	</div>
</div>