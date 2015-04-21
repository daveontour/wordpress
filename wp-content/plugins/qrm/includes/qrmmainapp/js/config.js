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

    $urlRouterProvider.otherwise("/index/explorer");

    $ocLazyLoadProvider.config({
        debug: true
    });

    $stateProvider
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
            },
            resolve: {
                loadPlugin2: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        files: ["js/qrm-common.js","js/d3/d3.min.js",'css/plugins/iCheck/custom.css', 'js/plugins/iCheck/icheck.min.js'],
                        cache: false
                    });
                }]
            }
        })
        .state('index.risk', {
            url: "/risk",
            templateUrl: "views/risk.html",
            controller: "RiskCtrl as ctl",
            resolve: {
                loadPlugin: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        files: ["css/qrm_styles.css", "js/d3/d3.min.js", "js/daterangepicker.js", "css/daterangepicker-bs3.css", 'css/plugins/iCheck/custom.css', 'js/plugins/iCheck/icheck.min.js'],
                        cache: true
                    });
                }],
                loadPlugin2: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        files: ["js/qrm-common.js"],
                        cache: false
                    });
                }]
            }
        })
}



angular.module('qrm')
    .config(config)
    .run(function ($rootScope, $state) {
        $rootScope.$state = $state;
    });