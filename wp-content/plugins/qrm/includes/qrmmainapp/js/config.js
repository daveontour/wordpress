function config($stateProvider, $urlRouterProvider, $ocLazyLoadProvider, IdleProvider, KeepaliveProvider) {

    // Configure Idle settings
    IdleProvider.idle(5); // in seconds
    IdleProvider.timeout(120); // in seconds

    $urlRouterProvider.otherwise("/explorer");

    $ocLazyLoadProvider.config({
        // Set to true if you want to see what and when is dynamically loaded
        debug: false
    });

    $stateProvider
        .state('explorer', {
            url: "/explorer",
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl+"views/qrm/m.explorer.html"
                } else {
                    return pluginurl+"views/qrm/explorer.html"
                }
            }, 
            controller: "ExplorerCtrl as exp",
            data: {
                pageTitle: 'Risk Explorer'
            }, 
            onEnter:function(){
                closeMenu();
                winWidth = $(document).width()-10;
                $("#container").css("width", winWidth + "px");
            }
        })
        .state('risk', {
            url: "/risk",
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl+"views/qrm/m.risk.html"
                } else {
                    return pluginurl+"views/qrm/risk.html"
                }
            }, 
            controller: "RiskCtrl as ctl",
            resolve: {
                loadPlugin: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        files: [pluginurl+"js/daterangepicker.js", pluginurl+"css/daterangepicker-bs3.css"],
                        cache: true
                    });
                }]
            }, 
            onEnter:function(){
                closeMenu();
            },
            onExit:function(){
                QRM.mainController.lookingForRisks();
            }
        })
        .state('calender', {
            url: "/calender",
            controller: 'CalenderController',
            controllerAs: 'cal',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl+"views/qrm/m.calender.html"
                } else {
                    return pluginurl+"views/qrm/calender.html"
                }
            },
            onEnter:function(){
                closeMenu();
            }

        })
        .state('rank', {
            url: "/rank",
            controller: 'RankController',
            controllerAs: 'rank',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl+"views/qrm/m.rank.html"
                } else {
                    return pluginurl+"views/qrm/rank.html"
                }
            },
            onEnter:function(){
                closeMenu();
            }
        })
        .state('matrix', {
            url: "/matrix",
            controller: 'RelMatrixController',
            controllerAs: 'relMatrix',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl+"views/qrm/m.relmatrix.html"
                } else {
                    return pluginurl+"views/qrm/relmatrix.html"
                }
            },
            onEnter:function(){
                closeMenu();
            }
        })
        .state('incidentExpl', {
            url: "/incidentExpl",
            controller: 'IncidentExplCtrl',
            controllerAs: 'incidentExpl',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl+"views/qrm/m.incidentExplorer.html"
                } else {
                    return pluginurl+"views/qrm/incidentExplorer.html"
                }
            },
            onEnter:function(){
                closeMenu();
            }
        })
        .state('incident', {
            url: "/incident",
            controller: 'IncidentCtrl',
            controllerAs: 'incident',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl+"views/qrm/m.incident.html"
                } else {
                    return pluginurl+"views/qrm/incident.html"
                }
            },
             resolve: {
                loadPlugin: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        files: [pluginurl+"js/daterangepicker.js", pluginurl+"css/daterangepicker-bs3.css"],
                        cache: true
                    });
                }]
            },
            onEnter:function(){
                closeMenu();
            }
        })    
        .state('reviewExpl', {
            url: "/reviewExpl",
            controller: 'ReviewExplCtrl',
            controllerAs: 'reviewExpl',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl+"views/qrm/m.reviewExplorer.html"
                } else {
                    return pluginurl+"views/qrm/reviewExplorer.html"
                }
            },
            onEnter:function(){
                closeMenu();
            }
        })  
        .state('review', {
            url: "/review",
            controller: 'ReviewCtrl',
            controllerAs: 'rev',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl+"views/qrm/m.review.html"
                } else {
                    return pluginurl+"views/qrm/review.html"
                }
            },
            onEnter:function(){
                closeMenu();
            }
        })  
        .state('analysis', {
            url: "/analysis",
            controller: 'AnalysisController',
            controllerAs: 'analysis',
            templateUrl: pluginurl+"views/qrm/analysis.html", 
            onEnter:function(){
                closeMenu();
            }
        });
}
angular
    .module('inspinia')
    .config(config)
    .run(function($rootScope, $state) {
        $rootScope.$state = $state;
    });
