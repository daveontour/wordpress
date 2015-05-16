<?php 
	wp_enqueue_style ('font-awesome' );
	wp_enqueue_style ('boostrap');
	wp_enqueue_style ('animate');
	wp_enqueue_style ('dropzone' );
	wp_enqueue_style ('ui-grid' );
	wp_enqueue_style ('notify');
	wp_enqueue_style ('pace' );
	wp_enqueue_style ('style');
	wp_enqueue_style ('qrm-angular');
	wp_enqueue_style ('qrm-style');
	wp_enqueue_style ('icheck');
	wp_enqueue_style ('treecontrol');
	wp_enqueue_style ('select');
	
	wp_enqueue_script('qrm-jquery');
	wp_enqueue_script('qrm-jqueryui');
	wp_enqueue_script('qrm-boostrap');
// 	wp_enqueue_script('qrm-metis');
// 	wp_enqueue_script('qrm-slimscroll');
// 	wp_enqueue_script('qrm-pace');
// 	wp_enqueue_script('qrm-inspinia');
	wp_enqueue_script('qrm-angular');
	wp_enqueue_script('qrm-test');
// 	wp_enqueue_script('qrm-lazyload');
// 	wp_enqueue_script('qrm-router');
 	wp_enqueue_script('qrm-bootstraptpl');
 	wp_enqueue_script('qrm-uigrid');
 	wp_enqueue_script('qrm-icheck');
 	wp_enqueue_script('qrm-notify');
// 	wp_enqueue_script('qrm-dropzone');
// 	wp_enqueue_script('qrm-moment');
// 	wp_enqueue_script('qrm-app');
// 	wp_enqueue_script('qrm-config');
// 	wp_enqueue_script('qrm-directives');
// 	wp_enqueue_script('qrm-controllers');
// 	wp_enqueue_script('qrm-services');
 	wp_enqueue_script('qrm-d3');
 	wp_enqueue_script('qrm-common');
 	wp_enqueue_script('treecontrol');
 	wp_enqueue_script('select');
 	wp_enqueue_script('sanitize');
 	

?>
<style>
#wpcontent {
  height: 100%;
  padding-left: 0px;
}
</style>
<div ng-app="myApp" style="width:100%;height:100%">
 
 <div style="background-color:#f1f1f1">
   
    <div class="panel-body">
        <div class="col-lg-12" ng-controller="switchCtrl">
            
			<h1 class="pull-left" >Quay Risk Manager</h1>
            <div class="pull-left" style="height:3em;clear:both" >
            <button style="margin-right:15px" class="btn btn-w-m btn-success pull-left" ng-model="t1" ng-click="tabswitch(1)">Risk Projects</button>        	
            <button style="margin-right:15px" class="btn btn-w-m btn-success pull-left" ng-model="t3" ng-click="tabswitch(3)">Manage Users</button> 
            <button class="btn btn-w-m btn-success pull-left" ng-model="t4" ng-click="tabswitch(4)">Settings</button> 
            </div>
            <div style="width:100%;margin-top:20px">
            <div style="float:left;clear: both;width:100%" ng-show="t1" ng-controller="listCtrl"><?php include 'includes/list-project-widget.php';?></div>
            <div style="float:left;clear: both;width:100%" ng-show="t2" ng-controller="projectCtrl"><?php include 'includes/new-project-widget.php';?></div>
            <div style="float:left;clear: both;width:100%" ng-show="t3" ng-controller="userCtrl"><?php include 'includes/manage-users-widget.php';?></div>
           	<div style="float:left;clear: both;width:100%" ng-show="t4"></div>
           	<div style="float:left;clear: both;width:100%; text-align:center" ng-show="t0">
           	
           	<h1>Welcome to Quay Risk Manager</h1>
           	<h2>Powered by Wordpress</h2>
           	</div>
           	</div>
            
        </div>
    </div>
</div>
 
 </div>
