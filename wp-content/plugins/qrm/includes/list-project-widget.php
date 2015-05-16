<?php ?>

<div style="width: 100%">

	<div class="row">
	<div class="col-lg-12">
	<div class="pull-left"><h2 class="pull-left">Risk Projects</h2></div>
    <div class="pull-right"><button style="margin-top:15px" class="btn btn-w-m btn-primary" ng-model="t2" ng-click="newProject()">New Risk Project</button>
	</div>
	</div>
	
		<div class="col-lg-12" style="clear: both">
			<div id="grid1" ui-grid="gridOptions" ui-grid-tree-view
				ui-grid-auto-resize class="userGrid"></div>	
		</div>
	</div>
</div>