<?php


//网站域名
define('CURRENT_HOST', 'http://test.wangcaio2o.com');
//定义回调URL通用的URL
define('WEIBO_URL_CALLBACK', CURRENT_HOST.'/index.php?g=LabelAdmin&m=Weibo&a=sns_Callback&type=');
//系统固定配置文件，本文件不要随便改动
return array(
		'PRODUCTION_FLAG' => 0,
	//旺财web版登陆地址
		'WEB_LOGIN_URL' => 'http://app.app.imageco.cn/',
	//系统固定配置选项，不要变
		'SHOW_PAGE_TRACE' =>true,
		'DEFAULT_THEME'		=> '',
		'URL_MODEL' =>0,
		'DEFAULT_CHARSET' => 'utf-8',
		'APP_GROUP_LIST' => 'Home,Label,LabelAdmin,WangcaiPc',
		'DEFAULT_GROUP' =>'Home',
		'DEFAULT_MODULE' => 'Login',
		'DB_PREFIX' =>'',
		'TMPL_FILE_DEPR' => '_',
		'APP_SUB_DOMAIN_DEPLOY'=>1, // 开启子域名配置
	    //session都用 Redis
		'SESSION_TYPE' => '',
		//'SESSION_PREFIX' => 'sido2o:',
	/*子域名配置
	 *格式如: '子域名'=>array('分组名/[模块名]','var1=a&var2=b');
	*/
		'APP_SUB_DOMAIN_RULES'=>array(
				'halltest'=>array('Hall/Index'),  // admin域名指向Admin分组
		),
		'DOMAIN_FIRST'=>'test', //:U函数172行 用于区分测试和生产域名问题

		'TMPL_PARSE_STRING'  =>array(
				'__PUBLIC__' => './Home/Public', // 更改默认的__PUBLIC__ 替换规则
				'__UPLOAD__' => './Home/Upload',
				'__AIPAI__'  => 'http://222.44.51.34/app_image/prize'               //爱拍电子券图片页面访问路径
		),
		'LOG_RECORD' => true, // 开启日志记录
		'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR,SQL', // 只记录EMERG ALERT CRIT ERR 错误
		'LOG_FILE_SIZE' => 2000*1024000,//日志大小2G后切换（字节）
	//默认错误跳转对应的模板文件
		'TMPL_ACTION_ERROR' => './Home/Tpl/Public/Public_msg.html',
	//默认成功跳转对应的模板文件
		'TMPL_ACTION_SUCCESS' => './Home/Tpl/Public/Public_msg.html',

	//界面异常跳转
		'TMPL_EXCEPTION_FILE' => INDEX_PATH.'/502.html',
	//404跳转
		'URL_404_REDIRECT'=> '/index.php?g=Home&m=Empty&a=index',

	//以下是默认载入其它扩展配置，
		'LOAD_EXT_CONFIG' =>
				'configDb,configInterface,configUserRights,configSns,configBatch,configTmpl,configFb,configTipsInfo,configRedis,configFunctionSwitch,configWechatShareDefaultInfo',	//数据库配置,各种接口参数'

	//爱拍赢大奖地址
		'AI_PAI_URL' => 'http://222.44.51.34/ppool/wangcai_prize/index.php?',

	//发码接口
		'WC_SEND_ARR' => array(
				'url' => 'http://test.wangcaio2o.com/AppServ/index.php?a=CodeSendForLable'
		),
	//重发接口
		'WC_RESEND_ARR' => array(
				'url' => 'http://test.wangcaio2o.com/AppServ/index.php?a=CodeResendReq'
		),
	//撤消接口
		'WC_CANCEL_ARR' => array(
				'url' => 'http://test.wangcaio2o.com/AppServ/index.php?a=CodeCancelByReqId'
		),
	//下载临时文件目录
		'DOWN_TEMP' => APP_PATH.'Upload/downTemp/',
		'UPLOAD'=>APP_PATH.'Upload/',
	//爱拍电子券图片目录
		'AI_PAI_UPLOAD' => '/www/app_image/prize/',
	//哈根达斯非标项目商户号
		'DM_Haagen_Dazs' => array(),
		'CURRENT_DOMAIN'=>'http://test.wangcaio2o.com/',
		'PAYGIVE_DOMAIN'=>'http://test.wangcaio2o.com',
	//移动至configTmpl.php
	//'SITE_TITLE'=>'旺财O2O营销平台 - 集工具、渠道、资源、管理于一体的O2O营销平台',
	//'SITE_KEYWORDS'=>'翼码旺财,O2O营销,O2O解决方案,移动互联网营销,微信营销,微第三方,微官网',
	//'SITE_DESCRIPTION'=>'翼码旺财是上海翼码信息科技股份有限公司推出的O2O营销平台，为企业开展O2O营销、O2O解决方案、多宝电商、全民营销、支付宝条码支付、微信 营销，开展异业合作，打通O2O线上线下推广渠道，提供一站式O2O营销服务。',
		'CHECK_FUCK_WORD'=>TRUE,//敏感词过滤
		'WCADMIN_UPLOAD'=>'http://test.wangcaio2o.com/wcadmin/upload/',

		'CUSTOM_LOG_PATH'=>'E:/Log/',
		'SERVER_LOG_FILE_PATH'=>'E:/Log/',
	//最大下载量
		'DOWNLOAD_MAX_COUNT'=>100000,
	//微信卡券辅助码前缀
		'CARD_ASSIST_PRE_NUMBER' => '7063',
	//新浪配置
		'THINK_SDK_SINA2'=>array(
				'APP_KEY'=>'4294557489',
				'APP_SECRET'=>'44a15d8275a371a9a47990b2dee1835f',
				'CALLBACK'   => WEIBO_URL_CALLBACK . 'sina2',
				'AUTHORIZE'=>'forcelogin=true'
		),
		'SESSION_OPTIONS' => array('expire'=>10800), // session 配置数组 支持type name id path expire domain 等参数

		'WCADMIN_URL'=>'http://test.wangcaio2o.com/wcadmin/login.php?token=',
		'SSO_ADMIN'=>'http://admin.app.imageco.cn/index.php?m=Admin&a=Index&token=',
		'TEST_IMG_HOSTURL'=>'test.wangcaio2o.com',
		'FEERATE' => 0.02,    //默认汇率

	//如果手机访问跳转微官网地址
		'MICRO_SITE'=>'http://test.wangcaio2o.com/index.php?&g=Label&m=MicroWeb&a=index&id=14081',

	//行业版地址
		'WCBX_DOMAIN'=>'bx-dev.wangcaio2o.com',
	//禁止缓存
	'TMPL_CACHE_ON' => false,

	'STATIC_DOMAIN' => 'http://test.wangcaio2o.com',
	 //'DEFAULT_FILTER' => 'mysql_real_escape_string',//修改默认过滤函数
	'DEFAULT_FILTER' => false,//修改默认过滤函数
);