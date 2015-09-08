function checkValid(proj, scope) {
    var rtn = {
        code: 1,
        msg: ""
    }

    //Check for circular parent/child relationships.

    if (isCircular(proj, scope)) {
        rtn.code = -1;
        rtn.msg = "Circular Parent/Child Relationship Detected";
        return rtn;
    }

    // Project Risk Manager Set
    if (typeof (proj.projectRiskManager) == "undefined") {
        rtn.code = -1;
        rtn.msg = "Project Risk Manager Not Set";
        return rtn;
    }


    return rtn;

}

function isCircular(proj, scope) {
    var pID = proj.parent_id;

    while (pID != 0) {

        var search = $.grep(scope.projectsLinear, function (value) {
            return value.id == pID;
        });

        if (search.length == 0) {
            pID = 0;
            continue;
        }

        var pp = search[0];
        if (pp.id == proj.id) {
            return true;
        } else {
            pID = pp.parent_id;
        }
    }

    return false;

}

function ProjectController($scope, ngNotify, remoteService, QRMDataService, ngDialog) {

    QRM.projCtrl = this;
    var projCtrl = this;

    $scope.tempObjectiveID = -1;
    $scope.catParentID = 100;
    $scope.catID = -1;
    $scope.ref = {};
    $scope.parentProjectID = 0;

    $scope.projectSelect = function (item, model) {

        /*
         *   $scope.parentProjectID keeps track of the valid original parent Project ID
         *   in case it is changed to a value that would create a circular reference (set when the editor is first initaited)
         */

        if (typeof (item) == "undefined" || typeof (model) == "undefined") {
            $scope.projMap.get($scope.proj.id).parent_id = 0;
        } else {

            $scope.projMap.get($scope.proj.id).parent_id = model;

            if (isCircular($scope.projMap.get($scope.proj.id), $scope)) {
                ngNotify.set('Parent Project not updated because it would create a circular relationship between parent and children', {
                    type: "grimace"
                });
                $scope.projMap.get($scope.proj.id).parent_id = $scope.parentProjectID;
            } else {
                $scope.parentProjectID = $scope.projMap.get($scope.proj.id).parent_id;
            }
        }

        $scope.projectObjectives = getLinearObjectives($scope.projMap, $scope.proj.id);
        $scope.gridObjectiveOptions.data = objectiveSort(getLinearObjectives($scope.projMap, $scope.proj.id));
        setTimeout(function () {
            $scope.objGridApi.treeView.expandAllRows();
        }, 100);

        $scope.catData = getFamilyCats($scope.projMap, $scope.proj.id);
        $scope.gridPrimCatOptions.data = $scope.catData;
        $scope.gridSecCatOptions.data = [];
        $scope.primCatID = 0;
    }

    $scope.cancelChanges = function () {
        QRM.switchCtrl.tabswitch(1);
    }

    this.saveProject = function () {
        $scope.saveChanges();
    }

    $scope.saveChanges = function () {

        if (typeof ($scope.proj.parent_id) == "undefined") {
            $scope.proj.parent_id = 0;
        }
        if ($scope.proj.id == $scope.proj.parent_id) {
            $scope.proj.parent_id = 0;
        }

        var valid = checkValid($scope.proj, $scope);
        if (valid.code > 0) {
            remoteService.saveProject(JSON.stringify($scope.proj))
                .then(function (response) {
                    ngNotify.set("Project Saved", "success");
                    $scope.handleGetProjects(response);
                    projCtrl.editProject($scope.projMap.get(projectID));

                });
        } else {
            ngNotify.set("Project Not Saved<br/><br/><i>" + valid.msg + "</i>", {
                html: true,
                type: "error"
            });
        }
    }
    
    $scope.parentIsNull = function(){
    	return $scope.projMap.get($scope.proj.id).parent_id == 0;
    }

    $scope.matrixChange = function () {
        setConfigMatrix($scope.proj.matrix.tolString, $scope.proj.matrix.maxImpact, $scope.proj.matrix.maxProb, "#svgDIV", $scope.matrixChangeCB);
    }
    $scope.matrixChangeCB = function () {
        $("#svgDIV rect").each(function () {
            var html = this.outerHTML;
            var i = html.indexOf("qrmID=");
            var ip = html.substring(i + 7, i + 9);
            var impact = Number(ip.substring(0, 1));
            var prob = Number(ip.substring(1));
            var tol = Number(this.className.baseVal.substring(4, 5));
            var index = (prob - 1) * $scope.proj.matrix.maxImpact + impact - 1;

            $scope.proj.matrix.tolString = $scope.proj.matrix.tolString.substring(0, index) + tol + $scope.proj.matrix.tolString.substring(index + 1);
        })
    }

    $scope.changeOwner = function (e) {

        if (typeof ($scope.proj.ownersID) == 'undefined') {
            $scope.proj.ownersID = [];
        }

        if (!e.pOwner) {
            $scope.proj.ownersID = $.grep($scope.proj.ownersID, function (value) {
                return value != e.ID;
            })
        } else if ($.inArray(e.ID, $scope.proj.ownersID) < 0) {
            $scope.proj.ownersID.push(e.ID);
        }

    };
    $scope.changeManager = function (e) {

        if (typeof ($scope.proj.managersID) == 'undefined') {
            $scope.proj.managersID = [];
        }

        if (!e.pManager) {
            $scope.proj.managersID = $.grep($scope.proj.managersID, function (value) {
                return value != e.ID;
            })
        } else if ($.inArray(e.ID, $scope.proj.managersID) < 0) {
            $scope.proj.managersID.push(e.ID);
        }

    };
    $scope.changeUser = function (e) {

        if (typeof ($scope.proj.usersID) == 'undefined') {
            $scope.proj.usersID = [];
        }

        if (!e.pUser) {
            $scope.proj.usersID = $.grep($scope.proj.usersID, function (value) {
                return value != e.ID;
            })
        } else if ($.inArray(e.ID, $scope.proj.usersID) < 0) {
            $scope.proj.usersID.push(e.ID);
        }

    }

    $scope.addCat = function (isPrim) {

        var cat = {
            title: isPrim ? $scope.ref.catText : $scope.ref.catSubText,
            id: $scope.catID--,
            primCat: true,
            parentID: 0,
            projectID: $scope.proj.id
        }

        if (!isPrim) {
            cat.parentID = $scope.primCatID;
            cat.primCat = false;
        } else {
            // Set the current selected parent to the newly created 
            $scope.primCatID = $scope.catID + 1;
        }

        $scope.proj.categories.push(cat);
        $scope.catData = getFamilyCats($scope.projMap, $scope.proj.id);
        $scope.gridSecCatOptions.data = $.grep($scope.catData, function (cat) {
            return cat.parentID == $scope.primCatID;
        });
        $scope.gridPrimCatOptions.data = $scope.catData;

        $scope.ref.catText = "";
        $scope.ref.catSubText = "";

    }
    $scope.editCategory = function (cat) {

        $scope.dialogCategory = cat;
        $scope.origCat = cat.title;


        ngDialog.openConfirm({
            template: "editCategoryModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {


        }, function (reason) {
            cat.title = $scope.origCat;
        });


    }
    $scope.deleteCategory = function (cat) {

        $scope.dialogCategory = cat;

        ngDialog.openConfirm({
            template: "deleteCategoryModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            $scope.proj.categories = jQuery.grep($scope.proj.categories, function (value) {
                return (value.id != cat.id && value.parentID != cat.id);
            });

            if (cat.primCat) {
                $scope.catData = getFamilyCats($scope.projMap, $scope.proj.id);
                $scope.gridPrimCatOptions.data = $scope.catData;
                $scope.gridSecCatOptions.data = [];
                $scope.primCatID = 0;
            } else {
                $scope.catData = getFamilyCats($scope.projMap, $scope.proj.id);
                $scope.gridPrimCatOptions.data = $scope.catData;
                $scope.gridSecCatOptions.data = $.grep($scope.catData, function (cat) {
                    return cat.parentID == $scope.primCatID;
                });
            }

        });
    }
    $scope.changePrimCategory = function (id) {
        $scope.primCatID = id;
        $scope.gridSecCatOptions.data = $.grep($scope.catData, function (cat) {
            return cat.parentID == id;
        });
    };

    $scope.addObjective = function (isPrim) {

        if ($scope.ref.objectiveText == "" || $scope.ref.objectiveText == null) return;
        delete $scope.projectObjectives;
        if ($scope.proj.objectives == null) {
            $scope.proj.objectives = [];
        }

        if (isPrim) {
            $scope.proj.objectives.push({
                title: $scope.ref.objectiveText,
                projectID: $scope.proj.id,
                id: $scope.tempObjectiveID--,
                parentID: 0
            })
        } else if ($scope.ref.selectedObjective != null) {
            $scope.proj.objectives.push({
                title: $scope.ref.objectiveText,
                projectID: $scope.proj.id,
                id: $scope.tempObjectiveID--,
                parentID: $scope.ref.selectedObjective.id
            })
        } else {
            ngNotify.set('Please select an objective to add a sub-objective', {
                type: "grimace"
            });
        }

        $scope.projMap.get($scope.proj.id).objectives = $scope.proj.objectives;
        $scope.projectObjectives = getLinearObjectives($scope.projMap, $scope.proj.id);
        $scope.gridObjectiveOptions.data = objectiveSort(getLinearObjectives($scope.projMap, $scope.proj.id));
        setTimeout(function () {
            $scope.objGridApi.treeView.expandAllRows();
        }, 100);

        delete $scope.ref.objectiveText;
        delete $scope.ref.selectedObjective;

    }
    $scope.editObjective = function (node) {

        $scope.dialogObjective = node;
        $scope.origObjective = node.title;

        ngDialog.openConfirm({
            template: "editObjectiveModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {


        }, function (reason) {
            node.title = $scope.origObjective;
        });

    }
    $scope.deleteObjective = function (node) {

        $scope.dialogObjective = node;

        ngDialog.openConfirm({
            template: "deleteObjectiveModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            $scope.proj.objectives = jQuery.grep($scope.proj.objectives, function (value) {
                return (value.id != node.id && value.parentID != node.id);
            });

            $scope.projMap.get($scope.proj.id).objectives = $scope.proj.objectives;
            $scope.projectObjectives = getLinearObjectives($scope.projMap, $scope.proj.id);
            $scope.gridObjectiveOptions.data = objectiveSort(getLinearObjectives($scope.projMap, $scope.proj.id));

            delete $scope.ref.objectiveText;
            delete $scope.ref.selectedObjective;

        }, function (reason) {
            //           alert("NO");
        });

    }
    $scope.changeSelectedObjective = function (obj) {
        $scope.ref.selectedObjective = obj;
    }

    $scope.gridPrimCatOptions = {
        minRowsToShow: 7,
        rowHeight: 30,
        enableFiltering: true,
        data: $scope.catData,
        rowTemplate: '<div ng-click="grid.appScope.changePrimCategory(row.entity.id)"   ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-style="grid.appScope.catRowStyle(row.entity.id)" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
        columnDefs: [

            {
                name: 'Primary Category',
                width: "*",
                field: 'title',
                type: 'text',
                enableFiltering: false,

            },
            {
                field: 'primCat',
                filter: {
                    term: true
                },
                visible: false
            },
            {
                name: 'id',
                enableColumnMoving: false,
                enableFiltering: false,
                enableSorting: false,
                enableHiding: false,
                enableCellEdit: false,
                cellTemplate: '<i class="fa fa-edit" style="cursor:pointer;color:green;" ng-click="$event.stopPropagation();grid.appScope.editCategory(row.entity)" ng-show="grid.appScope.proj.id==row.entity.projectID"></i>&nbsp;&nbsp;<i class="fa fa-trash" style=";color:red;cursor:pointer" ng-click="$event.stopPropagation();grid.appScope.deleteCategory(row.entity)" ng-show="grid.appScope.proj.id==row.entity.projectID"></i><small ng-show="grid.appScope.proj.id!=row.entity.projectID">Parent</small>',
                width: 60,
                headerCellClass: 'header-hidden',
                cellClass: 'cellCentered'

            }

        ]

    };
    $scope.gridSecCatOptions = {
        minRowsToShow: 7,
        rowHeight: 30,
        enableFiltering: true,
        columnDefs: [

            {
                name: 'Secondary Category',
                width: "*",
                field: 'title',
                type: 'text',
                enableFiltering: false

            }, {
                field: 'primCat',
                filter: {
                    term: false
                },
                visible: false
            }
            , {
                name: 'id',
                enableColumnMoving: false,
                enableFiltering: false,
                enableSorting: false,
                enableHiding: false,
                enableCellEdit: false,
                cellTemplate: '<i class="fa fa-edit" style="cursor:pointer;color:green;" ng-click="$event.stopPropagation();grid.appScope.editCategory(row.entity)" ng-show="grid.appScope.proj.id==row.entity.projectID"></i>&nbsp;&nbsp;<i class="fa fa-trash" style=";color:red;cursor:pointer" ng-click="$event.stopPropagation();grid.appScope.deleteCategory(row.entity)" ng-show="grid.appScope.proj.id==row.entity.projectID"></i><small ng-show="grid.appScope.proj.id!=row.entity.projectID">Parent</small>',
                width: 60,
                headerCellClass: 'header-hidden',
                cellClass: 'cellCentered'

            }
        ]
    };
    $scope.gridObjectiveOptions = {
        enableSorting: false,
        enableFiltering: false,
        minRowsToShow: 7,
        rowHeight: 30,
        onRegisterApi: function (gridApi) {
            $scope.objGridApi = gridApi;
        },
        rowTemplate: '<div ng-style="grid.appScope.objRowStyle(row.entity)" ng-click="grid.appScope.changeSelectedObjective(row.entity)"   ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
        columnDefs: [

            {
                name: 'Objective',
                width: "*",
                field: 'title',
                type: 'text',
                enableFiltering: false,
                cellTemplate: '<div ng-style="grid.appScope.rowStyle(row.entity)" ng-click="grid.appScope.changeSelectedObjective(row.entity)" class="ui-grid-cell-contents" title="TOOLTIP">{{COL_FIELD CUSTOM_FILTERS}}</div>',

            }, {
                name: 'id',
                enableColumnMoving: false,
                enableFiltering: false,
                enableSorting: false,
                enableHiding: false,
                enableCellEdit: false,
                cellTemplate: '<div style="text-align:center"><i class="fa fa-edit" style="cursor:pointer;color:green;" ng-click="$event.stopPropagation();grid.appScope.editObjective(row.entity)" ng-show="grid.appScope.proj.id==row.entity.projectID"></i>&nbsp;&nbsp;<i class="fa fa-trash" style=";color:red;cursor:pointer" ng-click="$event.stopPropagation();grid.appScope.deleteObjective(row.entity)" ng-show="grid.appScope.proj.id==row.entity.projectID"></i><small ng-show="grid.appScope.proj.id!=row.entity.projectID">Parent</small></div>',
                width: 60,
                headerCellClass: 'header-hidden',
                cellClass: 'cellCentered'

            }
        ]
    };
    $scope.gridOwnerOptions = {
        enableSorting: true,
        minRowsToShow: 5,
        rowHeight: 30,
        onRegisterApi: function (gridApi) {
            $scope.gridOwnerApi = gridApi;
        },
        columnDefs: [
            {
                name: 'name',
                width: 150,
                field: 'display_name',
                type: 'text',
                enableHiding: false

            },
            {
                name: 'emailAddress',
                width: "*",
                field: 'user_email',
                type: 'text',
                enableHiding: false
            }, {
                name: 'Risk Owner',
                width: 140,
                type: 'text',
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox"  ng-change="grid.appScope.changeOwner(row.entity)" ng-model="row.entity.pOwner" ng-checked="row.entity.pOwner">',
                cellClass: 'cellCentered',
                enableHiding: false
            }
        ]
    };
    $scope.gridManagerOptions = {
        enableSorting: true,
        minRowsToShow: 5,
        onRegisterApi: function (gridApi) {
            $scope.gridManagerApi = gridApi;
        },
        rowHeight: 30,
        columnDefs: [
            {
                name: 'name',
                width: 150,
                field: 'display_name',
                type: 'text',
                enableHiding: false
           },
            {
                name: 'emailAddress',
                width: "*",
                field: 'user_email',
                type: 'text',
                enableHiding: false
            }, {
                name: 'Risk Manager',
                width: 140,
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox"  ng-change="grid.appScope.changeManager(row.entity)" ng-model="row.entity.pManager" ng-checked="row.entity.pManager">',
                cellClass: 'cellCentered',
                type: 'text',
                enableHiding: false
            }
        ]
    };
    $scope.gridUserOptions = {
        enableSorting: true,
        minRowsToShow: 5,
        onRegisterApi: function (gridApi) {
            $scope.gridUserApi = gridApi;
        },
        rowHeight: 30,
        columnDefs: [
            {
                name: 'name',
                width: 150,
                field: 'display_name',
                type: 'text',
                enableHiding: false
            },
            {
                name: 'emailAddress',
                width: "*",
                field: 'user_email',
                type: 'text',
                enableHiding: false
            }, {
                name: 'Risk User',
                width: 140,
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-change="grid.appScope.changeUser(row.entity)" ng-model="row.entity.pUser" ng-checked="row.entity.pUser">',
                cellClass: 'cellCentered',
                type: 'text',
                enableHiding: false

            }
        ]
    };

    $scope.catRowStyle = function (id) {
        if (id == $scope.primCatID) {
            return {
                'background-color': 'lightblue',
                'cursor': 'pointer'
            };
        } else {
            return {
                'cursor': 'pointer'
            };
        }
    };
    $scope.objRowStyle = function (obj) {

        if (typeof ($scope.ref.selectedObjective) == 'undefined') {
            return {
                'cursor': 'pointer'
            };
        }

        if (obj.id == $scope.ref.selectedObjective.id) {
            return {
                'background-color': 'lightblue',
                'cursor': 'pointer'
            };
        } else {
            return {
                'cursor': 'pointer'
            };
        }
    };
    $scope.rowStyle = function (e) {
        return {
            "margin-left": e.$$treeLevel * 20 + "px"
        }
    }

    this.editProject = function (project) {

        if (typeof (project) == 'undefined' || project == null) {
            // Will happen for in the case where "Add a New" is used
            project = QRMDataService.getDefaultProject();
            project.id = projectID;
            
            $scope.projectsLinear.push(project);
            $scope.sortedParents = [];
            $scope.projMap = new Map();

             $scope.sortedParents = parentSort($scope.projectsLinear);
            $scope.projMap = new Map();
            $scope.projectsLinear.forEach(function (e) {
                $scope.projMap.put(e.id, e);
            });
        }

        $scope.proj = project;

        if (typeof ($scope.proj.parent_id) == "undefined") {
            $scope.proj.parent_id = 0;
        }

        $scope.parentProjectID = $scope.proj.parent_id;

        setConfigMatrix($scope.proj.matrix.tolString, $scope.proj.matrix.maxImpact, $scope.proj.matrix.maxProb, "#svgDIV", $scope.matrixChangeCB);
        remoteService.getSiteUsersCap()
            .then(function (response) {
                response.data.forEach(function (e) {
                    e.pOwner = ($.inArray(e.ID, $scope.proj.ownersID) > -1);
                    e.pManager = ($.inArray(e.ID, $scope.proj.managersID) > -1);
                    e.pUser = ($.inArray(e.ID, $scope.proj.usersID) > -1);
                })
                $scope.ref.riskProjectManagers = response.data;
                $scope.gridOwnerOptions.data = response.data;
                $scope.gridManagerOptions.data = response.data;
                $scope.gridUserOptions.data = response.data;

            });

        $scope.projectObjectives = getLinearObjectives($scope.projMap, $scope.proj.id);
        $scope.gridObjectiveOptions.data = objectiveSort(getLinearObjectives($scope.projMap, $scope.proj.id));

        setTimeout(function () {
            $scope.objGridApi.treeView.expandAllRows();
        }, 100);

        $scope.catData = getFamilyCats($scope.projMap, $scope.proj.id);
        $scope.gridPrimCatOptions.data = $scope.catData;
        $scope.gridSecCatOptions.data = [];
        $scope.primCatID = 0;

        $scope.gridManagerApi.core.refresh();
        $scope.gridOwnerApi.core.refresh();
        $scope.gridUserApi.core.refresh();

    }

    $scope.handleGetProjects = function (response) {
        $scope.projectsLinear = [];
        $scope.sortedParents = [];
        $scope.projMap = new Map();

        if (response.data.data.length != 0) {
            $scope.projectsLinear = response.data.data;
            $scope.sortedParents = parentSort(response.data.data);
            $scope.projMap = new Map();
            $scope.projectsLinear.forEach(function (e) {
                $scope.projMap.put(e.id, e);
            });
        }
    }

    // Load the data
    remoteService.getProjects()
        .then(function (response) {
            $scope.handleGetProjects(response);
            // projectID is dynamically set by the PHP that generates the page
            projCtrl.editProject($scope.projMap.get(projectID));
        });
}

var app = angular.module('myApp', [
        'ui.grid',
        'ui.grid.autoResize',
        'ui.bootstrap',
        'ui.grid.treeView',
        'ngNotify',
        'ui.select',
        'ngSanitize',
        'ngDialog'
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
app.controller('projectCtrl', ['$scope', 'ngNotify', 'remoteService', 'QRMDataService', 'ngDialog', ProjectController]);
app.service('remoteService', ['$http', RemoteService]);
app.service('QRMDataService', DataService);