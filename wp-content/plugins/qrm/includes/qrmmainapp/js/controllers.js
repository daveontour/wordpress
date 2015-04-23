function ExplorerCtrl($scope, QRMDataService, $state, riskService) {

    QRMDataService.riskID = 0;
    $scope.project = QRMDataService.project;

    $scope.valPre = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $scope.valPost = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    $scope.gridOptions = {
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
//             {
//                name: "currentProb"
//            },
//            {
//                name: "currentImpact"
//            },
//              {
//                name: "treated"
//            },
//          {
//                name: "inherentProb"
//            },
//            {
//                name: "inherentImpact"
//            },
//            {
//                name: "treatedProb"
//            },
//            {
//                name: "treatedImpact"
//            },
            {
                name: 'owner',
                width: 140,
                cellClass: 'compact',
                field: 'owner.name'

            },
            {
                name: 'manager',
                width: 140,
                cellClass: 'compact',
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
                cellClass: 'cellCentered compact'

            }

    ]
    };

    $scope.ignoreOptionChange = false;


    $scope.resetFilter = function () {
        // used for flagging clearance of matrix highlights
        $scope.filterMatrix = false;
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

    $scope.filterOptions = $scope.resetFilter();
    $scope.$watch("filterOptions", function () {
        if (!$scope.ignoreOptionChange) {
            $scope.filterRisks();
        }
    }, true);

    // General purpose functions
    $scope.editRisk = function (riskID) {
        QRMDataService.riskID = riskID;
        $state.go('index.risk');
    }
    $scope.deleteRisk = function (riskID) {
        QRMDataService.riskID = riskID;
        alert("Delete Risk: " + riskID);
    }
    $scope.getRisks = function () {
        riskService.getRisks(QRMDataService.url)
            .then(function (response) {

                $scope.rawRisks = response.data.data;
                $scope.gridOptions.data = response.data.data;

                var maxImpact = Number(QRMDataService.project.matrix.maxImpact);
                var maxProb = Number(QRMDataService.project.matrix.maxProb);


                for (var i = 0; i < maxImpact * maxProb; i++) {
                    $scope.valPre[i] = 0;
                    $scope.valPost[i] = 0;
                }


                response.data.data.forEach(function (el) {
                    var iP = Math.floor(Number(el.inherentProb));
                    var iI = Math.floor(Number(el.inherentImpact));
                    var tP = Math.floor(Number(el.treatedProb));
                    var tI = Math.floor(Number(el.treatedImpact));


                    $scope.valPre[((iP - 1) * maxImpact) + iI - 1] ++;
                    $scope.valPost[((tP - 1) * maxImpact) + tI - 1] ++;

                });

                //                setMatrix(QRMDataService.project.matrix.tolString, maxImpact, maxProb, valPre, "#svgDIVPreMit", false, $scope.matrixFilter);
                //                setMatrix(QRMDataService.project.matrix.tolString, maxImpact, maxProb, valPost, "#svgDIVPostMit", true, $scope.matrixFilter);
                $scope.filterRisks();
            });

    }

    // Filtering functions
    $scope.filterRisks = function () {

        $scope.filterMatrix = $scope.filterOptions.filterMatrix;

        $scope.gridOptions.data = [];
        $scope.rawRisks.forEach(function (r) {
            // Reject the risk until it's passes
            var pass = false;

            if ($scope.filterOptions.filterMatrix) {

                debugger;

                var i;
                var p;

                if ($scope.filterOptions.matrixTreated) {
                    i = Math.floor(Number(r.treatedImpact));
                    p = Math.floor(Number(r.treatedProb));
                } else {
                    i = Math.floor(Number(r.inherentImpact));
                    p = Math.floor(Number(r.inherentProb));
                }

                if (i == $scope.filterOptions.matrixImpact && p == $scope.filterOptions.matrixProb) {
                    pass = true;
                }

            } else {

                if ($scope.filterOptions.treated && r.treated) pass = true;
                if ($scope.filterOptions.untreated && !r.treated) pass = true;

                if (!pass) return;
                pass = false;

                if ($scope.filterOptions.tolEx && Number(r.currentTolerance) == 5) pass = true;
                if ($scope.filterOptions.tolHigh && Number(r.currentTolerance) == 4) pass = true;
                if ($scope.filterOptions.tolSig && Number(r.currentTolerance) == 3) pass = true;
                if ($scope.filterOptions.tolModerate && Number(r.currentTolerance) == 2) pass = true;
                if ($scope.filterOptions.tolLow && Number(r.currentTolerance) == 1) pass = true;

                if (!pass) return;
                pass = false;

                var own = $scope.filterOptions.owner.name;
                var man = $scope.filterOptions.manager.name;
                var ownPass = false;
                var manPass = false;

                ownPass = (own == r.owner || own == undefined || own == null);
                manPass = (man == r.manager || man == undefined || man == null);
                if (ownPass && manPass) {
                    pass = true;
                }

                if (!pass) return;

                pass = false;

                debugger;
                //Filter on exposure;

                var now = moment();
                
                var endDiff = now.diff(moment(r.end));
                var startDiff = now.diff(moment(r.start));

                if ($scope.filterOptions.expInactive && endDiff > 0) pass = true;
                if ($scope.filterOptions.expPending && startDiff < 0) pass = true;
                if ($scope.filterOptions.expActive && startDiff > 0 && endDiff < 0) pass = true;
                
                }



            if (!pass) return;
            if (pass) $scope.gridOptions.data.push(r);
        });
    }
    $scope.matrixFilter = function (impact, prob, treated) {

        $scope.filterOptions = $scope.resetFilter()

        $scope.filterOptions.matrixProb = prob;
        $scope.filterOptions.matrixImpact = impact;
        $scope.filterOptions.matrixTreated = treated;
        $scope.filterOptions.filterMatrix = true;

        $scope.filterMatrix = true;

        $scope.filterRisks();

        //Unset the matrix filtering option, without initiating a re filter because of the change
        $scope.ignoreOptionChange = true;
        $scope.filterOptions.filterMatrix = false;
        $scope.$apply();
        $scope.ignoreOptionChange = false;

    }
    $scope.clearFilters = function () {


        var resetClassName = "tol" + QRMDataService.selectedCellTol + " qrmMatElementID" + QRMDataService.selectedCellImpact + QRMDataService.selectedCellProb;
        if (QRMDataService.selectedCellTreated) {
            resetClassName = resetClassName + "T";
        } else {
            resetClassName = resetClassName + "U";
        }

        d3.select("rect.selectedMatCell").attr("class", resetClassName);
        $scope.filterOptions = $scope.resetFilter();
    }


    // Control the appearance of the matrix cells
    $scope.getCellValue = function (prob, impact, treated) {
        if (treated) {
            var val = $scope.valPost[(prob - 1) * QRMDataService.project.matrix.maxImpact + impact - 1];
            return (val == 0) ? "" : val
        } else {
            var val = $scope.valPre[(prob - 1) * QRMDataService.project.matrix.maxImpact + impact - 1];
            return (val == 0) ? "" : val
        }

    }
    $scope.cellHighlight = function (prob, impact, treated) {
        return ($scope.filterMatrix && $scope.filterOptions.matrixProb == prob &&
            $scope.filterOptions.matrixImpact == impact && $scope.filterOptions.matrixTreated == treated)
    }
    $scope.cellClass = function (prob, impact, tol) {
        var index = (prob - 1) * QRMDataService.project.matrix.maxImpact + impact - 1;
        return (Number(QRMDataService.project.matrix.tolString.substring(index, index + 1)) == tol)
    }


    // Initial filling of the grid
    $scope.getRisks();

}

var app = angular.module('qrm');

app.controller('ExplorerCtrl', ['$scope', 'QRMDataService', '$state', 'riskService', ExplorerCtrl]);
app.controller('RiskCtrl', ['$scope', '$modal', 'QRMDataService', '$state', '$stateParams', 'riskService', RiskCtrl]);
app.controller('ModalInstanceCtrl', ['$scope', '$modalInstance', 'text', 'item', ModalInstanceCtrl]);
app.controller('ModalInstanceCtrlMitigation', ['$scope', '$modalInstance', 'title', 'plan', 'update', ModalInstanceCtrlMitigation]);