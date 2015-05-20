function config($stateProvider, $urlRouterProvider, $ocLazyLoadProvider) {
    $urlRouterProvider.otherwise("/index/explorer");

    $ocLazyLoadProvider.config({
        // Set to true if you want to see what and when is dynamically loaded
        debug: false
    });

    $stateProvider
        .state('index', {
            abstract: true,
            url: "/index",
            templateUrl: pluginurl+"views/content.html",
        })
        .state('index.main', {
            url: "/main",
            template:"<h1>DAVE WAS HERE</h1>",
            data: {
                pageTitle: 'Example view'
            }
        })
        .state('index.explorer', {
            url: "/explorer",
            templateUrl: pluginurl+"views/explorer.html",
            controller: "ExplorerCtrl as exp",
            data: {
                pageTitle: 'Risk Explorer'
            }
        })
        .state('index.risk', {
            url: "/risk",
            templateUrl: pluginurl+"views/risk.html",
            controller: "RiskCtrl as ctl",
            onEnter: function () {
                minimiseSideBar();
            },
            resolve: {
                loadPlugin: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        files: [pluginurl+"js/daterangepicker.js", pluginurl+"css/daterangepicker-bs3.css"],
                        cache: true
                    });
                }]
            }
        })

    .state('index.calender', {
            url: "/calender",
            controller: 'CalenderController',
            controllerAs: 'cal',
            templateUrl:pluginurl+'views/calender.html'

    })
        .state('index.rank', {
            url: "/rank",
            controller: 'RankController',
            controllerAs: 'rank',
            templateUrl:pluginurl+ "views/rank.html"
        })
        .state('index.matrix', {
            url: "/matrix",
        controller:'RelMatrixController',
        controllerAs:'relMatrix',
            templateUrl: pluginurl+"views/relmatrix.html"
        })
}
angular
    .module('inspinia')
    .config(config)
    .run(function ($rootScope, $state) {
        $rootScope.$state = $state;
    });