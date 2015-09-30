function pageTitle($rootScope, $timeout) {
    return {
        link: function (scope, element) {
            var listener = function (event, toState, toParams, fromState, fromParams) {
                // Default title - load on Dashboard 1
                var title = 'QRM | Quay Risk Manager';
                // Create your own title pattern
                if (toState.data && toState.data.pageTitle) title = 'QRM | ' + toState.data.pageTitle;
                $timeout(function () {
                    element.text(title);
                });
            };
            $rootScope.$on('$stateChangeStart', listener);
        }
    }
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
                    jQuery(element).iCheck('update');
                })

                return jQuery(element).iCheck({
                    checkboxClass: 'icheckbox_square-green',
                    radioClass: 'iradio_square-green'

                }).on('ifChanged', function (event) {
                    if (jQuery(element).attr('type') === 'checkbox' && $attrs['ngModel']) {
                        $scope.$apply(function () {
                            return ngModel.$setViewValue(event.target.checked);
                        });
                    }
                    if (jQuery(element).attr('type') === 'radio' && $attrs['ngModel']) {
                        return $scope.$apply(function () {
                            return ngModel.$setViewValue(value);
                        });
                    }
                });
            });
        }
    };
}

function riskmat(QRMDataService) {
    //Creates the risk matrices on the explorer page
    return {
        restrict: "E",
        compile: function (element, attrs) {
            
            var maxProb = 8;
            var maxImpact = 8;
//            var hP = 100/(maxProb+1);
//            var wP = 100/(maxImpact+1);
            
            var mat = "<table border='1' cellspacing='5' cellpadding='0' style='width:180px;height:180px;cursor:pointer;cursor:hand'>";
            for (var prob = maxProb; prob > 0; prob--) {
                mat = mat + "<tr ng-class='{cellHidden: exp.rowClass(" + prob + ")}'>";
                for (var impact = 1; impact <= maxImpact; impact++) {
                    mat = mat + "<td ng-style='exp.cellStyle()' ng-click='exp.matrixFilter(" + impact + "," + prob + "," + attrs.treated + ")' ng-class='{cellHidden: exp.cellClass(" + prob + "," + impact + ",0),cellLow: exp.cellClass(" + prob + "," + impact + ",1), cellModerate:exp.cellClass(" + prob + "," + impact + ",2), cellSignificant:exp.cellClass(" + prob + "," + impact + ",3), cellHigh:exp.cellClass(" + prob + "," + impact + ",4), cellExtreme:exp.cellClass(" + prob + "," + impact + ",5), matCellHighLight:exp.cellHighlight(" + prob + "," + impact + "," + attrs.treated + ")}'>{{exp.getCellValue(" + prob + "," + impact + "," + attrs.treated + ")}}</td>";
                }
                mat = mat + "</tr>";
            }
            mat = mat + "</table>";
            element.replaceWith(mat);
        }
    };
}

function dropzone() {
    function link (scope, element, attrs) {
        var config, dropzone;
        config = scope[attrs.dropzone];
        // create a Dropzone for the element with the given options
        dropzone = new Dropzone(element[0], config.options);
        // bind the given event handlers
        angular.forEach(config.eventHandlers, function (handler, event) {
            dropzone.on(event, handler);
        });
    };
    
    return{
        link:link
    }
}

function textAngularFocus($parse, $timeout, textAngularManager) {

    return {
      link: function(scope, element, attributes) {

        // Parse the focus expression
        var shouldFocus = $parse(attributes.focus)(scope);

        if (!shouldFocus) return;

        $timeout(function() {

          // Retrieve the scope and trigger focus
          var editorScope = textAngularManager.retrieveEditor(attributes.name).scope;
          editorScope.displayElements.text.trigger('focus');
        }, 0, false);
      }
    };
  }


angular.module('qrm')
    .directive('pageTitle', ['$rootScope', '$timeout',pageTitle])
    .directive('riskmat', ['QRMDataService',riskmat])
    .directive('icheck', ['$timeout',icheck])
    .directive('dropzone', dropzone)
    .directive('textAngular', ['$parse', '$timeout', 'textAngularManager', textAngularFocus])