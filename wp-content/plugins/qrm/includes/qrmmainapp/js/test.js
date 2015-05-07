var app = angular.module('myApp', [
        'ui.grid',
        'ui.grid.autoResize',
        'ui.grid.edit',
        'ui.grid.moveColumns'
]);

app.controller('myCtrl', function ($scope) {

    $scope.t1 = true;
    $scope.t2 = false;
    $scope.t3 = false;
    $scope.t4 = false;

    $scope.firstName = "John";
    $scope.lastName = "Doe";
});

app.controller('userCtrl', function ($scope, adminService) {

    $scope.changeUser = function (e) {
        e.dirty = true;
    }

    $scope.saveChanges = function (e) {
        adminService.saveSiteUsers($scope.gridOptions.data)
            .then(function (response) {
                $scope.gridOptions.data = response.data.data;
                alert("Users Updated");
            });

    }

    $scope.cancelChanges = function (e) {
        adminService.getSiteUsers()
            .then(function (response) {
                $scope.gridOptions.data = response.data.data;
            });
    }
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
                cellTemplate: '<input type="checkbox" />',
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
    }

    this.saveSiteUsers = function (data) {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=saveSiteUsers",
            data: data,
            cache: false
        });
    }

});