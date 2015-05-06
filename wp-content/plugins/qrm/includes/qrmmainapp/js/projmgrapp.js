function MainCtrl($scope) {
    $scope.title = "Dave was here";
};

angular.module('qrmprojmgr', []).controller('MainCtrl', ['$scope',MainCtrl]);
