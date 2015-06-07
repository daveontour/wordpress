var app = angular.module('myApp', [
        'ui.grid',
        'ui.grid.autoResize',
        'ui.bootstrap',
        'ui.grid.edit',
        'ui.grid.moveColumns',
         'cgNotify'
    ]);

app.controller('userCtrl', function ($scope, adminService) {

    QRM.userCtrl = this;

    $scope.changeUser = function (e) {
        e.dirty = true;
    };

    $scope.saveChanges = function (e) {
        adminService.saveSiteUsers($scope.gridOptions.data)
            .then(function (response) {
                $scope.gridOptions.data = response.data.data;
                notify({
                    message: 'Site Risk Users Updated',
                    classes: 'alert-success',
                    duration: 5000,
                    templateUrl: "../wp-content/plugins/qrm/includes/qrmmainapp/views/common/notify.html",
                    topOffset: 25
                });
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
                field: 'data.display_name',
                type: 'text'


            },
            {
                name: 'emailAddress',
                width: "*",
                field: 'data.user_email',
                type: 'text'


            },
            {
                name: 'Administrator',
                width: 120,
                field: 'allcaps.risk_admin',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_admin"  ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered',
                type: 'text'

            },
            {
                name: 'Project Mgr',
                width: 120,
                field: 'caps.risk_project_manager',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_project_manager"  ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered',
                type: 'text'

            },
            {
                name: 'Owner',
                width: 80,
                field: 'caps.risk_owner',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_owner" ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered',
                type: 'text'

            },
            {
                name: 'Manager',
                width: 80,
                field: 'caps.risk_manager',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_manager"  ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered',
                type: 'text'

            },
            {
                name: 'User',
                width: 80,
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_user"  ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered',
                type: 'text'

            }
        ]
    };



    this.load = function () {
        $scope.gridOptions.data = QRM.siteUsers;
    }
    
        adminService.getSiteUsers()
        .then(function (response) {
            QRM.siteUsers = response.data.data;
            QRM.userCtrl.load();
        });

});
app.service('adminService', function ($http, $modal) {
    
        this.getRisk = function (riskID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getRisk"
            },
            cache: false,
            data: riskID
        });
    };
    this.saveRisk = function (risk) {

        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "saveRisk"
            },
            cache: false,
            data: risk
        });
    };
    this.getAllProjectRisks = function (projectID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getAllProjectRisks"
            },
            cache: false,
            data: projectID
        }).error(function (data, status, headers, config) {
            alert(data.msg);
        });
    };
    this.getSiteUsersCap = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getSiteUsersCap"
            },
            cache: false
        });
    };
    this.getSiteUsers = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getSiteUsers"
            },
            cache: false
        });
    };
    this.getProjects = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getProjects"
            },
            cache: false
        });
    };
    this.saveProject = function (data) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "saveProject"
            },
            data: data,
            cache: false
        });
    };
    this.addComment = function (comment, riskID) {
        data = {
            comment: comment,
            riskID: riskID
        }
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "addComment"
            },
            cache: false,
            data: JSON.stringify(data)
        });
    };
    this.updateRisksRelMatrix = function (data) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "updateRisksRelMatrix"
            },
            cache: false,
            data: JSON.stringify(data)
        }).error(function (data, status, headers, config) {
            alert(data.msg);
        });
    };
    this.saveRankOrder = function (data) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "saveRankOrder"
            },
            cache: false,
            data: JSON.stringify(data)
        }).error(function (data, status, headers, config) {
            alert(data.msg);
        });
    };
    this.getRiskAttachments = function (riskID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getRiskAttachments"
            },
            cache: false,
            data: riskID
        });
    };
    this.getCurrentUser = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getCurrentUser"
            },
            cache: false
        });
    };
    
        this.saveSiteUsers = function (data) {
        return $http({
            method: 'POST',
            url: ajaxurl,
                        params: {
                action: "saveSiteUsers"
            },
            data: data,
            cache: false
        });
    };


    this.getDefaultProject = function () {
        return {
            id: -1,
            title: "Project Title",
            description: "Description of the Project",
            useAdvancedConsequences: false,
            projectCode: "",
            ownersID: [],
            managersID: [],
            usersID: [],
            matrix: {
                maxImpact: 5,
                maxProb: 5,
                tolString: "1123312234223443345534455555555555555555555555555555555555555555",
                probVal1: 20,
                probVal2: 40,
                probVal3: 60,
                probVal4: 80,
                probVal5: 100,
                probVal6: 100,
                probVal7: 100,
                probVal8: 100
            },
            inheritParentCategories: true,
            inheritParentObjectives: true,
            categories: [],
            objectives: [],
            parent_id: 0,
        };

    }

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
});
app.filter('usernameFilter', function () {
    return function (input) {
        if (typeof (input) == 'undefined') return;
        return $.grep(QRM.siteUsers, function (e) {
            return e.ID == input
        })[0].data.display_name;
    }

});