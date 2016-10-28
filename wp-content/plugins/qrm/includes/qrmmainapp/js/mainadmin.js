function SampleController($scope, remoteService, ngNotify) {
	
	$scope.min = 10;
	$scope.max = 20;

	Dropzone.autoDiscover = false;
    var samp = this; 
    $scope.installSample = function () {
        remoteService.installSample()
            .then(function (response) {
                ngNotify.set(response.data.msg, {type:"success", duration:1000, theme:"pure"});
            });
    }
    $scope.installSampleProjects = function () {
        ngNotify.set("Installing Sample Data. Please Standby", {type:"info", sticky:true, theme:"pure"});
        remoteService.installSampleProjects([$scope.min, $scope.max])
            .then(function (response) {
                ngNotify.set(response.data.msg, {type:"success", duration:1000, theme:"pure"});
            });
    }

    $scope.reindexRiskCount = function () {
        ngNotify.set("Re Indexing Risk Counts", {type:"info", sticky:true, theme:"pure"});
        remoteService.reindexRiskCount()
            .then(function (response) {
                ngNotify.set("Re Indexing Complete", {type:"success", duration:1000, theme:"pure"});
            });
    }
    $scope.removeAllData = function () {
    	var r = confirm("Press confirm you wish to remove all the data from Quay Risk Manager");
    	if (r == true) {
            ngNotify.set("Removing All Quay Risk Manager Data. Please Standby", {type:"info", sticky:true, theme:"pure"});
            remoteService.removeSample(true)
            .then(function (response) {
                ngNotify.set("All Quay Risk Manager Data Removed", {type:"success", duration:1000, theme:"pure"});
            });
    	} 
    }
    this.downloadJSON = function () {
        jQuery("body").append("<iframe src='" + ajaxurl + "?action=downloadJSON' style='display: none;' ></iframe>");
    }

    
    this.riskAttachmentReady = function (dropzone, file) {
        samp.dropzone = dropzone;
        samp.dzfile = file;
        samp.disableAttachmentButon = false;
        $scope.$apply();
    }

    this.uploadAttachmentDescription = "";
    this.disableAttachmentButon = true;
    this.dropzone = "";
    this.uploadImport = function () {
        ngNotify.set("File Is Being Uploaded and Imported. Please Standby", {type:"info", sticky:true, theme:"pure"});
        samp.dropzone.processFile(samp.dzfile);
    }
    this.cancelUpload = function () {
        samp.dropzone.removeAllFiles(true);
        samp.uploadAttachmentDescription = null;
        samp.disableAttachmentButon = true;
        samp.dropzone = null;
        samp.dzfile = null;
        $scope.$apply();
    }
    $scope.dropzoneConfigAdmin = {
        options: { // passed into the Dropzone constructor
            url: ajaxurl + "?action=uploadImport",
            previewTemplate: document.querySelector('#preview-template').innerHTML,
            parallelUploads: 1,
            thumbnailHeight: 100,
            thumbnailWidth: 100,
            maxFilesize: 6,
            filesizeBase: 1000,
            autoProcessQueue: false,
            thumbnail: function (file, dataUrl) {
                if (file.previewElement) {
                    file.previewElement.classList.remove("dz-file-preview");
                    var images = file.previewElement.querySelectorAll("[data-dz-thumbnail]");
                    for (var i = 0; i < images.length; i++) {
                        var thumbnailElement = images[i];
                        thumbnailElement.alt = file.name;
                        thumbnailElement.src = dataUrl;
                    }
                    setTimeout(function () {
                        file.previewElement.classList.add("dz-image-preview");
                    }, 1);
                }
            },
            init: function () {
                this.on("addedfile", function (file) {
                    samp.riskAttachmentReady(this, file);
                });
                this.on('complete', function (file) {
                    file.previewElement.classList.add('dz-complete');
                    samp.cancelUpload();
                    ngNotify.set("File Imported", {type:"success", duration:1000, theme:"pure"});
                });
                this.on('error', function (file, message, xhr) {
                    file.previewElement.classList.add('dz-complete');
                    samp.cancelUpload();
                    ngNotify.set(message, {type:"error", duration:1000, theme:"pure"});
                });
            },
        },
    };
}
function UserNameController($scope, remoteService) {
	
	var userNameCtrl = this;

	$scope.setUserName = function(e){
    	var options = new Object();
    	
        options.displayUser = $scope.status.val;
    	
        remoteService.saveDisplayUser(options)
            .then(function (response) {
            	alert ("Changes Saved");
            });

	}

	$scope.status = { val: "notdefined" };
	
    remoteService.getDisplayUser().then(function (response) {          
        $scope.status.val = response.data.displayUser;
        QRM.displayUser = response.data.displayUser;
    }); 
}
function ReportController($scope, remoteService) {
	
	$scope.reports = [{menuName:"Detail Risk 1", description:"A detaile d description of the report"},
	                        {menuName:"Detail Risk 2", description:"A detaile d description of the report",riskExplorer:true, incidentExplorer:true},
	                        {menuName:"Detail Risk 3", description:"A detaile d description of the report"},
	                        {menuName:"Detail Risk 4", description:"A detaile d description of the report"},
	                        {menuName:"Detail Risk 5", description:"A detaile d description of the report"},
	                        ]
	$scope.selectedReport = $scope.reports[0];
	
	$scope.clear = function(){
		$scope.selectedReport = {};
	}
	
    $scope.saveChanges = function (e) {
    	
    	var options = new Object();
    	
        options.url = $scope.url = $scope.url;
        options.siteName = $scope.siteName;
        options.siteID = $scope.siteID;
        options.siteKey = $scope.siteKey;
    	
        remoteService.saveReportOptions(options)
            .then(function (response) {
                alert("Changes Saved");
            });
    };
    
    remoteService.getReportOptions()
        .then(function (response) {          
            $scope.url = response.data.url;
            $scope.siteName = response.data.siteName;
            $scope.siteID = response.data.siteID;
            $scope.siteKey = response.data.siteKey;
        });

}
//function ReportController($scope, remoteService) {
//
//    $scope.saveChanges = function (e) {
//    	
//    	var options = new Object();
//    	
//        options.url = $scope.url = $scope.url;
//        options.siteName = $scope.siteName;
//        options.siteID = $scope.siteID;
//        options.siteKey = $scope.siteKey;
//    	
//        remoteService.saveReportOptions(options)
//            .then(function (response) {
//                alert("Changes Saved");
//            });
//    };
//    
//    remoteService.getReportOptions()
//        .then(function (response) {          
//            $scope.url = response.data.url;
//            $scope.siteName = response.data.siteName;
//            $scope.siteID = response.data.siteID;
//            $scope.siteKey = response.data.siteKey;
//        });
//}

function UserController($scope, remoteService, ngNotify) {

    QRM.userCtrl = this;

    $scope.changeUser = function (e) {
        e.dirty = true;
    };

    $scope.saveChanges = function (e) {
        remoteService.saveSiteUsers($scope.gridOptions.data)
            .then(function (response) {
                $scope.gridOptions.data = response.data;
                alert("Site Risk Users Updated");
            });
    };

    $scope.cancelChanges = function (e) {
        remoteService.getSiteUsers()
            .then(function (response) {
                $scope.gridOptions.data = response.data;
                alert("Changed Cancelled");
            });
    };
    $scope.gridOptions = {
        enableSorting: true,
        minRowsToShow: 8,
        rowHeight: 30,
        columnDefs: [
            {
                name: 'name',
                width: 150,
                field: 'data.display_name',
                type: 'text',
                enableHiding: false
                },
            {
                name: 'emailAddress',
                width: "*",
                field: 'data.user_email',
                type: 'text',
                enableHiding: false
                },
//            {
//                name: 'Administrator',
//                width: 120,
//                field: 'allcaps.risk_admin',
//                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_admin"  ng-click="grid.appScope.changeUser(row.entity)">',
//                cellClass: 'cellCentered',
//                type: 'text',
//                enableHiding: false
//                },
            {
                name: 'Risk User',
                width: 130,
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-model="row.entity.caps.risk_user"  ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered',
                type: 'text',
                enableHiding: false
                }
                ]
    };

    this.load = function () {
        $scope.gridOptions.data = QRM.siteUsers;
    }

    remoteService.getSiteUsers()
        .then(function (response) {
            QRM.siteUsers = response.data;
            QRM.userCtrl.load();
        });

}

function dropzone() {
    function link (scope, element, attrs) {
        var config, dropzone;
        config = scope[attrs.dropzone];
        // create a Dropzone for the element with the given options
        dropzone = new Dropzone(element[0], config.options);
        // bind the given event handlers
        angular.forEach(config.eventHandlers, function (handler, event) {
            dropzone.on(event, handler);
        });
    };
    
    return{
        link:link
    }
}

var app = angular.module('myApp', [
'ngNotify',
'ngSanitize',
'ui.grid',
'ui.grid.autoResize',
'ui.bootstrap'
]);

(function(){

//app.service('remoteService', ['$http', RemoteService]);
app.filter('usernameFilter', function () {
//    return function (input) {
//        if (typeof (input) == 'undefined') return;
//        return jQuery.grep(QRM.siteUsers, function (e) {
//            return e.ID == input
//        })[0].data.display_name;
//    }
    
	return function (input) {
		if (typeof (input) == "object") input = input.ID;
		if (input < 0) return "Not Assigned"
		if (typeof (input) == 'undefined') return;

		var user = jQuery.grep(QRM.siteUsers, function (e) {
			return e.ID == input
		})

		if (typeof (user) == "undefined") return "Unknown";
		if (user.length == 0) return "Not Found";
		if (user.length > 1) return "Unknown (too many)";
		
		var display = "";
		if (QRM.displayUser == "userdisplayname"){
			display = user[0].data.user_login;
		}			
		if (QRM.displayUser == "userlogin"){
			display = user[0].data.user_login;
		}			
		if (QRM.displayUsery == "usernicename"){
			display = user[0].data.user_nicename;
		}			
		if (QRM.displayUser == "useremail"){
			display = user[0].data.user_email;
		}			
		if (QRM.displayUser == "usernickname"){
			display = user[0].data.nickname;
		}			
		if (QRM.displayUser == "userfirstname"){
			display = user[0].data.first_name;
		}			
		if (QRM.displayUser == "userlastname"){
			display = user[0].data.last_name;
		}
		
		if (display == "" && user[0].data.display_name !="") { display = user[0].data.display_name }
		if (display == "" && user[0].data.user_login !="") { display = user[0].data.user_login }
		if (display == "" && user[0].data.user_nicename !="") { display = user[0].data.user_nicename }
		if (display == "" && user[0].data.user_email !="") { display = user[0].data.user_email }
		if (display == "" && user[0].data.nickname !="") { display = user[0].data.nickname }
		if (display == "" && user[0].data.first_name !="") { display = user[0].data.first_name }
		if (display == "" && user[0].data.last_name !="") { display = user[0].data.last_name }

		return display;
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
    this.installSampleProjects = function (minmax) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            data: minmax,
            params: {
                action: "installSampleProjects"
            },
            cache: false
        });
    };

    this.reindexRiskCount = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "reindexRiskCount"
            },
            cache: false
        });
    };
    this.removeSample = function (all) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "removeSample"
            },
            data:all,
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
    
    this.saveReportOptions = function (data) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "saveReportOptions"
            },
            data: data,
            cache: false
        });
    };
    this.saveDisplayUser = function (data) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "saveDisplayUser"
            },
            data: data,
            cache: false
        });
    };    
    this.getReportOptions = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getReportOptions"
            },
            cache: false
        });
    };
    this.getDisplayUser = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getDisplayUser"
            },
            cache: false
        });
    };
});
app.controller('userCtrl', ['$scope', 'remoteService', 'ngNotify', UserController]);	
app.controller('sampleCtrl', ['$scope', 'remoteService', 'ngNotify', SampleController]);
//app.controller('repCtrl', ['$scope', 'remoteService', ReportController]);
app.controller('reportCtrl', ['$scope', 'remoteService', ReportController]);
app.controller('userNameCtrl', ['$scope', 'remoteService', UserNameController]);
app.directive('dropzone', dropzone);  
})();