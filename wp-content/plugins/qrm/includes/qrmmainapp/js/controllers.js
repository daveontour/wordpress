function MainCtrl() {

    this.risk = {
        title: "Risk Title",
        description: "Description of the Risk",
        cause: "Possible causes of the risk"
    };
    this.hello = function () {
        alert("Hello");
    };
    this.test = "Initial Setting";
    this.userName = 'Example user';
    this.helloText = 'Welcome in SeedProject';
    this.descriptionText = 'It is an application skeleton for a typical AngularJS web app. You can use it to quickly bootstrap your angular webapp projects and dev environment for these projects.';

}

function ExplorerCtrl($scope, $modal, QRMDataService, $http, $state, riskService) {

    QRMDataService.riskID = 0;

    $scope.project = QRMDataService.project;
    

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
            {
                name: "inherentProb"
            },
            {
                name: "inherentImpact"
            },
            {
                name: "treatedProb"
            },
            {
                name: "treatedImpact"
            },
            {
                name: 'owner',
                width: 150,
                cellClass: 'compact'

            },
            {
                name: 'manager',
                width: 150,
                cellClass: 'compact'
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

    $scope.defaultFilters = {
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
        riskCode: ""

    };

    $scope.filterOptions = $scope.defaultFilters;

    $scope.ignoreOptionChange = false;
    $scope.$watch("filterOptions", function () {
        if (!$scope.ignoreOptionChange) {
            $scope.filterRisks();
        }
    }, true);

    $scope.editRisk = function (riskID) {
        QRMDataService.riskID = riskID;
        $state.go('index.risk');
    }

    $scope.deleteRisk = function (riskID) {
        QRMDataService.riskID = riskID;
        alert("Delete Risk: " + riskID);
    }

    $scope.filterRisks = function () {


        $scope.gridOptions.data = [];
        $scope.rawRisks.forEach(function (r) {
            // Reject the risk until it's passes
            var pass = false;

            if ($scope.filterOptions.filterMatrix) {

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

                debugger;
                var own = $scope.filterOptions.owner.name;
                var man = $scope.filterOptions.manager.name;
                var ownPass = false;
                var manPass = false;
                
                ownPass = (own == r.owner || own == undefined || own == null);
                manPass = (man == r.manager || man == undefined || man == null);
                if (ownPass && manPass ) {
                    pass = true;
                }

            }

            if (!pass) return;

            if (pass) $scope.gridOptions.data.push(r);
        });
    }

    $scope.getRisks = function () {
        riskService.getRisks(QRMDataService.url + "?feed=allRisks&post_type=risk")
            .then(function (response) {
                $scope.rawRisks = response.data.data;
                $scope.gridOptions.data = response.data.data;

                var maxImpact = Number(QRMDataService.project.matrix.maxImpact);
                var maxProb = Number(QRMDataService.project.matrix.maxProb);


                var valPre = [];
                var valPost = [];
                for (var i = 0; i < maxImpact; i++) {
                    for (var j = 1; j <= maxProb; j++) {
                        valPre.push(0);
                        valPost.push(0);
                    }
                }

                response.data.data.forEach(function (el) {
                    var iP = Math.floor(Number(el.inherentProb));
                    var iI = Math.floor(Number(el.inherentImpact));
                    var tP = Math.floor(Number(el.treatedProb));
                    var tI = Math.floor(Number(el.treatedImpact));


                    valPre[((iP - 1) * maxImpact) + iI - 1] ++;
                    valPost[((tP - 1) * maxImpact) + tI - 1] ++;

                });

                setMatrix(QRMDataService.project.matrix.tolString, maxImpact, maxProb, valPre, "#svgDIVPreMit", false, $scope.matrixFilter);
                setMatrix(QRMDataService.project.matrix.tolString, maxImpact, maxProb, valPost, "#svgDIVPostMit", true, $scope.matrixFilter);
                $scope.filterRisks();
            });
    }

    $scope.matrixFilter = function (impact, prob, treated, tol) {
        $scope.filterOptions = {
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
            riskCode: "",
            matrixProb: prob,
            matrixImpact: impact,
            matrixTreated: treated,
            filterMatrix: true,
        };

        $scope.filterRisks();
        $scope.$apply();

        //Unset the matrix filtering option, without initiating a re filter because of the change
        $scope.ignoreOptionChange = true;
        $scope.filterOptions.filterMatrix = false;
        $scope.$apply();
        $scope.ignoreOptionChange = false;

    }

    $scope.clearFilters = function () {

        $scope.filterOptions = {
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
            riskCode: "",
            filterMatrix: false,
            inactive: true,
            active: true,
            pending: true


        };
    }

    $scope.getRisks();

}

function ModalInstanceCtrl($scope, $modalInstance, text, item) {

    debugger;

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

    debugger;

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

var app = angular.module('qrm');

app.factory('riskService', function ($http) {
    return {
        getRisk: function (url, riskID) {
            return $http({
                method: 'POST',
                url: url + "?qrmfn=getRisk",
                cache: false,
                data: riskID
            });
        },

        saveRisk: function (url, risk) {

            return $http({
                method: 'POST',
                url: url + "?qrmfn=saveRisk",
                cache: false,
                data: risk
            });
        },

        getRisks: function (url, riskID) {
            return $http({
                method: 'POST',
                url: url,
                cache: false,
                data: riskID
            });
        },
    }
});
app.service('QRMDataService', function () {
    var loc = window.location.href;
    this.url = loc.slice(0, loc.indexOf("wp-content"));
    this.lorem = "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur"
    this.matrixDisplayConfig = {
        width: 200,
        height: 200,
        radius: 15
    };
    this.project = {
        riskOwners: [
            {
                name: "David Burton",
                email: "dave_on_tour@yahoo.com"
            },
            {
                name: "Fionna Millikan",
                email: "fionna_on_tour@yahoo.com"
            },
            {
                name: "Kerri Whitney",
                email: "kerri_on_tour@yahoo.com"
            }
        ],
        riskManagers: [
            {
                name: "David Burton",
                email: "dave_on_tour@yahoo.com"
            },
            {
                name: "Fionna Millikan",
                email: "fionna_on_tour@yahoo.com"
            },
            {
                name: "Kerri Whitney",
                email: "kerri_on_tour@yahoo.com"
            }
        ],
        categories: [
            {
                name: "Financial",
                id: 100000,
                sec: [
                    {
                        name: "Regulatory",
                        id: 200000
                    },
                    {
                        name: "Accounting",
                        id: 300000
                    },
                    {
                        name: "Management",
                        id: 400000
                    },
                    {
                        name: "Cash Flow",
                        id: 500000
                    }]

            },
            {
                name: "Vendor",
                id: 600000,

                sec: [{
                    name: "Performance",
                    id: 700000
                }, {
                    name: "Relatioship",
                    id: 800000
                }]

            }
             ],
        matrix: {
            maxProb: 5,
            maxImpact: 5,
            tolString: "1111122222333334444455555",
            probVal1: 20,
            probVal2: 40,
            probVal3: 60,
            probVal4: 80,
            probVal5: 100,
            probVal6: 100,
            probVal7: 100,
            probVal8: 100
        }

    };
    this.risk = {
        title: this.url,
        description: this.lorem,
        cause: this.lorem,
        consequence: this.lorem,
        owner: {
            name: "Fionna Millikan",
            email: "fionna_on_tour@yahoo.com"
        },
        manager: {
            name: "Kerri Whitney",
            email: "kerri_on_tour@yahoo.com"
        },
        inherentProb: 5.5,
        inherentImpact: 4.5,
        treatedProb: 2.5,
        treatedImpact: 1.5,
        riskProjectCode: "RK1",
        impRep: true,
        impSafety: false,
        impEnviron: true,
        impCost: true,
        impTime: true,
        impSpec: true,
        treatAvoid: true,
        treatRetention: false,
        treatTransfer: true,
        treatMinimise: true,
        treated: true,
        summaryRisk: true,
        useCalContingency: true,
        useCalcProb: false,
        likeType: 1,
        likeAlpha: 1,
        likeT: 365,
        likePostType: 1,
        likePostAlpha: 1,
        likePostT: 365,
        estContingency: 500000,
        start: moment().subtract(1, 'week'),
        end: moment().add(1, 'month'),
        primcat: {
            name: "Vendor"
        },
        seccat: {
            name: "Performance"
        },
        mitigation: {
            mitPlanSummary: "Summary of the Mitigation Plan",
            mitPlanSummaryUpdate: "Update to the Summary of the Mitigation Plan",
            mitPlan: [
                {
                    description: "Do something kjfdlkdsf jdslkjf sdfkj sklf dkfj hdslkfhd fkjds hfkdsjf hdskjf hsdkjf hdskfj dskfj dskjfhds kfjdsh kdsj hfkdsjf hdskjf dskfj hdskf hdskfj dskfjdsh fkds hfkdsfh skdf kdsf sdkf hskd",
                    update: "I did somrthing",
                    person: "Kezza",
                    cost: "123456",
                    complete: 50,
                    due: moment().add(1, "week").toString
                },
                {
                    description: "Do something",
                    update: "I did somrthing",
                    person: "Kezza",
                    cost: "123456",
                    complete: 50,
                    due: moment().add(1, "week").toString
                },
                {
                    description: "Do something",
                    update: "I did somrthing",
                    person: "Kezza",
                    cost: "123456",
                    complete: 50,
                    due: moment().add(1, "week").toString
                },
                {
                    description: "Do something",
                    update: "I did somrthing",
                    person: "Kezza",
                    cost: "123456",
                    complete: 50,
                    due: moment().add(1, "week").toString
                }
            ]
        },
        response: {
            respPlanSummary: "Summary of the Mitigation Plan",
            respPlanSummaryUpdate: "Update to the Summary of the Mitigation Plan",
            respPlan: [
                {
                    description: "Do something",
                    update: "I did somrthing",
                    person: "Kezza",
                    cost: "123456",
                    complete: 50,
                    due: moment().add(1, "week").toString
                },
                {
                    description: "Do something",
                    update: "I did somrthing",
                    person: "Kezza",
                    cost: "123456",
                    complete: 50,
                    due: moment().add(1, "week").toString
                },
                {
                    description: "Do something",
                    update: "I did somrthing",
                    person: "Kezza",
                    cost: "123456",
                    complete: 50,
                    due: moment().add(1, "week").toString
                },
                {
                    description: "Do something",
                    update: "I did somrthing",
                    person: "Kezza",
                    cost: "123456",
                    complete: 50,
                    due: moment().add(1, "week").toString
                }
            ]
        }
    };


});

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
app.controller('MainCtrl', MainCtrl);
app.controller('ExplorerCtrl', ['$scope', '$modal', 'QRMDataService', '$http', '$state', 'riskService', ExplorerCtrl]);
app.controller('RiskCtrl', ['$scope', '$modal', 'QRMDataService', '$http', '$state', '$stateParams', 'riskService', RiskCtrl]);
app.controller('ModalInstanceCtrl', ['$scope', '$modalInstance', 'text', 'item', ModalInstanceCtrl]);
app.controller('ModalInstanceCtrlMitigation', ['$scope', '$modalInstance', 'title', 'plan', 'update', ModalInstanceCtrlMitigation]);