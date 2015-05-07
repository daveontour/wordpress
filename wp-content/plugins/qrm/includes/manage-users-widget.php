<?php ?>

<div style="width:100%">
	<h2>Manage Users</h2>
	<div style="width:100%" id="userGrid" ui-grid="gridOptions" ui-grid-auto-resize ng-style="getTableHeight()" class="userGrid"></div>
    <div style="float:right;margin-top:15px">
         <button type="button" class="btn btn-w-m btn-primary" ng-click="saveChanges()">Save Changes</button>
          <button type="button" style="margin-left:10px" class="btn btn-w-m btn-warning" ng-click="cancelChanges()">Cancel</button>
    </div>
</div>