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