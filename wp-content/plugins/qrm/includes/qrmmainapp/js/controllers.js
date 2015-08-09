function SortByProjectCode(a, b) {
    if (a == null || b == null) return 0;

    var aName, bName;
    if (typeof (a) == "object") {
        aName = a.riskProjectCode.toLowerCase();
        bName = b.riskProjectCode.toLowerCase();
    } else {
        aName = $.grep(QRM.mainController.risks, function (e) {
            return e.id == a
        })[0].riskProjectCode;
        bName = $.grep(QRM.mainController.risks, function (e) {
            return e.id == b
        })[0].riskProjectCode;
    }

    return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
}

function QRMCtrl($scope, QRMDataService, remoteService, $state, $timeout, $q) {

    a = remoteService.getSiteUsers()
        .then(function (response) {
            if (response.data == "0" || response.data == "-1") {
                //                intro.sessionOK = false;
            } else {
                //                intro.sessionOK = true;
                QRMDataService.siteUsers = response.data.data;
            }
        });

    b = remoteService.getCurrentUser()
        .then(function (response) {
            if (response.data == "0" || response.data == "-1") {
                //                intro.sessionOK = false;
            } else {
                //                intro.sessionOK = true;
                QRMDataService.currentUser = response.data;
                QRM.mainController.userName = QRMDataService.currentUser.data.display_name;
            }
        });
    c = remoteService.getAllRisks()
        .then(function (response) {
            if (response.data == "0" || response.data == "-1") {
                //                intro.sessionOK = false;
            } else {
                //                intro.sessionOK = true;
                QRM.mainController.risks = response.data;
                QRM.mainController.risks = jQuery.grep(QRM.mainController.risks, function (value) {
                    if (value.riskProjectCode == null) return false;
                    return value != null;
                });
                QRM.mainController.risks.sort(SortByProjectCode);
                QRMDataService.risks = QRM.mainController.risks;
            }
        });

    d = remoteService.getProjects()
        .then(function (response) {
            if (response.data == "0" || response.data == "-1") {
                //                intro.sessionOK = false;
            } else {
                //                intro.sessionOK = true;
                QRMDataService.handleGetProjects(response);
            }

        });
};

function NonQRMCtrl($scope, QRMDataService, remoteService, $state, $timeout, $q) {

}

function IntroCtrl($scope, QRMDataService, remoteService, $state, $timeout, $q) {

    // This is the entry for the app
    // If the user is not logged on, simply present the login screen
    // If they are, switch the view according to the item they selected

    var intro = this;

    QRM.introController = this;

    $scope.introloading = true;

    intro.sessionOK = false;
    intro.qrmUser = false;

    z = remoteService.checkSession()
        .then(function (response) {
            if (response.data == "0" || response.data == "-1") {
                intro.sessionOK = false;
            } else {
                intro.sessionOK = true;
                intro.qrmUser = response.data.loggedin;
            }
        });

    a = remoteService.getSiteUsers()
        .then(function (response) {
            if (response.data == "0" || response.data == "-1") {
                intro.sessionOK = false;
            } else {
                intro.sessionOK = true;
                if (response.data != "-3") {
                    QRMDataService.siteUsers = response.data.data;
                }
            }
        });

    b = remoteService.getCurrentUser()
        .then(function (response) {
            if (response.data == "0" || response.data == "-1") {
                intro.sessionOK = false;
            } else {
                intro.sessionOK = true;
                if (response.data != "-3") {
                    QRMDataService.currentUser = response.data;
                    QRM.mainController.userName = QRMDataService.currentUser.data.display_name;
                }
            }
        });
    c = remoteService.getAllRisks()
        .then(function (response) {
            if (response.data == "0" || response.data == "-1") {
                intro.sessionOK = false;
            } else {
                intro.sessionOK = true;
                if (response.data != "-3") {

                    QRM.mainController.risks = response.data;
                    QRM.mainController.risks = jQuery.grep(QRM.mainController.risks, function (value) {
                        if (value.riskProjectCode == null) return false;
                        return value != null;
                    });
                    QRM.mainController.risks.sort(SortByProjectCode);
                    QRMDataService.risks = QRM.mainController.risks;
                }
            }
        });

    //postType is a global variable, set by PHP when the page get's created on the server

    if (postType == "risk" || postType == "review") {

        //Add additional data fetch
        d = remoteService.getProjects()
            .then(function (response) {
                if (response.data == "0" || response.data == "-1") {
                    intro.sessionOK = false;
                } else {
                    intro.sessionOK = true;
                    if (response.data != "-3") {
                        QRMDataService.handleGetProjects(response);
                    }
                }
            });

        $q.all([z, a, b, c, d]).then(function () {
            intro.switch();
        });

    } else {
        $q.all([z, a, b, c]).then(function () {
            intro.switch();
        });
    }

    this.switch = function () {

        if (!intro.sessionOK) {
            $state.go("login");
            return;
        }

        if (!intro.qrmUser) {
            $state.go("nonQRM");
        }

        switch (postType) {
        case 'risk':
            QRMDataService.selectProject(projectID);
            QRMDataService.riskID = postID;
            $scope.introloading = false;
            $state.go("qrm.risk");
            break;
        case 'riskproject':
            $scope.introloading = false;
            $state.go("qrm.explorer");
            break;
        case 'review':
            remoteService.getReview(postID)
                .then(function (response) {
                    QRMDataService.review = response.data;
                    if (QRMDataService.review.risks != null) {
                        QRMDataService.review.risks.sort(SortByProjectCode);
                    }
                    QRMDataService.reviewID = QRMDataService.review.id;
                    QRMDataService.review.actualdate = new Date(QRMDataService.review.actualdate);
                    QRMDataService.review.scheddate = new Date(QRMDataService.review.scheddate);
                    $scope.introloading = false;
                    $state.go("qrm.review");
                });
            break;
        case 'incident':
            remoteService.getIncident(postID)
                .then(function (response) {
                    QRMDataService.incident = response.data;
                    if (QRMDataService.incident.risks != null) {
                        QRMDataService.incident.risks.sort(SortByProjectCode);
                    }
                    QRMDataService.incidentID = QRMDataService.incident.id;
                    QRMDataService.incident.date = new Date(QRMDataService.incident.date);
                    $scope.introloading = false;
                    $state.go("qrm.incident");
                });
            break;
        default:
            $scope.introloading = false;
            $state.go("qrm.explorer");
            break;
        }
    }
};

function MainCtrl(QRMDataService, remoteService, $state) {

    QRM.mainController = this;


    this.showStatusBoard = false;
    this.showSpinner = false;
    this.showSelectProject = true;
    this.showLookingForRisks = false;
    this.showNoRisks = false;
    this.loading = false;
    this.sideOpen = false;

    this.showLookingForReviews = false;
    this.showNoReviews = false;

    this.showLookingForIncidents = false;
    this.showNoIncidents = false;


    this.pluginurl = pluginurl;
    this.loaderSrc = this.pluginurl + 'views/ajax-loader.gif'


    this.go = function (state) {
        $state.go(state);
    }

    this.lookingForRisks = function () {
        this.showSelectProject = false;
        this.showLookingForRisks = true;
        this.showNoRisks = false;
        this.loading = false;
    }

    this.noRisksFound = function () {
        this.showSelectProject = false;
        this.showLookingForRisks = false;
        this.showNoRisks = true;
        this.loading = false;
    }

    this.risksFound = function () {
        this.showSelectProject = false;
        this.showLookingForRisks = false;
        this.showNoRisks = false;
        this.loading = false;
    }

    this.loadingProject = function () {
        this.showSelectProject = false;
        this.showLookingForRisks = false;
        this.showNoRisks = false;
        this.loading = true;
    }

    this.lookingForIncidents = function () {
        this.showLookingForIncidents = true;
        this.showNoIncidents = false;
    }

    this.incidentsFound = function () {
        this.showLookingForIncidents = false;
        this.showNoIncidents = false;
    }

    this.noIncidentsFound = function () {
        this.showLookingForIncidents = false;
        this.showNoIncidents = true;
    }

    this.lookingForReviews = function () {
        this.showLookingForReviews = true;
        this.showNoReviews = false;
    }

    this.reviewsFound = function () {
        this.showLookingForReviews = false;
        this.showNoReviews = false;
    }

    this.noReviewsFound = function () {
        this.showLookingForReviews = false;
        this.showNoReviews = true;
    }

    this.getBtnClass = function (btnClass) {

        if (btnClass == 'primary' && this.class == 'primary') {
            return true;
        }
        if (btnClass == 'danger' && this.class == 'danger') {
            return true;
        }
        if (btnClass == 'info' && this.class == 'info') {
            return true;
        }
        if (btnClass == 'warning' && this.class == 'warning') {
            return true;
        }
    }

    this.checkUserCap = function (action, risk) {

        //        return true; 

        if (typeof (QRMDataService.currentUser) == 'undefined') return false;
        var userID = QRMDataService.currentUser.ID;
        var p = QRMDataService.project;
        //        if (typeof(p) == 'undefined') return false;


        switch (action) {

        case "edit_risk_grid":

            if (risk.owner == userID) return true;
            if (risk.manager == userID) return true;
            if (QRMDataService.project.projectRiskManager == userID) return true;
            return false;
            break;

        case "view_risk_grid":

            if (risk.owner == userID) return true;
            if (risk.manager == userID) return true;
            if (QRMDataService.project.projectRiskManager == userID) return true;
            if (p.ownersID.indexOf(userID) > -1) return true;
            if (p.managersID.indexOf(userID) > -1) return true;
            if (p.usersID.indexOf(userID) > -1) return true;

            return false;
            break;

        case "delete_risk_grid":
            if (risk.owner == userID) return true;
            if (QRMDataService.project.projectRiskManager == userID) return true;
            return false;
            break;


        case "new_risk":

            if (typeof (p) == 'undefined') return false;

            if (p.projectRiskManager == userID) return true;

            if (typeof (p.ownersID) == 'undefined') return false;
            if (p.ownersID.indexOf(userID) > -1) return true;

            if (typeof (p.managersID) == 'undefined') return false;
            if (p.managersID.indexOf(userID) > -1) return true;

            return false;
            break;

        case "save_risk":
            if (QRMDataService.riskID < 0) return true;
            if (typeof (QRMDataService.risk) == 'undefined') return false;
            if (QRMDataService.risk.owner == userID) return true;
            if (QRMDataService.risk.manager == userID) return true;
            if (QRMDataService.project.projectRiskManager == userID) return true;
            return false;
            break;
        case "new_incident":
            return true;
            break;
        case "new_review":
            return true;
            break;
        default:
            return false;
        }
    }

    this.logout = function () {
        remoteService.logout()
            .finally(function () {
                QRMDataService.siteUsers = null;
                QRM.mainController.risks = null;
                QRMDataService.currentUser = null;
                $state.go("login");
            });
    }

    this.toggleMenu = function () {

        var open = $("#header_container").hasClass("sideMenuOpen");
        console.log("Side Menu Open " + open);

        //Currently Open, so want to close side menu
        if (open) {
            $("#header_container").removeClass("sideMenuOpen");
            $("#footer_container").removeClass("sideMenuOpen");
            $("#cbp-spmenu-s1").removeClass("cbp-spmenu-open");
            $("body").toggleClass('cbp-spmenu-push-toright');
         } else {
            // Open the side menu
            $("#header_container").addClass("sideMenuOpen");
            $("#footer_container").addClass("sideMenuOpen");
            $("#cbp-spmenu-s1").addClass("cbp-spmenu-open");
            $("body").toggleClass('cbp-spmenu-push-toright');
        }
        
        QRM.mainController.sideOpen = !open;

//        $("#header_container").toggleClass("sideMenuOpen");
//        $("#footer_container").toggleClass("sideMenuOpen");
//        $("#cbp-spmenu-s1").toggleClass("cbp-spmenu-open");
//        $("body").toggleClass('cbp-spmenu-push-toright');
//
//        if ($("#cbp-spmenu-s1").hasClass("cbp-spmenu-open")) {
//            $("#qrm-title").removeClass("hidden-qrm");
//            $("#qrm-subtitle").removeClass("hidden-qrm");
//            $("#welcome-name").removeClass("hidden-qrm");
//            $("#qrm-titleSM").addClass("hidden-qrm");
//        } else {
//            $("#qrm-subtitle").addClass("hidden-qrm");
//            $("#qrm-title").addClass("hidden-qrm");
//            $("#welcome-name").addClass("hidden-qrm");
//            $("#qrm-titleSM").removeClass("hidden-qrm");
//        }

    }

    this.titleBar = "Please Select Project";

    this.init = function () {

        remoteService.getSiteUsers()
            .then(function (response) {
                QRMDataService.siteUsers = response.data.data;
            });

        remoteService.getCurrentUser()
            .then(function (response) {
                QRMDataService.currentUser = response.data;
                QRM.mainController.userName = QRMDataService.currentUser.data.display_name;
            });
        remoteService.getAllRisks()
            .then(function (response) {
                QRM.mainController.risks = response.data;
                QRM.mainController.risks = jQuery.grep(QRM.mainController.risks, function (value) {
                    if (value.riskProjectCode == null) return false;
                    return value != null;
                });
                QRM.mainController.risks.sort(SortByProjectCode);
                QRMDataService.risks = QRM.mainController.risks;
            });
    }

    //    this.init();
};

function ExplorerCtrl($scope, QRMDataService, $state, $timeout, remoteService, ngDialog) {

    QRM.mainController.titleBar = QRMDataService.project.title;

    QRM.expController = this;

    this.getTableHeight = function () {
        return {
            height: "calc(100vh - 320px)"
        };
    }
    this.getTableHeightSm = function () {
        return {
            height: "calc(100vh - 145px)"
        };
    }

    QRMDataService.riskID = 0;
    var exp = this;
    if (QRMDataService.project.id > 0) {
        this.project = QRMDataService.project;
    }

    //Delegate for Main controller
    $scope.checkUserCap = function (x, y) {
        return QRM.mainController.checkUserCap(x, y);
    }

    $scope.savingrisk = false;

    this.valPre = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.valPost = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    this.filterMatrixFlag = false;
    this.filterMatrixHighlightFlag = false;

    this.gridOptions = {
        enableSorting: true,
        //        minRowsToShow: 10,
        //        rowHeight: 25,
        rowTemplate: '<div ng-click="grid.appScope.editRisk(row.entity.id)" style="cursor:pointer" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
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
            }
    ]
    };
    this.gridOptionsSm = {
        enableSorting: true,
        rowTemplate: '<div ng-click="grid.appScope.editRisk(row.entity.id)" style="cursor:pointer" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
        columnDefs: [
            {
                field: 'riskProjectCode',
                enableColumnMoving: false,
                width: 80,
                name: "Risk ID",
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
                name: 'owner',
                width: 100,
                field: 'owner',
                cellFilter: 'usernameFilter'

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
        $state.go('qrm.risk');
    }

    this.newPushDownRisk = function () {

        this.pushDown = QRMDataService.getTemplateRisk();
        this.pushDown.title = "Title of the new Push Down Risk";
        this.pushDown.description = "Description of the Push Down Risk";
        this.pushDown.type = 1;
        this.pushDown.projectID = QRMDataService.project.id;
        this.pushDown.inherentProb = QRMDataService.project.matrix.maxProb + 0.5;
        this.pushDown.inherentImpact = QRMDataService.project.matrix.maxImpact + 0.5;

        var index = (Math.floor(this.pushDown.treatedProb - 1)) * QRMDataService.project.matrix.maxImpact + Math.floor(this.pushDown.treatedImpact - 1);
        index = Math.min(index, QRMDataService.project.matrix.tolString.length - 1);
        this.pushDown.treatedTolerance = QRMDataService.project.matrix.tolString.substring(index, index + 1);

        index = (Math.floor(this.pushDown.inherentProb - 1)) * QRMDataService.project.matrix.maxImpact + Math.floor(this.pushDown.inherentImpact - 1);
        index = Math.min(index, QRMDataService.project.matrix.tolString.length - 1);
        this.pushDown.inherentTolerance = QRMDataService.project.matrix.tolString.substring(index, index + 1);

        this.pushDown.currentProb = this.pushDown.inherentProb;
        this.pushDown.currentImpact = this.pushDown.inherentImpact;
        this.pushDown.currentTolerance = this.pushDown.inherentTolerance;


        ngDialog.openConfirm({
            template: "editPushDownDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            $scope.savingrisk = true;

            remoteService.newPushDownRisk(exp.pushDown)
                .then(function (response) {

                }).finally(function () {
                    remoteService.getAllRisks()
                        .then(function (response) {
                            QRM.mainController.risks = response.data;
                            QRM.mainController.risks = jQuery.grep(QRM.mainController.risks, function (value) {
                                if (value.riskProjectCode == null) return false;
                                return value != null;
                            });
                            QRM.mainController.risks.sort(SortByProjectCode);
                            QRMDataService.risks = QRM.mainController.risks;

                        }).finally(function () {
                            $scope.savingrisk = false;
                        });
                    exp.getAllProjectRisks();
                });
        });
    }

    $scope.editRisk = function (riskID) {
        exp.editRisk(riskID);
    }
    this.editRisk = function (riskID) {
        postType = null;
        QRMDataService.riskID = riskID;
        $scope.loading = true;
        remoteService.getRisk(QRMDataService.riskID, $scope)
            .then(function (response) {
                QRMDataService.pRisk = response.data;
                QRMDataService.passRisk = true;
                $state.go('qrm.risk');
            }).finally(function () {
                $scope.loading = true;
            });
    }
    this.deleteRisk = function (riskID) {
        QRMDataService.riskID = riskID;
        alert("Delete Risk: " + riskID);
    }
    this.getAllProjectRisks = function () {
        QRM.mainController.lookingForRisks();
        remoteService.getAllProjectRisks(exp.project.id)
            .then(function (response) {

                if (response.data == "0" || response.data == "-1") {
                    $state.go("login");
                    return;
                }

                if (response.data.data.length == 0) {
                    QRM.mainController.noRisksFound();
                } else {
                    QRM.mainController.risksFound();
                }
                exp.rawRisks = response.data.data;
                QRMDataService.projectRisks = response.data.data;
                exp.gridOptions.data = response.data.data;
                exp.gridOptionsSm.data = response.data.data;

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

                var winWidth = $(document).width() - 10;
                $("#container").css("width", winWidth + "px");
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
            QRM.mainController.loadingProject();
            QRMDataService.selectProject(projectID);
            this.project = QRMDataService.project;
            QRM.mainController.titleBar = this.project.title;
            this.getAllProjectRisks(this.project.id);
            this.clearFilters();

            jQuery("a.disable-noproject").removeClass("disable-link");

            $timeout(function () {
                $scope.$apply()
            });
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
    
    
    this.riskDetailReport = function(){
        remoteService.getReportRiskJSON([], QRMDataService.project.id)
            .then(function (response) {
                $('input[name="reportData"]').val(JSON.stringify(response.data));
                $('input[name="reportID"]').val(1);
                $('#reportForm').attr('action', response.data.reportServerURL);
                $("#reportForm").submit();
            }).finally(function () {
                
            });
    }
    this.riskSummaryDateFormatReport = function(){
        remoteService.getReportRiskJSON([], QRMDataService.project.id)
            .then(function (response) {
                $('input[name="reportData"]').val(JSON.stringify(response.data));
                $('input[name="reportID"]').val(2);
                $('#reportForm').attr('action', response.data.reportServerURL);
                $("#reportForm").submit();
            }).finally(function () {
                
            });
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

                if (response.data == "0" || response.data == "-1") {
                    $state.go("login");
                    return;
                } else {
                    jQuery("#explorer-wrapper").removeClass("hidden-qrm");
                }

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
    winWidth = $(window).innerWidth() - 10;
    $("#container").css("width", winWidth + "px");
    this.init();
}

function RiskCtrl($scope, $modal, QRMDataService, $state, $stateParams, $timeout, remoteService, ngNotify, ngDialog, $q) {

    var vm = this;
    this.riskID = QRMDataService.riskID;
    this.reviewType = -1;
    this.stakeholders = [];
    this.additionalHolders = [];
    this.project = QRMDataService.project;
    this.categories = QRMDataService.catData;
    this.objectives = QRMDataService.projectObjectives;
    $scope.data = {
        comment: ""
    };
    $scope.siteUsers = [];
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
                    remoteService.getAttachments(vm.riskID)
                        .then(function (response) {
                            vm.risk.attachments = response.data;
                        });
                });
            },
            sending: function (file, xhr, formData) {
                formData.append("postID", QRMDataService.riskID);
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

    this.openAuditEditor = function () {

        ngDialog.openConfirm({
            template: "registerAudit",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            if (vm.reviewType == -1) {
                ngNotify.set("Please Enter Audit Type", "grimace");
                return;
            }
            remoteService.registerAudit(vm.reviewType, vm.reviewComment, vm.risk.id)
                .then(function (response) {
                    vm.risk.audit = response.data;
                    ngNotify.set("Audit Registered", "success");
                }).finally(function () {
                    vm.reviewType = -1;
                    vm.reviewComment = "";
                });
        }, function (reason) {
            vm.reviewType = -1;
            vm.reviewComment = "";
        });
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
        $state.go('qrm.explorer');
    }
    this.getRisk = function () {

        if (isNaN(vm.riskID) || vm.riskID == 0) {
            return;
        }
        remoteService.getRisk(vm.riskID)
            .then(function (response) {
                vm.risk = response.data;
                $scope.risk = vm.risk;
                QRMDataService.risk = vm.risk;
                vm.updateRisk();
                vm.setRiskMatrixID("riskEditorMatrixID");
                vm.setRiskMatrix();
                $timeout(function () {
                    $scope.$apply()
                });
            });
    };
    this.saveRisk = function () {
        $scope.savingrisk = true;
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
                QRMDataService.risk = vm.risk;
                vm.updateRisk();
                ngNotify.set("Risk Saved", "success");
            }).finally(function () {
                $scope.savingrisk = false;
            });
    };
    this.updateRisk = function () {

        QRM.mainController.titleBar = "Risk - " + vm.risk.riskProjectCode;
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

            if (vm.risk.useCalProb) {
                vm.inherentAbsProb = calcProb(vm.risk, true);
                vm.treatedAbsProb = calcProb(vm.risk, false);
            } else {
                vm.treatedAbsProb = probFromMatrix(vm.risk.treatedProb, vm.project.matrix);
                vm.inherentAbsProb = probFromMatrix(vm.risk.inherentProb, vm.project.matrix);
            }
            
            vm.risk.inherentAbsProb = vm.inherentAbsProb;
            vm.risk.treatedAbsProb = vm.treatedAbsProb;
            
        } catch (e) {
            alert("Error" + e.message);
        }

        // Change the panel class (color) according to the current tolerance

        var panelClass;
        switch (Number(vm.risk.currentTolerance)) {
        case 5:
            panelClass = "panel-danger";
            break;
        case 4:
            panelClass = "panel-warning";
            break;
        case 3:
            panelClass = "panel-sig";
            break;
        case 2:
            panelClass = "panel-info";
            break;
        case 1:
            panelClass = "panel-success";
            break;
        }

        jQuery("div.panel")
            .removeClass("panel-info")
            .removeClass("panel-warning")
            .removeClass("panel-success")
            .removeClass("panel-danger")
            .removeClass("panel-sig")
            .addClass(panelClass);

        //Set the date controll



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

        try {
            jQuery('#exposure').data('daterangepicker').setStartDate(s);
            jQuery('#exposure').data('daterangepicker').setEndDate(e);
        } catch (e) {
            //Do nothing, will happen on mobile interface
        }
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
            cost: 0
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
            remoteService.addGeneralComment($scope.data.comment, QRMDataService.riskID)
                .then(function (response) {
                    ngNotify.set("Comment added to risk", "success");
                    vm.risk.comments = response.data;
                });
        }, function (reason) {
            // Restore the old values

        });
    }
    this.addCommentSm = function () {
        remoteService.addGeneralComment($scope.data.comment, QRMDataService.riskID)
            .then(function (response) {
                ngNotify.set("Comment added to risk", "success");
                vm.risk.comments = response.data;
            });

        $scope.data.comment = "";

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
    
    this.riskDetailReport = function(){
         remoteService.getReportRiskJSON([vm.riskID], null)
            .then(function (response) {
                $('input[name="reportData"]').val(JSON.stringify(response.data));
                $('#reportForm').attr('action', response.data.reportServerURL);
                $("#reportForm").submit();
            }).finally(function () {
                
            });
    }

    // Handle formatting of objectives
    this.rowStyle = function (e) {
        return {
            "margin-left": e.$$treeLevel * 15 + "px"
        }
    }
    this.getPanelColor = function (x) {
        if (typeof (vm.risk) == 'undefined') return false;
        if (x == 'danger' && vm.risk.currentTolerance == 5) {
            return true;
        } else if (x == 'warning' && vm.risk.currentTolerance == 4) {
            return true;
        } else if (x == 'sig' && vm.risk.currentTolerance == 3) {
            return true;
        } else if (x == 'info' && vm.risk.currentTolerance == 2) {
            return true;
        } else if (x == 'success' && vm.risk.currentTolerance == 1) {
            return true;
        } else {
            return false;
        }

    }
    this.init = function () {

        if (QRMDataService.passRisk) {
            vm.risk = QRMDataService.pRisk;
            $scope.risk = vm.risk;
            QRMDataService.risk = vm.risk;
            vm.updateRisk();
            vm.setRiskMatrixID("riskEditorMatrixID");
            vm.setRiskMatrix();
            $timeout(function () {
                $scope.$apply()
            });
            QRMDataService.passRisk = false;
            winWidth = $(window).innerWidth() - 10;
            $("#container").css("width", winWidth + "px");
            return;
        }

        if (postType == "risk") {
            vm.categories = QRMDataService.catData;
            vm.objectives = QRMDataService.projectObjectives;
            $scope.siteUsers = QRMDataService.siteUsers;
            vm.riskID = postID;
            vm.getRisk();

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
        winWidth = $(window).innerWidth() - 10;
        $("#container").css("width", winWidth + "px");
    }

    if (QRMDataService.siteUsers != null) {
        QRMDataService.siteUsers.forEach(function (e) {
            $scope.siteUsers.push(e.ID);
        });
        vm.init();
    } else {
        vm.init();
    }
}

function CalenderController($scope, QRMDataService, $state, remoteService) {

    QRM.mainController.titleBar = "Exposure Calender - " + QRMDataService.project.title;

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

    this.showFilters = function () {
        $("#calenderFilter").slideDown("slow");
    }
    this.closeFilters = function () {
        $("#calenderFilter").slideUp("slow");
    }

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
            if (risk.manager == cal.manager || cal.manager == "") {
                managerPass.push(risk);
            }
        });

        var ownerPass = new Array();
        managerPass.forEach(function (risk) {
            if (risk.owner == cal.owner || cal.owner == "") {
                ownerPass.push(risk);
            }
        });
        cal.layoutCalender(ownerPass);

        $("#calenderFilter").slideUp("slow");


    }
    this.editRisk = function (id) {
        QRMDataService.riskID = id;
        $state.go('qrm.risk');
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
        risks.forEach(function (risk) {
            tasks.push({
                "startDate": moment(risk.start),
                "endDate": moment(risk.end),
                "taskName": risk.riskProjectCode,
                "status": "RUNNING",
                "riskID": risk.id,
                "title": risk.title
            });
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

function RankController($scope, QRMDataService, $state, remoteService, ngNotify) {

    QRM.mainController.titleBar = QRMDataService.project.title;

    qrm.rankController = this;
    var rank = this;
    var myLayout;
    this.project = QRMDataService.project;
    this.editRisk = function (id) {
        QRMDataService.riskID = id;
        $state.go('qrm.risk');
    }

    this.showInstructions = true;

    this.saveChanges = function () {
        myLayout.normaliseRanks();
        var rankOrder = myLayout.orderedIDs;
        remoteService.saveRankOrder(myLayout.items).then(function () {
            ngNotify.set("Rank Order Saved", "success");
        })
    }

    this.cancelChanges = function () {
        this.loadGrid();
    }


    this.loadGrid = function () {
        QRM.mainController.titleBar = "Risk Ranking - " + QRMDataService.project.title;
        remoteService.getAllProjectRisks(QRMDataService.project.id)
            .then(function (response) {
                var risks = response.data.data;
                rank.dirty = false;
                rank.risks = risks;
                rank.layout = new SorterLayout(rank, $scope);

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

function AnalysisController($scope, QRMDataService, $state, remoteService, ngNotify) {

    var ac = this;

    $scope.getTableHeight = function () {
        return {
            height: "calc(100vh - 100px)"
        };
    }
    $scope.typeSelect = function (type) {
        $scope.chartName = type.name;
        d3.select('#chart svg').remove();
        d3.select('#chart').append("svg");

        ac.chart = nv.models.multiBarHorizontalChart()
            .x(function (d) {
                return d.label
            })
            .y(function (d) {
                return d.value
            })
            .margin({
                top: 30,
                right: 20,
                bottom: 50,
                left: 100
            })
            .stacked(true);

        //Don't show the control group if the screen is too small

        if (window.innerWidth > 991) {
            ac.chart.showControls(true); //Allow user to switch between "Grouped" and "Stacked" mode.
            //            ac.chart.showLegend(true); //Allow user to switch between "Grouped" and "Stacked" mode.
        } else {
            ac.chart.showControls(false); //Allow user to switch between "Grouped" and "Stacked" mode.
            //            ac.chart.showLegend(false); //Allow user to switch between "Grouped" and "Stacked" mode.
        }

        switch (type.id) {
        case "ownerT":
            nv.addGraph(function () {
                ac.chart.yAxis.axisLabel("Number of Risks").tickFormat(d3.format(',.2f'));
                ac.chart.xAxis.axisLabel("Risk Owner").axisLabelDistance(30);
                d3.select('#chart svg').datum(QRMDataService.owners).call(ac.chart);
                nv.utils.windowResize(ac.chart.update);
                return ac.chart;
            });

            break;
        case "managerT":
            nv.addGraph(function () {
                ac.chart.yAxis.axisLabel("Number of Risks").tickFormat(d3.format(',.2f'));
                ac.chart.xAxis.axisLabel("Risk Manager").axisLabelDistance(30);
                d3.select('#chart svg').datum(QRMDataService.managers).call(ac.chart);

                nv.utils.windowResize(ac.chart.update);

                return ac.chart;
            });
            break;
        case "cat":
            nv.addGraph(function () {
                ac.chart.yAxis.axisLabel("Number of Risks").tickFormat(d3.format(',.2f'));
                ac.chart.xAxis.axisLabel("Risk Category").axisLabelDistance(30);
                d3.select('#chart svg').datum(QRMDataService.categories).call(ac.chart);

                nv.utils.windowResize(ac.chart.update);

                return ac.chart;
            });
            break;
        case "status":
            nv.addGraph(function () {
                ac.chart.yAxis.axisLabel("Number of Risks").tickFormat(d3.format(',.2f'));
                ac.chart.xAxis.axisLabel("Risk Status").axisLabelDistance(30);
                d3.select('#chart svg').datum(QRMDataService.status).call(ac.chart);

                nv.utils.windowResize(ac.chart.update);

                return ac.chart;
            });
            break;
        }
    }

    $scope.chartName = "Risk Owner/Tolerance Allocation";
    $scope.projectTitle = QRMDataService.project.title;
    $scope.data = [
        {
            name: "Risk Owner/Tolerance Allocation",
            id: "ownerT"
            },
        {
            name: "Risk Manager/Tolerance Allocation",
            id: "managerT"
            },
        {
            name: "Category Allocation",
            id: "cat"
            },
        {
            name: "Status Allocation",
            id: "status"
            },
        {
            name: "Risk Owner/Status Allocation",
            id: "ownerS"
            },
        {
            name: "Risk Manager/Status Allocation",
            id: "managerS"
            },
        {
            name: "Risk Mitigation Cost",
            id: "mitCost"
            }
    ];

    $scope.gridOptions = {
        enableSorting: false,
        rowTemplate: '<div ng-click="grid.appScope.typeSelect(row.entity)" style="cursor:ponter" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
        columnDefs: [{
                name: 'Type',
                field: "name",
                enableFilering: false,
                enableSorting: false,
                enableHiding: false,
                cellClass: "cellPointer"
        }
        ],
        data: $scope.data
    };

    QRM.mainController.titleBar = "Analysis - " + QRMDataService.project.title;
    QRMDataService.analyseRisks();
    $scope.typeSelect($scope.gridOptions.data[0]);
}

function RelMatrixController($scope, QRMDataService, $state, remoteService, ngNotify) {

    QRM.mainController.titleBar = QRMDataService.project.title;

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

        $("#matrixFilter").slideUp("slow");

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
    this.showFilters = function () {
        $("#matrixFilter").slideDown("slow");
    }
    this.closeFilters = function () {
        $("#matrixFilter").slideUp("slow");
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

                var newTreatedImpact = (item.treatedClean) ? item.treatedImpact : item.newTreatedImpact;
                var newTreatedProb = (item.treatedClean) ? item.treatedProb : item.newTreatedProb;
                var newInherentImpact = (item.untreatedClean) ? item.inherentImpact : item.newInherentImpact;
                var newInherentProb = (item.untreatedClean) ? item.inherentProb : item.newInherentProb;

                var index = (Math.floor(newTreatedProb - 1)) * QRMDataService.project.matrix.maxImpact + Math.floor(newTreatedImpact - 1);
                index = Math.min(index, QRMDataService.project.matrix.tolString.length - 1);

                var treatedTolerance = QRMDataService.project.matrix.tolString.substring(index, index + 1);

                index = (Math.floor(newInherentProb - 1)) * QRMDataService.project.matrix.maxImpact + Math.floor(newInherentImpact - 1);
                index = Math.min(index, QRMDataService.project.matrix.tolString.length - 1);

                var inherentTolerance = QRMDataService.project.matrix.tolString.substring(index, index + 1);

                relMatChanges.push({
                    riskID: item.id,
                    newTreatedImpact: newTreatedImpact,
                    newTreatedProb: newTreatedProb,
                    newInherentImpact: newInherentImpact,
                    newInherentProb: newInherentProb,
                    treatedTolerance: treatedTolerance,
                    inherentTolerance: inherentTolerance
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
        $("#matrixFilter").slideUp("slow");
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

        $("#matrixFilter").slideUp("slow");
        this.svgMatrix(filteredRisks);

    }
    this.riskSelect = function () {
        $("#matrixFilter").slideUp("slow");

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
        this.closeFilters();
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
            right: 27,
            bottom: 45,
            left: 27
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
                "rect.tolNoHover5 {fill: #ed5565;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tolNoHover4 {fill: #f8ac59;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tolNoHover3 {fill: #ffff55;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tolNoHover2 {fill: #1ab394;stroke: #E6E6E6;stroke-width: 2px; }" +
                "rect.tolNoHover1 {fill: #1c84c6; stroke: #E6E6E6; stroke-width: 2px; }" +
                "g.state circle {stroke  : gray; cursor  : pointer;}" +
                "g.state circle.inner { fill : white;}" +
                "g.state circle.outer { display : none; stroke-dasharray: 4px;  stroke-opacity  : 0.5;}" +
                "text.chartTitle { fill:rgb(103,106,108) }" +
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
            .attr("class", "chartTitle")
            .attr("transform", "translate(" + [(width / 2), height + 20] + ")")
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
            .attr("class", "chartTitle")
            .attr("transform", "translate(" + [(width / 2 + margin.left), 20] + ")")
            .text(QRMDataService.project.title);

        topSVG.append("text")
            .attr("text-anchor", "middle")
            .attr("id", "relMatrixSubHeading")
            .style("font-size", "15px")
            .style("font-weight", "normal")
            .attr("class", "chartTitle")
            .attr("transform", "translate(" + [width / 2 + margin.left, 38] + ")")
            .text(state);

        svg.append("text")
            .attr("text-anchor", "middle")
            .style("font-size", "20px")
            .style("font-weight", "normal")
            .attr("transform", "translate(" + [-10, height / 2] + ") rotate(-90)")
            .attr("class", "chartTitle")
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
        QRM.mainController.titleBar = "Tolerance Matrix - " + QRMDataService.project.title;
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

function IncidentExplCtrl($scope, $modal, QRMDataService, $state, $stateParams, $timeout, remoteService, ngNotify, ngDialog) {

    var incident = this;
    this.loading = false;

    $scope.formatTreatedCol = function (row, check) {
        if (row.entity.resolved && check) {
            return true;
        }
        if (!row.entity.resolved && !check) {
            return true;
        }
        return false;
    };
    this.getTableHeight = function () {
        return {
            height: "calc(100vh - 100px)"
        };
    }
    this.getTableHeightSM = function () {
        return {
            height: "calc(100vh - 105px)"
        };
    }
    this.gridOptions = {
        enableSorting: true,
        rowTemplate: '<div ng-click="grid.appScope.editIncident(row.entity.id)" style="cursor:pointer" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
        columnDefs: [
            {
                width: "100",
                cellClass: 'compact',
                field: 'incidentCode',
                headerCellClass: 'header-hidden',

            }, {
                name: 'title',
                width: "*",
                cellClass: 'compact',
                field: 'title'

            },
            {
                name: 'Incident Date',
                width: 140,
                field: 'date',
                cellFilter: "date"
            },
            {
                name: 'Resolved',
                width: 70,
                field: 'resolved',
                cellTemplate: '<i style="color:green" ng-show="grid.appScope.formatTreatedCol(row, true)" class="fa fa-check"></i><i  style="color:red" ng-show="grid.appScope.formatTreatedCol(row, false)" class="fa fa-close"></i>',
                cellClass: 'cellCentered'

            },
            {
                name: 'Reported',
                width: 140,
                field: 'reportedby',
                cellFilter: 'usernameFilter'

            }
    ]
    };
    this.gridOptionsSM = {
        enableSorting: true,
        rowTemplate: '<div ng-click="grid.appScope.editIncident(row.entity.id)" style="cursor:pointer" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
        columnDefs: [
            {
                width: "100",
                cellClass: 'compact',
                field: 'incidentCode',
                headerCellClass: 'header-hidden',

            }, {
                name: 'title',
                width: "*",
                cellClass: 'compact',
                field: 'title'

            },
            {
                name: 'Resolved',
                width: 70,
                field: 'resolved',
                cellTemplate: '<i style="color:green" ng-show="grid.appScope.formatTreatedCol(row, true)" class="fa fa-check"></i><i  style="color:red" ng-show="grid.appScope.formatTreatedCol(row, false)" class="fa fa-close"></i>',
                cellClass: 'cellCentered'

            }
    ]
    };
    this.init = function () {
        QRM.mainController.titleBar = "Incidents";
        QRM.mainController.lookingForIncidents();
        remoteService.getAllIncidents()
            .then(function (response) {
                incident.gridOptions.data = response.data;
                incident.gridOptionsSM.data = response.data;
                if (incident.gridOptions.data.length > 0) {
                    QRM.mainController.incidentsFound();
                } else {
                    QRM.mainController.noIncidentsFound();
                }
            }).finally(function () {

            });
    }

    $scope.editIncident = function (id) {
        incident.editIncident(id);
    }

    this.editIncident = function (id) {
        incident.loading = true;
        remoteService.getIncident(id)
            .then(function (response) {

                QRMDataService.incident = response.data;
                if (QRMDataService.incident.risks != null) {
                    QRMDataService.incident.risks.sort(SortByProjectCode);
                }
                QRMDataService.incidentID = QRMDataService.incident.id;
                QRMDataService.incident.date = new Date(QRMDataService.incident.date);

                incident.loading = false;
                $state.go("qrm.incident");
            });
    }
    this.newIncident = function () {
        QRMDataService.incidentID = -1;
        $state.go('qrm.incident');
    }
    this.init();
}

function IncidentCtrl($scope, $modal, QRMDataService, $state, $stateParams, $timeout, remoteService, ngNotify, ngDialog) {
    var inc = this;
    //    this.siteUsers = QRMDataService.siteUsers;
    this.siteUsers = [];
    $scope.data = {};
    $scope.savingincident = false;
    QRMDataService.siteUsers.forEach(function (e) {
        inc.siteUsers.push(e.ID);
    });

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
                    inc.incidentAttachmentReady(this, file);
                });
                this.on('complete', function (file) {
                    file.previewElement.classList.add('dz-complete');
                    inc.cancelAttachment()
                    ngNotify.set("Attachment Added to Incident", "success");
                    remoteService.getAttachments(inc.incident.id)
                        .then(function (response) {
                            inc.incident.attachments = response.data;
                        });
                });
            },
            sending: function (file, xhr, formData) {
                formData.append("postID", QRMDataService.incidentID);
                formData.append("description", inc.uploadAttachmentDescription);
            }
        },
    };

    this.addRisk = function () {
        if (typeof (inc.incident.risks) == "undefined") {
            inc.incident.risks = [];
        }
        if (inc.riskID != null) {
            inc.incident.risks.push(inc.riskID);
            var unq = [];
            $.each(inc.incident.risks, function (i, el) {
                if ($.inArray(el, unq) === -1) unq.push(el);
            });
            inc.incident.risks = unq

            inc.incident.risks.sort(SortByProjectCode);
        }
        inc.riskID = null;
    }
    this.removeRisk = function (riskID) {
        inc.incident.risks = jQuery.grep(inc.incident.risks, function (value) {
            return value != riskID;
        });
    }
    this.incidentAttachmentReady = function (dropzone, file) {
        inc.dropzone = dropzone;
        inc.dzfile = file;
        inc.disableAttachmentButon = false;
        $scope.$apply();
    }

    this.uploadAttachmentDescription = "";
    this.disableAttachmentButon = true;
    this.dropzone = "";
    this.uploadAttachment = function () {
        inc.dropzone.processFile(inc.dzfile);
    }
    this.cancelAttachment = function () {
        inc.dropzone.removeAllFiles(true);
        inc.uploadAttachmentDescription = null;
        inc.disableAttachmentButon = true;
        inc.dropzone = null;
        inc.dzfile = null;
        $scope.$apply();
    }

    this.cancelIncident = function () {
        QRMDataService.incident = null;
        QRMDataService.incidentID = -1;
        $state.go("qrm.incidentExpl");
    }

    this.updateIncident = function () {

        QRM.mainController.titleBar = inc.incident.incidentCode;
        if (inc.incident.risks != null) {
            inc.incident.risks.sort(SortByProjectCode);
        }

    }

    this.openDescriptionEditor = function () {
        var oTitle = inc.incident.title;
        var oDescription = inc.incident.description;
        ngDialog.openConfirm({
            template: "editIncidentTitleModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            inc.incident.title = oTitle;
            inc.incident.description = oDescription;
        });
    }
    this.openActionsEditor = function () {
        var oActions = inc.incident.actions;
        ngDialog.openConfirm({
            template: "editActionsModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            inc.incident.actions = oActions;
        });
    }
    this.openLessonsEditor = function () {
        var oLessons = inc.incident.lessons;
        ngDialog.openConfirm({
            template: "editLessonsModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            inc.incident.lessons = oLessons;
        });
    }
    this.addComment = function () {
        $scope.data.comment = "";
        ngDialog.openConfirm({
            template: "addIncidentCommentModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            remoteService.addGeneralComment($scope.data.comment, QRMDataService.incidentID)
                .then(function (response) {
                    ngNotify.set("Comment Added to Incident", "success");
                    inc.incident.comments = response.data;
                });
        }, function (reason) {
            // Restore the old values

        });
    }
    this.addCommentSM = function () {
        remoteService.addGeneralComment($scope.data.comment, QRMDataService.incidentID)
            .then(function (response) {
                ngNotify.set("Comment Added to Incident", "success");
                inc.incident.comments = response.data;
                $scope.data.comment = "";
            });
    }

    this.saveIncident = function () {

        $scope.savingincident = true;
        if (inc.incident.risks != null) {
            inc.incident.risks.sort(SortByProjectCode);
        }
        remoteService.saveIncident(inc.incident)
            .then(function (response) {
                $scope.savingincident = false;
                ngNotify.set("Incident Saved", "success");
                inc.incident = response.data;
                inc.updateIncident();
            });
    }

    if (QRMDataService.incidentID == -1) {
        inc.incident = {
            title: "Incident Title",
            description: "Description of the incident",
            id: -1,
            incidentCode: "New Incident",
            resolved: false,
            identified: false,
            evaluated: false,
            controls: false,
            consequences: false,
            causes: false,
            reportedby: 0,
            lessons: "Enter the lessons learnt as a result of this incident",
            actions: "Enter a summary of actions taken to resolve the incident",
            date: new Date()
        }
    } else {
        inc.incident = QRMDataService.incident;
    }

    this.updateIncident();

}

function ReviewExplCtrl($scope, $modal, QRMDataService, $state, $stateParams, $timeout, remoteService, ngNotify, ngDialog) {

    var review = this;
    this.loading = false;
    $scope.formatTreatedCol = function (row, check) {
        if (row.entity.complete && check) {
            return true;
        }
        if (!row.entity.complete && !check) {
            return true;
        }
        return false;
    };
    this.getTableHeight = function () {
        return {
            height: "calc(100vh - 100px)"
        };
    }
    this.getTableHeightSM = function () {
        return {
            height: "calc(100vh - 105px)"
        };
    }

    this.gridOptions = {
        enableSorting: true,
        rowTemplate: '<div ng-click="grid.appScope.editReview(row.entity.id)" style="cursor:pointer" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
        columnDefs: [
            {
                width: "100",
                cellClass: 'compact',
                field: 'reviewCode',
                headerCellClass: 'header-hidden',

            }, {
                name: 'title',
                width: "*",
                cellClass: 'compact',
                field: 'title'

            },
            {
                name: 'Scheduled Date',
                width: 140,
                field: 'scheddate',
                cellFilter: 'date'
            },
            {
                name: 'Actual Date',
                width: 140,
                field: 'actualdate',
                cellFilter: 'date'
            },
            {
                name: 'Complete',
                width: 70,
                field: 'complete',
                cellTemplate: '<i style="color:green" ng-show="grid.appScope.formatTreatedCol(row, true)" class="fa fa-check"></i><i  style="color:red" ng-show="grid.appScope.formatTreatedCol(row, false)" class="fa fa-close"></i>',
                cellClass: 'cellCentered'

            },
            {
                name: 'Responsible',
                width: 140,
                field: 'responsible',
                cellFilter: 'usernameFilter'

            }
    ]
    };
    this.gridOptionsSM = {
        enableSorting: true,
        rowTemplate: '<div ng-click="grid.appScope.editReview(row.entity.id)" style="cursor:pointer" ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader }"  ui-grid-cell></div>',
        columnDefs: [
            {
                width: "100",
                cellClass: 'compact',
                field: 'reviewCode',
                headerCellClass: 'header-hidden',

            }, {
                name: 'title',
                width: "*",
                cellClass: 'compact',
                field: 'title'

            },
            {
                name: 'Scheduled Date',
                width: 90,
                field: 'scheddate',
                cellFilter: 'date'
            }
    ]
    };
    $scope.editReview = function (id) {
        review.editReview(id);
    }
    this.editReview = function (id) {
        review.loading = true;
        remoteService.getReview(id)
            .then(function (response) {

                QRMDataService.review = response.data;
                if (QRMDataService.review.risks != null) {
                    QRMDataService.review.risks.sort(SortByProjectCode);
                }
                QRMDataService.reviewID = QRMDataService.review.id;
                QRMDataService.review.actualdate = new Date(QRMDataService.review.actualdate);
                QRMDataService.review.scheddate = new Date(QRMDataService.review.scheddate);
                review.loading = false;
                $state.go("qrm.review");
            });
    }
    this.newReview = function () {
        QRMDataService.reviewID = -1;
        $state.go('qrm.review');
    }

    this.init = function () {
        QRM.mainController.titleBar = "Reviews";
        QRM.mainController.lookingForReviews();
        remoteService.getAllReviews()
            .then(function (response) {
                review.gridOptions.data = response.data;
                review.gridOptionsSM.data = response.data;
                if (review.gridOptions.data.length > 0) {
                    QRM.mainController.reviewsFound();
                } else {
                    QRM.mainController.noReviewsFound();
                }
            }).finally(function () {
                review.loading = false;
            });
    }

    this.init();

}

function ReviewCtrl($scope, $modal, QRMDataService, $state, $stateParams, $timeout, remoteService, ngNotify, ngDialog) {
    var rev = this;
    //    this.siteUsers = QRMDataService.siteUsers;
    this.siteUsers = [];
    this.sortedParents = QRMDataService.sortedParents;
    if (this.sortedParents == null) {
        remoteService.getProjects()
            .then(function (response) {
                QRMDataService.handleGetProjects(response);
                rev.sortedParents = QRMDataService.sortedParents;
            });
    }
    $scope.data = {};
    $scope.savingreview = false;
    QRMDataService.siteUsers.forEach(function (e) {
        rev.siteUsers.push(e.ID);
    });

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
                    rev.reviewAttachmentReady(this, file);
                });
                this.on('complete', function (file) {
                    file.previewElement.classList.add('dz-complete');
                    rev.cancelAttachment()
                    ngNotify.set("Attachment Added to Review", "success");
                    remoteService.getAttachments(rev.review.id)
                        .then(function (response) {
                            rev.review.attachments = response.data;
                        });
                });
            },
            sending: function (file, xhr, formData) {
                formData.append("postID", QRMDataService.reviewID);
                formData.append("description", rev.uploadAttachmentDescription);
            }
        },
    };

    // Handle formatting of select project pick box
    this.rowStyle = function (e) {
        return {
            "margin-left": e.$$treeLevel * 20 + "px"
        }
    }
    this.addRisk = function () {
        if (typeof (rev.review.risks) == "undefined") {
            rev.review.risks = [];
        }
        if (rev.riskID != null) {
            rev.review.risks.push(rev.riskID);
            var unq = [];
            $.each(rev.review.risks, function (i, el) {
                if ($.inArray(el, unq) === -1) unq.push(el);
            });
            rev.review.risks = unq
            rev.review.risks.sort(SortByProjectCode);
        }
        rev.riskID = null;
    }
    this.addProjectRisk = function () {
        if (typeof (rev.review.risks) == "undefined") {
            rev.review.risks = [];
        }
        if (rev.projectID != null) {
            QRMDataService.risks.forEach(function (r) {
                if (r.projectID == rev.projectID) {
                    rev.review.risks.push(r.id);
                }
            })

            var unq = [];
            $.each(rev.review.risks, function (i, el) {
                if ($.inArray(el, unq) === -1) unq.push(el);
            });
            rev.review.risks = unq
            rev.review.risks.sort(SortByProjectCode);
        }
        rev.projectID = null;
    }
    this.removeRisk = function (riskID) {
        rev.review.risks = jQuery.grep(rev.review.risks, function (value) {
            return value != riskID;
        });

        rev.review.riskComments = jQuery.grep(rev.review.riskComments, function (value) {
            return value.riskID != riskID;
        });
    }
    this.reviewAttachmentReady = function (dropzone, file) {
        rev.dropzone = dropzone;
        rev.dzfile = file;
        rev.disableAttachmentButon = false;
        $scope.$apply();
    }

    this.uploadAttachmentDescription = "";
    this.disableAttachmentButon = true;
    this.dropzone = "";
    this.uploadAttachment = function () {
        rev.dropzone.processFile(rev.dzfile);
    }
    this.cancelAttachment = function () {
        rev.dropzone.removeAllFiles(true);
        rev.uploadAttachmentDescription = null;
        rev.disableAttachmentButon = true;
        rev.dropzone = null;
        rev.dzfile = null;
        $scope.$apply();
    }

    this.cancelReview = function () {
        QRMDataService.review = null;
        QRMDataService.reviewID = -1;
        $state.go("qrm.reviewExpl");
    }

    this.updateReview = function () {

        QRM.mainController.titleBar = rev.review.reviewCode;

        if (rev.review.risks != null) {
            rev.review.risks.sort(SortByProjectCode);
        }
    }

    this.openDescriptionEditor = function () {
        var oTitle = rev.review.title;
        var oDescription = rev.review.description;
        ngDialog.openConfirm({
            template: "editReviewTitleModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            rev.review.title = oTitle;
            rev.review.description = oDescription;
        });
    }
    this.openNotesEditor = function () {
        var oNotes = rev.review.notes;
        ngDialog.openConfirm({
            template: "editReviewNotesModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            // Success. 
        }, function (reason) {
            rev.review.notes = oNotes;
        });
    }

    this.addComment = function () {
        $scope.data.comment = "";
        ngDialog.openConfirm({
            template: "addReviewCommentModalDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            remoteService.addGeneralComment($scope.data.comment, QRMDataService.reviewID)
                .then(function (response) {
                    ngNotify.set("Comment Added to Review", "success");
                    rev.review.comments = response.data;
                });
        }, function (reason) {
            // Restore the old values

        });
    }
    this.addCommentSM = function () {
        remoteService.addGeneralComment($scope.data.comment, QRMDataService.incidentID)
            .then(function (response) {
                ngNotify.set("Comment Added to Review", "success");
                rev.review.comments = response.data;
                $scope.data.comment = "";
            });
    }

    this.addCommonRiskComment = function () {
        if (rev.review.riskComments == null) {
            rev.review.riskComments = [];
        }

        rev.review.risks.forEach(function (riskID) {
            var riskComment = $.grep(rev.review.riskComments, function (e) {
                return e.riskID == riskID
            });
            if (riskComment.length > 0) {
                riskComment[0].comment = riskComment[0].comment + "<p>" + rev.commonComment + "</p>";
            } else {
                var newRiskComment = {
                    "riskID": riskID,
                    "comment": "<p>" + rev.commonComment + "</p>"
                }
                rev.review.riskComments.push(newRiskComment);
            }
        });
        rev.commomComment = null;
    }
    this.getRiskComment = function (riskID) {

        if (rev.review.riskComments == null) return;
        var riskComment = $.grep(rev.review.riskComments, function (e) {
            return e.riskID == riskID
        });

        if (riskComment == null) return;

        if (riskComment.length > 0) {
            return riskComment[0].comment;
        }

    }
    this.riskComment = function (riskID) {
        if (rev.review.riskComments == null) {
            rev.review.riskComments = [];
        }

        var riskComment = $.grep(rev.review.riskComments, function (e) {
            return e.riskID == riskID
        });
        if (riskComment.length > 0) {
            this.rc = riskComment[0];
        } else {
            this.rc = {
                riskID: riskID,
                comment: ""
            }
            rev.review.riskComments.push(this.rc);
        }

        var oComment = this.rc.comment;
        rev.riskForComment = riskID;
        ngDialog.openConfirm({
            template: "editReviewRiskCommentDialogId",
            className: 'ngdialog-theme-default',
            scope: $scope,
        }).then(function (value) {
            console.log(JSON.stringify(rev.review));
        }, function (reason) {
            this.rc.comment = oComment;
        });
    }

    this.saveReview = function () {

        $scope.savingreview = true;
        if (rev.review.risks != null) {
            rev.review.risks.sort(SortByProjectCode);
        }
        remoteService.saveReview(rev.review)
            .then(function (response) {
                $scope.savingreview = false;
                ngNotify.set("Review Saved", "success");
                rev.review = response.data;
                rev.updateReview();
            });
    }

    if (QRMDataService.reviewID == -1) {
        rev.review = {
            title: "Review Title",
            description: "Purpose of the Review",
            scheddate: new Date(),
            actualdate: new Date(),
            id: -1,
            reviewCode: "REVIEW-??",
            responsible: 0,
            notes: "Enter any general notes about the review"
        }
    } else {
        rev.review = QRMDataService.review;
    }

    this.updateReview();

}

function LoginCtrl($scope, $state, QRMDataService, $timeout, remoteService) {

    var login = this;
    this.showError = false;
    this.username = "";
    this.pass = "";

    this.login = function () {
        login.showError = false;
        remoteService.login(login.username, login.pass)
            .then(function (response) {
                var result = response.data;
                if (result.loggedin == true) {
                    if (!result.qrmuser) {
                        $state.go("nonQRM");
                    } else {
                        login.showError = false;
                        login.username = "";
                        login.pass = "";
                        QRM.mainController.init();
                        $state.go("qrm.explorer");
                    }
                } else {
                    login.showError = true;
                    login.username = "";
                    login.pass = "";
                }
            });
    }
}

var app = angular.module('qrm');

(function () {

    app.config(["$locationProvider", function ($locationProvider) {
        $locationProvider.html5Mode({
            requireBase: false,
            enabled: true
        });

}]);
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
    app.config(['cfpLoadingBarProvider', function (cfpLoadingBarProvider) {
        cfpLoadingBarProvider.includeSpinner = false;
  }]);
    app.config(function ($provide) {
        // this demonstrates how to register a new tool and add it to the default toolbar
        //    $provide.decorator('taOptions', ['taRegisterTool', '$delegate', function (taRegisterTool, taOptions) { // $delegate is the taOptions we are decorating
        //        taOptions.toolbar = [
        //      ['h1', 'h2', 'h3', 'h4', 'p'],
        //      ['bold', 'italics', 'underline', 'strikeThrough', 'ul', 'ol', 'redo', 'undo', 'clear', 'html'],
        //      ['justifyLeft', 'justifyCenter', 'justifyRight', 'indent', 'outdent']
        //  ];
        //        return taOptions;
        //  }]);

        $provide.decorator('taOptions', ['taRegisterTool', '$delegate', function (taRegisterTool, taOptions) { // $delegate is the taOptions we are decorating
            taOptions.toolbar = [
      ['h1', 'h2', 'p'],
      ['bold', 'italics', 'underline', 'strikeThrough', 'ul', 'ol', 'redo'],
      ['justifyRight', 'indent', 'outdent']
  ];
            return taOptions;
  }]);
    });
    app.controller('IntroCtrl', ['$scope', 'QRMDataService', 'RemoteService', '$state', '$timeout', '$q', IntroCtrl]);
    app.controller('QRMCtrl', ['$scope', 'QRMDataService', 'RemoteService', '$state', '$timeout', '$q', QRMCtrl]);
    app.controller('NonQRMCtrl', ['$scope', 'QRMDataService', 'RemoteService', '$state', '$timeout', '$q', NonQRMCtrl]);
    app.controller('MainCtrl', ['QRMDataService', 'RemoteService', '$state', MainCtrl]);
    app.controller('ExplorerCtrl', ['$scope', 'QRMDataService', '$state', '$timeout', 'RemoteService', 'ngDialog', ExplorerCtrl]);
    app.controller('RiskCtrl', ['$scope', '$modal', 'QRMDataService', '$state', '$stateParams', '$timeout', 'RemoteService', 'ngNotify', 'ngDialog', '$q', RiskCtrl]);
    app.controller('CalenderController', ['$scope', 'QRMDataService', '$state', 'RemoteService', CalenderController]);
    app.controller('RankController', ['$scope', 'QRMDataService', '$state', 'RemoteService', 'ngNotify', RankController]);
    app.controller('AnalysisController', ['$scope', 'QRMDataService', '$state', 'RemoteService', 'ngNotify', AnalysisController]);
    app.controller('RelMatrixController', ['$scope', 'QRMDataService', '$state', 'RemoteService', 'ngNotify', RelMatrixController]);
    app.controller('IncidentExplCtrl', ['$scope', '$modal', 'QRMDataService', '$state', '$stateParams', '$timeout', 'RemoteService', 'ngNotify', 'ngDialog', IncidentExplCtrl]);
    app.controller('IncidentCtrl', ['$scope', '$modal', 'QRMDataService', '$state', '$stateParams', '$timeout', 'RemoteService', 'ngNotify', 'ngDialog', IncidentCtrl]);
    app.controller('ReviewExplCtrl', ['$scope', '$modal', 'QRMDataService', '$state', '$stateParams', '$timeout', 'RemoteService', 'ngNotify', 'ngDialog', ReviewExplCtrl]);
    app.controller('ReviewCtrl', ['$scope', '$modal', 'QRMDataService', '$state', '$stateParams', '$timeout', 'RemoteService', 'ngNotify', 'ngDialog', ReviewCtrl]);
    app.controller('LoginCtrl', ['$scope', '$state', 'QRMDataService', '$timeout', 'RemoteService', LoginCtrl]);

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
    app.filter('nullFilter', function () {
        return function (value) {
            if (typeof (value) == 'undefined' || value == null) {
                return "-";
            } else {
                return value;
            }
        };
    });
    app.filter('usernameFilter', ['QRMDataService', 'RemoteService', '$q', function (QRMDataService, remoteService, $q) {
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
    app.filter('compoundRiskFilter', ['QRMDataService', function (QRMDataService) {
        return function (input) {

            if (typeof (input) == "object") return input.riskProjectCode + " - " + input.title;
            var risk = $.grep(QRMDataService.risks, function (e) {
                return e.id == input
            })
            return risk[0].riskProjectCode + " - " + risk[0].title;
        }
}]);
    app.filter('riskCodeFilter', ['QRMDataService', 'RemoteService', '$q', function (QRMDataService, remoteService, $q) {
        return function (input) {
            if (typeof (input) == "object") return input.riskProjectCode;

            var risk = $.grep(QRMDataService.risks, function (e) {
                return e.id == input
            })
            return risk[0].riskProjectCode;
        }
}]);
    app.filter('riskTitleFilter', ['QRMDataService', function (QRMDataService) {
        return function (input) {

            if (typeof (input) == "object") return input.title;
            var risk = $.grep(QRMDataService.risks, function (e) {
                return e.id == input
            })
            return risk[0].title
        }
}]);
})();