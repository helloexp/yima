<?php

// 图文编辑
class MedAction extends MyBaseAction {

    const BAOSHAN_BATCHID = '9445';
    // 2624 9445
    private $mobile_request = true;

    private $baoshan_upload = '';

    private $tempImagePath = '';

    public function _initialize() {
        parent::_initialize();
        
        $this->mobile_request = $this->_is_mobile_request();
        if (ACTION_NAME != 'index') {
            $this->_log_baoshan_visit();
        }
        
        $this->baoshan_upload = C('UPLOAD') . 'fb_bs/';
        $this->tempImagePath = APP_PATH . 'Upload/img_tmp/' . $this->node_id;
        $this->WeiXinService = D('WeiXin', 'Service');
    }

    public function _log_baoshan_visit() {
        $data = array(
            'label_id' => $this->id, 
            'bs_label_id' => I('bs_id'), 
            'visit_time' => date('YmdHis'), 
            'url' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"]);
        M('tfb_baoshan_visit_trace')->add($data);
    }

    public function index() {
        $id = $this->id;
        $nodeId = $this->node_id;
        if ($this->batch_type != '19')
            $this->error('错误访问！');
            
            // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        if ($this->batch_id == self::BAOSHAN_BATCHID) {
            $this->_baoshan2014();
            exit();
        }
        
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        
        // 积分
        $isGift = array(
            'integral_num' => 0, 
            'integral_sign' => 0);
        if (! empty($openid) && ! empty($row['config_data']) &&
             strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') != false) {
            $isGift = unserialize($row['config_data']);
        }
        // 校验openid
        $openid = cookie('openid');
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') != false && $isGift['integral_num'] !=0) {
            $isOpenM4 = D('IntegralConfigNode')->checkIntegralConfig($nodeId);
            if (empty($openid) && $isOpenM4 !== false) {
                $this->userOpenId();
                exit();
            }
        }
        // 单色背景
        if ($row['page_style'] == '5') {
            $oneColor = unserialize($row['config_data']);
            $this->assign('oneColor', $oneColor);
        }
        // 获取积分的名称
        $integralModel = M('tintegral_node_config');
        $integralName = $integralModel->where(
            array(
                'node_id' => $this->node_id))->getField('integral_name');
        
        $this->assign('integralName', $integralName);
        $this->assign('isGift', $isGift);
        

        $getNodeImg = M('tnode_info')->where(
            array(
                'node_id' => $nodeId))->getField('head_photo');
        
        // 微信分享配置
        $WxConfig = D('WeiXin', 'Service')->getWxShareConfig();
        $this->assign('WxConfige', $WxConfig);
        
        $imgUrl = C('TMPL_PARSE_STRING.__PUBLIC__') . '/Image/wap-logo-wc.png';
        // 分享的图标
        if (empty($row['share_pic'])) {
            if (empty($getNodeImg)) {
                $row['share_pic'] = $imgUrl;
            } else {
                $row['share_pic'] = C('TMPL_PARSE_STRING.__UPLOAD__') . '/' .
                     $getNodeImg;
            }
        } else {
            $row['share_pic'] = C('TMPL_PARSE_STRING.__UPLOAD__') . '/' .
                 $row['share_pic'];
        }
        /*
         * //头像的图标 if(empty($row['log_img'])){ if(empty($getNodeImg)){
         * $row['log_img'] = $imgUrl; }else{ $row['log_img'] =
         * C('TMPL_PARSE_STRING.__UPLOAD__').'/'.$getNodeImg; } }else{
         * $row['log_img'] =
         * C('TMPL_PARSE_STRING.__UPLOAD__').'/'.$row['log_img']; }
         */
        $query_arr = explode('-', $row['select_type']);
        $this->assign('id', $this->id);
        $this->assign('query_arr', $query_arr);
        $this->assign('row', $row);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        
        // $this->assign('node_name',$nodeName);
        $this->display();
        // 输出模板
    }

    /**
     *
     * @return void 去拿code
     */
    public function userOpenId() {
        $apiCallbackUrl = U('Label/Med/medCallback', 
            array(
                'id' => $this->id), '', '', true); // 拿到code后回来的地址
        $url = U('Label/Med/index', array(
            'id' => $this->id), '', '', true);
        // 旺财授权
        $this->WeiXinService->wechatAuthorizeAndRedirectByDetailParam(
            // 生产上使用下面的
            // $this->wxAuthorizeAndRedirectByDetailParam(
            C('WEIXIN.appid'), C('WEIXIN.secret'), 0, $apiCallbackUrl, 
            // $url,
            0);
    }
    // 请求到code码后的处理方法
    /**
     *
     * @return void
     */
    public function medCallback() {
        $code = I('code', null);
        $id = I('id', null);
        if ($code) {
            $result = $this->WeiXinService->getOpenid($code);
            if (! empty($result['openid'])) {
                cookie('openid', $result['openid'], 60 * 60 * 24 * 365);
            }
        }
        redirect(U('Label/Med/index', array(
            'id' => $id)));
    }

    /**
     *
     * @return MemberInstallModel
     */
    private function getMemberInstallModel() {
        return D('MemberInstall');
    }

    /**
     * 触发赠送积分
     */
    public function triggerGiveIntegral() {
        $getCookie = cookie('mId');
        // 拿到本篇图文编辑的ID
        $getPost = I('post.');
        $mId = $getPost['batchId'];
        $channelId = $getPost['id'];
        if (! stristr($getCookie, $mId)) {
            
            $nodeId = $this->node_id;
            $openId = cookie('openid');
            
            $memberInstallModel = $this->getMemberInstallModel();
            $isOk = $memberInstallModel->receiveTextPoint($openId, $nodeId, 
                $mId, $channelId);
            if ($isOk) {
                $mId = $getCookie . ',' . $mId;
                cookie('mId', $mId, 60 * 60 * 24 * 365);
                $this->ajaxReturn(
                    array(
                        'status' => 1, 
                        'info' => '恭喜您获得本次阅读积分'), 'JSON');
            }
        }
        $this->ajaxReturn(
            array(
                'status' => 0, 
                'info' => '您已获得过阅读积分'), 'JSON');
    }
    
    // 处理图片
    public function _baoshan_img($img) {
        // 目录创建
        if (! is_dir($this->tempImagePath)) {
            if (! mkdir($this->tempImagePath, 0777))
                $this->error('目录创建失败');
        }
        
        // 获取图片后缀
        $arr = explode(".", $img);
        $ext = end($extend);
        if (strlen($ext) > 4 || $ext == '') {
            $ext = 'jpg';
        }
        $thumb = $this->baoshan_upload . md5($img) . '_152X152.' . $ext;
        if (! file_exists($thumb)) {
            // 获取远程图
            if (strpos($img, 'http://') === 0) {
                $tmp_img = $this->tempImagePath . md5($img) . '.' . $ext;
                if (! file_exists($tmp_img))
                    file_put_contents($tmp_img, file_get_contents($img));
                $img = $tmp_img;
            }
            
            import('ORG.Util.Image');
            $thumb = Image::thumb($img, $thumb, '', 152, 152, true);
        }
        return $thumb;
    }

    public function _baoshan2014() {
        $this->_log_baoshan_visit();
        
        // 获取热门活动(不包含图文编辑、微官网)
        $map = array(
            'n.param1' => array(
                'exp', 
                "like '宝山%'"), 
            'a.batch_type' => array(
                'not in', 
                '19, 13'), 
            'c.channel_id' => array(
                'exp', 
                ' = ch.id'), 
            'a.status' => '1', 
            'a.end_time' => array(
                'gt', 
                date('YmdHis')), 
            'a.node_id' => array(
                'exp', 
                ' = n.node_id'), 
            'a.id' => array(
                'exp', 
                ' = c.batch_id'), 
            'c.status' => '1', 
            '_string' => " ifnull(ch.type, '') != '4' and ifnull(ch.sns_type, '') != '43' ");
        
        $batchList = M()->table(
            'tmarketing_info a, tnode_info n, tbatch_channel c, tchannel ch')
            ->where($map)
            ->field('a.name, a.memo, a.wap_info, a.batch_type, n.param1, c.id')
            ->order('a.batch_o2o_push_time desc, a.add_time desc')
            ->limit(5)
            ->select();
        
        $imgName = C('O2O_DEFULT_IMG');
        foreach ($batchList as $k => $v) {
            // $pregResult =
            // preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$v['wap_info'],$matches);
            $pregResult = preg_match_all("/src=\"\/?(.*?)\"/", $v['wap_info'], 
                $matches);
            if ($pregResult) {
                $batchList[$k]['img'] = $matches[1][0];
            } elseif (! empty($v['bg_pic'])) {
                $batchList[$k]['img'] = './Home/Upload/' . $v['bg_pic'];
            } else {
                $batchList[$k]['img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                     '/Image/new_pic/' . $imgName[$v['batch_type']] . '.jpg';
            }
            
            $batchList[$k]['img'] = $this->_baoshan_img($batchList[$k]['img']);
            $batchList[$k]['url'] = U('baoshan2014_item', 
                array(
                    'item_id' => $v['id'], 
                    'id' => $this->id));
        }
        
        $this->assign('batchList', $batchList);
        $this->display('baoshan2014' . ($this->mobile_request ? '' : '_pc'));
    }

    public function baoshan2014_items() {
        $bs_type = I('bs_type');
        $bs_arr = array(
            'bs' => '宝山', 
            'bs_bissc' => '宝山,商圈', 
            'bs_bissc_bi' => '宝山,商圈,巴黎春天', 
            'bs_bissc_wd' => '宝山,商圈,万达广场', 
            'bs_bissc_ny' => '宝山,商圈,诺亚新天地', 
            'bs_bissc_hj' => '宝山,商圈,黄金广场', 
            'bs_bissc_by' => '宝山,商圈,北翼商业街', 
            'bs_bissc_jz' => '宝山,商圈,家装天地', 
            'bs_car' => '宝山,汽车', 
            'bs_wine' => '宝山,酒类', 
            'bs_catering' => '宝山,餐饮', 
            'bs_news' => '宝山',  // 新闻
            'bs_website' => '宝山'); // 微官网
        
        if (! isset($bs_arr[$bs_type])) {
            $bs_type = 'bs';
        }
        
        $map = array(
            'a.status' => '1', 
            'a.node_id' => array(
                'exp', 
                ' = n.node_id'), 
            'c.channel_id' => array(
                'exp', 
                ' = ch.id'), 
            'a.id' => array(
                'exp', 
                ' = c.batch_id'), 
            'a.end_time' => array(
                'gt', 
                date('YmdHis')), 
            'c.status' => '1', 
            '_string' => "n.param1 like '{$bs_arr[$bs_type]}%'");
        
        $order_by = 'a.batch_o2o_push_time desc, a.add_time desc';
        // 新闻
        if ($bs_type == 'bs_news') {
            $map['_string'] .= " and a.id != '" . self::BAOSHAN_BATCHID .
                 "' and a.batch_type = '19' and ifnull(ch.type, '') != '4' and ifnull(ch.sns_type, '') != '43'";
        }  // 微官网
else if ($bs_type == 'bs_website') {
            $map['_string'] .= " and a.batch_type = '13'";
        }  // 其他显示营销活动
else {
            $map['_string'] .= " and a.batch_type not in ('13', '19', '17') and ifnull(ch.type, '') != '4' and ifnull(ch.sns_type, '') != '43'";
        }
        
        $list = M()->table(
            'tmarketing_info a, tnode_info n, tbatch_channel c, tchannel ch')
            ->where($map)
            ->field(
            'a.name, a.memo, a.wap_info, a.batch_type, n.param1, c.id, n.param1,a.start_time,a.end_time')
            ->order($order_by)
            ->select();
        
        $imgName = C('O2O_DEFULT_IMG');
        foreach ($list as $k => $v) {
            $pregResult = preg_match_all("/src=\"\/?(.*?)\"/", $v['wap_info'], 
                $matches);
            // $pregResult =
            // preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$v['wap_info'],$matches);
            if ($pregResult) {
                $list[$k]['img'] = $matches[1][0];
            } elseif (! empty($v['bg_pic'])) {
                $list[$k]['img'] = './Home/Upload/' . $v['bg_pic'];
            } else {
                $list[$k]['img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                     '/Image/new_pic/' . $imgName[$v['batch_type']] . '.jpg';
            }
            $list[$k]['img'] = $this->_baoshan_img($list[$k]['img']);
            
            // $list[$k]['url'] = U('baoshan2014_item',
            // array('item_id'=>$v['id'], 'id'=>$this->id));
            $list[$k]['url'] = U(C('BATCH_WAP_URL.' . $v['batch_type']), 
                array(
                    'id' => $v['id'], 
                    'bs_id' => $this->id));
        }
        
        if ($this->mobile_request && strpos($bs_type, 'bs_bissc') === 0) {
            $batchList = array();
            foreach ($list as $v) {
                list (, , $batch_name) = explode(',', $v['param1']);
                $more = '';
                foreach ($bs_arr as $kk => $vv) {
                    if ($v['param1'] == $vv) {
                        $more = $kk;
                        break;
                    }
                }
                $v['more_url'] = U('baoshan2014_items', 
                    array(
                        'id' => $this->id, 
                        'bs_type' => $more));
                $batchList[$batch_name][] = $v;
            }
        } else {
            $batchList = $list;
        }
        // 分页
        if (! $this->mobile_request) {
            $count = count($batchList);
            $pageInfo = $this->_pagination($count, 20);
            $firstRow = $pageInfo['firstRow'];
            $listRows = $pageInfo['listRows'];
            if ($count) {
                $batchList = array_slice($batchList, $firstRow, $listRows);
            }
            $this->assign('pagehtml', $pageInfo['html']);
        }
        $this->assign('batchList', $batchList);
        $this->assign('bs_type', $bs_type);
        $this->display(
            'baoshan2014_items' . ($this->mobile_request ? '' : '_pc'));
    }

    function baoshan2014_item() {
        $item_id = I('item_id');
        $info = M('tbatch_channel')->where("id = '{$item_id}'")->find();
        $wap_url = U(C('BATCH_WAP_URL.' . $info['batch_type']), 
            array(
                'id' => $info['id'], 
                'bs_id' => $this->id));
        if ($this->mobile_request) {
            header('Location: ' . $wap_url);
            exit();
        }
        $this->assign('wap_url', $wap_url);
        $this->display();
    }

    function store_nav() {
        $this->assign('wap_url', 
            'http://www.wangcaio2o.com/index.php?g=Label&m=Label&a=index&id=30463&bs_id=' .
                 $this->id);
        $this->display('baoshan2014_item');
    }

    function _is_mobile_request() {
        $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
        $mobile_browser = '0';
        if (preg_match(
            '/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', 
            strtolower($_SERVER['HTTP_USER_AGENT'])))
            $mobile_browser ++;
        if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(
            strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !==
             false))
            $mobile_browser ++;
        if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
            $mobile_browser ++;
        if (isset($_SERVER['HTTP_PROFILE']))
            $mobile_browser ++;
        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ', 
            'acs-', 
            'alav', 
            'alca', 
            'amoi', 
            'audi', 
            'avan', 
            'benq', 
            'bird', 
            'blac', 
            'blaz', 
            'brew', 
            'cell', 
            'cldc', 
            'cmd-', 
            'dang', 
            'doco', 
            'eric', 
            'hipt', 
            'inno', 
            'ipaq', 
            'java', 
            'jigs', 
            'kddi', 
            'keji', 
            'leno', 
            'lg-c', 
            'lg-d', 
            'lg-g', 
            'lge-', 
            'maui', 
            'maxo', 
            'midp', 
            'mits', 
            'mmef', 
            'mobi', 
            'mot-', 
            'moto', 
            'mwbp', 
            'nec-', 
            'newt', 
            'noki', 
            'oper', 
            'palm', 
            'pana', 
            'pant', 
            'phil', 
            'play', 
            'port', 
            'prox', 
            'qwap', 
            'sage', 
            'sams', 
            'sany', 
            'sch-', 
            'sec-', 
            'send', 
            'seri', 
            'sgh-', 
            'shar', 
            'sie-', 
            'siem', 
            'smal', 
            'smar', 
            'sony', 
            'sph-', 
            'symb', 
            't-mo', 
            'teli', 
            'tim-', 
            'tosh', 
            'tsm-', 
            'upg1', 
            'upsi', 
            'vk-v', 
            'voda', 
            'wap-', 
            'wapa', 
            'wapi', 
            'wapp', 
            'wapr', 
            'webc', 
            'winw', 
            'winw', 
            'xda', 
            'xda-');
        if (in_array($mobile_ua, $mobile_agents))
            $mobile_browser ++;
        if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
            $mobile_browser ++;
            
            // Pre-final check to reset everything if the user is on Windows
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
            $mobile_browser = 0;
            
            // But WP7 is also Windows, with a slightly different characteristic
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !==
             false)
            $mobile_browser ++;
        if ($mobile_browser > 0)
            return true;
        else
            return false;
    }
    
    // 分页
    protected function _pagination($totalRows, $listRows, $pageName = 'page') {
        $_get = I('get.');
        $nowPage = I('get.' . $pageName, '1', 'intval');
        $pageCount = ceil($totalRows / $listRows);
        if ($nowPage <= 1) {
            $nowPage = 1;
            $upPageUrl = 'javascript:void(0);';
            $upPageDiasabled = 'disabled';
        } else {
            $upPage = $nowPage - 1;
            $_get[$pageName] = $upPage;
            $upPageUrl = U('', $_get);
            $upPageDiasabled = '';
        }
        if ($nowPage >= $pageCount) {
            $nowPage = $pageCount;
            $downPageUrl = 'javascript:void(0);';
            $downPageDiasabled = 'disabled';
        } else {
            $downPage = $nowPage + 1;
            $_get[$pageName] = $downPage;
            $downPageUrl = U('', $_get);
            $downPageDiasabled = '';
        }
        $html = '<div class="custom-paginations-container">
            <div class="custom-paginations clearfix">
                <a href="' .
             $upPageUrl . '" class="custom-paginations-prev ' . $upPageDiasabled . '">上一页</a>
                <a href="' . $downPageUrl .
             '" class="custom-paginations-next js-no-follow ' .
             $downPageDiasabled . '">下一页</a>
            </div>
        </div>';
        $html = '<div class="page">
                        	<span>当前第' . $nowPage . '页</span>
                            <span class="ml20">共' .
             $pageCount .
             '页</span>
                            <a href="' .
             $upPageUrl .
             '" class="page-pre ml10" >上一页</a>
                            <a href="' .
             $downPageUrl . '" class="page-next">下一页</a>
                        </div>';
        return array(
            'html' => $html, 
            'firstRow' => ($nowPage - 1) * $listRows, 
            'listRows' => $listRows, 
            'pageCount' => $pageCount, 
            'nowPage' => $nowPage);
    }
}
