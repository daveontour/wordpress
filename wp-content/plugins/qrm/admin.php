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
// 	wp_enqueue_script('qrm-bootstraptpl');
// 	wp_enqueue_script('qrm-uigrid');
 	wp_enqueue_script('qrm-icheck');
// 	wp_enqueue_script('qrm-notify');
// 	wp_enqueue_script('qrm-dropzone');
// 	wp_enqueue_script('qrm-moment');
// 	wp_enqueue_script('qrm-app');
// 	wp_enqueue_script('qrm-config');
// 	wp_enqueue_script('qrm-directives');
// 	wp_enqueue_script('qrm-controllers');
// 	wp_enqueue_script('qrm-services');
// 	wp_enqueue_script('qrm-d3');
// 	wp_enqueue_script('qrm-common');


?>
<div ng-app="myApp" ng-controller="myCtrl" style="width:100%;height:100%">
 
 <div class="panel panel-success">
    <div class="panel-heading">Risk Management</div>
    <div class="panel-body">
        <div class="col-lg-6">
            <div class="row">
                <form method="get" class="form-horizontal">
                    <div class="form-group">
<label class="col-xs-4 col-sm-3 control-label">{{firstName}}</label>
                        <label class="col-xs-4 col-sm-3 control-label">Exposure</label>

                        <div class="col-xs-8 col-sm-9">
                            <div class="control-group" style="margin-top:3px">
                                <div class="controls">
                                    <div class="input-prepend input-group">
                                        <span class="add-on input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                                        <input type="text" id="exposure" class="form-control" />
                                    </div>
                                </div>
                            </div>

                        </div>

                        <label class="col-xs-4 col-sm-3 control-label">Risk Owner</label>
                        <div class="col-xs-8 col-sm-9">
                            <select ng-model="ctl.risk.owner" ng-change="ctl.updateRisk()" ng-options="person.name for person in ctl.project.riskOwners track by person.email" class="form-control"></select>

                        </div>
                        <label class="col-xs-4 col-sm-3 control-label" style="clear:both">Risk Manager</label>
                        <div class="col-xs-8 col-sm-9">
                            <select ng-model="ctl.risk.manager" ng-change="ctl.updateRisk()" ng-options="person.name for person in ctl.project.riskManagers track by person.email" class="form-control"></select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Impact</label>

                        <div class="col-sm-9">
                            <table>
                                <tr>
                                    <td>
                                        <label class="checkbox-inline" style="padding-left:0px">
                                            <input icheck type="checkbox" ng-model="ctl.risk.impSafety"> Safety </label>
                                    </td>
                                    <td>
                                        <label class="checkbox-inline" >
                                            <input icheck type="checkbox" ng-model="ctl.risk.impRep" > Reputation</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label class="checkbox-inline" style="padding-left:0px">
                                            <input icheck type="checkbox" ng-model="ctl.risk.impCost" > Cost </label>
                                    </td>

                                    <td>
                                        <label class="checkbox-inline" >
                                            <input icheck type="checkbox" ng-model="ctl.risk.impEnviron"> Environment </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label class="checkbox-inline" style="padding-left:0px">
                                            <input icheck type="checkbox" ng-model="ctl.risk.impTime"> Time </label>
                                    </td>
                                    <td>
                                        <label class="checkbox-inline">
                                            <input icheck type="checkbox" ng-model="ctl.risk.impSpec" > Specification </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Treatment Strategy</label>

                        <div class="col-sm-9">
                            <table>
                                <tr>
                                    <td>
                                        <label class="checkbox-inline"  style="padding-left:0px">
                                            <input icheck type="checkbox" ng-model="ctl.risk.treatAvoid"> Avoidence &nbsp;&nbsp;</label>
                                    </td>
                                    <td>
                                        <label class="checkbox-inline">
                                            <input icheck type="checkbox" ng-model="ctl.risk.treatRetention" > Retention </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label class="checkbox-inline" style="padding-left:0px">
                                            <input icheck type="checkbox" ng-model="ctl.risk.treatTransfer" > Transfer </label>
                                    </td>
                                    <td>
                                        <label class="checkbox-inline">
                                            <input icheck type="checkbox" ng-model="ctl.risk.treatMinimise" style="padding-left:0px"> Minimisation </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="form-group">


                        <label class="col-xs-4 col-sm-3 control-label">Primary Category</label>
                        <div class="col-xs-8 col-sm-9">
                            <select ng-model="ctl.risk.primcat" ng-change="ctl.updateRisk()" ng-options="cat.name for cat in ctl.project.categories track by cat.name" class="form-control" name="primcat"></select>
                        </div>
                        <label class="col-xs-4 col-sm-3 control-label" style="clear:both">Secondary Category</label>
                        <div class="col-xs-8 col-sm-9">
                            <select ng-model="ctl.risk.seccat" ng-options="sec.name for sec in ctl.secCatArray track by sec.name" class="form-control" name="seccat"></select>

                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-xs-4 col-sm-3 control-label">Estimated Contingency</label>

                        <div class="col-xs-8 col-sm-9">
                            <input class="form-control" type="number" name="input" ng-model="ctl.risk.estContingency" min="0" max="10000000" required>
                        </div>

                        <label class="col-sm-3 control-label"></label>
                        <div class="col-sm-9">
                            <label class="checkbox-inline" style="padding-left:0px">
                                <input icheck type="checkbox" ng-model="ctl.risk.useCalContingency"> Use Calculated Contingency</label>
                        </div>

                        <label class="col-sm-3 control-label">&nbsp;</label>
                        <div class="col-sm-9">
                            <label class="checkbox-inline" style="padding-left:0px">
                                <input icheck type="checkbox" ng-model="ctl.risk.treated"> Treated </label>
                        </div>

                        <label class="col-sm-3 control-label">&nbsp;</label>
                        <div class="col-sm-9">
                            <label class="checkbox-inline" style="padding-left:0px">
                                <input icheck type="checkbox" ng-model="ctl.risk.summaryRisk"> Summary Risk </label>
                        </div>

                        <label class="col-sm-3 control-label">Stakeholders</label>
                        <div class="col-sm-9" style="margin-top:8px">                         
                                <table>
                                <tr ng-repeat="s in ctl.stakeholders" style="cell">
                                    <td style="font-weight:bold">{{s.name}}</td>
                                    <td style="padding-left:5px">{{s.role}}</td>
                                    </tr>
                                </table>
                        </div>

                    </div>

                </form>



                <script>
//                     jQuery(document).ready(function () {
//                         jQuery('#exposure').daterangepicker({
//                                 format: 'MMMM D, YYYY',
//                                 separator: " - ",
//                                 showDropdowns: true,
//                                 drops: "down"
//                             },
//                             function (start, end, label) {
//                                 try {
//                                     // Update the Angular controller
//                                     angular.element("#exposure").controller().updateDates(start, end);
//                                 } catch (e) {
//                                     console.log(e.message);
//                                 }
//                             });

//                         $('#exposure').data('daterangepicker').setStartDate(angular.element("#exposure").controller().risk.start);
//                         $('#exposure').data('daterangepicker').setEndDate(angular.element("#exposure").controller().risk.end);

//                     });
                </script>
            </div>
        </div>
    </div>
</div>
 
 </div>
