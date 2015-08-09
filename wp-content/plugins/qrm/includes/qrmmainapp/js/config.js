function config($stateProvider, $urlRouterProvider, $ocLazyLoadProvider, IdleProvider, KeepaliveProvider) {

    // Configure Idle settings
    IdleProvider.idle(5); // in seconds
    IdleProvider.timeout(120); // in seconds

    //    $urlRouterProvider.otherwise("/intro");

    $ocLazyLoadProvider.config({
        // Set to true if you want to see what and when is dynamically loaded
        debug: true
    });

    $stateProvider
        .state('qrm', {
            abstract: true,
            controller: "QRMCtrl",
            templateUrl: function (params) {
                return pluginurl + "views/common/content.html"
            }
        })
        .state('intro', {
            controller: "IntroCtrl",
            templateUrl: function (params) {
                return pluginurl + "views/qrm/intro.html"
            },
        })
        .state('nonQRM', {
            controller: "NonQRMCtrl",
            templateUrl: function (params) {
                return pluginurl + "views/qrm/nonqrm.html"
            },
        })
        .state('login', {
            templateUrl: function (params) {
                return pluginurl + "views/qrm/login.html"
            },
            controller: "LoginCtrl as login",
            data: {
                pageTitle: 'Login'
            },
            onEnter: function () {
                closeMenu();
            },
            onExit: function () {}
        })
        .state('qrm.explorer', {
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl + "views/qrm/m.explorer.html"
                } else {
                    return pluginurl + "views/qrm/explorer.html"
                }
            },
            controller: "ExplorerCtrl as exp",
            data: {
                pageTitle: 'Risk Explorer'
            },
            onEnter: function () {
                closeMenu();
                winWidth = $(document).width() - 10;
                $("#container").css("width", winWidth + "px");
            }
        })
        .state('qrm.risk', {
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl + "views/qrm/m.risk.html"
                } else {
                    return pluginurl + "views/qrm/risk.html"
                }
            },
            controller: "RiskCtrl as ctl",
            resolve: {
                loadPlugin: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        files: [pluginurl + "js/daterangepicker.js", pluginurl + "css/daterangepicker-bs3.css"],
                        cache: true
                    });
                }]
            },
            onEnter: function () {
                closeMenu();
            },
            onExit: function () {
                QRM.mainController.lookingForRisks();
            }
        })
        .state('qrm.calender', {
            controller: 'CalenderController',
            controllerAs: 'cal',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl + "views/qrm/m.calender.html"
                } else {
                    return pluginurl + "views/qrm/calender.html"
                }
            },
            onEnter: function () {
                closeMenu();
            }

        })
        .state('qrm.rank', {
            controller: 'RankController',
            controllerAs: 'rank',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl + "views/qrm/m.rank.html"
                } else {
                    return pluginurl + "views/qrm/rank.html"
                }
            },
            onEnter: function () {
                closeMenu();
            }
        })
        .state('qrm.matrix', {
            controller: 'RelMatrixController',
            controllerAs: 'relMatrix',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl + "views/qrm/m.relmatrix.html"
                } else {
                    return pluginurl + "views/qrm/relmatrix.html"
                }
            },
            onEnter: function () {
                closeMenu();
            }
        })
        .state('qrm.incidentExpl', {
            controller: 'IncidentExplCtrl',
            controllerAs: 'incidentExpl',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl + "views/qrm/m.incidentExplorer.html"
                } else {
                    return pluginurl + "views/qrm/incidentExplorer.html"
                }
            },
            onEnter: function () {
                closeMenu();
            }
        })
        .state('qrm.incident', {
            controller: 'IncidentCtrl',
            controllerAs: 'incident',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl + "views/qrm/m.incident.html"
                } else {
                    return pluginurl + "views/qrm/incident.html"
                }
            },
            resolve: {
                loadPlugin: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        files: [pluginurl + "js/daterangepicker.js", pluginurl + "css/daterangepicker-bs3.css"],
                        cache: true
                    });
                }]
            },
            onEnter: function () {
                QRM.mainController.titleBar = "Risk Incidents";
                closeMenu();
            }
        })
        .state('qrm.reviewExpl', {
            controller: 'ReviewExplCtrl',
            controllerAs: 'reviewExpl',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl + "views/qrm/m.reviewExplorer.html"
                } else {
                    return pluginurl + "views/qrm/reviewExplorer.html"
                }
            },
            onEnter: function () {
                closeMenu();
            }
        })
        .state('qrm.review', {
            controller: 'ReviewCtrl',
            controllerAs: 'rev',
            templateUrl: function (params) {
                if (jQuery(window).width() < 768) {
                    return pluginurl + "views/qrm/m.review.html"
                } else {
                    return pluginurl + "views/qrm/review.html"
                }
            },
            onEnter: function () {
                QRM.mainController.titleBar = "Risk Reviews";
                closeMenu();
            }
        })
        .state('qrm.analysis', {
            controller: 'AnalysisController',
            controllerAs: 'analysis',
            templateUrl: pluginurl + "views/qrm/analysis.html",
            onEnter: function () {
                QRM.mainController.titleBar = "Analysis";
                closeMenu();
            }
        });
}
angular
    .module('qrm')
    .config(config)
    .run(function ($rootScope, $state) {
        $rootScope.$state = $state;

        // This tells the app where to go in the first instance
        $state.go("intro");
    });