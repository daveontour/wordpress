function pageTitle($rootScope, $timeout) {
    return {
        link: function (scope, element) {
            var listener = function (event, toState, toParams, fromState, fromParams) {
                // Default title - load on Dashboard 1
                var title = 'Quay Risk Manager';
                // Create your own title pattern
                if (toState.data && toState.data.pageTitle) title = 'Quay Risk Manager | ' + toState.data.pageTitle;
                $timeout(function () {
                    element.text(title);
                });
            };
            $rootScope.$on('$stateChangeStart', listener);
        }
    }
};

function sideNavigation($timeout) {
    return {
        restrict: 'A',
        link: function (scope, element) {
            // Call the metsiMenu plugin and plug it to sidebar navigation
            $timeout(function () {
                element.metisMenu();
            });
        }
    };
};

function iboxTools($timeout) {
    return {
        restrict: 'A',
        scope: true,
        templateUrl: 'views/common/ibox_tools.html',
        controller: function ($scope, $element) {
            // Function for collapse ibox
            $scope.showhide = function () {
                    var ibox = $element.closest('div.ibox');
                    var icon = $element.find('i:first');
                    var content = ibox.find('div.ibox-content');
                    content.slideToggle(200);
                    // Toggle icon from up to down
                    icon.toggleClass('fa-chevron-up').toggleClass('fa-chevron-down');
                    ibox.toggleClass('').toggleClass('border-bottom');
                    $timeout(function () {
                        ibox.resize();
                        ibox.find('[id^=map-]').resize();
                    }, 50);
                },
                // Function for close ibox
                $scope.closebox = function () {
                    var ibox = $element.closest('div.ibox');
                    ibox.remove();
                }
        }
    };
};

function minimalizaSidebar($timeout) {
    return {
        restrict: 'A',
        template: '<a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="" ng-click="minimalize()"><i class="fa fa-bars"></i></a>',
        controller: function ($scope, $element) {
            $scope.minimalize = function () {
                $("body").toggleClass("mini-navbar");
                if (!$('body').hasClass('mini-navbar') || $('body').hasClass('body-small')) {
                    // Hide menu in order to smoothly turn on when maximize menu
                    $('#side-menu').hide();
                    // For smoothly turn on menu
                    setTimeout(
                        function () {
                            $('#side-menu').fadeIn(500);
                        }, 100);
                } else if ($('body').hasClass('fixed-sidebar')) {
                    $('#side-menu').hide();
                    setTimeout(
                        function () {
                            $('#side-menu').fadeIn(500);
                        }, 300);
                } else {
                    // Remove all inline style from jquery fadeIn function to reset menu state
                    $('#side-menu').removeAttr('style');
                }
            }
        }
    };
};

function icheck($timeout) {
    return {
        restrict: 'A',
        require: 'ngModel',
        link: function ($scope, element, $attrs, ngModel) {
            return $timeout(function () {
                var value;
                value = $attrs['value'];

                $scope.$watch($attrs['ngModel'], function (newValue) {
                    $(element).iCheck('update');
                })

                return $(element).iCheck({
                    checkboxClass: 'icheckbox_square-green',
                    radioClass: 'iradio_square-green'

                }).on('ifChanged', function (event) {
                    if ($(element).attr('type') === 'checkbox' && $attrs['ngModel']) {
                        $scope.$apply(function () {
                            return ngModel.$setViewValue(event.target.checked);
                        });
                    }
                    if ($(element).attr('type') === 'radio' && $attrs['ngModel']) {
                        return $scope.$apply(function () {
                            return ngModel.$setViewValue(value);
                        });
                    }
                });
            });
        }
    };
}

function riskmat(){
            //Creates the risk matrices onthe explorer page
        return {
            restrict: "E",
            compile: function (element, attrs) {

                var mat = "<table border='1' cellspacing='5' cellpadding='0' style='width:180px;height:180px;cursor:pointer;cursor:hand'>";
                for (var prob = 5; prob > 0; prob--) {
                    mat = mat + "<tr>";
                    for (var impact = 1; impact <= 5; impact++) {
                        mat = mat + "<td style='width:20%;height:20%;text-align:center' ng-click='exp.matrixFilter(" + impact + "," + prob + "," + attrs.treated + ")' ng-class='{cellLow: exp.cellClass(" + prob + "," + impact + ",1), cellModerate:exp.cellClass(" + prob + "," + impact + ",2), cellSignificant:exp.cellClass(" + prob + "," + impact + ",3), cellHigh:exp.cellClass(" + prob + "," + impact + ",4), cellExtreme:exp.cellClass(" + prob + "," + impact + ",5), matCellHighLight:exp.cellHighlight(" + prob + "," + impact + "," + attrs.treated + ")}'>{{exp.getCellValue(" + prob + "," + impact + "," + attrs.treated + ")}}</td>";
                    }
                    mat = mat + "</tr>";
                }
                mat = mat + "</table>";
                element.replaceWith(mat);
            }
        };
}

function dropzone(QRMDataService){
            return function (scope, element, attrs) {

            var config, dropzone;

            config = scope.$parent[attrs.dropzone];

            // create a Dropzone for the element with the given options
            dropzone = new Dropzone(element[0], config.options);

            // bind the given event handlers
            angular.forEach(config.eventHandlers, function (handler, event) {
                dropzone.on(event, handler);
            });
        };
}

angular.module('qrm')
    .directive('pageTitle', pageTitle)
    .directive('sideNavigation', sideNavigation)
    .directive('iboxTools', iboxTools)
    .directive('minimalizaSidebar', minimalizaSidebar)
    .directive('icheck', icheck)
    .directive('riskmat', riskmat)
    .directive('dropzone', ['QRMDataService',dropzone]);