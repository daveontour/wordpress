function DataService() {

        var ds = this;
    this.lorem = "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur";
    this.loremSmall = "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipis";
    this.matrixDisplayConfig = {
        width: 200,
        height: 200,
        radius: 15
    };


    this.getDefaultProject = function () {
        return {
            id: -1,
            title: "Project Title",
            description: "Description of the Project",
            useAdvancedConsequences: false,
            projectCode: "",
            ownersID: [],
            managersID: [],
            usersID: [],
            matrix: {
                maxImpact:5,
                maxProb: 5,
                tolString: "1123312234223443345534455555555555555555555555555555555555555555",
                probVal1: 20,
                probVal2: 40,
                probVal3: 60,
                probVal4: 80,
                probVal5: 100,
                probVal6: 100,
                probVal7: 100,
                probVal8: 100
            },
            categories: [],
            objectives: [],
            parent_id: 0,
        };
    }

    this.selectProject = function(projectID){
             this.projectObjectives = objectiveSort(getLinearObjectives(this.projMap, projectID));
            this.catData = getFamilyCats(this.projMap, projectID);
             this.project =  this.projMap.get(projectID);
    }
    
    this.handleGetProjects = function (response) {
        
        this.projectsLinear = [];
        this.sortedParents = [];
        this.projMap = new Map();

        if (response.data.data.length != 0) {
            this.projectsLinear = response.data.data;
            this.sortedParents = parentSort(response.data.data);
            this.projMap = new Map();
            this.projectsLinear.forEach(function (e) {
                ds.projMap.put(e.id, e);
            });
        }
    }


    this.project = {
        title: "Sample Risk Project Name",
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
        objectives: [
            {
                "name": "Maintain IT Security",
                "id": "role1",
                "children": [
                    {
                        "name": "Prevent Unauthorised access to systems from external",
                        "id": "role11",
                        "children": []
                    },
                    {
                        "name": "Prevent internal access to un authorised users",
                        "id": "role12",
                        "children": [
                            {
                                "name": "Every User will have unique password",
                                "id": "role121",
                                "children": [
                                    {
                                        "name": "subUser2-1-1",
                                        "id": "role1211",
                                        "children": []
                                    },
                                    {
                                        "name": "subUser2-1-2",
                                        "id": "role1212",
                                        "children": []
                                    }
            ]
                            }
          ]
                    }
        ]
            },

            {
                "name": "Admin",
                "id": "role2",
                "children": []
            },

            {
                "name": "Guest",
                "id": "role3",
                "children": []
            }
      ],
        matrix: {
            maxProb: 5,
            maxImpact:5,
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
            title: "Title of Risk (Edit 'Description' to change)",
            description: "Description",
            cause: "Cause",
            consequence: "Consequence",
            owner: "",
            manager: "",
            inherentProb: 5.5,
            inherentImpact: 5.5,
            treatedProb: 1.5,
            treatedImpact: 1.5,
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
            start: moment(),
            end: moment().add(1, 'month'),
            primcat: {
                name: ""
            },
            seccat: {
                name: ""
            },
            mitigation: {
                mitPlanSummary: "Summary of the Mitigation Plan",
                mitPlanSummaryUpdate: "Update to the Summary of the Mitigation Plan",
                mitPlan: []
            },
            response: {
                respPlanSummary: "Summary of the Response Plan",
                respPlanSummaryUpdate: "Update to the Summary of the Mitigation Plan",
                respPlan: []
            },
            controls: [],
            objectives: {},
        }
    };
}

function RemoteService($http) {

    this.getRisk = function (riskID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getRisk"
            },
            cache: false,
            data: riskID
        });
    };

    this.saveRisk = function (risk) {

        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "saveRisk"
            },
            cache: false,
            data: risk
        });
    };

    this.getAllProjectRisks = function (projectID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getAllProjectRisks"
            },
            cache: false,
            data: projectID
        }).error(function (data, status, headers, config) {
            alert(data.msg);
        });
    };


    this.getSiteUsersCap = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getSiteUsersCap"
            },
            cache: false
        });
    };

    this.getSiteUsers = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getSiteUsers"
            },
            cache: false
        });
    };

    this.getProjects = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getProjects"
            },
            cache: false
        });
    };
    this.saveProject = function (data) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "saveProject"
            },
            data: data,
            cache: false
        });
    };


    this.addComment = function (comment, riskID) {
        data = {
            comment: comment,
            riskID: riskID
        }
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "addComment"
            },
            cache: false,
            data: JSON.stringify(data)
        });
    };

    this.updateRisksRelMatrix = function (url, data) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "updateRisksRelMatrix"
            },
            cache: false,
            data: JSON.stringify(data)
        }).error(function (data, status, headers, config) {
            alert(data.msg);
        });
    };

    this.getRiskAttachments = function (url, riskID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getRiskAttachments"
            },
            cache: false,
            data: riskID
        });
    };
}