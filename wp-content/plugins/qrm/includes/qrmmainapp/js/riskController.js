function RiskCtrl($scope, $modal, QRMDataService, $http, $state, $stateParams, riskService) {



    var riskCtl = this;
    this.riskID = QRMDataService.riskID;
    this.stakeholders = [];
    this.additionalHolders = [{
        name: "David Burton",
        role: "Horny Bastard"
    }];
    this.url = QRMDataService.url;
    this.project = QRMDataService.project;
    this.risk = QRMDataService.risk;
    
    this.test = function(){
        alert("HELLO");
    }

    this.gridOptions = {
        enableSorting: false,
        data: this.risk.mitigation.mitPlan,
        columnDefs: [
            {
                name: 'description',
                width:"*",
                enableCellEdit:true,
                type:'text'
            },
            {
                name: 'person',
                width:180
            },
            {
                name: 'cost',
                width:100,
                cellFilter:'currencyFilter'
            },
            {
                name: 'due',
                width:150
            },
            {
                name: 'complete',
                width:100,
                cellFilter:'percentFilter'
            },
            {
                name: 'update',
                width:"*",
                cellTemplate:"<div><button ng-click='ctl.test()'>Delete</div>"
            }

    ]
    }

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
                    return "Mitigation Plan"
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

        this.beginDescription = "Begining of risk exposure " + moment(this.risk.start).fromNow();
        this.endDescription = "End of risk exposure " + moment(this.risk.end).fromNow();

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
                    name: e.person,
                    role: "Mitigation Owner"
                })
            });
            this.risk.response.respPlan.forEach(function (e) {
                riskCtl.stakeholders.push({
                    name: e.person,
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

        var maxImpact = this.project.matrix.maxImpact;

        var index = (Math.floor(this.risk.treatedProb - 1)) * maxImpact + Math.floor(this.risk.treatedImpact - 1);
        index = Math.min(index, this.project.matrix.tolString.length - 1);
        this.risk.treatedTolerance = this.project.matrix.tolString.substring(index, index + 1);

        index = (Math.floor(this.risk.inherentProb - 1)) * maxImpact + Math.floor(this.risk.inherentImpact - 1);
        index = Math.min(index, this.project.matrix.tolString.length - 1);
        this.risk.inherentTolerance = this.project.matrix.tolString.substring(index, index + 1);

        this.risk.currentTolerance = (this.risk.treated) ? this.risk.treatedTolerance : this.risk.inherentTolerance

        this.risk.currentProb = (this.risk.treated) ? this.risk.treatedProb : this.risk.inherentProb;
        this.risk.currentImpact = (this.risk.treated) ? this.risk.treatedImpact : this.risk.inherentImpact;
        this.risk.currentTolerance = (this.risk.treated) ? this.risk.treatedTolerance : this.risk.inherentTolerance;

        this.treatedAbsProb = probFromMatrix(this.risk.treatedProb, this.project.matrix);
        this.inherentAbsProb = probFromMatrix(this.risk.inherentProb, this.project.matrix);

    }

    this.probChange = function () {

        switch (Number(this.risk.likeType)) {
        case 1:
        case "1":
            this.risk.likeT = 365;
            break;
        case 2:
        case '2':
            this.risk.likeT = 30;
            break;
        case 3:
        case "3":
            //do nothing, will already be set by model
            break;
        default:
            this.risk.likeT = 0;
        }
        switch (Number(this.risk.likePostType)) {
        case 1:
        case "1":
            this.risk.likePostT = 365;
            break;
        case 2:
        case "2":
            this.risk.likePostT = 30;
            break;
        case 3:
        case "3":
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

    this.impactChange = function () {
        this.updateRisk();
    }

    this.getRisk = function () {

        if (isNaN(this.riskID) || this.riskID == 0) {
            return;
        }
        riskService.getRisk(QRMDataService.url, this.riskID)
            .then(function (response) {
                riskCtl.risk = response.data;
                riskCtl.updateRisk();
            });
    };
    this.saveRisk = function () {
        riskService.saveRisk(QRMDataService.url, this.risk)
            .then(function (response) {
                riskCtl.risk = response.data;
                riskCtl.updateRisk();
            });

    };

    this.updateDates = function (start, end) {

        this.risk.start = start;
        this.risk.end = end;

        this.updateRisk();

    }

    this.setRiskMatrixID = function (matrixDIVID) {
        QRMDataService.matrixDIVID = matrixDIVID;
    }
    this.setRiskMatrix = function () {
        // Calls function in qrm-common.js
        setRiskEditorMatrix(this.risk, this.project.matrix, QRMDataService.matrixDIVID, QRMDataService.matrixDisplayConfig, this.dragStart, this.drag, this.dragEnd);
    }

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
    this.drag = function (d) {
        //alert("Matrix was updated");
    }

    this.getRisk();
    this.updateRisk();

}
