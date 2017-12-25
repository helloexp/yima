var prom=angular.module('prom', ['ngCookies','ngHead','ui.router','ngAside','ngFoot','pagination','string.substr','nav.left','input.maxlength']);
prom.config(function($stateProvider, $urlRouterProvider) {
    $urlRouterProvider.otherwise('/index');
    $stateProvider
        .state('index', {
            url: '/index',
            views: {
                '': {
                    templateUrl: 'main.html'
                },
               'leftNav@index': {
                    templateUrl: 'leftNav.html',
                    controller:function ($scope,$location,sidenav) {

                        /*初始化设置导航*/
                        $scope.sidenavTitle=sidenav.title;
                        $scope.sidenavName=sidenav.name; 
                       
                        $scope.leftTitle=function(t){
                            var ty=$(t).attr('data-type');
                            if($(t).parents('li').hasClass('open')){
                                   $scope.sidenavTitle=null;
                                   return false;
                            }
                            $scope.sidenavTitle=ty;
                        }
                       

                        
                    }
                },
                'main@index': {
                    templateUrl: 'home.html',
                    controller:'home'
                }
            }
        })
        .state('index.phone', {
            url: '/index_phone',
            views: {
                'main@index': {
                    templateUrl: 'phone.html',
                    controller:'phone'
                }
            }
        })
        .state('index.detiled', {
            url: '/index_detiled',
            views: {
                'main@index':{
                   templateUrl: 'detiled.html' 
                }
            }
        })
        .state('index.detiled.base', {
            url: '/base',
            views: {
                'main@index.detiled': {
                    templateUrl: 'detiled_base.html',
                    controller:'detiled_base'
                }
            }
        })
        .state('index.detiled.move', {
            url: '/move',
            views: {
                'main@index.detiled': {
                    templateUrl: 'detiled_move.html',
                    controller:'detiled_move'
                }
            }
        })
        .state('index.detiled.info', {
            url: '/info',
            views: {
                'main@index.detiled': {
                    templateUrl: 'detiled_info.html',
                    controller:'detiled_info'
                }
            }
        })  
        .state('index.full', {
            url: '/sales_full',
            views: {
                'main@index': {
                    templateUrl: 'full.html',
                    controller:'full'
                }
            }
        })
        .state('index.fans', {
            url: '/sales_fans',
            views: {
                'main@index': {
                    templateUrl: 'fans.html',
                    controller:'fans'
                }
            }
        })
        .state('index.cashEdit', {
            url: '/index_edit',
            views: {
                'main@index': {
                    templateUrl: 'cashEdit.html',
                    controller:'cashEdit'
                }
            }
        })
        .state('index.fullEdit', {
            url: '/sales_full_edit',
            views: {
                'main@index': {
                    templateUrl: 'fullEdit.html',
                    controller:'fullEdit'
                }
            }
        })
        .state('index.fansEdit', {
            url: '/sales_fans_edit',
            views: {
                'main@index': {
                    templateUrl: 'fansEdit.html',
                    controller:'fansEdit'
                }
            }
        })
        
});