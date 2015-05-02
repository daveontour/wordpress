function MainCtrl() {

    this.userName = 'Example user';
    this.helloText = 'Welcome in SeedProject';
    this.descriptionText = 'It is an application skeleton for a typical AngularJS web app. You can use it to quickly bootstrap your angular webapp projects and dev environment for these projects.';

};

function ExplorerCtrl($scope, QRMDataService, $state, riskService) {

    QRMDataService.riskID = 0;
    var exp = this;
    this.project = QRMDataService.project;

    this.valPre = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.valPost = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    this.gridOptions = {
        enableSorting: true,
        minRowsToShow: 10,
        rowHeight: 25,
        rowTemplate: '<div ng-click="grid.appScope.editRisk(row.entity.id)"   ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
        columnDefs: [
            {
                name: 'currentTolerance',
                cellTemplate: '<i class="fa fa-circle"></i>',
                enableColumnMoving: false,
                width: 30,
                headerCellClass: 'header-hidden',
                cellClass: function (grid, row, col, rowRenderIndex, colRenderIndex) {
                    switch (Number(grid.getCellValue(row, col))) {
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
                cellClass: 'compact'

            },
            {
                name: 'owner',
                width: 140,
                field: 'owner.name'

            },
            {
                name: 'manager',
                width: 140,
                field: 'manager.name'
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


    this.filterMatrixFlag = false;
    this.filterMatrixHighlightFlag = false;

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
    this.getRisks = function () {
        riskService.getRisks(QRMDataService.url)
            .then(function (response) {
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

                var own = exp.filterOptions.owner.name;
                var man = exp.filterOptions.manager.name;

                if (!(own == r.owner.name || own == undefined || own == null) || !(man == r.manager.name || man == undefined || man == null)) {
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
        var index = (prob - 1) * QRMDataService.project.matrix.maxImpact + impact - 1;
        return (Number(QRMDataService.project.matrix.tolString.substring(index, index + 1)) == tol)
    }


    // Initial filling of the grid
    this.getRisks();

}

function RiskCtrl($scope, $modal, QRMDataService, $state, $stateParams, riskService, notify) {

    var vm = this;
    this.riskID = QRMDataService.riskID;
    this.stakeholders = [];
    this.additionalHolders = [];
    this.url = QRMDataService.url;
    this.project = QRMDataService.project;
    this.risk = QRMDataService.getTemplateRisk();

    $scope.dropzoneConfig = {
        options: { // passed into the Dropzone constructor
            url: QRMDataService.url + "?qrmfn=uploadFile",
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
                    notify({
                        message: 'Attachment added to risk',
                        classes: 'alert-info',
                        duration: 300,
                        templateUrl: "views/common/notify.html"
                    });

                    riskService.getRiskAttachments(QRMDataService.url, vm.riskID)
                        .then(function (response) {
                            debugger;
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
        var modalInstance = $modal.open({
            templateUrl: 'myModalContentDescription.html',
            controller: function ($modalInstance, description, title, riskTitle) {
                var vm = this;

                vm.description = description;
                vm.title = title;
                vm.riskTitle = riskTitle;

                vm.ok = function () {
                    $modalInstance.close({
                        description: vm.description,
                        riskTitle: vm.riskTitle
                    });
                };
                vm.cancel = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            resolve: {
                description: function () {
                    return vm.risk.description
                },
                title: function () {
                    return "Risk Title and Description"
                },
                riskTitle: function () {
                    return vm.risk.title
                },
            },
            size: "lg"
        });

        modalInstance.result.then(function (response) {
            vm.risk.description = response.description;
            vm.risk.title = response.riskTitle;
        });
    }
    this.openConsequenceEditor = function () {
        var modalInstance = $modal.open({
            templateUrl: 'myModalContentConsequence.html',
            size: "lg",
            controller: function ($modalInstance, consequence) {

                this.consequence = consequence;
                this.ok = function () {
                    $modalInstance.close({
                        d: this.consequence
                    });
                };
                this.cancel = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            resolve: {
                consequence: function () {
                    return vm.risk.consequence;
                }
            }

        });

        modalInstance.result.then(function (r) {
            vm.risk.consequence = r.d;
        });
    }
    this.openCauseEditor = function () {
        var modalInstance = $modal.open({
            templateUrl: 'myModalContentCause.html',
            size: "lg",
            controller: function ($modalInstance, cause) {

                this.cause = cause;
                this.ok = function () {
                    $modalInstance.close({
                        d: this.cause
                    });
                };
                this.cancel = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            resolve: {
                cause: function () {
                    return vm.risk.cause;
                }
            }

        });

        modalInstance.result.then(function (r) {
            vm.risk.cause = r.d;
        });
    }
    this.openMitEditor = function () {

        var modalInstance = $modal.open({
            templateUrl: 'myModalContentMitigationResponse.html',
            size: "lg",
            controller: function ($modalInstance, title, plan, update) {

                this.title = title;
                this.plan = plan;
                this.update = update;

                this.ok = function () {

                    $modalInstance.close({
                        plan: this.plan,
                        update: this.update
                    });
                };

                this.cancel = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            resolve: {
                title: function () {
                    return "Mitigation Plan"
                },
                plan: function () {
                    return vm.risk.mitigation.mitPlanSummary
                },
                update: function () {
                    return vm.risk.mitigation.mitPlanSummaryUpdate
                }
            }

        });

        modalInstance.result.then(function (r) {

            vm.risk.mitigation.mitPlanSummary = r.plan;
            vm.risk.mitigation.mitPlanSummaryUpdate = r.update;
        });
    }
    this.openRespEditor = function () {

        var modalInstance = $modal.open({
            templateUrl: 'myModalContentMitigationResponse.html',
            size: "lg",
            controller: function ($modalInstance, title, plan, update) {

                this.title = title;
                this.plan = plan;
                this.update = update;

                this.ok = function () {
                    $modalInstance.close({
                        plan: this.plan,
                        update: this.update
                    });
                };

                this.cancel = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            resolve: {
                title: function () {
                    return "Response Plan"
                },
                plan: function () {
                    return vm.risk.response.respPlanSummary
                },
                update: function () {
                    return vm.risk.response.respPlanSummaryUpdate
                }
            }

        });

        modalInstance.result.then(function (r) {
            vm.risk.response.respPlanSummary = r.plan;
            vm.risk.response.respPlanSummaryUpdate = r.update;
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
        riskService.getRisk(QRMDataService.url, vm.riskID)
            .then(function (response) {
                vm.risk = response.data;
                vm.updateRisk();
            });
    };
    this.saveRisk = function () {
        // Ensure all the changes have been made
        vm.updateRisk();
        //Zero out the comments as these are managed separately
        vm.risk.comments = [];
        vm.risk.attachments = [];
        riskService.saveRisk(QRMDataService.url, vm.risk)
            .then(function (response) {
                vm.risk = response.data;
                // Update the risk with changes that may have been made by the host.
                QRMDataService.riskID = vm.risk.riskID;
                vm.updateRisk();
                notify({
                    message: 'Risk Saved',
                    classes: 'alert-info',
                    duration: 1500,
                    templateUrl: "views/common/notify.html"
                });
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
                name: vm.risk.owner.name,
                email: vm.risk.owner.email,
                role: "Risk Owner"
            });
            vm.stakeholders.push({
                name: vm.risk.manager.name,
                email: vm.risk.manager.email,
                role: "Risk Manager"
            });
            vm.risk.mitigation.mitPlan.forEach(function (e) {
                vm.stakeholders.push({
                    name: e.person.name,
                    role: "Mitigation Owner"
                })
            });
            vm.risk.response.respPlan.forEach(function (e) {
                vm.stakeholders.push({
                    name: e.person.name,
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
        $('#exposure').data('daterangepicker').setStartDate(moment(vm.risk.start));
        $('#exposure').data('daterangepicker').setEndDate(moment(vm.risk.end));


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
            person: "No Assigned Responsibility",
            cost: 0,
            complete: 0,
            due: new Date()
        });
    }
    this.addResp = function () {
        vm.risk.response.respPlan.push({
            description: "No Description of the Action Entered ",
            person: "No Assigned Responsibility",
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
        var modalInstance = $modal.open({
            templateUrl: "myModalContentAddComment.html",
            controller: function ($modalInstance) {

                this.comment = "";

                this.ok = function () {
                    $modalInstance.close(this.comment);
                };

                this.cancel = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            size: "lg"
        });

        modalInstance.result.then(function (comment) {
            riskService.addComment(QRMDataService.url, comment, QRMDataService.riskID)
                .then(function (response) {
                    vm.risk.comments = response.data.comments;
                });
        });
    }

    this.editMitStep = function (s) {
        var modalInstance = $modal.open({
            templateUrl: "myModalContentEditMit.html",
            controller: function ($modalInstance, step, stakeholders) {

                this.step = step;
                this.stakeholders = stakeholders;

                // Need to convert the date object back to a string
                this.ok = function () {
                    if (typeof (this.step.due) == "Date") {
                        this.step.due = vm.step.due.toString();
                    }
                    $modalInstance.close(this.step);
                };

                vm.cancel = function () {
                    if (typeof (this.step.due) == "Date") {
                        this.step.due = this.step.due.toString();
                    }
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            size: "lg",
            resolve: {
                step: function () {
                    // Date input requires a Date object, so convert string to object
                    s.due = new Date(s.due);
                    return s;
                },
                stakeholders: function () {
                    return getProjectStakeholders(vm.project);
                }
            }
        });

        modalInstance.result.then(function (o) {
            // Object will be updated, but need to signal change TODO
        });
    }
    this.editControl = function (s) {
        var modalInstance = $modal.open({
            templateUrl: "myModalContentEditControl.html",
            controller: function ($modalInstance, control) {

                this.control = control;
                this.effectArray = ["Ad Hoc", "Repeatable", "Defined", "Managed", "Optimising"];
                this.contribArray = ["Minimal", "Minor", "Significant", "Major"];

                this.ok = function () {
                    $modalInstance.close();
                };

                this.cancel = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            size: "lg",
            resolve: {
                control: function () {
                    return s;
                }
            }
        });

        modalInstance.result.then(function (o) {
            // Object will be updated, but need to signal change TODO
        });
    }
    this.editRespStep = function (s) {
        var modalInstance = $modal.open({
            templateUrl: "myModalContentEditResp.html",
            controller: function ($modalInstance, resp, stakeholders) {


                this.resp = resp;
                this.stakeholders = stakeholders;

                // Need to convert the date object back to a string
                this.ok = function () {
                    $modalInstance.close($scope.resp);
                };

                this.cancel = function () {
                    $modalInstance.dismiss('cancel');
                };
            },
            controllerAs: "vm",
            size: "lg",
            resolve: {
                resp: function () {
                    return s;
                },
                stakeholders: function () {
                    return getProjectStakeholders(vm.project);
                }
            }
        });

        modalInstance.result.then(function (o) {
            // Object will be updated, but need to signal change TODO
        });
    }

    this.deleteMitStep = function (s) {
        for (var i = 0; i < vm.risk.mitigation.mitPlan.length; i++) {
            if (vm.risk.mitigation.mitPlan[i].$$hashKey == s.$$hashKey) {
                vm.risk.mitigation.mitPlan.splice(i, 1);
                break;
            }
        }
    }
    this.deleteRespStep = function (s) {
        for (var i = 0; i < vm.risk.response.respPlan.length; i++) {
            if (vm.risk.response.respPlan[i].$$hashKey == s.$$hashKey) {
                vm.risk.response.respPlan.splice(i, 1);
                break;
            }
        }
    }
    this.deleteControl = function (s) {
        for (var i = 0; i < vm.risk.controls.length; i++) {
            if (vm.risk.controls[i].$$hashKey == s.$$hashKey) {
                vm.risk.controls.splice(i, 1);
                break;
            }
        }
    }

    this.getRisk();

}

function CalenderController($scope, QRMDataService, $state, riskService) {

    
    var cal = this;
    this.project = QRMDataService.project;
    this.editRisk = function(id){
        QRMDataService.riskID = id;
        $state.go('index.risk');
    }
    this.getRisks = function () {
        riskService.getRisks(QRMDataService.url)
            .then(function (response) {

                var tasks = new Array();
                var taskNames = new Array();
                var index = 0;
                response.data.data.forEach(function (risk) {
                    debugger;
                    tasks.push({
                        "startDate": moment(risk.start),
                        "endDate": moment(risk.end),
                        "taskName": "RISKID"+index,
                        "status": "RUNNING",
                        "riskID":risk.id
                    });
                    index++;
                });
            
                          var now = new Date();
              tasks.sort(function(a, b) {return a.startDate - b.startDate; });
              tasks.forEach(function(task){
                 if (task.startDate > now){
                    task.className = 'future';
                 } else if( task.endDate < now){
                    task.className = 'past';
                 } else {
                    task.className = 'now';
                 }
                
                 taskNames.push(task.taskName);
              });
            
            debugger;
              d3.select("#svgcalID").selectAll("svg").remove();
            
              var gantt = d3.gantt(cal).taskTypes(taskNames).tickFormat("%b %Y");
              gantt(tasks, "#svgcalID",$('#svgcalIDPanel').width(), $('#svgcalIDPanel').height());
            
//              $('rect').tooltip({'placement':'top'});
            });

    }

    this.getRisks();
}

function RankController($scope, QRMDataService, $state, riskService) {

    
    var rank = this;
    this.project = QRMDataService.project;
    this.editRisk = function(id){
        QRMDataService.riskID = id;
        $state.go('index.risk');
    }
    
    
    this.loadGrid = function(){
        
        riskService.getRisks(QRMDataService.url)
            .then(function (response) {
            var risks = response.data.data;
            rank.dirty = false;
            rank.risks = risks;
            rank.layout = new SorterLayout(rank);
            
            var html = "<div style='valign:top'><br><hr><br/>Rearrange the rank order of the risks by dragging and droping the risks. <br/><br/>The risks are initially arranged in rank order from top to bottom, left to right<br/><br/></strong><hr></div>";
        //    $('#qrm-rankDetail').html(html);

            debugger;
            myLayout = rank.layout;
            myLayout.setHeight($('#qrm-SubRankPanel').height());
            myLayout.setWidth($('#qrm-SubRankPanel').width());
            myLayout.setItemHeight(35);
            myLayout.setItemWidth($('#qrm-SubRankPanel').width()/2);
            myLayout.scale(1,1);
            myLayout.setItems(rank.risks);
            myLayout.setSVGDiv("subRankSVGDiv");
            myLayout.setDirtyListener(function(){rank.dirty = true;});
            myLayout.layoutTable();
        });      

   }
 
    this.loadGrid();
}


var app = angular.module('inspinia')
app.controller('MainCtrl', MainCtrl);
app.controller('ExplorerCtrl', ['$scope', 'QRMDataService', '$state', 'riskService', ExplorerCtrl]);
app.controller('RiskCtrl', ['$scope', '$modal', 'QRMDataService', '$state', '$stateParams', 'riskService', 'notify', RiskCtrl]);
app.controller('CalenderController', ['$scope', 'QRMDataService', '$state', 'riskService', CalenderController]);
app.controller('RankController', ['$scope', 'QRMDataService', '$state', 'riskService', RankController]);

