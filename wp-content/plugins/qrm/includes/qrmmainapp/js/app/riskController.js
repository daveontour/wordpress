function RiskCtrl($scope, $modal, QRMDataService, $state, $stateParams, riskService, notify) {

    var riskCtl = this;
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
            params: {
                riskID: QRMDataService.riskID
            },
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
                    riskCtl.riskAttachmentReady(this, file);
                });
                this.on('complete', function (file) {
                    file.previewElement.classList.add('dz-complete');
                    this.removeFile(file);
                    
                    riskService.getRiskAttachments(QRMDataService.url, riskCtl.riskID).then(function (response){
                        riskCtl.risk.attachments = response.data;
                    });
                });
            }
        },
    };

    this.riskAttachmentReady = function (dropzone, file) {
        this.dropzone = dropzone;
        this.dzfile = file;
        this.enableUploadButton = true;
    }

    this.uploadAttachmentComment = "";
    this.enableUploadButton = false;
    this.dropzone = "";
    this.uploadAttachment = function () {
        
            $scope.dropzoneConfig.options.params = {riskID: QRMDataService.riskID, description:this.uploadAttachmentComment}
            this.dropzone.processFile(this.dzfile);
        
    }

    //Opens Modal Dialog box
    this.open = function (item, size) {

        var templateUrl;
        var controller;
        var reslove;

        switch (item) {
        case 'mitigation':
            templateUrl = 'myModalContentMitigationResponse.html';
            controller = 'ModalInstanceCtrlMitigation';
            resolve = {
                title: function () {
                    return "Mitigation Plan"
                },
                plan: function () {
                    return riskCtl.risk.mitigation.mitPlanSummary
                },
                update: function () {
                    return riskCtl.risk.mitigation.mitPlanSummaryUpdate
                }
            }
            break;
        case 'response':
            templateUrl = 'myModalContentMitigationResponse.html';
            controller = 'ModalInstanceCtrlMitigation';
            resolve = {
                title: function () {
                    return "Response Plan"
                },
                plan: function () {
                    return riskCtl.risk.response.respPlanSummary
                },
                update: function () {
                    return riskCtl.risk.response.respPlanSummaryUpdate
                }
            }
            break;
        case 'description':
            templateUrl = 'myModalContentDescription.html';
            controller = 'ModalInstanceCtrl';
            resolve = {
                text: function () {
                    return [riskCtl.risk.description, riskCtl.risk.cause, riskCtl.risk.consequence, riskCtl.risk.title];
                },
                item: function () {
                    return item;
                }
            }
            break;
        default:
            templateUrl = 'myModalContent.html';
            controller = 'ModalInstanceCtrl';
            resolve = {
                text: function () {
                    return [riskCtl.risk.description, riskCtl.risk.cause, riskCtl.risk.consequence, riskCtl.risk.title];
                },
                item: function () {
                    return item;
                }
            }
        }

        var modalInstance = $modal.open({
            templateUrl: templateUrl,
            controller: controller,
            size: size,
            resolve: resolve
        });

        modalInstance.result.then(function (o) {


            switch (item) {
            case 'description':
                riskCtl.risk.description = o.text;
                riskCtl.risk.title = o.title;
                break;
            case 'cause':
                riskCtl.risk.cause = o.text;
                break;
            case 'consequence':
                riskCtl.risk.consequence = o.text;
                break;
            case 'mititgation':
                riskCtl.risk.mitigation.mitPlanSummary = o.plan;
                riskCtl.risk.mitigation.mitPlanSummaryUpdate = o.update;
                break;
            case 'response':
                riskCtl.risk.response.respPlanSummary = o.plan;
                riskCtl.risk.response.respPlanSummaryUpdate = o.update;
                break;
            }

        });
    }

    this.updateRisk = function () {

        //Static Definitions


        // secondary risk category
        try {
            this.secCatArray = jQuery.grep(this.project.categories, function (e) {
                return e.name == riskCtl.risk.primcat.name
            })[0].sec;
        } catch (e) {
            console.log(e.message);
        }

        //Update the Matrix
        try {
            this.setRiskMatrix(this.matrixDIVID);
        } catch (e) {
            console.log(e.message);
        }

        // Create a list of stakeholders
        try {
            this.stakeholders = [];
            this.stakeholders.push({
                name: this.risk.owner.name,
                email: this.risk.owner.email,
                role: "Risk Owner"
            });
            this.stakeholders.push({
                name: this.risk.manager.name,
                email: this.risk.manager.email,
                role: "Risk Manager"
            });
            this.risk.mitigation.mitPlan.forEach(function (e) {
                riskCtl.stakeholders.push({
                    name: e.person.name,
                    role: "Mitigation Owner"
                })
            });
            this.risk.response.respPlan.forEach(function (e) {
                riskCtl.stakeholders.push({
                    name: e.person.name,
                    role: "Response Owner"
                })
            });
            this.stakeholders = this.stakeholders.concat(this.additionalHolders);
        } catch (e) {
            console.log(e.message);
        }

        // Remove any Duplicate 
        var arr = {};
        for (var i = 0; i < this.stakeholders.length; i++)
            arr[this.stakeholders[i]['name'] + this.stakeholders[i]['role']] = this.stakeholders[i];

        var temp = new Array();
        for (var key in arr)
            temp.push(arr[key]);

        this.stakeholders = temp;

        //Sort out the probs and impact

        try {

            var index = (Math.floor(this.risk.treatedProb - 1)) * this.project.matrix.maxImpact + Math.floor(this.risk.treatedImpact - 1);
            index = Math.min(index, this.project.matrix.tolString.length - 1);

            this.risk.treatedTolerance = this.project.matrix.tolString.substring(index, index + 1);


            index = (Math.floor(this.risk.inherentProb - 1)) * this.project.matrix.maxImpact + Math.floor(this.risk.inherentImpact - 1);
            index = Math.min(index, this.project.matrix.tolString.length - 1);

            this.risk.inherentTolerance = this.project.matrix.tolString.substring(index, index + 1);

            if (this.risk.treated) {
                this.risk.currentProb = this.risk.treatedProb;
                this.risk.currentImpact = this.risk.treatedImpact;
                this.risk.currentTolerance = this.risk.treatedTolerance;


            } else {
                this.risk.currentProb = this.risk.inherentProb;
                this.risk.currentImpact = this.risk.inherentImpact;
                this.risk.currentTolerance = this.risk.inherentTolerance;
            }



            this.treatedAbsProb = probFromMatrix(this.risk.treatedProb, this.project.matrix);
            this.inherentAbsProb = probFromMatrix(this.risk.inherentProb, this.project.matrix);
        } catch (e) {
            alert("Error" + e.message);
        }

    }

    this.impactChange = function () {
        this.updateRisk();
    }
    this.probChange = function () {

        switch (Number(this.risk.likeType)) {
        case 1:
            this.risk.likeT = 365;
            break;
        case 2:
            this.risk.likeT = 30;
            break;
        case 3:
            //do nothing, will already be set by model
            break;
        default:
            this.risk.likeT = 0;
        }
        switch (Number(this.risk.likePostType)) {
        case 1:
            this.risk.likePostT = 365;
            break;
        case 2:
            this.risk.likePostT = 30;
            break;
        case 3:
            //do nothing, will already be set by model
            break;
        default:
            this.risk.likePostT = 0;
        }


        // This caculates the 1-8 prob based on the calculated prob and the matrix config
        this.risk.inherentProb = probToMatrix(calcProb(this.risk, true), this.project.matrix);
        this.risk.treatedProb = probToMatrix(calcProb(this.risk, false), this.project.matrix);

        // This will update the matrix
        this.updateRisk();
    }


    this.getRisk = function () {

        if (isNaN(this.riskID) || this.riskID == 0) {
            return;
        }
        riskService.getRisk(QRMDataService.url, this.riskID)
            .then(function (response) {
                debugger;
                riskCtl.risk = response.data;
                riskCtl.updateRisk();
            });
    };
    this.saveRisk = function () {
        // Ensure all the changes have been made
        this.updateRisk();
        //Zero out the comments as these are managed separately
        this.risk.comments = [];
        this.risk.attachments = [];
        riskService.saveRisk(QRMDataService.url, this.risk)
            .then(function (response) {
                riskCtl.risk = response.data;
                // Update the risk with changes that may have been made by the host.
                riskCtl.updateRisk();
                notify({
                    message: 'Risk Saved',
                    classes: 'alert-info',
                    duration: 1000,
                    templateUrl: "views/common/notify.html"
                });
            });
    };

    //Called by listener set by jQuery on the date-range control
    this.updateDates = function (start, end) {
        this.risk.start = start;
        this.risk.end = end;

        this.updateRisk();
    }

    // The probability matrix
    this.setRiskMatrixID = function (matrixDIVID) {
        QRMDataService.matrixDIVID = matrixDIVID;
    }
    this.setRiskMatrix = function () {
            // Calls function in qrm-common.js
            setRiskEditorMatrix(this.risk, this.project.matrix, QRMDataService.matrixDIVID, QRMDataService.matrixDisplayConfig, this.dragStart, this.drag, this.dragEnd);
        }
        //Callbacks for start and finish of dragging an item inthe probability matrix
    this.dragEnd = function (d) {

        riskCtl.risk.useCalProb = false;
        riskCtl.risk.liketype = 4;
        riskCtl.risk.likepostType = 4;

        if (d.treated) {
            riskCtl.risk.treatedProb = Number(d.prob);
            riskCtl.risk.treatedImpact = Number(d.impact);
        } else {
            riskCtl.risk.inherentProb = Number(d.prob);
            riskCtl.risk.inherentImpact = Number(d.impact);
        }

        riskCtl.updateRisk();

        $scope.$apply();

    }
    this.dragStart = function (d) {
        riskCtl.risk.useCalProb = false;
        riskCtl.risk.useCalProb = false;
        riskCtl.risk.liketype = 4;
        riskCtl.risk.likepostType = 4;

    }

    this.drag = function () {

    }


    //Mitigation and Response Editing

    this.addMit = function () {
        this.risk.mitigation.mitPlan.push({
            description: "No Description of the Action Entered ",
            person: "No Assigned Responsibility",
            cost: 0,
            complete: 0,
            due: new Date()
        });
    }
    this.addResp = function () {
        this.risk.response.respPlan.push({
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

        if (this.risk.controls) {
            this.risk.controls.push(control);

        } else {
            this.risk.controls = [control]
        }

    }
    this.editMitStep = function (s) {
        var modalInstance = $modal.open({
            templateUrl: "myModalContentEditMit.html",
            controller: MitController,
            size: "lg",
            resolve: {
                step: function () {
                    // Date input requires a Date object, so convert string to object
                    s.due = new Date(s.due);
                    return s;
                },
                stakeholders: function () {
                    return getProjectStakeholders(riskCtl.project);
                }
            }
        });

        modalInstance.result.then(function (o) {
            // Object will be updated, but need to signal change TODO
        });
    }

    this.addComment = function (s) {
        var modalInstance = $modal.open({
            templateUrl: "myModalContentAddComment.html",
            controller: CommentController,
            size: "lg"
        });

        modalInstance.result.then(function (comment) {
            riskService.addComment(QRMDataService.url, comment, QRMDataService.riskID)
                .then(function (response) {
                    riskCtl.risk.comments = response.data.comments;
                });
        });
    }

    this.deleteMitStep = function (s) {
        for (var i = 0; i < this.risk.mitigation.mitPlan.length; i++) {
            if (this.risk.mitigation.mitPlan[i].$$hashKey == s.$$hashKey) {
                this.risk.mitigation.mitPlan.splice(i, 1);
                break;
            }
        }
    }
    this.editControl = function (s) {
        var modalInstance = $modal.open({
            templateUrl: "myModalContentEditControl.html",
            controller: ControlController,
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
    this.deleteRespStep = function (s) {
        for (var i = 0; i < this.risk.response.respPlan.length; i++) {
            if (this.risk.response.respPlan[i].$$hashKey == s.$$hashKey) {
                this.risk.response.respPlan.splice(i, 1);
                break;
            }
        }
    }
    this.editRespStep = function (s) {
        var modalInstance = $modal.open({
            templateUrl: "myModalContentEditResp.html",
            controller: RespController,
            size: "lg",
            resolve: {
                resp: function () {
                    return s;
                },
                stakeholders: function () {
                    return getProjectStakeholders(riskCtl.project);
                }
            }
        });

        modalInstance.result.then(function (o) {
            // Object will be updated, but need to signal change TODO
        });
    }
    this.deleteControl = function (s) {
        for (var i = 0; i < this.risk.controls.length; i++) {
            if (this.risk.controls[i].$$hashKey == s.$$hashKey) {
                this.risk.controls.splice(i, 1);
                break;
            }
        }
    }

    this.getRisk();

}

// Controllers for the modal dialog box editors
function ModalInstanceCtrl($scope, $modalInstance, text, item) {

    switch (item) {
    case 'description':
        $scope.text = text[0];
        $scope.title = "Risk Title and Description";
        $scope.risktitle = text[3];
        break;
    case 'cause':
        $scope.text = text[1];
        $scope.title = "Risk Cause";
        break;
    case 'consequence':
        $scope.text = text[2];
        $scope.title = "Risk Consequences";
        break;
    }


    $scope.ok = function () {
        $modalInstance.close({
            text: $scope.text,
            title: $scope.risktitle
        });
    };

    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
}

function ModalInstanceCtrlMitigation($scope, $modalInstance, title, plan, update) {

    switch (title) {
    case 'Response Plan':
        $scope.title = "Response Plan";
        break;
    case 'Mitigation Plan':
        $scope.title = "Mitigation Plan";
        break;
    }

    $scope.plan = plan;
    $scope.update = update;

    $scope.ok = function () {
        $modalInstance.close({
            plan: $scope.plan,
            update: $scope.update
        });
    };

    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
}

function MitController($scope, $modalInstance, step, stakeholders) {

    debugger;

    $scope.step = step;
    $scope.stakeholders = stakeholders;

    // Need to convert the date object back to a string
    $scope.ok = function () {
        if (typeof ($scope.step.due) == "Date") {
            $scope.step.due = $scope.step.due.toString();
        }
        $modalInstance.close($scope.step);
    };

    $scope.cancel = function () {
        if (typeof ($scope.step.due) == "Date") {
            $scope.step.due = $scope.step.due.toString();
        }
        $modalInstance.dismiss('cancel');
    };
}

function RespController($scope, $modalInstance, resp, stakeholders) {

    $scope.resp = resp;
    $scope.stakeholders = stakeholders;

    // Need to convert the date object back to a string
    $scope.ok = function () {
        $modalInstance.close($scope.resp);
    };

    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
}

function ControlController($scope, $modalInstance, control) {

    $scope.control = control;
    $scope.effectArray = ["Ad Hoc", "Repeatable", "Defined", "Managed", "Optimising"];
    $scope.contribArray = ["Minimal", "Minor", "Significant", "Major"];

    // Need to convert the date object back to a string
    $scope.ok = function () {
        $modalInstance.close($scope.resp);
    };

    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
}

function CommentController($scope, $modalInstance) {

    $scope.comment = "";

    // Need to convert the date object back to a string
    $scope.ok = function () {
        $modalInstance.close($scope.comment);
    };

    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
}