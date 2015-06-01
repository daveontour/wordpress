function DataService() {

        var ds = this;
    this.matrixDisplayConfig = {
        width: 200,
        height: 200,
        radius: 15
    };

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

    //Only here to provide skeleton for the matrix on the explorer page when no project is selected
    this.project = {
        id:-1,
        matrix: {
            maxProb: 5,
            maxImpact:5,
            tolString: "1123312234223443345534455"
        }

    };
    this.getTemplateRisk = function () {

        return {
            title: "Title of Risk",
            riskProjectCode:" New Risk ",
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
            useCalProb: false,
            likeType: 1,
            likeAlpha: 1,
            likeT: 365,
            likePostType: 1,
            likePostAlpha: 1,
            likePostT: 365,
            estContingency: 0,
            start: moment(),
            end: moment().add(1, 'month'),
            primcat:0,
            seccat:0,
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

    this.updateRisksRelMatrix = function (data) {
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

    this.saveRankOrder = function (data) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "saveRankOrder"
            },
            cache: false,
            data: JSON.stringify(data)
        }).error(function (data, status, headers, config) {
            alert(data.msg);
        });
    };

    this.getRiskAttachments = function (riskID) {
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
    this.getCurrentUser = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getCurrentUser"
            },
            cache: false
        });
    };
}