var app = angular.module('myApp', [
        'ui.grid',
        'ui.grid.autoResize',
        'ui.grid.edit',
        'ui.grid.moveColumns'
    ]);

app.controller('projectCtrl', function ($scope, adminService) {

    $scope.ref = {}

    $scope.proj = {
        title: "Project Title",
        description: "Description of the Project",
        useAdvancedLiklihood: true,
        useAdvancedConsequences: false,
        ownersID: [],
        managersID: [],
        usersID: [],
        matrix:{
            maxImpact:5,
            maxProb:5,
            tolString:"1123312234223443345534455555555555555555555555555555555555555555"
        }
        
    };
    
    $scope.matrixChange =  function(){
          setConfigMatrix($scope.proj.matrix.tolString, $scope.proj.matrix.maxImpact,$scope.proj.matrix.maxProb ,  "#svgDIV",$scope.matrixChangeCB);
    }

    $scope.matrixChangeCB =  function(){
        $("#svgDIV rect").each(function(){
            var html = this.outerHTML;
            var i = html.indexOf("qrmID=");
            var ip = html.substring(i+7,i+9);
            var impact = Number(ip.substring(0,1));
            var prob = Number(ip.substring(1));
            var tol = Number(this.className.baseVal.substring(4,5));
            var index = (prob - 1) * $scope.proj.matrix.maxImpact + impact - 1;
            
            var frontStr = $scope.proj.matrix.tolString.substring(0,index);
            var backStr = $scope.proj.matrix.tolString.substring(index+1);
            $scope.proj.matrix.tolString = frontStr+tol+backStr;
            
            console.log(tol+"   "+impact+"  "+prob+"  "+$scope.proj.matrix.tolString);
        })
    }
    $scope.changeOwner = function (e) {
        if ($.inArray(e.ID, $scope.proj.ownersID) > -1) {
            $scope.proj.ownersID = $.grep($scope.proj.ownersID, function (value) {
                return value != e.ID;
            })
        } else {
            $scope.proj.ownersID.push(e.ID);
        }
    };
    $scope.changeManager = function (e) {
        if ($.inArray(e.ID, $scope.proj.ownersID) > -1) {
            $scope.proj.managersID = $.grep($scope.proj.managersID, function (value) {
                return value != e.ID;
            })
        } else {
            $scope.proj.managersID.push(e.ID);
        }
    };
    $scope.changeUser = function (e) {
        if ($.inArray(e.ID, $scope.proj.userssID) > -1) {
            $scope.proj.usersID = $.grep($scope.proj.usersID, function (value) {
                return value != e.ID;
            })
        } else {
            $scope.proj.usersID.push(e.ID);
        };
    }

    $scope.gridOwnerOptions = {
        enableSorting: true,
        minRowsToShow: 5,
        rowHeight: 30,
        columnDefs: [

            {
                name: 'name',
                width: 150,
                field: 'data.display_name'

            },
            {
                name: 'emailAddress',
                width: "*",
                field: 'data.user_email'

            }, {
                name: 'Owner',
                width: 80,
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-checked="$.inArray(row.entity.ID,grid.appScope.proj.ownersID)" ng-click="grid.appScope.changeOwner(row.entity)">',
                cellClass: 'cellCentered'
            }
        ]
    };

    $scope.gridManagerOptions = {
        enableSorting: true,
        minRowsToShow: 5,
        rowHeight: 30,
        columnDefs: [

            {
                name: 'name',
                width: 150,
                field: 'data.display_name'

            },
            {
                name: 'emailAddress',
                width: "*",
                field: 'data.user_email'

            }, {
                name: 'Manager',
                width: 80,
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-click="grid.appScope.changeManager(row.entity)">',
                cellClass: 'cellCentered'
            }
        ]
    };



    $scope.gridUserOptions = {
        enableSorting: true,
        minRowsToShow: 5,
        rowHeight: 30,
        columnDefs: [

            {
                name: 'name',
                width: 150,
                field: 'data.display_name'

            },
            {
                name: 'emailAddress',
                width: "*",
                field: 'data.user_email'

            }, {
                name: 'User',
                width: 80,
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered'
            }
        ]
    };

    adminService.getSiteProjectManagers()
        .then(function (response) {
            $scope.ref.riskProjectManagers = response.data.data;
        });

    adminService.getSiteOwners()
        .then(function (response) {
            $scope.gridOwnerOptions.data = response.data.data;
        });

    adminService.getSiteManagers()
        .then(function (response) {
            $scope.gridManagerOptions.data = response.data.data;
        });

    adminService.getSiteRiskUsers()
        .then(function (response) {
            $scope.gridUserOptions.data = response.data.data;
        });
    
    setConfigMatrix($scope.proj.matrix.tolString, $scope.proj.matrix.maxImpact,$scope.proj.matrix.maxProb , "#svgDIV",$scope.matrixChangeCB)

});

app.controller('userCtrl', function ($scope, adminService) {

    $scope.changeUser = function (e) {
        e.dirty = true;
    };

    $scope.saveChanges = function (e) {
        adminService.saveSiteUsers($scope.gridOptions.data)
            .then(function (response) {
                $scope.gridOptions.data = response.data.data;
                alert("Users Updated");
            });

    };

    $scope.cancelChanges = function (e) {
        adminService.getSiteUsers()
            .then(function (response) {
                $scope.gridOptions.data = response.data.data;
            });
    };
    $scope.gridOptions = {
        enableSorting: true,
        minRowsToShow: 10,
        rowHeight: 30,
        columnDefs: [

            {
                name: 'name',
                width: 150,
                field: 'data.display_name'

            },
            {
                name: 'emailAddress',
                width: "*",
                field: 'data.user_email'

            },
            {
                name: 'Administrator',
                width: 120,
                field: 'allcaps.risk_admin',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_admin"  ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered'
            },
            {
                name: 'Project Mgr',
                width: 120,
                field: 'caps.risk_project_manager',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_project_manager"  ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered'
            },
            {
                name: 'Owner',
                width: 80,
                field: 'caps.risk_owner',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_owner" ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered'
            },
            {
                name: 'Manager',
                width: 80,
                field: 'caps.risk_manager',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_manager"  ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered'

            },
            {
                name: 'User',
                width: 80,
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_user"  ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered'
            }
        ]
    };

    adminService.getSiteUsers()
        .then(function (response) {
            $scope.gridOptions.data = response.data.data;
        });

});

app.service('adminService', function ($http) {

    var service = this;
    var loc = window.location.href;
    this.url = loc.slice(0, loc.indexOf("wp-admin"));

    this.getSiteUsers = function () {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=getSiteUsers",
            cache: false
        });
    };

    this.getSiteProjectManagers = function () {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=getSiteProjectManagers",
            cache: false
        });
    };
    this.getSiteOwners = function () {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=getSiteOwners",
            cache: false
        });
    };
    this.getSiteManagers = function () {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=getSiteManagers",
            cache: false
        });
    };
    this.getSiteRiskUsers = function () {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=getSiteRiskUsers",
            cache: false
        });
    };
    this.saveSiteUsers = function (data) {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=saveSiteUsers",
            data: data,
            cache: false
        });
    };

});

app.directive('icheck', function icheck($timeout) {
    return {
        restrict: 'A',
        require: 'ngModel',
        link: function ($scope, element, $attrs, ngModel) {
            return $timeout(function () {
                var value;
                value = $attrs['value'];

                $scope.$watch($attrs['ngModel'], function (newValue) {
                    $(element).iCheck('update');
                })

                return $(element).iCheck({
                    checkboxClass: 'icheckbox_square-green',
                    radioClass: 'iradio_square-green'

                }).on('ifChanged', function (event) {
                    if ($(element).attr('type') === 'checkbox' && $attrs['ngModel']) {
                        $scope.$apply(function () {
                            return ngModel.$setViewValue(event.target.checked);
                        });
                    }
                    if ($(element).attr('type') === 'radio' && $attrs['ngModel']) {
                        return $scope.$apply(function () {
                            return ngModel.$setViewValue(value);
                        });
                    }
                });
            });
        }
    };
})