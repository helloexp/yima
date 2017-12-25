var agent = angular.module('agent', ['ngCookies','ngHeadAgent','ui.router','ngAside','ngFoot','pagination','ui.time','string.substr','nav.left','ui.setspectime']);
agent.config(function($stateProvider, $urlRouterProvider) {
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
                    controller:function ($scope,$location,$log,sidenav) {

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
        .state('index.myShop', {
            url: '/firm_myShop',
            views: {
                'main@index': {
                    templateUrl: 'myShop.html',
                    controller:'myShop'
                }
            }
        })
        .state('index.addShop', {
            url: '/firm_myShop_addShop',
            views: {
                'main@index': {
                    templateUrl: 'addShop.html',
                    controller:'addShop'
                }
            }
        })
        .state('index.openService', {
            url: '/firm_openService',
            views: {
                'main@index': {
                    templateUrl: 'openService.html',
                    controller:'openService'
                }
            }
        })
        .state('index.recharge', {
            url: '/money_recharge',
            views: {
                'main@index': {
                    templateUrl: 'recharge.html'
                }
            }
        })
        .state('index.debit', {
            url: '/money_debit',
            views: {
                'main@index': {
                    templateUrl: 'debit.html'
                }
            }
        })
        .state('index.debit.date', {
            url: '/date',
            views: {
                'main@index.debit': {
                    templateUrl: 'debit_date.html'
                }
            }
        })
        .state('index.debit.shop', {
            url: '/shop',
            views: {
                'main@index.debit': {
                    templateUrl: 'debit_shop.html'
                }
            }
        })
        .state('index.debit.type', {
            url: '/type',
            views: {
                'main@index.debit': {
                    templateUrl: 'debit_type.html'
                }
            }
        })
        .state('index.debit.info', {
            url: '/info',
            views: {
                'main@index.debit': {
                    templateUrl: 'debit_info.html'
                }
            }
        })
        .state('index.rebate', {
            url: '/money_rebate',
            views: {
                'main@index': {
                    templateUrl: 'rebate.html'
                }
            }
        })
        .state('index.detail', {
            url: '/deal_detail',
            views: {
                'main@index': {
                    templateUrl: 'detail.html',
                    controller:'detail'
                }
            }
        })
        .state('index.total', {
            url: '/deal_total',
            views: {
                'main@index': {
                    templateUrl: 'total.html'
                }
            }
        })
        .state('index.total.shop', {
            url: '/shop',
            views: {
                'main@index.total': {
                    templateUrl: 'total_shop.html',
                    controller:'total_shop'
                }
            }
        })
        .state('index.total.date', {
            url: '/date',
            views: {
                'main@index.total': {
                    templateUrl: 'total_date.html',
                    controller:'total_date'
                }
            }
        })
        .state('index.total.type', {
            url: '/type',
            views: {
                'main@index.total': {
                    templateUrl: 'total_type.html',
                    controller:'total_type'
                }
            }
        })
});




