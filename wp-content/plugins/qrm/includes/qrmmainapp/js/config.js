function config($stateProvider, $urlRouterProvider, $ocLazyLoadProvider) {
    $urlRouterProvider.otherwise( function(){
        // Route directly to the risk viewer if risk was selected
         if (postType =='risk') return "/index/risk";
        return "/index/explorer";
    });


    $ocLazyLoadProvider.config({
        // Set to true if you want to see what and when is dynamically loaded
        debug: true
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
            templateUrl: function(params){
               if (jQuery(window).width() < 600){
                    return pluginurl+"views/m.risk.html"
                } else {   
                    return pluginurl+"views/risk.html"
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
        .state('index.analysis', {
            url: "/analysis",
        controller:'AnalysisController',
        controllerAs:'analysis',
            templateUrl: pluginurl+"views/analysis.html"
        })
}
angular
    .module('inspinia')
    .config(config)
    .run(function ($rootScope, $state) {
        $rootScope.$state = $state;
    });