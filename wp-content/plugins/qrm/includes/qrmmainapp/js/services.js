function DataService() {

    var ds = this;
    this.matrixDisplayConfig = {
        width: 200,
        height: 200,
        radius: 15
    };

    this.selectProject = function (projectID) {
        this.projectObjectives = objectiveSort(getLinearObjectives(this.projMap, projectID));
        this.catData = getFamilyCats(this.projMap, projectID);
        this.project = this.projMap.get(projectID);
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
        id: -1,
        matrix: {
            maxProb: 5,
            maxImpact: 5,
            tolString: "1123312234223443345534455"
        }

    };
    this.getTemplateRisk = function () {

        return {
            title: "Title of Risk",
            riskProjectCode: " New Risk ",
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
            primcat: 0,
            seccat: 0,
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

    this.analyseRisks = function () {
        var map = this.getAnalysisMap();
        var userMap = new Map();
        this.siteUsers.forEach(function (u) {
            userMap.put(u.data.ID, {
                "id": u.data.ID,
                "ownCount": 0,
                "manCount": 0
            });
        });
        userMap.put("", {
                "id": "",
                "ownCount": 0,
                "manCount": 0
            });

        this.projectRisks.forEach(function (r) {

            
            var tol = r.currentTolerance;

            var own = jQuery.grep(map.get("RiskOwner").get(tol).values, function (o) {
                return Number(o.id) == Number(r.owner);
            })
            own[0].value = own[0].value + 1;
            userMap.get(r.owner).ownCount++;

            var man = jQuery.grep(map.get("RiskManager").get(tol).values, function (o) {
                return Number(o.id) == Number(r.manager);
            })
            man[0].value = man[0].value + 1;
            userMap.get(r.manager).manCount++;



            if (map.get("Categories").get(tol).valuesMap.findIt(r.primcat.title) > -1) {
                map.get("Categories").get(tol).valuesMap.put(r.primcat.title, map.get("Categories").get(tol).valuesMap.get(r.primcat.title) + 1);
            } else {
                map.get("Categories").get(tol).valuesMap.put(r.primcat.title, 1);
            }
        });

        //Sort the array according to greatest total value
        var countArray = userMap.valArray;
        countArray.sort(function (a, b) {
            return Number(b.ownCount) - Number(a.ownCount);
        });
         for (var i = 5; i > 0; i--){
            var sortOwn = new Array();
            var arr = map.get("RiskOwner").get(i).values;
            countArray.forEach(function (a){
                var item = jQuery.grep(arr, function(b){
                    return Number(b.id) == Number (a.id);
                })
                sortOwn.push(item[0]);
                map.get("RiskOwner").get(i).values = sortOwn;
            });
        }
        
        this.owners = map.get("RiskOwner").valArray;

        
        

        console.log(this.owners);


    }

    this.getAnalysisMap = function () {
        var map = new Map();

        map.put("RiskOwner", new Map());
        var ownMap = map.get("RiskOwner");
        ownMap.put(5, {
            "key": "Extreme",
            "color": "#ed5565",
            values: new Array()
        });
        ownMap.put(4, {
            "key": "High",
            "color": "#f8ac59",
            values: new Array()
        });
        ownMap.put(3, {
            "key": "Significant",
            "color": "#ffff55",
            values: new Array()
        });
        ownMap.put(2, {
            "key": "Moderate",
            "color": "#1ab394",
            values: new Array()
        });
        ownMap.put(1, {
            "key": "Low",
            "color": "#1c84c6",
            values: new Array()
        });

        map.put("RiskManager", new Map());
        var manMap = map.get("RiskManager");
        manMap.put(5, {
            "key": "Extreme",
            "color": "#ed5565",
            values: new Array()
        });
        manMap.put(4, {
            "key": "High",
            "color": "#f8ac59",
            values: new Array()
        });
        manMap.put(3, {
            "key": "Significant",
            "color": "#ffff55",
            values: new Array()
        });
        manMap.put(2, {
            "key": "Moderate",
            "color": "#1ab394",
            values: new Array()
        });
        manMap.put(1, {
            "key": "Low",
            "color": "#1c84c6",
            values: new Array()
        });

        this.siteUsers.forEach(function (u) {
            for (var i = 5; i > 0; i--) {
                ownMap.get(i).values.push({
                    "label": u.data.display_name,
                    "value": 0,
                    "id": u.data.ID
                });
                manMap.get(i).values.push({
                    "label": u.data.display_name,
                    "value": 0,
                    "id": u.data.ID
                });
            }
        });

        //FOr the undefined
        for (var i = 5; i > 0; i--) {
            ownMap.get(i).values.push({
                "label": "Unassigned",
                "value": 0,
                "id": ""
            });
            manMap.get(i).values.push({
                "label": "Unassigned",
                "value": 0,
                "id": ""
            });
        }

        map.put("Categories", new Map());
        var catMap = map.get("Categories");
        catMap.put(5, {
            "key": "Extreme",
            "color": "#ed5565",
            "valuesMap": new Map(),
            values: new Array()
        });
        catMap.put(4, {
            "key": "High",
            "color": "#f8ac59",
            "valuesMap": new Map(),
            values: new Array()
        });
        catMap.put(3, {
            "key": "Significant",
            "color": "#ffff55",
            "valuesMap": new Map(),
            values: new Array()
        });
        catMap.put(2, {
            "key": "Moderate",
            "color": "#1ab394",
            "valuesMap": new Map(),
            values: new Array()
        });
        catMap.put(1, {
            "key": "Low",
            "color": "#1c84c6",
            "valuesMap": new Map(),
            values: new Array()
        });

        return map;
    }


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