/**
 * INSPINIA - Responsive Admin Theme
 * Copyright 2015 Webapplayers.com
 *
 * Inspinia theme use AngularUI Router to manage routing and views
 * Each view are defined as state.
 * Initial there are written state for all view in theme.
 *
 */
function config($stateProvider, $urlRouterProvider, $ocLazyLoadProvider) {
    $urlRouterProvider.otherwise("/index/main");

    $ocLazyLoadProvider.config({
        // Set to true if you want to see what and when is dynamically loaded
        debug: false
    });

    $stateProvider

    //        .state('index', {
    //            abstract: true,
    //            url: "/index",
    //            templateUrl: "views/common/content.html",
    //        })
    //        .state('index.main', {
    //            url: "/main",
    //            templateUrl: "views/main.html",
    //            data: { pageTitle: 'Example view' }
    //        })
    //        .state('index.minor', {
    //            url: "/minor",
    //            templateUrl: "views/minor.html",
    //            data: { pageTitle: 'Example view' }
    //        })

        .state('index', {
            abstract: true,
            url: "/index",
            templateUrl: "views/common/content.html",
        })
        .state('index.main', {
            url: "/main",
            templateUrl: "views/main.html",
            data: {
                pageTitle: 'Example view'
            }
        })
        .state('index.explorer', {
            url: "/explorer",
            templateUrl: "views/explorer.html",
            controller: "ExplorerCtrl as exp",
            data: {
                pageTitle: 'Risk Explorer'
            }
        })
        .state('index.risk', {
            url: "/risk",
            templateUrl: "views/risk.html",
            controller: "RiskCtrl as ctl",
            onEnter: function () {
                minimiseSideBar();
            },
            resolve: {
                loadPlugin: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        files: ["js/daterangepicker.js", "css/daterangepicker-bs3.css"],
                        cache: true
                    });
                }]
            }
        })

    .state('index.calender', {
            url: "/calender",
            controller: 'CalenderController',
            controllerAs: 'cal',
            templateUrl:'views/calender.html'

    })
        .state('index.rank', {
            url: "/rank",
            controller: 'RankController',
            controllerAs: 'rank',
            templateUrl: "views/rank.html"
        })
        .state('index.matrix', {
            url: "/matrix",
        controller:'RelMatrixController',
        controllerAs:'relMatrix',
            templateUrl: "views/relmatrix.html"
        })
}
angular
    .module('inspinia')
    .config(config)
    .run(function ($rootScope, $state) {
        $rootScope.$state = $state;
    });