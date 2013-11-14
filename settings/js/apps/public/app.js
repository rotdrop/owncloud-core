var appSettings = angular.module('appSettings', ['ngResource']).
config(['$httpProvider', '$routeProvider', '$windowProvider', '$provide',
	function($httpProvider,$routeProvider, $windowProvider, $provide) {
		
		// Always send the CSRF token by default
		$httpProvider.defaults.headers.common.requesttoken = oc_requesttoken;

		$routeProvider.when('/:appId', {
			templateUrl : 'rightcontent.html',
			controller : 'detailController'
		}).otherwise({
			redirectTo: '/'
		});

		var $window = $windowProvider.$get();
		var url = $window.location.href;
		var baseUrl = url.split('index.php')[0] + 'index.php/settings';

		$provide.value('Config', {
			baseUrl: baseUrl
		});
	}
]);
appSettings.controller('applistController', ['$scope', 'AppListService',
	function($scope, AppListService){
		/* Displays the list of files in the Left Sidebar */
		$scope.allapps = AppListService.listAllApps().get();
	}
]);
appSettings.controller('detailController', ['$scope', '$routeParams', 'AppListService', 'AppActionService',
	function($scope,$routeParams,AppListService,AppActionService){

		var appId = $routeParams.appId;
		var val;
		$scope.allapps = AppListService.listAllApps().get(function(apps){
			$scope.allapps = apps.data;
			for (var i = 0; i <= $scope.allapps.length; i++) {
				if (appId == $scope.allapps[i].id) {
					val = i;
					break;
				}
			}
			$scope.appname = $scope.allapps[val].name;
			$scope.preview = $scope.allapps[val].preview;
			$scope.licence = $scope.allapps[val].licence;
			$scope.authorname = $scope.allapps[val].author;
			$scope.desc = $scope.allapps[val].description;
			$scope.vers = $scope.allapps[val].version;
		});
		$scope.enable = function (appId) {
			AppActionService.enableApp(appId);
		};

		$scope.disable = function (appId) {
			AppActionService.disableApp(appId);
		};

		$scope.update = function (appId) {
			AppActionService.updateApp(appId);
		};
	}
]);
appSettings.directive('loading',
	[ function() {
		return {
			restrict: 'E',
			replace: true,
			template:"<div class='loading'></div>",
			link: function($scope, element, attr) {
				$scope.$watch('loading', function(val) {
					if (val) {
						$(element).show();
					}
					else {
						$(element).hide();
					}
				});
			}		
		};
	}]
);
appSettings.factory('AppActionService', ['$resource',
	function ($resource) {
		return {
			enableApp : function(appId) {
				return ($resource(OC.filePath('settings', 'ajax', 'enableapp.php')).post(
					{ appid : appId }
				));
			},
			disableApp : function(appId) {
				return ($resource(OC.filePath('settings', 'ajax', 'disableapp.php')).post(
					{ appid : appId }
				));
			},
			updateApp : function(appId) {
				return ($resource(OC.filePath('settings', 'ajax', 'updateApp.php')).post(
					{ appid : appId }
				));
			}
		};
	}
]);
appSettings.factory('AppListService', ['$resource',
	function ($resource) {
		return {
			listAllApps : function() {
				return ($resource(OC.filePath('settings', 'ajax', 'applist.php')));
			}
		};
	}
]);