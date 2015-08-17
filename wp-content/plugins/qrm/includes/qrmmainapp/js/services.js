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
            owner: -1,
            manager: -1,
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

        var now = moment();
        
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
            

            var cat = jQuery.grep(map.get("Categories").get(tol).values, function (o) {
                var c =(typeof(r.primcat.title)=='undefined')?"Unassigned":r.primcat.title;
                return o.label == c;
            })
            
            if (cat.length == 0){
                map.get("Categories").get(tol).values.push({"label":r.primcat.title, value:1})
            } else {
                cat[0].value = cat[0].value+1;
            }
            
                            

            var endDiff = now.diff(moment(r.end));
            var startDiff = now.diff(moment(r.start));
            
            var status = 0

            if (endDiff > 0) status = -1;
            if (startDiff < 0) status = 1;
            if (startDiff > 0 && endDiff < 0) status = 0;
            
             var status = jQuery.grep(map.get("Status").get(tol).values, function (o) {
                return Number(o.state) == Number(status);
            })
            status[0].value = status[0].value + 1;
            

        });
        
        this.categories = map.get("Categories").valArray;
        
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

        countArray.sort(function (a, b) {
            return Number(b.manCount) - Number(a.manCount);
        });
         for (var i = 5; i > 0; i--){
            var manOwn = new Array();
            var arr = map.get("RiskManager").get(i).values;
            countArray.forEach(function (a){
                var item = jQuery.grep(arr, function(b){
                    return Number(b.id) == Number (a.id);
                })
                manOwn.push(item[0]);
                map.get("RiskManager").get(i).values = manOwn;
            });
        }
        this.managers = map.get("RiskManager").valArray;
        this.status = map.get("Status").valArray;
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
        
        map.put("Categories", new Map());
        var catMap = map.get("Categories");
        catMap.put(5, {
            "key": "Extreme",
            "color": "#ed5565",
            "values": new Array()
        });
        catMap.put(4, {
            "key": "High",
            "color": "#f8ac59",
            "values": new Array()
        });
        catMap.put(3, {
            "key": "Significant",
            "color": "#ffff55",
            "values": new Array()
        });
        catMap.put(2, {
            "key": "Moderate",
            "color": "#1ab394",
            "values": new Array()
        });
        catMap.put(1, {
            "key": "Low",
            "color": "#1c84c6",
            "values": new Array()
        });
        
        this.catData.forEach(function(c){
            if (c.primCat){
            for (var i = 5; i > 0; i--) {
                catMap.get(i).values.push({
                    "label": c.title,
                    "value": 0
                });
            } 
            }
        });
        
        map.put("Status", new Map());
        var sMap = map.get("Status");
        sMap.put(5, {
            "key": "Extreme",
            "color": "#ed5565",
            values: new Array()
        });
        sMap.put(4, {
            "key": "High",
            "color": "#f8ac59",
            values: new Array()
        });
        sMap.put(3, {
            "key": "Significant",
            "color": "#ffff55",
            values: new Array()
        });
        sMap.put(2, {
            "key": "Moderate",
            "color": "#1ab394",
            values: new Array()
        });
        sMap.put(1, {
            "key": "Low",
            "color": "#1c84c6",
            values: new Array()
        });
        
        for (var i = 5; i > 0; i--) {
                sMap.get(i).values.push({"label": "Inactive","value": 0, state: -1 });
                sMap.get(i).values.push({"label": "Active","value": 0, state: 0 });
                sMap.get(i).values.push({"label": "Pending","value": 0, state: 1 });
        }

        
 //For the undefined
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
            catMap.get(i).values.push({
                "label": "Unassigned",
                "value": 0
            });
        }

        return map;
    }
    
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
                maxImpact: 5,
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
            inheritParentCategories: true,
            inheritParentObjectives: true,
            categories: [],
            objectives: [],
            parent_id: 0,
        };

    }
}

function RemoteService($http) {

    this.getRisk = function (riskID, $scope) {
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
    this.getServerMeta = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getServerMeta"
            },
            cache: false
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
    this.getAllRisks = function () {

        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getAllRisks"
            },
            cache: false
        });
    };
    this.registerAudit = function (auditType,auditComment,riskID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "registerAudit"
            },
            cache: false,
            data: {auditType:auditType, auditComment:auditComment,riskID:riskID}
        });
    };
    this.getAllProjectRisks = function (projectID, childProjects) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getAllProjectRisks"
            },
            cache: false,
            data: {projectID:projectID, childProjects:childProjects}
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
    this.getAttachments = function (postID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getAttachments"
            },
            cache: false,
            data: postID
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
    this.getAllIncidents = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getAllIncidents"
            },
            cache: false
        });
    };
    this.getIncident = function (incidentID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getIncident"
            },
            cache: false,
            data: JSON.stringify(incidentID)
        });
    };
    this.getReview = function (reviewID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getReview"
            },
            cache: false,
            data: JSON.stringify(reviewID)
        });
    };
    this.saveIncident = function (incident) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "saveIncident"
            },
            cache: false,
            data: JSON.stringify(incident)
        });
    };
    this.getAllReviews = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getAllReviews"
            },
            cache: false
        });
    };
    this.getIncident = function (incidentID) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getIncident"
            },
            cache: false,
            data: JSON.stringify(incidentID)
        });
    };
    this.saveReview = function (review) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "saveReview"
            },
            cache: false,
            data: JSON.stringify(review)
        });
    };
    this.addGeneralComment = function (comment, postID) {
        data = {
            comment: comment,
            ID: postID
        }
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "addGeneralComment"
            },
            cache: false,
            data: JSON.stringify(data)
        });
    };
    this.login = function (user, pass) {
        data = {
            user: user,
            pass: pass
        }
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "login",
            },
            cache: false,
            data: JSON.stringify(data)
        });
    };
    this.logout = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "logout",
            },
            cache: false
        });
    };
    this.checkSession = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "checkSession",
            },
            cache: false
        });
    };
    
    this.getJSON = function () {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getJSON",
            },
            cache: false
        });
    };
    this.getReportRiskJSON = function (risks,projectID, childProjects) {
        var data = {risks:risks, projectID:projectID, childProjects:childProjects};
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "getReportRiskJSON",
            },
            data:JSON.stringify(data),
            cache: false
        });
    };
    
    
    this.newPushDownRisk = function (pushdown) {
        return $http({
            method: 'POST',
            url: ajaxurl,
            params: {
                action: "newPushDown",
            },
            cache: false,
            data:JSON.stringify(pushdown)
        });
    };
}