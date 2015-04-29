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
        var r =  ( this.filterMatrixHighlightFlag && this.filterOptions.matrixProb == prob &&
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

var app = angular.module('qrm');

app.controller('ExplorerCtrl', ['$scope', 'QRMDataService', '$state', 'riskService', ExplorerCtrl]);
app.controller('RiskCtrl', ['$scope', '$modal', 'QRMDataService', '$state', '$stateParams', 'riskService', 'notify', RiskCtrl]);