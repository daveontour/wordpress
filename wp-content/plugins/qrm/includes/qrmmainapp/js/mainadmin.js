function SampleController($scope, remoteService, ngNotify){
    $scope.installSample = function(){
     remoteService.installSample()
		.then(function (response) {
            alert(response.data.msg);
		});
    }
    $scope.removeSample = function(){
             remoteService.removeSample()
		.then(function (response) {
            alert(response.data.msg);
		});
    }
    
        $scope.downloadJSON = function(){
             remoteService.downloadJSON()
		.then(function (response) {
            window.location = 'download.php';
		});
    }
}

function UserController($scope, remoteService, ngNotify){

	QRM.userCtrl = this;

	$scope.changeUser = function (e) {
		e.dirty = true;
	};

	$scope.saveChanges = function (e) {
		remoteService.saveSiteUsers($scope.gridOptions.data)
		.then(function (response) {
			$scope.gridOptions.data = response.data.data;
            alert("Site Risk Users Updated");
		});
	};

	$scope.cancelChanges = function (e) {
		remoteService.getSiteUsers()
		.then(function (response) {
			$scope.gridOptions.data = response.data.data;
             alert("Changed Cancelled");
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
			            	 type: 'text',
			            	 enableHiding:false
			             },
			             {
			            	 name: 'emailAddress',
			            	 width: "*",
			            	 field: 'data.user_email',
			            	 type: 'text',
			            	 enableHiding:false
			             },
			             {
			            	 name: 'Administrator',
			            	 width: 120,
			            	 field: 'allcaps.risk_admin',
			            	 cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_admin"  ng-click="grid.appScope.changeUser(row.entity)">',
			            	 cellClass: 'cellCentered',
			            	 type: 'text',
			            	 enableHiding:false
			             },
			             {
			            	 name: 'User',
			            	 width: 80,
			            	 field: 'caps.risk_user',
			            	 cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_user"  ng-click="grid.appScope.changeUser(row.entity)">',
			            	 cellClass: 'cellCentered',
			            	 type: 'text',
			            	 enableHiding:false
			             }
			             ]
	};

	this.load = function () {
		$scope.gridOptions.data = QRM.siteUsers;
	}

	remoteService.getSiteUsers()
	.then(function (response) {
		QRM.siteUsers = response.data.data;
		QRM.userCtrl.load();
	});

}

var app = angular.module('myApp', [
'ngNotify',
'ngDialog',
'ui.grid',
'ui.grid.autoResize',
'ui.bootstrap'
]);

app.config(['ngDialogProvider', function (ngDialogProvider) {
	ngDialogProvider.setDefaults({
		className: 'ngdialog-theme-default',
		plain: false,
		showClose: true,
		closeByDocument: true,
		closeByEscape: true,
		appendTo: false
	});
}]);
app.service('remoteService', ['$http', RemoteService ]);
app.filter('usernameFilter', function () {
	return function (input) {
		if (typeof (input) == 'undefined') return;
		return $.grep(QRM.siteUsers, function (e) {
			return e.ID == input
		})[0].data.display_name;
	}
});
app.service('remoteService', function ($http, $modal) {
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
    this.installSample = function () {
		return $http({
			method: 'POST',
			url: ajaxurl,
			params: {
				action: "installSample"
			},
			cache: false
		});
	};
    this.removeSample = function () {
		return $http({
			method: 'POST',
			url: ajaxurl,
			params: {
				action: "removeSample"
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
    this.downloadJSON = function (data) {
		return $http({
			method: 'POST',
			url: ajaxurl,
			params: {
				action: "downloadJSON"
			},
			data: data,
			cache: false
		});
	};
});

app.controller('userCtrl', ['$scope', 'remoteService','ngNotify', UserController]);
app.controller('sampleCtrl', ['$scope', 'remoteService','ngNotify', SampleController]);
