var app = angular.module('myApp', [
        'ui.grid',
        'ui.grid.autoResize',
        'ui.bootstrap',
        'ui.grid.treeView',
        'ui.grid.edit',
        'ui.grid.moveColumns',
        'treeControl',
        'cgNotify'
    ]);

app.config(['$compileProvider', function ($compileProvider) {
    $compileProvider.debugInfoEnabled(false);
}]);

function arrangeProjects(projects) {
    var projMap = new Map();
    var retn = new Array();

    projects.forEach(function (e) {
        projMap.put(e.id, e);
    });

    projects.forEach(function (e) {
        if (projMap.findIt(e.parent_id) > -1) {
            parent = projMap.get(e.parent_id);
            if (parent.children == null) {
                parent.children = new Array();
            }
            parent.children.push(e);
        } else {
            retn.push(e);
        }
    });
    return retn;
}

function parentSort(projArr) {
    
     projArr.forEach(function (e) {
        e.$$treeLevel = -100;
    });
    
    var sortedArray = $.grep(projArr, function (value) {
        return value.parent_id <= 0;
    })

    sortedArray.forEach(function (e) {
        e.$$treeLevel = 0;
    });

    projArr = $.grep(projArr, function (value) {
        return value.$$treeLevel < 0
    });

    while (projArr.length > 0) {
        
        for (j = 0; j < projArr.length; j++){
            
            var child = projArr[j];
            var found = false;
            for (var i = 0; i < sortedArray.length; i++) {
                
                var parent = sortedArray[i];
                
                if (child.parent_id == parent.id ) {
                    child.$$treeLevel = parent.$$treeLevel + 1;
                    sortedArray.splice(i + 1, 0, child);
                    found = true;
                    break;
                }
            }
            if (found) break;
        }
        projArr = $.grep(projArr, function (value) { return value.$$treeLevel < 0 });
    }

    return sortedArray;

}

function arrangeObjectives(objectives) {
    var objMap = new Map();
    var retn = new Array();

    objectives.forEach(function (e) {
        objMap.put(e.id, e);
    });

    objectives.forEach(function (e) {
        if (objMap.findIt(e.parentID) > -1) {
            var obj = objMap.get(e.parentID);
            if (obj.children == null) {
                obj.children = new Array();
            }
            obj.children.push(e);
        } else {
            retn.push(e);
        }
    });
    return retn;
}

function getProjectParents(projMap, projectID) {
    var parentID = projMap.get(projectID).parent_id;

    retn = new Array();
    if (projMap.findIt(parentID) > -1) {
        var tmp = projMap.get(parentID);
        retn.push(tmp);
        return retn.concat(getProjectParents(projMap, parentID));
    } else {
        return retn;
    }
}

function getFamilyObjectives(projMap, projectID) {


    var obj = new Array();
    var proj = projMap.get(projectID);

    obj = obj.concat(proj.objectives);

    getProjectParents(projMap, projectID).forEach(function (p) {
        if (p.id != projectID) {
            obj = obj.concat(p.objectives);
        }
    });

    obj.forEach(function (e) {
        delete e.children;
    })

    return arrangeObjectives(obj);

}

function getFamilyCats(projMap, projectID) {


    var cat = new Array();
    var proj = projMap.get(projectID);

    cat = cat.concat(proj.categories);

    getProjectParents(projMap, projectID).forEach(function (p) {
        if (p.id != projectID) {
            cat = cat.concat(p.categories);
        }
    });

    return cat;

}

app.controller('switchCtrl', function ($scope, adminService) {

    QRM.switchCtrl = this;

    $scope.t0 = true;
    $scope.t1 = false;
    $scope.t2 = false;
    $scope.t3 = false;
    $scope.t4 = false;

    this.tabswitch = function (p) {
        $scope.tabswitch(p);
    }
    $scope.tabswitch = function (p) {

        $scope.t0 = $scope.t1 = $scope.t2 = $scope.t3 = $scope.t4 = false;

        switch (p) {
        case 1:
            $scope.t1 = true;
            QRM.listCtrl.load();
            break;
        case 2:
            $scope.t2 = true;
            QRM.projCtrl.load();
            break;
        case 3:
            $scope.t3 = true;
            QRM.userCtrl.load();
            break;
        case 4:
            $scope.t4 = true;
            break;
        }
    }

});
app.controller('listCtrl', function ($scope, adminService) {

    QRM.listCtrl = this;
    $scope.gridOptions = {
        enableSorting: false,
        enableFiltering: false,
        onRegisterApi: function( gridApi ) {
            $scope.gridApi = gridApi;
        },
        columnDefs: [
            {
                name: 'Project Title',
                field: 'title',
                width: '*',
                cellTemplate:'<div ng-style="grid.appScope.rowStyle(row.entity)" class="ui-grid-cell-contents" title="TOOLTIP">{{COL_FIELD CUSTOM_FILTERS}}</div>'
            },{
                name:"Project Code",
                field:"projectCode",
                width:130
            },{
                name:"Project Risk Manager",
                field:"projectRiskManager.display_name",
                width:170
            },
            {
                name: 'id',
                enableColumnMoving: false,
                enableFiltering: false,
                enableSorting: false,
                enableHiding: false,
                enableCellEdit: false,
                cellTemplate: '<div style="text-align:center"><i class="fa fa-edit" style="cursor:pointer;color:green;" ng-click="$event.stopPropagation();grid.appScope.editProject(row.entity.id)" ></i>&nbsp;&nbsp;<i class="fa fa-trash" style=";color:red;cursor:pointer" ng-click="$event.stopPropagation();grid.appScope.deleteProject(row.entity.id)" ></i></div>',
                width: 60,
                headerCellClass: 'header-hidden',
                cellClass: 'cellCentered'

            }
    ]
    };
    
    $scope.rowStyle = function(e){
        return {
            "margin-left":e.$$treeLevel*20+"px"
        }
    }

    this.load = function () {
        adminService.getProjects()
            .then(function (response) {
                $scope.sortedParents = parentSort(response.data.data);
                $scope.gridOptions.data = $scope.sortedParents; 
                setTimeout(function () {
                    $scope.gridApi.treeView.expandAllRows();
                }, 100);
                $scope.gridApi.treeView.expandAllRows();
            });
    }

    $scope.editProject = function (id) {
        adminService.getProject(id)
            .then(function (response) {
                QRM.switchCtrl.tabswitch(2);
                QRM.projCtrl.editProject(response.data.data);
            });
    }

});
app.controller('projectCtrl', function ($scope, $modal, notify, adminService) {

    QRM.projCtrl = this;
    var projCtrl = this;

    $scope.status = {
        isFirstOpen: false
    };

    $scope.tempObjectiveID = -1;

    $scope.treeOptions = {
        nodeChildren: "children",
        dirSelectable: true,
        multiSelection: false,
        injectClasses: {
            ul: "a1",
            li: "a2",
            liSelected: "a7",
            iExpanded: "a3",
            iCollapsed: "a4",
            iLeaf: "a5",
            label: "a6",
            labelSelected: "a8"
        }
    }

    $scope.ref = {};

    $scope.addObjective = function (isPrim) {


        debugger;
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
            notify({
                message: 'Please select an objective to add a sub-objective to',
                classes: 'alert-warning',
                duration: 5000,
                templateUrl: "../wp-content/plugins/qrm/includes/qrmmainapp/views/common/notify.html",
                topOffset: 25
            });
        }
        $scope.projectObjectives = getFamilyObjectives($scope.projMap, $scope.proj.id);


        delete $scope.ref.objectiveText;
        delete $scope.ref.selectedObjective;

    }
    $scope.treeObjectiveOptions = {
        nodeChildren: "children",
        dirSelectable: true,
        multiSelection: false,
        injectClasses: {
            ul: "a1",
            li: "a2",
            liSelected: "a7",
            iExpanded: "a3",
            iCollapsed: "a4",
            iLeaf: "a5",
            label: "a6",
            labelSelected: "a8"
        }
    }

    $scope.projectSelect = function (parentProject) {
        $scope.proj.parent_id = parentProject.id;
        $scope.projectObjectives = getFamilyObjectives($scope.projMap, $scope.proj.id);

        $scope.catData = getFamilyCats($scope.projMap, $scope.proj.id);
        $scope.primGridOptions.data = $scope.catData;
        $scope.secGridOptions.data = [];
        $scope.primCatID = 0;

    }

    $scope.saveChanges = function () {
        if ($scope.proj.id == $scope.proj.parent_id) {
            $scope.proj.parent_id = 0;
        }
        adminService.saveProject(JSON.stringify($scope.proj))
            .then(function (response) {
                QRM.switchCtrl.tabswitch(1);
                notify({
                    message: 'Project Saved',
                    classes: 'alert-success',
                    duration: 2500,
                    templateUrl: "../wp-content/plugins/qrm/includes/qrmmainapp/views/common/notify.html",
                    topOffset: 25
                });
            });
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

    $scope.catID = -1;
    $scope.changePrimCategory = function (id) {
        debugger;
        $scope.primCatID = id;
        $scope.secGridOptions.data = $.grep($scope.catData, function (cat) {
            return cat.parentID == id;
        });
    };
    $scope.addCat = function (isPrim) {
        debugger;

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
        $scope.secGridOptions.data = $.grep($scope.catData, function (cat) {
            return cat.parentID == $scope.primCatID;
        });
        $scope.primGridOptions.data = $scope.catData;

        $scope.ref.catText = "";
        $scope.ref.catSubText = "";

    }
    $scope.rowStyle = function (id) {
        if (id == $scope.primCatID) {
            return {
                'background-color': 'yellow'
            };
        } else {
            return {};
        }
    };

    $scope.editObjective = function (node) {

        if (node.projectID != $scope.proj.id) {
            notify({
                message: 'The selected objective belongs to a parent project and cannot be edited here',
                classes: 'alert-danger',
                duration: 5000,
                templateUrl: "../wp-content/plugins/qrm/includes/qrmmainapp/views/common/notify.html",
                topOffset: 25
            });
            return;
        }

        var modalInstance = $modal.open({
            templateUrl: 'myModalContentCat.html',
            size: "md",
            controller: function ($modalInstance, title, node) {
                this.title = title;
                this.catTitle = node.title;
                this.ok = function () {
                    $modalInstance.close(this.catTitle);
                };
                this.cancel = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            resolve: {
                title: function () {
                    return "Project Objective"
                },
                node: function () {
                    return node
                }
            }
        });

        modalInstance.result.then(function (r) {
            node.title = r;
        });
    }
    $scope.deleteObjective = function (node) {
        if (node.projectID != $scope.proj.id) {
            notify({
                message: 'The selected objective belongs to a parent project and cannot be deleted from here',
                classes: 'alert-danger',
                duration: 5000,
                templateUrl: "../wp-content/plugins/qrm/includes/qrmmainapp/views/common/notify.html",
                topOffset: 25
            });
            return;
        } else {

            adminService.confirm("Do you wish to delete the selected Objective?", function (confirm) {
                if (confirm) {
                    $scope.proj.objectives = jQuery.grep($scope.proj.objectives, function (value) {
                        return (value.id != node.id && value.parentID != node.id);
                    });

                    $scope.projectObjectives = getFamilyObjectives($scope.projMap, $scope.proj.id);
                    delete $scope.ref.objectiveText;
                    delete $scope.ref.selectedObjective;

                }

            })

        }
    }
    $scope.editCategory = function (cat) {
        if (cat.projectID != $scope.proj.id) {
            notify({
                message: 'The selected category belongs to a parent project and cannot be edited here',
                classes: 'alert-danger',
                duration: 5000,
                templateUrl: "../wp-content/plugins/qrm/includes/qrmmainapp/views/common/notify.html",
                topOffset: 25
            });
            return;
        }

        var modalInstance = $modal.open({
            templateUrl: 'myModalContentCat.html',
            size: "md",
            controller: function ($modalInstance, title, cat) {
                this.title = title;
                this.catTitle = cat.title;
                this.ok = function () {
                    $modalInstance.close(this.catTitle);
                };
                this.cancel = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            resolve: {
                title: function () {
                    return (cat.primCat) ? "Primary Category" : "Seconday Category"
                },
                cat: function () {
                    return cat
                }
            }
        });

        modalInstance.result.then(function (r) {
            cat.title = r;
        });
    }

    $scope.deleteCategory = function (cat) {
        if (cat.projectID != $scope.proj.id) {
            notify({
                message: 'The selected category belongs to a parent project and cannot be deleted from here',
                classes: 'alert-danger',
                duration: 5000,
                templateUrl: "../wp-content/plugins/qrm/includes/qrmmainapp/views/common/notify.html",
                topOffset: 25
            });
            return;
        } else {

            adminService.confirm("Do you wish to delete the selected Category?", function (confirm) {
                if (confirm) {
                    $scope.proj.categories = jQuery.grep($scope.proj.categories, function (value) {
                        return (value.id != cat.id && value.parentID != cat.id);
                    });

                    if (cat.primCat) {
                        $scope.catData = getFamilyCats($scope.projMap, $scope.proj.id);
                        $scope.primGridOptions.data = $scope.catData;
                        $scope.secGridOptions.data = [];
                        $scope.primCatID = 0;
                    } else {
                        $scope.catData = getFamilyCats($scope.projMap, $scope.proj.id);
                        $scope.primGridOptions.data = $scope.catData;
                        $scope.secGridOptions.data = $.grep($scope.catData, function (cat) {
                            return cat.parentID == $scope.primCatID;
                        });
                    }
                }

            })

        }
    }
    $scope.catParentID = 100;
    $scope.primGridOptions = {
        minRowsToShow: 5,
        rowHeight: 30,
        enableFiltering: true,
        data: $scope.catData,
        rowTemplate: '<div ng-click="grid.appScope.changePrimCategory(row.entity.id)"   ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-style="grid.appScope.rowStyle(row.entity.id)" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
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

    $scope.secGridOptions = {
        minRowsToShow: 5,
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

    $scope.gridOwnerOptions = {
        enableSorting: true,
        minRowsToShow: 5,
        rowHeight: 30,
        columnDefs: [

            {
                name: 'name',
                width: 150,
                field: 'display_name',
                type: 'text'

            },
            {
                name: 'emailAddress',
                width: "*",
                field: 'user_email',
                type: 'text'


            }, {
                name: 'Owner',
                width: 80,
                type: 'text',
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
                field: 'display_name',
                type: 'text'


            },
            {
                name: 'emailAddress',
                width: "*",
                field: 'user_email',
                type: 'text'


            }, {
                name: 'Manager',
                width: 80,
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-click="grid.appScope.changeManager(row.entity)">',
                cellClass: 'cellCentered',
                type: 'text'

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
                field: 'display_name',
                type: 'text'


            },
            {
                name: 'emailAddress',
                width: "*",
                field: 'user_email',
                type: 'text'


            }, {
                name: 'User',
                width: 80,
                field: 'caps.risk_user',
                cellTemplate: '<input style="height:15px" type="checkbox" ng-click="grid.appScope.changeUser(row.entity)">',
                cellClass: 'cellCentered',
                type: 'text'

            }
        ]
    };

    $scope.load = function () {

        adminService.getSiteUsersCap()
            .then(function (response) {
                $scope.ref.riskProjectManagers = jQuery.grep(response.data.data, function (e) {
                    return e.bProjMgr
                });
            debugger;
                $scope.gridOwnerOptions.data = jQuery.grep(response.data.data, function (e) {
                    return e.bOwner
                });
                $scope.gridManagerOptions.data = jQuery.grep(response.data.data, function (e) {
                    return e.bManager
                });
                $scope.gridUserOptions.data = jQuery.grep(response.data.data, function (e) {
                    return e.bUser
                });
            });

        adminService.getProjects()
            .then(function (response) {
                $scope.projectsLinear = response.data.data;
                $scope.projects = arrangeProjects($scope.projectsLinear);

                $scope.projMap = new Map();
                $scope.projects.forEach(function (e) {
                    $scope.projMap.put(e.id, e);
                    if (e.id == $scope.proj.parent_id) {
                        $scope.selectedProject = e;
                    }
                });

                $scope.projMap.put($scope.proj.id, $scope.proj);
                $scope.projectObjectives = getFamilyObjectives($scope.projMap, $scope.proj.id);
                $scope.catData = getFamilyCats($scope.projMap, $scope.proj.id);
                $scope.primGridOptions.data = $scope.catData;
                $scope.secGridOptions.data = [];
                $scope.primCatID = 0;

            });
    }

    this.load = function () {
        $scope.proj = adminService.getDefaultProject();
        setConfigMatrix($scope.proj.matrix.tolString, $scope.proj.matrix.maxImpact, $scope.proj.matrix.maxProb, "#svgDIV", $scope.matrixChangeCB);
        $scope.load();
    }

    this.editProject = function (project) {
        $scope.proj = project;
        setConfigMatrix($scope.proj.matrix.tolString, $scope.proj.matrix.maxImpact, $scope.proj.matrix.maxProb, "#svgDIV", $scope.matrixChangeCB);
        $scope.load();
    }
});
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
        adminService.getSiteUsers()
            .then(function (response) {
                $scope.gridOptions.data = response.data.data;
            });
    }

});
app.service('adminService', function ($http, $modal) {

    var service = this;
    var loc = window.location.href;
    this.url = loc.slice(0, loc.indexOf("wp-admin"));

    this.confirm = function (msg, cbFn) {
        var modalInstance = $modal.open({
            templateUrl: 'myModalContentConfirm.html',
            size: "md",
            controller: function ($modalInstance, title) {
                this.title = title;
                this.ok = function () {
                    $modalInstance.close(true);
                };
                this.cancel = function () {
                    $modalInstance.close(false);
                };
            },
            controllerAs: "vm",
            resolve: {
                title: function () {
                    return msg
                }
            }
        });

        modalInstance.result.then(function (r) {
            cbFn(r);
        });

    }

    this.getSiteUsers = function () {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=getSiteUsers",
            cache: false
        });
    };
    this.getSiteUsersCap = function () {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=getSiteUsersCap",
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
    this.getProjects = function () {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=getProjects",
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
    this.saveSiteUsersCap = function (data) {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=saveSiteUsersCap",
            data: data,
            cache: false
        });
    };
    this.saveProject = function (data) {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=saveProject",
            data: data,
            cache: false
        });
    };
    this.getProject = function (projectID) {
        return $http({
            method: 'POST',
            url: service.url + "?qrmfn=getProject",
            data: projectID,
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