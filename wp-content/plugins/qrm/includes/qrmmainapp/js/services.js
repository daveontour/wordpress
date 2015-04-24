var app = angular.module('qrm');

app.service('riskService', function ($http) {
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

        getRisks: function (url) {
            return $http({
                method: 'POST',
                url: url + "?qrmfn=getAllRisks",
                cache: false
            }).error(function (data, status, headers, config) {
                alert(data.msg);
            });
        },
    }
});
app.service('QRMDataService', function () {
    var loc = window.location.href;
    this.url = loc.slice(0, loc.indexOf("wp-content"));
    this.lorem = "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur";
    this.loremSmall = "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipis";
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
            tolString: "1123312234223443345534455",
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
    this.getTemplateRisk = function () {

        return {
            title: this.url,
            description: "Description",
            cause: "Cause",
            consequence: "Consequence",
            owner: "",
            manager: "",
            inherentProb: 5.5,
            inherentImpact: 5.5,
            treatedProb: 1.5,
            treatedImpact: 1.5,
            riskProjectCode: "RK1",
            impRep: true,
            impSafety: true,
            impEnviron: true,
            impCost: true,
            impTime: true,
            impSpec: true,
            treatAvoid: true,
            treatRetention: true,
            treatTransfer: true,
            treatMinimise: true,
            treated: false,
            summaryRisk: false,
            useCalContingency: false,
            useCalcProb: false,
            likeType: 1,
            likeAlpha: 1,
            likeT: 365,
            likePostType: 1,
            likePostAlpha: 1,
            likePostT: 365,
            estContingency: 0,
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
                        description: this.loremSmall,
                        update: "I did somrthing",
                        person: "Kezza",
                        cost: "123456",
                        complete: 50,
                        due: moment().add(1, "week").toString
                }, {
                        description: this.loremSmall,
                        update: "I did somrthing",
                        person: "Kezza",
                        cost: "123456",
                        complete: 50,
                        due: moment().add(1, "week").toString
                }, {
                        description: this.loremSmall,
                        update: "I did somrthing",
                        person: "Kezza",
                        cost: "123456",
                        complete: 50,
                        due: moment().add(1, "week").toString
                }, {
                        description: this.loremSmall,
                        update: "I did somrthing",
                        person: "Kezza",
                        cost: "123456",
                        complete: 50,
                        due: moment().add(1, "week").toString
                }
            ]
            },
            response: {
                respPlanSummary: "Summary of the Response Plan",
                respPlanSummaryUpdate: "Update to the Summary of the Mitigation Plan",
                respPlan: [
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
            controls: [
                {
                    description: this.loremSmall,
                    effectiveness: "Ad Hoc",
                    contribution: "Minor"

                },
                {
                    description: this.loremSmall,
                    effectiveness: "Ad Hoc",
                    contribution: "Minor"

                }, {
                    description: this.loremSmall,
                    effectiveness: "Ad Hoc",
                    contribution: "Minor"

                }, {
                    description: this.loremSmall,
                    effectiveness: "Ad Hoc",
                    contribution: "Minor"

                }, {
                    description: this.loremSmall,
                    effectiveness: "Ad Hoc",
                    contribution: "Minor"

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