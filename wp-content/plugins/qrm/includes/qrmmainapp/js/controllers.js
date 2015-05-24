function MainCtrl(QRMDataService, remoteService, $state, $sce) {
    QRM.mainController = this;
    
    this.noProjectMsg = $sce.trustAsHtml("<span style='color:rgb(236,71,88);font-style:italic'>Please select a Risk Project from the selector above</span>");
    this.resetMsg = function(){
        this.noRisksMsg = $sce.trustAsHtml("<span style='color:rgb(28,132,198);font-style:italic'>Looking for risks in project <strong>"+QRM.expController.project.title+"</strong><span>");
    }
    this.setMsg = function(){
        this.noRisksMsg = $sce.trustAsHtml("<span style='color:rgb(236,71,88);font-style:italic'>No risks found for risk project <strong>"+QRM.expController.project.title+"</strong></span>");
    }
    
    remoteService.getSiteUsers()
        .then(function (response) {
            QRMDataService.siteUsers = response.data.data;
        });


};

function ExplorerCtrl($scope, QRMDataService, $state, remoteService) {
    
    QRM.expController = this;
    
    this.getTableHeight = function () {
        return {
            height: "calc(100vh - 380px)"
        };
    }

    QRMDataService.riskID = 0;
    var exp = this;
    if (QRMDataService.project.id > 0){
        this.project = QRMDataService.project;
    }

    this.valPre = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.valPost = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.filterMatrixFlag = false;
    this.filterMatrixHighlightFlag = false;

    this.gridOptions = {
        enableSorting: true,
        //        minRowsToShow: 10,
        //        rowHeight: 25,
        rowTemplate: '<div ng-click="grid.appScope.editRisk(row.entity.id)" style="cursor:pointer;"  ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
        columnDefs: [
            {
                //               name: 'currentTolerance',
                //                field:'currentTolerance',
                //                cellTemplate: '<i class="fa fa-circle"> {{grid.appScope.formatCodeCol(grid, row)}}</i>',
                field: 'riskProjectCode',
                enableColumnMoving: false,
                width: 80,
                headerCellClass: 'header-hidden',
                cellClass: function (grid, row, col, rowRenderIndex, colRenderIndex) {
                    switch (Number(row.entity.currentTolerance)) {
                    case 1:
                        return 'blue compact';
                    case 2:
                        return 'green compact';
                    case 3:
                        return 'yellow compact';
                    case 4:
                        return 'orange compact';
                    case 5:
                        return 'red compact';
                    }
                }

            },
            {
                name: 'title',
                width: "*",
                cellClass: 'compact',
                field: 'title'

            },
            {
                name: 'treated',
                width: 70,
                field: 'treated',
                cellTemplate: '<i style="color:green" ng-show="grid.appScope.formatTreatedCol(row, true)" class="fa fa-check"></i><i  style="color:red" ng-show="grid.appScope.formatTreatedCol(row, false)" class="fa fa-close"></i>',
                cellClass: 'cellCentered'

            },
            {
                name: 'owner',
                width: 140,
                field: 'owner',
                cellFilter: 'usernameFilter'

            },
            {
                name: 'manager',
                width: 140,
                field: 'manager',
                cellFilter: 'usernameFilter'
            },
            {
                name: 'id',
                enableColumnMoving: false,
                enableSorting: false,
                enableHiding: false,
                cellTemplate: '<i class="fa fa-edit" style="cursor:pointer;color:green;"></i>&nbsp;&nbsp;<i class="fa fa-trash" style=";color:red;cursor:pointer" ng-click="$event.stopPropagation();grid.appScope.deleteRisk(grid.getCellValue(row, col))"></i>',
                width: 60,
                headerCellClass: 'header-hidden',
                cellClass: 'cellCentered'

            }

    ]
    };
    $scope.formatTreatedCol = function (row, check) {
        if (row.entity.treated && check) {
            return true;
        }
        if (!row.entity.treated && !check) {
            return true;
        }
        return false;
    };
    this.resetFilter = function () {
        // used for flagging clearance of matrix highlights
        this.filterMatrix = false;
        return {
            owner: "",
            manager: "",
            category: "",
            expActive: true,
            expPending: true,
            expInactive: true,
            treated: true,
            untreated: true,
            tolEx: true,
            tolHigh: true,
            tolSig: true,
            tolModerate: true,
            tolLow: true,
            inactive: true,
            active: true,
            pending: true,
            filterMatrix: false,
            riskCode: "",
            inactive: true,
            active: true,
            pending: true
        }
    };
    this.filterOptions = this.resetFilter();

    $scope.$watch("exp.filterOptions", function () {
        exp.filterRisks();
    }, true);

    // General purpose functions
    this.newRisk = function () {
        postType = null;
        QRMDataService.riskID = -1;
        $state.go('index.risk');
    }
    $scope.editRisk = function (riskID) {
        exp.editRisk(riskID);
    }
    this.editRisk = function (riskID) {
        QRMDataService.riskID = riskID;
        $state.go('index.risk');
    }
    this.deleteRisk = function (riskID) {
        QRMDataService.riskID = riskID;
        alert("Delete Risk: " + riskID);
    }
    this.getAllProjectRisks = function () {
        QRM.mainController.resetMsg();
        remoteService.getAllProjectRisks(exp.project.id)
            .then(function (response) {
                if (response.data.data.length == 0) QRM.mainController.setMsg();
                exp.rawRisks = response.data.data;
                exp.gridOptions.data = response.data.data;

                var maxImpact = Number(QRMDataService.project.matrix.maxImpact);
                var maxProb = Number(QRMDataService.project.matrix.maxProb);


                for (var i = 0; i < maxImpact * maxProb; i++) {
                    exp.valPre[i] = 0;
                    exp.valPost[i] = 0;
                }


                response.data.data.forEach(function (el) {
                    var iP = Math.floor(Number(el.inherentProb));
                    var iI = Math.floor(Number(el.inherentImpact));
                    var tP = Math.floor(Number(el.treatedProb));
                    var tI = Math.floor(Number(el.treatedImpact));


                    exp.valPre[((iP - 1) * maxImpact) + iI - 1] ++;
                    exp.valPost[((tP - 1) * maxImpact) + tI - 1] ++;

                });

                exp.filterRisks();
            });

    }

    // Filtering functions
    this.filterRisks = function () {

        if (this.rawRisks == null) return;

        this.gridOptions.data = [];
        this.rawRisks.forEach(function (r) {
            // Reject the risk until it's passes
            var pass = false;

            if (exp.filterMatrixFlag) {

                var i;
                var p;

                if (exp.filterOptions.matrixTreated) {
                    i = Math.floor(Number(r.treatedImpact));
                    p = Math.floor(Number(r.treatedProb));
                } else {
                    i = Math.floor(Number(r.inherentImpact));
                    p = Math.floor(Number(r.inherentProb));
                }

                if (i == exp.filterOptions.matrixImpact && p == exp.filterOptions.matrixProb) {
                    pass = true;
                }
            } else {

                exp.filterMatrixHighlightFlag = false;

                if (exp.filterOptions.treated && r.treated) pass = true;
                if (exp.filterOptions.untreated && !r.treated) pass = true;

                if (!pass) return;
                pass = false;

                if (exp.filterOptions.tolEx && Number(r.currentTolerance) == 5) pass = true;
                if (exp.filterOptions.tolHigh && Number(r.currentTolerance) == 4) pass = true;
                if (exp.filterOptions.tolSig && Number(r.currentTolerance) == 3) pass = true;
                if (exp.filterOptions.tolModerate && Number(r.currentTolerance) == 2) pass = true;
                if (exp.filterOptions.tolLow && Number(r.currentTolerance) == 1) pass = true;

                if (!pass) return;

                pass = false;

                var own = exp.filterOptions.owner;
                var man = exp.filterOptions.manager;

                if (!(own == r.owner || own == undefined || own == null || own == "") || !(man == r.manager || man == undefined || man == null || man == "")) {
                    return;
                }

                //Filter on exposure;

                var now = moment();

                var endDiff = now.diff(moment(r.end));
                var startDiff = now.diff(moment(r.start));

                if (exp.filterOptions.expInactive && endDiff > 0) pass = true;
                if (exp.filterOptions.expPending && startDiff < 0) pass = true;
                if (exp.filterOptions.expActive && startDiff > 0 && endDiff < 0) pass = true;

            }

            if (!pass) return;
            if (pass) exp.gridOptions.data.push(r);


        });

        this.filterMatrixFlag = false;

    }
    this.matrixFilter = function (impact, prob, treated) {

        this.filterMatrixFlag = true;
        this.filterMatrixHighlightFlag = true;

        this.filterOptions = this.resetFilter()
        this.filterOptions.matrixProb = prob;
        this.filterOptions.matrixImpact = impact;
        this.filterOptions.matrixTreated = treated;

        //The watch on the filterOptions will kick off the filtering

    }
    this.clearFilters = function () {
        this.filterOptions = this.resetFilter();
    }

    // Handle formatting of select project pick box
    this.rowStyle = function (e) {
        return {
            "margin-left": e.$$treeLevel * 20 + "px"
        }
    }

    this.projectSelect = function (item, projectID) {
            QRMDataService.selectProject(projectID);
            this.project = QRMDataService.project;
            this.getAllProjectRisks(this.project.id);
            this.clearFilters();
        }
        // Control the appearance of the matrix cells
    this.getCellValue = function (prob, impact, treated) {
        if (treated) {
            var val = this.valPost[(prob - 1) * QRMDataService.project.matrix.maxImpact + impact - 1];
            return (val == 0) ? "" : val
        } else {
            var val = this.valPre[(prob - 1) * QRMDataService.project.matrix.maxImpact + impact - 1];
            return (val == 0) ? "" : val
        }

    }
    this.cellHighlight = function (prob, impact, treated) {
        var r = (this.filterMatrixHighlightFlag && this.filterOptions.matrixProb == prob &&
            this.filterOptions.matrixImpact == impact && this.filterOptions.matrixTreated == treated);


        return r;
    }
    this.cellClass = function (prob, impact, tol) {

        if (impact > QRMDataService.project.matrix.maxImpact || prob > QRMDataService.project.matrix.maxProb) {
            return true;
        }
        var index = (prob - 1) * QRMDataService.project.matrix.maxImpact + impact - 1;
        return (Number(QRMDataService.project.matrix.tolString.substring(index, index + 1)) == tol)
    }

    this.cellStyle = function (prob, impact, tol) {

        var vh = 100 / (QRMDataService.project.matrix.maxProb)
        var vw = 100 / QRMDataService.project.matrix.maxImpact

        return {
            "width": vw + "%",
            "height": vh + "%",
            "text-align": "center"
        }
    }

    this.rowClass = function (prob) {
        if (prob > QRMDataService.project.matrix.maxProb) {
            return true;
        } else {
            return false;
        }
    }


    this.init = function () {
        remoteService.getProjects()
            .then(function (response) {
                QRMDataService.handleGetProjects(response);
                exp.projectsLinear = QRMDataService.projectsLinear;
                exp.sortedParents = QRMDataService.sortedParents;
                exp.projMap = QRMDataService.projMap;

                // Go to the selected project
                if (postType == "riskproject") {
                    exp.projectSelect(null, postID);
                    postType = null;
                } else {
                    if (QRMDataService.project.id > 0) exp.getAllProjectRisks(QRMDataService.project.id);
                }
            });
    }

    // Initial filling of the grid
    this.init();


}

function RiskCtrl($scope, $modal, QRMDataService, $state, remoteService, ngNotify, ngDialog) {

    var vm = this;
    this.riskID = QRMDataService.riskID;
    this.stakeholders = [];
    this.additionalHolders = [];
    this.project = QRMDataService.project;
    this.categories = QRMDataService.catData;
    this.objectives = QRMDataService.projectObjectives;
    $scope.data = {
        comment: ""
    };

    $scope.dropzoneConfig = {
        options: { // passed into the Dropzone constructor
            url: ajaxurl + "?action=uploadFile",
            previewTemplate: document.querySelector('#preview-template').innerHTML,
            parallelUploads: 1,
            thumbnailHeight: 120,
            thumbnailWidth: 120,
            maxFilesize: 3,
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
                    vm.riskAttachmentReady(this, file);
                });
                this.on('complete', function (file) {
                    file.previewElement.classList.add('dz-complete');
                    vm.cancelAttachment()
                    ngNotify.set("Attachment added to risk", "success");
                    remoteService.getRiskAttachments(vm.riskID)
                        .then(function (response) {
                            vm.risk.attachments = response.data;
                        });
                });
            },
            sending: function (file, xhr, formData) {
                formData.append("riskID", QRMDataService.riskID);
                formData.append("description", vm.uploadAttachmentDescription);
            }
        },
    };

    this.riskAttachmentReady = function (dropzone, file) {
        vm.dropzone = dropzone;
        vm.dzfile = file;
        vm.disableAttachmentButon = false;
        $scope.$apply();
    }

    this.uploadAttachmentDescription = "";
    this.disableAttachmentButon = true;
    this.dropzone = "";
    this.uploadAttachment = function () {
        vm.dropzone.processFile(vm.dzfile);
    }
    this.cancelAttachment = function () {
        vm.dropzone.removeAllFiles(true);
        vm.uploadAttachmentDescription = null;
        vm.disableAttachmentButon = true;
        vm.dropzone = null;
        vm.dzfile = null;
        $scope.$apply();
    }

    this.openDescriptionEditor = function () {
        var oTitle = vm.risk.title;
        var oDescription = vm.risk.description;
        ngDialog.openConfirm({
            template: "editTitleModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            vm.risk.title = oTitle;
            vm.risk.description = oDescription;
        });
    }
    this.openConsequenceEditor = function () {
        var oConsq = vm.risk.consequence;
        ngDialog.openConfirm({
            template: "editConsqModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            vm.risk.consequence = oConsq;
        });
    }
    this.openCauseEditor = function () {
        var oCause = vm.risk.cause;
        ngDialog.openConfirm({
            template: "editCauseModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            vm.risk.cause = oCause;
        });
    }
    this.openMitEditor = function (summary) {
        var oPlan = vm.risk.mitigation.mitPlanSummary;
        var oUpdate = vm.risk.mitigation.mitPlanSummaryUpdate
        ngDialog.openConfirm({
            template: (summary) ? "editMitigationModalDialogId" : "editMitigationModalDialogId2",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            vm.risk.mitigation.mitPlanSummary = oPlan;
            vm.risk.mitigation.mitPlanSummaryUpdate = oUpdate;
        });
    }
    this.openRespEditor = function (summary) {
        var oPlan = vm.risk.response.respPlanSummary;
        var oUpdate = vm.risk.response.respPlanSummaryUpdate
        ngDialog.openConfirm({
            template: (summary) ? "editResponseModalDialogId" : "editResponseModalDialogId2",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            vm.risk.response.respPlanSummary = oPlan;
            vm.risk.response.respPlanSummaryUpdate = oUpdate;
        });
    }

    this.impactChange = function () {
        vm.updateRisk();
    }
    this.probChange = function () {

        switch (Number(vm.risk.likeType)) {
        case 1:
            vm.risk.likeT = 365;
            break;
        case 2:
            vm.risk.likeT = 30;
            break;
        case 3:
            //do nothing, will already be set by model
            break;
        default:
            vm.risk.likeT = 0;
        }
        switch (Number(vm.risk.likePostType)) {
        case 1:
            vm.risk.likePostT = 365;
            break;
        case 2:
            vm.risk.likePostT = 30;
            break;
        case 3:
            //do nothing, will already be set by model
            break;
        default:
            vm.risk.likePostT = 0;
        }


        // This caculates the 1-8 prob based on the calculated prob and the matrix config
        vm.risk.inherentProb = probToMatrix(calcProb(vm.risk, true), vm.project.matrix);
        vm.risk.treatedProb = probToMatrix(calcProb(vm.risk, false), vm.project.matrix);

        // This will update the matrix
        vm.updateRisk();
    }

    this.cancelRisk = function () {
        $state.go('index.explorer');
    }
    this.getRisk = function () {

        if (isNaN(vm.riskID) || vm.riskID == 0) {
            return;
        }
        remoteService.getRisk(vm.riskID)
            .then(function (response) {
                vm.risk = response.data;
                $scope.risk = vm.risk;
                vm.updateRisk();
                angular.element("#riskEditorMatrixID").controller().setRiskMatrixID("riskEditorMatrixID");
                angular.element("#riskEditorMatrixID").controller().setRiskMatrix();
            });
    };
    this.saveRisk = function () {
        // Ensure all the changes have been made
        vm.updateRisk();
        //Zero out the comments as these are managed separately
        vm.risk.comments = [];
        vm.risk.projectID = QRMDataService.project.id;
        vm.risk.attachments = [];
        remoteService.saveRisk(vm.risk)
            .then(function (response) {
                vm.risk = response.data;
                // Update the risk with changes that may have been made by the host.
                QRMDataService.riskID = vm.risk.riskID;
                vm.updateRisk();
                ngNotify.set("Risk Saved", "success");
            });
    };
    this.updateRisk = function () {

        // secondary risk category
        try {
            vm.secCatArray = jQuery.grep(vm.project.categories, function (e) {
                return e.name == vm.risk.primcat.name
            })[0].sec;
        } catch (e) {
            console.log(e.message);
        }

        //Update the Matrix
        try {
            vm.setRiskMatrix(vm.matrixDIVID);
        } catch (e) {
            console.log(e.message);
        }

        // Create a list of stakeholders
        try {
            vm.stakeholders = [];
            vm.stakeholders.push({
                name: vm.risk.owner,
                role: "Risk Owner"
            });
            vm.stakeholders.push({
                name: vm.risk.manager,
                role: "Risk Manager"
            });
            vm.risk.mitigation.mitPlan.forEach(function (e) {
                vm.stakeholders.push({
                    name: e.person,
                    role: "Mitigation Owner"
                })
            });
            vm.risk.response.respPlan.forEach(function (e) {
                vm.stakeholders.push({
                    name: e.person,
                    role: "Response Owner"
                })
            });
            vm.stakeholders = vm.stakeholders.concat(vm.additionalHolders);
        } catch (e) {
            console.log(e.message);
        }

        // Remove any Duplicate 
        var arr = {};
        for (var i = 0; i < vm.stakeholders.length; i++)
            arr[vm.stakeholders[i]['name'] + vm.stakeholders[i]['role']] = vm.stakeholders[i];

        var temp = new Array();
        for (var key in arr)
            temp.push(arr[key]);

        vm.stakeholders = temp;

        //Sort out the probs and impact

        try {

            var index = (Math.floor(vm.risk.treatedProb - 1)) * vm.project.matrix.maxImpact + Math.floor(vm.risk.treatedImpact - 1);
            index = Math.min(index, vm.project.matrix.tolString.length - 1);

            vm.risk.treatedTolerance = vm.project.matrix.tolString.substring(index, index + 1);


            index = (Math.floor(vm.risk.inherentProb - 1)) * vm.project.matrix.maxImpact + Math.floor(vm.risk.inherentImpact - 1);
            index = Math.min(index, vm.project.matrix.tolString.length - 1);

            vm.risk.inherentTolerance = vm.project.matrix.tolString.substring(index, index + 1);

            if (vm.risk.treated) {
                vm.risk.currentProb = vm.risk.treatedProb;
                vm.risk.currentImpact = vm.risk.treatedImpact;
                vm.risk.currentTolerance = vm.risk.treatedTolerance;


            } else {
                vm.risk.currentProb = vm.risk.inherentProb;
                vm.risk.currentImpact = vm.risk.inherentImpact;
                vm.risk.currentTolerance = vm.risk.inherentTolerance;
            }

            vm.treatedAbsProb = probFromMatrix(vm.risk.treatedProb, vm.project.matrix);
            vm.inherentAbsProb = probFromMatrix(vm.risk.inherentProb, vm.project.matrix);
        } catch (e) {
            alert("Error" + e.message);
        }

        //Set the date controll
        debugger;
        // Need to be fixed ????
jQuery('#exposure').daterangepicker({
                        format: 'MMMM D, YYYY',
                        separator: " - ",
                        showDropdowns: true,
                        drops: "down"
                    },
                    function (start, end, label) {
                        try {
                            // Update the Angular controller
                            angular.element("#exposure").controller().updateDates(start, end);
                        } catch (e) {
                            console.log(e.message);
                        }
                    });
        var s = moment(vm.risk.start);
        var e = moment(vm.risk.end);
        jQuery('#exposure').data('daterangepicker').setStartDate(s);
        jQuery('#exposure').data('daterangepicker').setEndDate(e);


    }

    //Called by listener set by jQuery on the date-range control
    this.updateDates = function (start, end) {
        vm.risk.start = start;
        vm.risk.end = end;

        vm.updateRisk();
    }

    // The probability matrix
    this.setRiskMatrixID = function (matrixDIVID) {
        QRMDataService.matrixDIVID = matrixDIVID;
    }
    this.setRiskMatrix = function () {
        // Calls function in qrm-common.js
        setRiskEditorMatrix(vm.risk, vm.project.matrix, QRMDataService.matrixDIVID, QRMDataService.matrixDisplayConfig, vm.dragStart, vm.drag, vm.dragEnd);
    }
    this.dragEnd = function (d) {

        vm.risk.useCalProb = false;
        vm.risk.liketype = 4;
        vm.risk.likepostType = 4;

        if (d.treated) {
            vm.risk.treatedProb = Number(d.prob);
            vm.risk.treatedImpact = Number(d.impact);
        } else {
            vm.risk.inherentProb = Number(d.prob);
            vm.risk.inherentImpact = Number(d.impact);
        }

        vm.updateRisk();

        $scope.$apply();

    }
    this.dragStart = function (d) {
        vm.risk.useCalProb = false;
        vm.risk.useCalProb = false;
        vm.risk.liketype = 4;
        vm.risk.likepostType = 4;

    }
    this.drag = function () {

    }


    this.addMit = function () {
        vm.risk.mitigation.mitPlan.push({
            description: "No Description of the Action Entered ",
            person: -1,
            cost: 0,
            complete: 0,
            due: new Date()
        });
    }
    this.addResp = function () {
        vm.risk.response.respPlan.push({
            description: "No Description of the Action Entered ",
            person: -1,
            cost: "No Cost Allocated"
        });
    }
    this.addControl = function () {
        var control = {
            description: "No Description of the Control Entered ",
            effectiveness: "No Assigned Effectiveness",
            contribution: "No Contribution Entered"
        }

        if (vm.risk.controls) {
            vm.risk.controls.push(control);

        } else {
            vm.risk.controls = [control]
        }

    }
    this.addComment = function (s) {
        $scope.data.comment = "";
        ngDialog.openConfirm({
            template: "addCommentModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            debugger;
            remoteService.addComment($scope.data.comment, QRMDataService.riskID)
                .then(function (response) {
                    ngNotify.set("Comment added to risk", "success");
                    vm.risk.comments = response.data.comments;
                });
        }, function (reason) {
            // Restore the old values

        });
    }

    this.editMitStep = function (s) {
        var key = s.$$hashKey;
        s.due = new Date(s.due);
        var oldStepObject = jQuery.extend(true, {}, s);
        $scope.step = s;
        ngDialog.openConfirm({
            template: "editMitStepModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            // Restore the old values
            var stepObj = jQuery.grep(vm.risk.mitigation.mitPlan, function (e) {
                return e.$$hashKey == key;
            })[0];
            stepObj.complete = oldStepObject.complete;
            stepObj.cost = oldStepObject.cost;
            stepObj.description = oldStepObject.description;
            stepObj.due = oldStepObject.due;
            stepObj.person = oldStepObject.person;
        });
    }
    this.editControl = function (s) {

        var key = s.$$hashKey;
        var oldStepObject = jQuery.extend(true, {}, s);
        $scope.control = s;
        $scope.effectArray = ["Ad Hoc", "Repeatable", "Defined", "Managed", "Optimising"];
        $scope.contribArray = ["Minimal", "Minor", "Significant", "Major"];

        ngDialog.openConfirm({
            template: "editControlModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            // Restore the old values
            var stepObj = jQuery.grep(vm.risk.controls, function (e) {
                return e.$$hashKey == key;
            })[0];
            stepObj.effectiveness = oldStepObject.effectiveness;
            stepObj.description = oldStepObject.description;
            stepObj.contribution = oldStepObject.contribution;
        });
    }
    this.editRespStep = function (s) {
        var key = s.$$hashKey;
        s.due = new Date(s.due);
        var oldStepObject = jQuery.extend(true, {}, s);
        $scope.step = s;
        ngDialog.openConfirm({
            template: "editRespStepModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            // Restore the old values
            var stepObj = jQuery.grep(vm.risk.response.respPlan, function (e) {
                return e.$$hashKey == key;
            })[0];
            stepObj.cost = oldStepObject.cost;
            stepObj.description = oldStepObject.description;
            stepObj.person = oldStepObject.person;
        });
    }

    this.deleteMitStep = function (s) {
        ngDialog.openConfirm({
            template: "deleteMitStepModalDialogId",
            className: 'ngdialog-theme-default'
        }).then(function (value) {
            for (var i = 0; i < vm.risk.mitigation.mitPlan.length; i++) {
                if (vm.risk.mitigation.mitPlan[i].$$hashKey == s.$$hashKey) {
                    vm.risk.mitigation.mitPlan.splice(i, 1);
                    break;
                }
            }
        });
    }
    this.deleteRespStep = function (s) {

        ngDialog.openConfirm({
            template: "deleteRespStepModalDialogId",
            className: 'ngdialog-theme-default'
        }).then(function (value) {
            for (var i = 0; i < vm.risk.response.respPlan.length; i++) {
                if (vm.risk.response.respPlan[i].$$hashKey == s.$$hashKey) {
                    vm.risk.response.respPlan.splice(i, 1);
                    break;
                }
            }
        });
    }
    this.deleteControl = function (s) {

        ngDialog.openConfirm({
            template: "deleteControlModalDialogId",
            className: 'ngdialog-theme-default'
        }).then(function (value) {
            for (var i = 0; i < vm.risk.controls.length; i++) {
                if (vm.risk.controls[i].$$hashKey == s.$$hashKey) {
                    vm.risk.controls.splice(i, 1);
                    break;
                }
            }
        });
    }

    // Handle formatting of objectives
    this.rowStyle = function (e) {
        return {
            "margin-left": e.$$treeLevel * 15 + "px"
        }
    }

    this.init = function () {

        if (postType == "risk") {

            // Jumped here directly from risk list in WP
            remoteService.getProjects()
                .then(function (response) {

                    QRMDataService.handleGetProjects(response);
                    QRMDataService.selectProject(projectID);
                    QRMDataService.riskID = postID;

                    vm.project = QRMDataService.project;
                    vm.categories = QRMDataService.catData;
                    vm.objectives = QRMDataService.projectObjectives;
                    $scope.siteUsers = QRMDataService.siteUsers;

                    vm.riskID = postID;
                    vm.getRisk();
                });
            // Zero it out so no reoccurance
            postType = null;
        } else {
            if (QRMDataService.riskID == -1) {
                vm.risk = QRMDataService.getTemplateRisk();
                vm.risk.inherentProb = vm.project.matrix.maxProb + 0.5;
                vm.risk.inherentImpact = vm.project.matrix.maxImpact + 0.5;
                $scope.risk = vm.risk;
                vm.updateRisk();
                vm.setRiskMatrixID("riskEditorMatrixID");
                vm.setRiskMatrix();
            } else {
                // Normal transfer from Explorer
                this.getRisk();
            }
        }
    }

    this.init();

}

function CalenderController($scope, QRMDataService, $state, remoteService) {


    qrm.calenderController = this;
    var cal = this;
    this.project = QRMDataService.project;

    this.showDesc = false;
    this.riskProjectCode = "";
    this.title = "";
    this.description = "";
    this.status = {
        val: 0
    };

    this.owner = "";
    this.manager = "";

    var tasks = new Array();
    var taskNames = new Array();

    this.ownerSelect = function () {
        cal.manager = "";
        cal.stateSelectorChanged();

    }
    this.managerSelect = function () {
        cal.owner = "";
        cal.stateSelectorChanged();
    }
    this.clearFilters = function () {
        cal.manager = "";
        cal.owner = "";
        cal.status.val = 0;
        cal.stateSelectorChanged();
    }
    this.stateSelectorChanged = function () {

        var datePass = new Array();
        var now = new Date();
        cal.risks.forEach(function (risk) {
            switch (Number(cal.status.val)) {
            case 0:
                datePass.push(risk);
                break;
            case 1:
                if (moment(risk.end) < now) {
                    datePass.push(risk);
                }
                break;
            case 2:
                if (moment(risk.start) > now) {
                    datePass.push(risk);
                }
                break;
            case 3:
                if (moment(risk.start) < now && moment(risk.end) > now) {
                    datePass.push(risk);
                }
                break;

            }
        });

        var managerPass = new Array();
        datePass.forEach(function (risk) {
            if (risk.manager.name == cal.manager.name || cal.manager == "") {
                managerPass.push(risk);
            }
        });

        var ownerPass = new Array();
        managerPass.forEach(function (risk) {
            if (risk.owner.name == cal.owner.name || cal.owner == "") {
                ownerPass.push(risk);
            }
        });
        cal.layoutCalender(ownerPass);




    }
    this.editRisk = function (id) {
        QRMDataService.riskID = id;
        $state.go('index.risk');
    }
    this.getRisks = function () {
        remoteService.getAllProjectRisks(QRMDataService.project.id)
            .then(function (response) {
                cal.risks = response.data.data;
                cal.layoutCalender(cal.risks);
            });

    }

    this.layoutCalender = function (risks) {
        tasks = new Array();
        taskNames = new Array();
        var index = 0;
        risks.forEach(function (risk) {
            tasks.push({
                "startDate": moment(risk.start),
                "endDate": moment(risk.end),
                "taskName": "RISKID" + index,
                "status": "RUNNING",
                "riskID": risk.id,
                "title": risk.title
            });
            index++;
        });

        var now = new Date();
        tasks.sort(function (a, b) {
            return a.startDate - b.startDate;
        });
        tasks.forEach(function (task) {
            if (task.startDate > now) {
                task.className = 'future';
            } else if (task.endDate < now) {
                task.className = 'past';
            } else {
                task.className = 'now';
            }

            taskNames.push(task.taskName);
        });
        d3.select("#svgcalID").selectAll("svg").remove();

        var gantt = d3.gantt(cal).taskTypes(taskNames).tickFormat("%b %Y");
        gantt(tasks, "#svgcalID", $('#svgcalID').width(), $('#svgcalID').height());

    }

    this.getRisks();

    this.resize = function () {
        d3.select("#svgcalID").selectAll("svg").remove();
        var gantt = d3.gantt(cal).taskTypes(taskNames).tickFormat("%b %Y");
        gantt(tasks, "#svgcalID", $('#svgcalID').width(), $('#svgcalID').height());
    }

    this.toolTip = function (d) {
        if (!d) {
            cal.showDesc = false;
            cal.startDate = null;
            cal.endDate = null;
            cal.taskName = null;
            cal.title = null;

        } else {
            cal.showDesc = true;
            cal.startDate = moment(d.startDate).format("MMM DD, gggg");
            cal.endDate = moment(d.endDate).format("MMM DD, gggg");
            cal.title = d.title;
            cal.taskName = d.taskName;
        }

        $scope.$apply();

    }


}

function RankController($scope, QRMDataService, $state, remoteService) {

    qrm.rankController = this;
    var rank = this;
    var myLayout;
    this.project = QRMDataService.project;
    this.editRisk = function (id) {
        QRMDataService.riskID = id;
        $state.go('index.risk');
    }

    this.saveChanges = function () {
        myLayout.normaliseRanks();
        var rankOrder = myLayout.items;
        alert(JSON.stringify(rankOrder));
    }

    this.cancelChanges = function () {
        this.loadGrid();
    }


    this.loadGrid = function () {

        remoteService.getAllProjectRisks(QRMDataService.project.id)
            .then(function (response) {
                var risks = response.data.data;
                rank.dirty = false;
                rank.risks = risks;
                rank.layout = new SorterLayout(rank);

                var html = "<div style='valign:top'><br><hr><br/>Rearrange the rank order of the risks by dragging and droping the risks. <br/><br/>The risks are initially arranged in rank order from top to bottom, left to right<br/><br/></strong><hr></div>";
                //    $('#qrm-rankDetail').html(html);

                myLayout = rank.layout;
                myLayout.setHeight($('#subRankSVGDiv').height());
                myLayout.setWidth($('#subRankSVGDiv').width());
                myLayout.setItemHeight(35);
                myLayout.setItemWidth($('#subRankSVGDiv').width() / 2);
                myLayout.scale(1, 1);
                myLayout.setItems(rank.risks);
                myLayout.setSVGDiv("subRankSVGDiv");
                myLayout.setDirtyListener(function () {
                    rank.dirty = true;
                });
                myLayout.layoutTable();
            });

    }

    this.loadGrid();

    this.resize = function () {
        myLayout.setHeight($('#subRankSVGDiv').height());
        myLayout.setWidth($('#subRankSVGDiv').width());
        myLayout.setItemHeight(35);
        myLayout.setItemWidth($('#subRankSVGDiv').width() / 2);
        myLayout.scale(1, 1);
        myLayout.setSVGDiv("subRankSVGDiv");
        myLayout.layoutTable();
    }
}

function RelMatrixController($scope, QRMDataService, $state, remoteService, ngNotify) {

    var relMatrixCtrl = this;

    //In the global space.

    qrm.matrixController = this;

    this.project = QRMDataService.project;
    this.status = {
        val: 0
    };

    this.showDesc = false;
    this.riskProjectCode = "";
    this.title = "";
    this.description = "";

    this.owner = "";
    this.manager = "";
    this.selectedRisk = "";

    this.currentItems = new Array();
    this.matrixDirty = false;
    this.editorChanges = false;
    this.transMatrix = [1, 0, 0, 1, 45, 45];

    this.stateSelectorChanged = function () {

        //Clear present position so the layout can take care of non overlapping
        relMatrixCtrl.risks.forEach(function (risk) {
            risk.x = 0;
            risk.y = 0;
        });

        var maxProb = QRMDataService.project.matrix.maxProb;

        d3.selectAll("g.state")
            .transition()
            .duration(2000)
            .attr("transform", function (d, i) {
                var prob = null;
                var impact = null;

                switch (Number(relMatrixCtrl.status.val)) {
                case 0:
                    if (d.treated) {
                        prob = (d.treatedClean) ? d.treatedProb : d.newTreatedProb;
                        impact = (d.treatedClean) ? d.treatedImpact : d.newTreatedImpact;
                    } else {
                        prob = (d.untreatedClean) ? d.inherentProb : d.newInherentProb;
                        impact = (d.untreatedClean) ? d.inherentImpact : d.newInherentImpact;
                    }
                    break;
                case 1:
                    if (d.untreatedClean) {
                        prob = d.inherentProb;
                        impact = d.inherentImpact;
                    } else {
                        prob = d.newInherentProb;
                        impact = d.newInherentImpact;
                    }
                    break;
                case 2:
                    if (d.treatedClean) {
                        prob = d.treatedProb;
                        impact = d.treatedImpact;
                    } else {
                        prob = d.newTreatedProb;
                        impact = d.newTreatedImpact;
                    }
                    break;

                }

                var x = (impact - 1) * QRMDataService.relMatrixGridSizeX;
                var y = (maxProb + 1 - prob) * QRMDataService.relMatrixGridSizeY;

                d.x = x;
                d.y = y;
                //Prevent initial layout from overlapping items.
                relMatrixCtrl.risks.forEach(function (risk) {
                    if (Math.abs(risk.x - d.x) < 10 && Math.abs(risk.y - d.y) < 10 && risk.id != d.id) {
                        d.x += 5;
                        d.y += 5;
                    }
                });

                return "translate(" + [d.x, d.y] + ")";
            });

        var state = "Current State";

        switch (Number(relMatrixCtrl.status.val)) {
        case 0:
            state = "Current State";
            break;
        case 1:
            state = "Un Treated State";
            break;
        case 2:
            state = "Treated State";
            break;

        }

        d3.select("#relMatrixSubHeading").text(state);

    }

    this.cancelChanges = function () {
        this.matrixDirty = false;
        this.risks.forEach(function (risk) {
            risk.untreatedClean = true;
            risk.treatedClean = true;
            risk.dirty = false;
        });
        // Move the risks back to where they should be.
        this.stateSelectorChanged();
    }
    this.saveChangesWrapper = function () {
        this.saveChanges(false);
    }
    this.saveChanges = function (switchTab, tabPanel, newCard, newProjectID) {

        var relMatChanges = new Array();

        this.risks.forEach(function (item) {
            if (item.dirty) {
                relMatChanges.push({
                    riskID: item.id,
                    newTreatedImpact: (item.treatedClean) ? item.treatedImpact : item.newTreatedImpact,
                    newTreatedProb: (item.treatedClean) ? item.treatedProb : item.newTreatedProb,
                    newInherentImpact: (item.untreatedClean) ? item.inherentImpact : item.newInherentImpact,
                    newInherentProb: (item.untreatedClean) ? item.inherentProb : item.newInherentProb
                });
            }
        });

        if (relMatChanges.length < 1) {
            ngNotify.set('There are no changes to save', "grimace");
            return;
        }

        remoteService.updateRisksRelMatrix(relMatChanges)
            .then(function (response) {
                relMatrixCtrl.risks.forEach(function (item) {
                    item.treatedImpact = (item.treatedClean) ? item.treatedImpact : item.newTreatedImpact;
                    item.treatedProb = (item.treatedClean) ? item.treatedProb : item.newTreatedProb;
                    item.inherentImpact = (item.untreatedClean) ? item.inherentImpact : item.newInherentImpact;
                    item.inherentProb = (item.untreatedClean) ? item.inherentProb : item.newInherentProb;
                    item.treatedClean = true;
                    item.untreatedClean = true;
                    item.dirty = false;
                });

                ngNotify.set('Changes to Probability/Impact have been saved', "success");
            });

    }
    this.getState = function () {
        return Number(this.status.val);
    }
    this.ownerSelect = function () {
        relMatrixCtrl.manager = "";
        relMatrixCtrl.selectedRisk = "";
        var filteredRisks = new Array();

        this.risks.forEach(function (risk) {
            if (risk.owner == relMatrixCtrl.owner) {
                risk.x = 0;
                risk.y = 0;
                filteredRisks.push(risk);
            }
        });

        this.svgMatrix(filteredRisks);
    }
    this.managerSelect = function () {
        relMatrixCtrl.owner = "";
        relMatrixCtrl.selectedRisk = "";

        var filteredRisks = new Array();

        this.risks.forEach(function (risk) {
            if (risk.manager == relMatrixCtrl.manager) {
                risk.x = 0;
                risk.y = 0;
                filteredRisks.push(risk);
            }
        });

        this.svgMatrix(filteredRisks);

    }
    this.riskSelect = function () {

        relMatrixCtrl.owner = "";
        relMatrixCtrl.manager = "";
        //Clear present position so the layout can take care of non overlapping
        relMatrixCtrl.risks.forEach(function (risk) {
            risk.x = 0;
            risk.y = 0;
        });
        this.svgMatrix(relMatrixCtrl.risks);

        var riskID = "#riskID" + relMatrixCtrl.selectedRisk.id;
        var g = d3.select(riskID);
        g.node().parentNode.appendChild(g.node());


        g.select("circle.inner").transition().duration(500)
            .styleTween("fill", function () {
                return d3.interpolate("white", "black");
            })
            .attr("r", "40")
            .transition().duration(500)
            .styleTween("fill", function () {
                return d3.interpolate("black", "white");
            })
            .attr("r", "25")
            .transition().duration(500)
            .styleTween("fill", function () {
                return d3.interpolate("white", "black");
            })
            .attr("r", "40")
            .transition().duration(500)
            .styleTween("fill", function () {
                return d3.interpolate("black", "white");
            })
            .attr("r", "25")
            .transition().duration(500)
            .styleTween("fill", function () {
                return d3.interpolate("white", "black");
            })
            .attr("r", "40")
            .transition().duration(500)
            .styleTween("fill", function () {
                return d3.interpolate("black", "white");
            })
            .attr("r", "25");


        relMatrixCtrl.showDesc = false;
        relMatrixCtrl.selectedRisk = "";

    }
    this.resizePanel = function () {
        this.svgMatrix(this.risks);
    }
    this.clearFilters = function () {
        relMatrixCtrl.manager = "";
        relMatrixCtrl.owner = "";
        relMatrixCtrl.selectedRisk = "";
        //Clear present position so the layout can take care of non overlapping
        relMatrixCtrl.risks.forEach(function (risk) {
            risk.x = 0;
            risk.y = 0;
        });
        this.resetPZ();
        this.svgMatrix(this.risks);
    }
    this.pan = function (dx, dy) {

        this.transMatrix[4] += dx;
        this.transMatrix[5] += dy;

        var newMatrix = "matrix(" + this.transMatrix.join(' ') + ")";

        d3.select("g.relMatrixGroupHolder").attr("transform", newMatrix);
    }
    this.zoom = function (scale) {

        for (var i = 0; i < this.transMatrix.length; i++) {
            this.transMatrix[i] *= scale;
        }

        var newMatrix = "matrix(" + this.transMatrix.join(' ') + ")";
        d3.select("g.relMatrixGroupHolder").attr("transform", newMatrix);
    }
    this.resetPZ = function () {
        this.transMatrix[0] = 1;
        this.transMatrix[1] = 0;
        this.transMatrix[2] = 0;
        this.transMatrix[3] = 1;
        this.transMatrix[4] = 45;
        this.transMatrix[5] = 45;

        var newMatrix = "matrix(" + this.transMatrix.join(' ') + ")";
        d3.select("g.relMatrixGroupHolder").attr("transform", newMatrix);

    }

    this.svgMatrix = function (risks) {

        var tolString = QRMDataService.project.matrix.tolString;
        var maxImpact = QRMDataService.project.matrix.maxImpact;
        var maxProb = QRMDataService.project.matrix.maxProb;
        var divWidth = $('#relMatrixSVGDiv').width();
        var divHeight = $('#relMatrixSVGDiv').height();
        var margin = {
            top: 45,
            right: 45,
            bottom: 45,
            left: 45
        };
        var width = divWidth - margin.left - margin.right;
        var height = divHeight - margin.top - margin.bottom;

        var data = new Array();

        for (var prob = maxProb; prob > 0; prob--) {
            for (var impact = 1; impact <= maxImpact; impact++) {
                var index = (prob - 1) * maxImpact + impact - 1;
                var tol = tolString.substring(index, index + 1);
                data.push({
                    "impact": impact,
                    "prob": prob,
                    "tol": tol
                });
            }
        }

        var gridSizeX = Math.floor(width / maxImpact);
        var gridSizeY = Math.floor(height / maxProb);

        QRMDataService.relMatrixGridSizeX = gridSizeX;
        QRMDataService.relMatrixGridSizeY = gridSizeY;

        //Create the matrix

        d3.select("#relMatrixSVGDiv svg").remove();

        var topSVG = d3.select("#relMatrixSVGDiv").append("svg")
            .attr("class", "relMatrixGroupHolderTop")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom);

        //Need to embed the style into the SVG element so it can be interpreted by the PNGTranscoder on the server
        topSVG.append("defs")
            .append("style")
            .attr("type", "text/css")
            .text(
                "rect.tolNoHover5 {fill: #ff0000;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tolNoHover4 {fill: #ffa500;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tolNoHover3 {fill: #ffff00;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tolNoHover2 {fill: #00ff00;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tolNoHover1 {fill: #00ffff; stroke: #E6E6E6; stroke-width: 2px; }" +
                "g.state circle {stroke  : gray; cursor  : pointer;}" +
                "g.state circle.inner { fill : white;}" +
                "g.state circle.outer { display : none; stroke-dasharray: 4px;  stroke-opacity  : 0.5;}" +
                "g.state text.untreated { fill:red; font: 12px sans-serif; font-weight : bold; pointer-events : none; }" +
                "g.state text.treated { fill:blue; font: 12px sans-serif; font-weight : bold; pointer-events : none; }");

        var svg = topSVG
            .append("g")
            .attr("class", "relMatrixGroupHolder")
            .attr("transform", "translate(" + margin.left + "," + margin.top + ") ");

        var heatMap = svg.selectAll()
            .data(data)
            .enter().append("g")
            .attr("class", "tolCellNoHover");

        heatMap.append("rect")
            .attr("x", function (d) {
                return (d.impact - 1) * gridSizeX;
            })
            .attr("y", function (d) {
                return (maxProb - d.prob) * gridSizeY;
            })
            .attr("rx", 2)
            .attr("ry", 2)
            .attr("class", function (d) {
                return "tolNoHover" + d.tol;
            })
            .attr("width", gridSizeX)
            .attr("height", gridSizeY);


        svg.append("text")
            .attr("text-anchor", "middle")
            .style("font-size", "20px")
            .style("font-weight", "normal")
            .attr("transform", "translate(" + [width / 2, height + 20] + ")")
            .text("Impact");



        switch (Number(relMatrixCtrl.status.val)) {
        case 0:
            state = "Current State";
            break;
        case 1:
            state = "Un Treated State";
            break;
        case 2:
            state = "Treated State";
            break;
        }

        topSVG.append("text")
            .attr("text-anchor", "middle")
            .style("font-size", "20px")
            .style("font-weight", "normal")
            .attr("transform", "translate(" + [width / 2, 20] + ")")
            .text(QRMDataService.project.title);

        topSVG.append("text")
            .attr("text-anchor", "middle")
            .attr("id", "relMatrixSubHeading")
            .style("font-size", "15px")
            .style("font-weight", "normal")
            .attr("transform", "translate(" + [width / 2, 38] + ")")
            .text(state);

        svg.append("text")
            .attr("text-anchor", "middle")
            .style("font-size", "20px")
            .style("font-weight", "normal")
            .attr("transform", "translate(" + [-10, height / 2] + ") rotate(-90)")
            .text("Probability");

        topSVG.append("g")
            .html(
                '<circle cx="50" cy="50" r="42" fill="white" opacity="0.75" />' +
                '<path class="compass-button" onclick="qrm.matrixController.pan( 0, 50)" d="M50 10 l12   20 a40, 70 0 0,0 -24,  0z" />' +
                '<path class="compass-button" onclick="qrm.matrixController.pan( 50, 0)" d="M10 50 l20  -12 a70, 40 0 0,0   0, 24z" />' +
                '<path class="compass-button" onclick="qrm.matrixController.pan( 0,-50)" d="M50 90 l12  -20 a40, 70 0 0,1 -24,  0z" />' +
                '<path class="compass-button" onclick="qrm.matrixController.pan(-50, 0)" d="M90 50 l-20 -12 a70, 40 0 0,1   0, 24z" />' +
                '<circle class="compass" cx="50" cy="50" r="20" onclick="qrm.matrixController.resetPZ()" />' +
                '<circle class="compass-button" cx="50" cy="41" r="8" onclick="qrm.matrixController.zoom(0.8)" />' +
                '<circle class="compass-button" cx="50" cy="59" r="8" onclick="qrm.matrixController.zoom(1.25)" />' +
                '<rect class="plus-minus" x="46" y="39.5" width="8" height="3" />' +
                '<rect class="plus-minus" x="46" y="57.5" width="8" height="3" />' +
                '<rect class="plus-minus" x="48.5" y="55" width="3" height="8" />'
            )
            .attr("transform", "translate(0 0)")
            .attr("class", "hidden-xs")

        //Configure the drag behaviour

        var drag = d3.behavior.drag()
            .on("dragstart", function () {
                var e = d3.event.sourceEvent;
                if (e.ctrlKey) return;
                d3.event.sourceEvent.stopPropagation();
            })
            .on("drag", function () {
                var e = d3.event.sourceEvent;
                if (e.ctrlKey) return;
                d3.select(this).attr("transform", function (d, i) {
                    if (d3.event.ctrlKey) return;
                    g = this.parentNode,
                        isSelected = d3.select(g).classed("selected");


                    d.x += d3.event.dx;
                    d.y += d3.event.dy;
                    if (d.x < 0) {
                        d.x = 0;
                    }
                    if (d.y < 0) {
                        d.y = 0;
                    }

                    if (d.x > width) {
                        d.x = width;
                    }

                    if (d.y > height) {
                        d.y = height;
                    }
                    return "translate(" + [d.x, d.y] + ")";
                });
            })
            .on("dragend", function (d) {
                var e = d3.event.sourceEvent;
                if (e.ctrlKey) return;
                d3.event.sourceEvent.stopPropagation();
                relMatrixCtrl.matrixDirty = true;
                d.dirty = true;
                var impact = 1 + (d.x / QRMDataService.relMatrixGridSizeX);
                var prob = (QRMDataService.project.matrix.maxProb + 1) - (d.y / QRMDataService.relMatrixGridSizeY);

                switch (Number(relMatrixCtrl.status.val)) {
                case 0:
                    if (d.treated) {
                        d.treatedClean = false;
                        d.newTreatedImpact = impact;
                        d.newTreatedProb = prob;
                    } else {
                        d.untreatedClean = false;
                        d.newInherentImpact = impact;
                        d.newInherentProb = prob;
                    }
                    break;
                case 1:
                    d.untreatedClean = false;
                    d.newInherentImpact = impact;
                    d.newInherentProb = prob;
                    break;
                case 2:
                    d.treatedClean = false;
                    d.newTreatedImpact = impact;
                    d.newTreatedProb = prob;
                }

                d3.event.sourceEvent.stopPropagation();
            });


        //Create the items on the matrix

        var radius = 25;

        var holder = svg.append("g")
            .attr("class", "risk");

        var gRisks = holder.selectAll().data(risks);
        var gRisk = gRisks.enter().append("g")
            .attr("id", function (d) {
                return "riskID" + d.id;
            })
            .attr({
                "transform": function (d) {
                    var prob = null;
                    var impact = null;
                    switch (Number(relMatrixCtrl.status.val)) {
                    case 0:
                        if (d.treated) {
                            prob = (d.treatedClean) ? d.treatedProb : d.newTreatedProb;
                            impact = (d.treatedClean) ? d.treatedImpact : d.newTreatedImpact;
                        } else {
                            prob = (d.untreatedClean) ? d.inherentProb : d.newInherentProb;
                            impact = (d.untreatedClean) ? d.inherentImpact : d.newInherentImpact;
                        }
                        break;
                    case 1:
                        if (d.untreatedClean) {
                            prob = d.inherentProb;
                            impact = d.inherentImpact;
                        } else {
                            prob = d.newInherentProb;
                            impact = d.newInherentImpact;
                        }
                        break;
                    case 2:
                        if (d.treatedClean) {
                            prob = d.treatedProb;
                            impact = d.treatedImpact;
                        } else {
                            prob = d.newTreatedProb;
                            impact = d.newTreatedImpact;
                        }
                        break;
                    }

                    var x = (impact - 1) * QRMDataService.relMatrixGridSizeX;
                    var y = (maxProb + 1 - prob) * QRMDataService.relMatrixGridSizeY;

                    d.x = x;
                    d.y = y;
                    //Prevent initial layout from overlapping items.
                    relMatrixCtrl.risks.forEach(function (risk) {
                        if (Math.abs(risk.x - d.x) < 10 && Math.abs(risk.y - d.y) < 10 && risk.id != d.id) {
                            d.x += 5;
                            d.y += 5;
                        }
                    });
                    return "translate(" + [d.x, d.y] + ")";
                },
                'class': 'state'
            });

        gRisk.call(drag);

        gRisk.append("circle").attr({
            r: radius + 4,
            class: 'outer'
        });

        gRisk.append("circle").attr({
                r: radius,
                class: 'inner'
            })
            .on("click", function (d, i) {
                var e = d3.event,
                    g = this.parentNode,
                    isSelected = d3.select(g).classed("selected");

                if (!e.ctrlKey) {
                    d3.selectAll('g.selected').classed("selected", false);
                }

                d3.select(g).classed("selected", !isSelected);
                g.parentNode.appendChild(g);
            })
            .on("mouseover", function (d) {
                var g = this.parentNode;
                var isSelected = d3.select(g).classed("selected");
                d3.selectAll('g.selected').classed("selected", false);
                d3.select(g).classed("selected", !isSelected);
                // reappend dragged element as last so that its stays on top 
                g.parentNode.appendChild(g);

                relMatrixCtrl.showDesc = true;
                relMatrixCtrl.riskProjectCode = d.riskProjectCode;
                relMatrixCtrl.title = d.title;
                relMatrixCtrl.description = d.description.substring(0, 300);
                d3.select(this).style("fill", "aliceblue");
                $scope.$apply();
            })
            .on("mouseout", function (d) {
                d3.select(this).style("fill", "white");
                d3.selectAll('g.selected').classed("selected", false);
                relMatrixCtrl.showDesc = false;
                $scope.$apply();
            })
            .on("click", function (d) {
                var e = d3.event;
                if (!e.ctrlKey) return;
                if (d3.event.defaultPrevented) return;
                if (relMatrixCtrl.matrixDirty) {
                    msg("Open Risk", "Please save or cancel existing changes before opening the risk");
                } else {
                    relMatrixCtrl.listenForEditorChanges = true;
                    //swtich to the editor fo rthe risk
                    //getRiskCodeAndDisplayInt(d.riskProjectCode);
                }
            });

        gRisk.append("text").attr({
                'text-anchor': 'middle',
                y: 4
            })
            .attr("class", function (d) {
                if (d.treated) {
                    return "treated";
                } else return "untreated";
            })
            .text(function (d) {
                //  return d.riskProjectCode;
                return d.riskProjectCode
            });

        gRisk.append("title").text(function (d) {
            return d.riskProjectCode;
        });
    }

    this.resize = function () {
        relMatrixCtrl.svgMatrix(relMatrixCtrl.risks);
    }

    this.getRisksAndPlace = function () {
        remoteService.getAllProjectRisks(QRMDataService.project.id)
            .then(function (response) {
                var risks = response.data.data;
                risks.forEach(function (risk) {
                    risk.untreatedClean = true;
                    risk.treatedClean = true;
                    risk.dirty = false;
                });
                relMatrixCtrl.risks = risks;
                relMatrixCtrl.svgMatrix(risks);

            });
    }

    this.getRisksAndPlace();
}

var app = angular.module('inspinia');
app.config(['ngDialogProvider', function (ngDialogProvider) {
    ngDialogProvider.setDefaults({
        className: 'ngdialog-theme-default',
        plain: false,
        showClose: false,
        closeByDocument: false,
        closeByEscape: false,
        appendTo: false
    });
}]);
app.config(['cfpLoadingBarProvider', function(cfpLoadingBarProvider) {
    cfpLoadingBarProvider.includeSpinner = true;
  }]);
app.config(function ($provide) {
    // this demonstrates how to register a new tool and add it to the default toolbar
    $provide.decorator('taOptions', ['taRegisterTool', '$delegate', function (taRegisterTool, taOptions) { // $delegate is the taOptions we are decorating
        taOptions.toolbar = [
      ['h1', 'h2', 'h3', 'h4', 'p'],
      ['bold', 'italics', 'underline', 'strikeThrough', 'ul', 'ol', 'redo', 'undo', 'clear', 'html'],
      ['justifyLeft', 'justifyCenter', 'justifyRight', 'indent', 'outdent']
  ];
        return taOptions;
  }]);
});
app.controller('MainCtrl', ['QRMDataService', 'RemoteService','$state', '$sce', MainCtrl]);
app.controller('ExplorerCtrl', ['$scope', 'QRMDataService', '$state', 'RemoteService', ExplorerCtrl]);
app.controller('RiskCtrl', ['$scope', '$modal', 'QRMDataService', '$state', 'RemoteService', 'ngNotify', 'ngDialog', RiskCtrl]);
app.controller('CalenderController', ['$scope', 'QRMDataService', '$state', 'RemoteService', CalenderController]);
app.controller('RankController', ['$scope', 'QRMDataService', '$state', 'RemoteService', RankController]);
app.controller('RelMatrixController', ['$scope', 'QRMDataService', '$state', 'RemoteService', 'ngNotify', RelMatrixController]);

app.service('RemoteService', ['$http', RemoteService]);
app.service('QRMDataService', DataService);
app.filter('currencyFilter', function () {
    return function (value) {
        return '$' + Number(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

    };
});
app.filter('percentFilter', function () {
    return function (value) {
        return Number(value).toFixed(1).replace(/\d(?=(\d{3})+\.)/g, '$&,') + "%";

    };
});
app.filter('usernameFilter', ['QRMDataService', function (QRMDataService) {
    return function (input) {
        if (typeof (input) == "object") input = input.ID;
        if (input < 0) return "Not Assigned"
        if (typeof (input) == 'undefined') return;
        var user = $.grep(QRMDataService.siteUsers, function (e) {
            return e.ID == input
        })

        if (typeof (user) == "undefined") return "Unknown";
        if (user.length == 0) return "Not Found";
        if (user.length > 1) return "Unknown (too many)";

        return user[0].data.display_name;
    }

}]);