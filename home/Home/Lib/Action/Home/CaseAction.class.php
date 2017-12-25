<?php

class CaseAction extends Action {

    public function _initialize() {
        
        // 校验敏感词
        if ($this->isPost() && C('CHECK_FUCK_WORD')) {
            $result = has_fuck_word();
            if ($result) {
                $this->error("输入内容含有敏感词【" . implode(',', $result) . "】");
            }
        }
        
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        
        $this->assign("userInfo", $userInfo);
    }

    public function entrance() {
        $hotCity = array(
            '021' => '上海', 
            '010' => '北京', 
            '020' => '广州', 
            '755' => '深圳', 
            '571' => '杭州', 
            '022' => '天津', 
            '029' => '西安', 
            '592' => '厦门', 
            '025' => '南京', 
            '028' => '成都', 
            '023' => '重庆', 
            '591' => '福州');
        
        // 查询二级活动
        $twotype = M('tbatch_type')->where('O2O_flag=1')->select();
        // 组装
        $newType = array();
        
        $onetypeArr = C("O2O_BATCH_TYPE");
        if (! empty($onetypeArr)) {
            foreach ($onetypeArr as $k => $l) {
                
                $twotype = M('tbatch_type')->where(
                    "O2O_flag=1 AND flag='" . $k . "'")->select();
                $newType[$k] = $twotype;
                
                /*
                 * foreach($twotype as $n=>$v){ if($v['flag']==$k){
                 * $newType[$k]=$v; } }
                 */
            }
        }
        
        $city_name = $this->get_city();
        
        session("city_name", $city_name);
        
        /*
         * $mapCity=array( 'city_level'=>'2',
         * 'city'=>array('like',"%{$city_name}%") ); //选中的城市 if($city_name!=""){
         * $cityInfo =
         * M('tcity_code')->field('city_code,city')->where($mapCity)->find(); }
         */
        if (! $cityInfo) {
            $cityInfo = array(
                'city_code' => '-1', 
                'city' => '全部地区');
        }
        
        // 所有行业
        // $industryData =
        // M('tindustry_info')->field('industry_id,industry_name')->where('status=0')->select();
        
        // 查询二级活动
        $twotype = M('tbatch_type')->where('O2O_flag=1')->select();
        // 组装
        $newType = array();
        
        $onetypeArr = C("O2O_BATCH_TYPE");
        if (! empty($onetypeArr)) {
            foreach ($onetypeArr as $k => $l) {
                
                $twotype = M('tbatch_type')->where(
                    "O2O_flag=1 AND flag='" . $k . "'")->select();
                $newType[$k] = $twotype;
                /*
                 * foreach($twotype as $n=>$v){ if($v['flag']==$k){
                 * $newType[$k]=$v; } }
                 */
            }
        }
        
        // 查询广告
        $bannerList = M('tbatch_o2o_ad')->where("status='0'")
            ->order("ad_sort asc")
            ->select();
        
        // 查询案例
        $caseList = M('tbatch_o2o_case')->where("status='0'")
            ->order("case_sort asc")
            ->limit(5)
            ->select();
        
        // 查询关键字
        $keywords = M('tbatch_o2o_hotword')->order("sort asc")->select();
        
        // 查询推荐
        $commendInfo = M('tbatch_o2o_commend')->order("id desc")
            ->limit(1)
            ->find();
        
        $where = array(
            
            // 'a.status' => '1',
            // 'b.sns_type' => '12',
            // 'a.status'=>'1',
            'c.node_type' => array(
                'in', 
                '0,1'));
        
        // if($cityInfo['city_code']!=""){
        // $where['c.node_citycode'] =
        // array('like',"%".$cityInfo['city_code']."%");
        // }
        
        // 查询热门O2O活动
        /*
         * $list = M()->table('tbatch_channel a')
         * ->field('a.id,a.node_id,c.node_name,a.status,d.name,d.batch_type,d.start_time,d.end_time,d.wap_info,d.bg_pic,d.click_count,d.cj_count,c.node_short_name,d.push_img')
         * ->join('tchannel b on a.channel_id=b.id ') ->join('tnode_info c on
         * a.node_id=c.node_id') ->join('tbatch_type t on a.batch_type=t.type_id
         * ') ->join('tmarketing_info d on a.batch_id=d.id')
         * ->join('tindustry_info e on c.trade_type=e.industry_id')
         * ->where($where) ->order('d.batch_o2o_top_time DESC')
         * ->limit(6)->select();
         */
        
        $list = M()->table('tmarketing_info a')
            ->field(
            'a.id,c.node_id,a.node_name,a.status,a.name,a.batch_type,a.start_time,a.end_time,a.wap_info,a.bg_pic,a.click_count,a.cj_count,c.node_short_name,a.push_img')
            ->join('tnode_info c on a.node_id=c.node_id')
            ->where($where)
            ->order('a.batch_o2o_top_time DESC')
            ->limit(6)
            ->select();
        
        $batchArr = C("BATCH_TYPE_NAME");
        
        if ($list) {
            $imgName = C('O2O_DEFULT_IMG');
            foreach ($list as $k => $v) {
                
                // 优先查询O2O渠道
                $twhere = array(
                    'a.node_id' => $v['node_id'], 
                    'a.batch_id' => $v['id'], 
                    'b.sns_type' => '12');
                $o2ochannel = M()->table('tbatch_channel a')
                    ->field('a.id')
                    ->join('tchannel b on a.channel_id=b.id')
                    ->where($twhere)
                    ->find();
                
                if ($o2ochannel['id'] != "") {
                    $list[$k]['label_id'] = $o2ochannel['id'];
                } else {
                    // 查询渠道访问ID
                    $chanwhere = array(
                        'batch_id' => $v['id'], 
                        'batch_type' => $v['batch_type']);
                    $labelArr = M()->table('tbatch_channel')
                        ->where($chanwhere)
                        ->order('click_count desc ')
                        ->find();
                    $list[$k]['label_id'] = $labelArr['id'];
                }
                
                // $pregResult =
                // preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$v['wap_info'],$matches);
                if (! empty($v['push_img'])) {
                    $list[$k]['img'] = C('adminUploadImgUrl') . $v['push_img'];
                } elseif (! empty($v['bg_pic'])) {
                    $list[$k]['img'] = get_upload_url($v['bg_pic']);
                } elseif ($pregResult) {
                    
                    list ($width, $height, $type, $attr) = getimagesize(
                        $matches[1]);
                    if ($width >= 300) {
                        $list[$k]['img'] = $matches[1];
                    } else {
                        $list[$k]['img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                             '/Image/new_pic/' . $imgName[$v['batch_type']] .
                             '.jpg';
                    }
                } else { // 默认图片
                    $list[$k]['img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                         '/Image/new_pic/' . $imgName[$v['batch_type']] . '.jpg';
                }
                
                $collectWhere['label_id'] = $v['id'];
                
                // 查询收藏的数量
                $colcount = M()->table('tuser_batch_collect a')
                    ->where($collectWhere)
                    ->count();
                $list[$k]['collect_count'] = $colcount;
                
                // 查询点赞的数量
                $loveWhere['m_id'] = $v['id'];
                $lovecount = M()->table('tbatch_o2o_love a')
                    ->field('love')
                    ->where($loveWhere)
                    ->find();
                
                $list[$k]['love_count'] = $lovecount['love'];
                
                // 查询评论数量
                $guestCount = M()->table('tbatch_guestbook')
                    ->where("m_id='" . $v['id'] . "' AND pid=0 AND status=0")
                    ->count();
                $list[$k]['guestCount'] = $guestCount;
                
                $list[$k]['batch_type_name'] = $batchArr[$v['batch_type']];
                
                unset($list[$k]['wap_info']);
                unset($list[$k]['bg_pic']);
            }
        }
        
        $this->assign('commendInfo', $commendInfo);
        $this->assign('keywords', $keywords);
        $this->assign('bannerList', $bannerList);
        $this->assign('caseList', $caseList);
        $this->assign('list', $list);
        $this->assign('twotype', $newType);
        
        $this->assign('twotype', $newType);
        $this->assign('industryData', C('NEW_INDUSTRY'));
        $this->assign('hotCity', $hotCity);
        $this->assign('cityInfo', $cityInfo);
        $this->assign('onetype', C("O2O_BATCH_TYPE"));
        
        $uploadurl = C("WCADMIN_UPLOAD");
        $new_industry = C("NEW_INDUSTRY");
        $this->assign('uploadurl', $uploadurl);
        $this->assign('new_industry', $new_industry);
        
        $this->display();
    }

    public function caselist() {
        $industry_id = intval(I("industry_id"));
        
        if ($industry_id != "") {
            $map = array(
                'industry_id' => $industry_id, 
                'status' => '0');
        } else {
            $map = array(
                'status' => '0');
        }
        
        // 查询当前案例所属行业分类有哪些
        $industryList = M('tbatch_o2o_case')->field('distinct industry_id')->select();
        
        if ($industryList) {
            $industr = array();
            foreach ($industryList as $k => $v) {
                
                $industr[$k] = $v['industry_id'];
            }
        }
        
        // 查询案例分类
        $caseList = M('tbatch_o2o_case')->where($map)
            ->order("case_sort asc,add_time desc")
            ->select();
        
        $brandArr = array();
        $streamArr = array();
        $dealArr = array();
        $buyArr = array();
        
        if (! empty($caseList)) {
            foreach ($caseList as $k => $val) {
                if (strpos($val['batch_purpose'], "1") !== false) {
                    $brandArr[] = $val;
                }
                
                if (strpos($val['batch_purpose'], "2") !== false) {
                    $streamArr[] = $val;
                }
                
                if (strpos($val['batch_purpose'], "3") !== false) {
                    $dealArr[] = $val;
                }
                
                if (strpos($val['batch_purpose'], "4") !== false) {
                    $buyArr[] = $val;
                }
                
                /*
                 * elseif(strpos($val['batch_purpose'],"2")!== false){
                 * $streamArr[]=$val;
                 * }elseif(strpos($val['batch_purpose'],"3")!== false){
                 * $dealArr[]=$val; }else{ $buyArr[]=$val; }
                 */
            }
        }
        
        $uploadurl = C("WCADMIN_UPLOAD");
        $new_industry = C("NEW_INDUSTRY");
        $this->assign('uploadurl', $uploadurl);
        $this->assign('new_industry', $new_industry);
        
        $this->assign('industr', $industr);
        $this->assign('brandArr', $brandArr);
        $this->assign('streamArr', $streamArr);
        $this->assign('dealArr', $dealArr);
        $this->assign('buyArr', $buyArr);
        
        $this->display();
    }
    
    // 案例详情
    public function casedetail() {
        $id = I("id");
        
        // 查询案例详情
        $caseInfo = M('tbatch_o2o_case')->where("id='" . $id . "'")->find();
        
        // 查询更多案例
        $caseList = M('tbatch_o2o_case')->where("status='0'")
            ->order("case_sort asc")
            ->limit(6)
            ->select();
        
        $joinArr = array(
            "1" => "新客户", 
            "2" => "老客户", 
            "3" => "粉丝");
        
        // 查询地区
        $cityinfo = M('tcity_code')->where(
            "province_code='" . $caseInfo['province'] . "' AND city_code='" .
                 $caseInfo['city'] . "'")->find();
        $uploadurl = C("WCADMIN_UPLOAD");
        $new_industry = C("NEW_INDUSTRY");
        $batch_type_name = C("BATCH_TYPE_NAME");
        $this->assign('uploadurl', $uploadurl);
        $this->assign('new_industry', $new_industry);
        $this->assign('caseList', $caseList);
        $this->assign('joinArr', $joinArr);
        $this->assign('caseInfo', $caseInfo);
        $this->assign('cityinfo', $cityinfo);
        $this->assign('batch_type_name', $batch_type_name);
        $this->display();
    }

    public function index() {
        $hotCity = array(
            '021' => '上海', 
            '010' => '北京', 
            '020' => '广州', 
            '755' => '深圳', 
            '571' => '杭州', 
            '022' => '天津', 
            '029' => '西安', 
            '592' => '厦门', 
            '025' => '南京', 
            '028' => '成都', 
            '023' => '重庆', 
            '591' => '福州', 
            'otherCity' => '其他');
        
        // 我创建的活动,根据当前登录用户查询
        $nowmy = I('nowmy', null); // 我创建的活动
        $where = array();
        $where = array(
            // 'b.type' => '1', // 渠道
            // 'd.status' => '1', // 活动状态
            'b.sns_type' => '12',  // 渠道类型为O2O案例
                                  // 'd.end_time'=>array('EGT',date('YmdHis')),
                                  // //活动是否过期
                                  // 'a.status'=>'1', // 标签状态
            'b.status' => '1',  // 渠道状态正常
            'c.node_type' => array(
                'in', 
                '0,1'));
        // if($nowmy!='1'){
        // // $where['a.status'] ='1';
        // }
        // 判断是否手机客户端
        $mobileType = I('type');
        $route = I('route');
        if ($mobileType == 'mobile') {
            // $where['d.batch_o2o_top_time']=array('neq',0);
            $where["_string"] = "d.batch_o2o_top_time != '0'";
        }
        // 活动类型
        $nowtype = I('nowtype', null);
        // 活动子类型
        $nowactivity = I('nowactivity', null);
        if ($nowactivity != "0" && $nowactivity != "") {
            $where['a.batch_type'] = $nowactivity;
        } else {
            // 如果不存在小类，则取所有小类的id
            if ($nowtype != "0" && $nowtype != "") {
                // 查询
                $typelist = M('tbatch_type')->where(
                    "O2O_flag=1 AND flag='" . $nowtype . "'")->select();
                // $onetype=C("O2O_CHILD_TYPE");
                // $typelist=$onetype[$nowtype];
                // 查找子类的ID列表
                $liststr = "";
                foreach ($typelist as $tk => $tal) {
                    if ($liststr != "") {
                        $liststr .= ",";
                    }
                    $liststr .= $tal['type_id'];
                }
                if ($liststr != "") {
                    $where['a.batch_type'] = array(
                        'in', 
                        $liststr);
                }
            }
        }
        
        $city_name = session("city_name");
        
        // 搜索关键字
        $searchres = I('searchres', null);
        if ($searchres != "") {
            // $where['d.name'] = array('like',$searchres);
            // $where['a.node_name'] = array('like',$searchres);
            $where['_string'] = " (d.name like '%" . $searchres .
                 "%' OR c.node_name like '%" . $searchres .
                 "%' OR t.type_name like '%" . $searchres . "%') ";
            // $where->put("c.node_citycode,a.node_name",array(array('like',"%{$searchres}%"),array("like","%{$searchres}%"),'OR'));
            $city_name = "";
        }
        
        // 是否收藏
        $nowcollect = I('nowcollect', null);
        
        if ($nowcollect == '1') {
            // 查询收藏的ID
            if (empty($this->userInfo)) {
                $this->error("请先登录！<a href='javascript:openlogin();'>马上登录</a>");
            }
            $collectArr = M('tuser_batch_collect')->field('label_id')
                ->where(
                "node_id='" . $this->userInfo['node_id'] . "' AND user_id='" .
                     $this->userInfo['user_id'] . "'")
                ->select();
            // echo M('tuser_batch_collect')->getLastSql();
            $labelstr = "";
            if (! empty($collectArr)) {
                foreach ($collectArr as $k => $val) {
                    if ($labelstr != "") {
                        $labelstr .= ",";
                    }
                    $labelstr .= $val['label_id'];
                }
            }
            
            if ($labelstr != "") {
                $where['a.id'] = array(
                    'in', 
                    $labelstr);
            } else {
                $this->error("你还未收藏活动！");
            }
        }
        if ($nowmy == '1') {
            if (empty($this->userInfo)) {
                $this->error("请先登录！<a href='javascript:openlogin();'>马上登录</a>");
            } else {
                $where['a.node_id'] = $this->userInfo['node_id'];
            }
        }
        
        $trade = I('trade', null); // 行业
        $transTrade = C("NEW_INDUSTRY_RELATION");
        if (! empty($trade)) {
            $where['c.trade_type'] = array(
                'in', 
                $transTrade[$trade]);
            // $where['c.trade_type'] = $transTrade[$trade];
        } else {
            $where['c.trade_type'] = array(
                'not in', 
                $transTrade[2]); // 因需求先隐藏金融行业查找
        }
        $address = I('address', null); // 地区
        if ($address != '-1') {
            if ((! empty($address)) && $address != 'otherCity') {
                
                $where['c.node_citycode'] = array(
                    'like', 
                    "%{$address}%");
            } elseif ($address == 'otherCity') {
                $cityList = M('tcity_code')->field('province_code,city_code')
                    ->where(
                    array(
                        'province' => array(
                            'in', 
                            $hotCity), 
                        'city_code' => array(
                            'neq', 
                            '')))
                    ->select();
                $arrCity = array();
                foreach ($cityList as $key => $val) {
                    $arrCity[] = $val['province_code'] . $val['city_code'];
                }
                $where['c.node_citycode'] = array(
                    'not in', 
                    $arrCity);
            } else {
                if ($nowmy != '1' && $mobileType != 'mobile') {
                    
                    if ($city_name != "") {
                        $mapCity = array(
                            'city_level' => '2', 
                            'city' => array(
                                'like', 
                                "%{$city_name}%"));
                        
                        // 选中的城市
                        if ($city_name != "") {
                            $addressInfo = M('tcity_code')->field(
                                'city_code,city')
                                ->where($mapCity)
                                ->find();
                        }
                    }
                    
                    $address = $addressInfo['city_code'];
                    $where['c.node_citycode'] = array(
                        'like', 
                        "%" . $addressInfo['city_code'] . "%");
                }
            }
        }
        
        if ($nowmy == '1') {
            $orderStr = 'd.add_time DESC';
        } else {
            $time = I('time', null);
            if ($mobileType == 'mobile') {
                $time = "red";
            }
            switch ($time) {
                case 'week':
                    // 本周开始和结束日期
                    $tWeeks = date('YmdHis', 
                        mktime(0, 0, 0, date('m'), date('d') - date('N'), 
                            date('Y')));
                    $tWeeke = date('YmdHis', 
                        mktime(23, 59, 59, date('m'), date('d') - date('N') + 6, 
                            date('Y')));
                    $where['d.start_time'] = array(
                        'elt', 
                        $tWeeks);
                    $where['d.end_time'] = array(
                        'egt', 
                        $tWeeke);
                    $orderStr = 'a.id DESC';
                    break;
                case 'red':
                    $orderStr = 'd.batch_o2o_top_time DESC';
                    break;
                case 'hot':
                    $orderStr = 'd.click_count DESC';
                    break;
                case 'new':
                    $orderStr = 'd.add_time DESC';
                    break;
                case 'all':
                    $orderStr = 'd.add_time';
                    break;
                default:
                    $orderStr = 'd.batch_o2o_top_time DESC';
            }
        }
        
        $mapcount = M()->table('tbatch_channel a')
            ->join('tchannel b on a.channel_id=b.id ')
            ->join('tbatch_type t on a.batch_type=t.type_id ')
            ->join('tnode_info c on a.node_id=c.node_id')
            ->join('tmarketing_info d on a.batch_id=d.id')
            ->where($where)
            ->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($mapcount, 5); // 实例化分页类 传入总记录数和每页显示的记录数
                                        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "$key=" . urlencode($val) . "&";
        }
        
        $list = M()->table('tbatch_channel a')
            ->field(
            'a.id,a.node_id,c.node_name,a.status,d.name,d.batch_type,d.start_time,d.end_time,d.wap_info,d.bg_pic,d.click_count,d.cj_count,c.node_short_name,d.push_img')
            ->join('tchannel b on a.channel_id=b.id ')
            ->join('tnode_info c on a.node_id=c.node_id')
            ->join('tbatch_type t on a.batch_type=t.type_id ')
            ->join('tmarketing_info d on a.batch_id=d.id')
            ->join('tindustry_info e on c.trade_type=e.industry_id')
            ->where($where)
            ->order($orderStr)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        // 获取图片路径
        $batchArr = C("BATCH_TYPE_NAME");
        if ($list) {
            $imgName = C('O2O_DEFULT_IMG');
            foreach ($list as $k => $v) {
                // $pregResult =
                // preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$v['push_img'],$matches);
                if (! empty($v['push_img'])) {
                    $list[$k]['img'] = C('adminUploadImgUrl') . $v['push_img'];
                } elseif (! empty($v['bg_pic'])) {
                    $list[$k]['img'] = get_upload_url($v['bg_pic']);
                }  /*
                   * elseif($pregResult){ list($width, $height, $type, $attr) =
                   * getimagesize($matches[1]); if($width>=300){
                   * $list[$k]['img'] = $matches[1]; }else{ $list[$k]['img'] =
                   * C('TMPL_PARSE_STRING.__PUBLIC__').'/Image/new_pic/'.$imgName[$v['batch_type']].'.jpg';
                   * } }
                   */
else { // 默认图片
                    $imgName[$v['batch_type']] = ! empty(
                        $imgName[$v['batch_type']]) ? $imgName[$v['batch_type']] : 'batch_defult';
                    $list[$k]['img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                         '/Image/new_pic/' . $imgName[$v['batch_type']] . '.jpg';
                }
                
                $collectWhere['label_id'] = $v['id'];
                
                // 查询收藏的数量
                $colcount = M()->table('tuser_batch_collect a')
                    ->where($collectWhere)
                    ->count();
                $list[$k]['collect_count'] = $colcount;
                
                // 查询点赞的数量
                $loveWhere['label_id'] = $v['id'];
                $lovecount = M()->field('love')
                    ->table('tbatch_o2o_love a')
                    ->where($loveWhere)
                    ->find();
                $list[$k]['love_count'] = $lovecount['love'];
                
                // 查询评论数量
                $guestCount = M()->table('tbatch_guestbook')
                    ->where(
                    "label_id='" . $v['id'] . "' AND pid=0 AND status=0")
                    ->count();
                $list[$k]['guestCount'] = $guestCount;
                
                $list[$k]['batch_type_name'] = $batchArr[$v['batch_type']];
                
                unset($list[$k]['wap_info']);
                unset($list[$k]['bg_pic']);
            }
        }
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        if ($nowPage > ceil($mapcount / 5)) {
            $list = array();
        }
        $this->assign('list', $list);
        
        if ($nowPage > '1') {
            if ($mobileType == 'mobile') {
                $this->display('mobileo2o2');
            } else {
                $this->assign('nowmy', $nowmy);
                $this->assign('curr_node', $this->userInfo['node_id']);
                $this->display('index2');
            }
        } else {
            // 组装下一页url
            $nexUrl = U('Home/Case/index') . '&' . $Page->parameter . 'p=' .
                 ($nowPage + 1);
            // 所有行业
            // $industryData =
            // M('tindustry_info')->field('industry_id,industry_name')->where('status=0')->select();
            
            if ($mobileType == 'mobile') {
                $this->assign('nex_url', $nexUrl);
                $this->assign('industryData', C('NEW_INDUSTRY'));
                $this->assign('hotCity', $hotCity);
                $this->assign('route', $route);
                $this->display('mobileo2o');
                exit();
            }
            // 选中的城市
            if ($address != "") {
                $cityInfo = M('tcity_code')->field('city_code,city')
                    ->where("city_code={$address}")
                    ->find();
            }
            if (! $cityInfo) {
                $cityInfo = array(
                    'city_code' => '-1', 
                    'city' => '全部地区');
            }
            node_log("首页+在线活动展示");
            
            // 查询关键字
            $keywordsInfo = M('tsystem_param')->field('param_value')
                ->where("param_name='O2O_KEYWORDS'")
                ->order("seq_id desc ")
                ->find();
            
            $pvday = date('Ymd');
            // 查询今日pv
            $pvDaycount = M('tbatch_o2o_pv')->where("day='{$pvday}'")->sum('pv');
            // 查询总PV
            $allDaycount = M('tbatch_o2o_pv')->sum('pv');
            
            // 查询虚拟总数
            $pvInfo = M('tsystem_param')->field('param_value')
                ->where("param_name='O2OPV'")
                ->find();
            $allDaycount = $allDaycount + $pvInfo['param_value'];
            // 增加pv
            $this->add_pv();
            
            // 查询
            
            $this->assign('pvDaycount', $pvDaycount);
            $this->assign('allDaycount', $allDaycount);
            $this->assign('keywordsInfo', $keywordsInfo);
            $this->assign('nex_url', $nexUrl);
            $this->assign('industryData', C('NEW_INDUSTRY'));
            $this->assign('curr_node', $this->userInfo['node_id']);
            $this->assign('hotCity', $hotCity);
            $this->assign('cityInfo', $cityInfo);
            $this->assign('mapcount', $mapcount);
            $this->assign('nowcollect', $nowcollect);
            $this->assign('nowmy', $nowmy);
            $this->assign('onetype', C("O2O_BATCH_TYPE"));
            
            // 查询二级活动
            $twotype = M('tbatch_type')->where('O2O_flag=1')->select();
            // 组装
            $newType = array();
            
            $onetypeArr = C("O2O_BATCH_TYPE");
            if (! empty($onetypeArr)) {
                foreach ($onetypeArr as $k => $l) {
                    
                    $twotype = M('tbatch_type')->where(
                        "O2O_flag=1 AND flag='" . $k . "'")->select();
                    $newType[$k] = $twotype;
                    
                    /*
                     * foreach($twotype as $n=>$v){ if($v['flag']==$k){
                     * $newType[$k]=$v; } }
                     */
                }
            }
            
            // print_r($newType);
            
            $this->assign('twotype', $newType);
            $this->display();
        }
    }

    public function add_pv() {
        
        // 增加pv
        // 判断今天PV是否存在，存在更新否则添加
        $pvday = date('Ymd');
        $pvInfo = M('tbatch_o2o_pv')->field('id')
            ->where("day='{$pvday}'")
            ->find();
        if ($pvInfo['id'] != "") {
            $query_arr = M('tbatch_o2o_pv')->where("day='" . $pvday . "'")->setInc(
                'pv', 3);
        } else {
            
            $pvdata = array(
                'day' => $pvday, 
                'pv' => 3);
            $pvInfo = M('tbatch_o2o_pv')->add($pvdata);
        }
        
        return true;
    }

    public function my_batch() {
        $this->display();
    }
    
    // 获取在线活动的城市
    public function getOnCity() {
        $where = array(
            'd.status' => '1', 
            'd.start_time' => array(
                'elt', 
                date('YmdHis')), 
            'd.end_time' => array(
                'egt', 
                date('YmdHis')), 
            'c.node_type' => array(
                'in', 
                '0,1'), 
            '_string' => 'e.city is not null');
        
        $list = M()->table('tmarketing_info d')
            ->field('e.province_code,e.city_code,city')
            ->join('tnode_info c on d.node_id=c.node_id')
            ->join('tcity_code e on e.path = c.node_citycode')
            ->where($where)
            ->group('e.city')
            ->order('e.city')
            ->select();
        return $list;
    }

    public function activityView() {
        $id = I('show_id');
        $hid = $_REQUEST['hid'];
        $this->assign('hid', $hid);
        $istype = I('istype');
        if ($istype == "") {
            $istype = 2;
        }
        // $where['b.sns_type'] = array('in','12');
        if ($istype == 1) {
            $where = array(
                'a.id' => $id, 
                // 'b.type' => '1',//现在所有渠道都可以 modified by
                // Jeff.liu<imageco.com.cn> for zhaiyueming
                // 'd.start_time' => array('elt',date('YmdHis')),
                // 'd.end_time' => array('egt',date('YmdHis')),
                'c.node_type' => array(
                    'in', 
                    '0,1'));
        } else {
            $where = array(
                'a.id' => $id, 
                // 'b.type' => '1',//现在所有渠道都可以 modified by
                // Jeff.liu<imageco.com.cn> for zhaiyueming
                'd.status' => '1', 
                // 'd.start_time' => array('elt',date('YmdHis')),
                // 'd.end_time' => array('egt',date('YmdHis')),
                'c.node_type' => array(
                    'in', 
                    '0,1'));
        }
        $batchData = M()->table('tbatch_channel a')
            ->field(
            'a.id,a.node_id,d.name,d.batch_type,d.start_time,d.end_time,d.log_img,d.wap_info,d.bg_pic,d.click_count,d.cj_count,d.status,d.is_cj,c.node_short_name')
            ->join('tchannel b on a.channel_id=b.id ')
            ->join('tnode_info c on a.node_id=c.node_id')
            ->join('tmarketing_info d on a.batch_id=d.id')
            ->where($where)
            ->find();
        
        if (! $batchData)
            $this->error('未找到该活动信息');
            
            // 图片判断
        if (! empty($batchData['log_img'])) {
            $logo_path = get_upload_url($batchData['log_img']);
            $batchData['log_img'] = $logo_path;
        } else {
            $batchData['log_img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                 "/Image/new_pic/default_photo.jpg";
        }
        
        // 判断如果是我收藏的可以取消收藏，如果是别人收藏的显示数量
        
        if (! empty($this->userInfo)) {
            $qWhere['label_id'] = $id;
            $qWhere['node_id'] = $this->userInfo['node_id'];
            $colcount = M()->table('tuser_batch_collect a')
                ->where($qWhere)
                ->count();
            if ($colcount > 0) {
                $batchData['my_count'] = $colcount;
            }
        }
        
        // 查询收藏的数量
        $collectWhere['label_id'] = $id;
        $colcount = M()->table('tuser_batch_collect a')
            ->where($collectWhere)
            ->count();
        $batchData['collect_count'] = $colcount;
        
        // 查询此渠道对应的活动号
        $channelInfo = M('tbatch_channel')->field('batch_id')
            ->where("id='{$id}'")
            ->find();
        
        // 查询活动点赞
        $loveWhere = array(
            'label_id' => $id, 
            'm_id' => $channelInfo['batch_id']);
        $loveInfo = M('tbatch_o2o_love')->field('love')
            ->where($loveWhere)
            ->find();
        $batchData['love_count'] = isset($loveInfo['love']) ? $loveInfo['love'] : 0;
        
        // 推荐的活动
        unset($where['a.id']);
        $where['d.batch_type'] = $batchData['batch_type'];
        $where['a.status'] = '1';
        $batchList = M()->table('tbatch_channel a')
            ->field(
            'a.id,d.name,d.batch_type,d.wap_info,d.sns_type,d.bg_pic,d.start_time,d.end_time,d.log_img,d.wap_info,d.bg_pic,d.click_count,d.cj_count,d.status,d.is_cj,c.node_short_name,d.push_img')
            ->join('tchannel b on a.channel_id=b.id ')
            ->join('tnode_info c on a.node_id=c.node_id')
            ->join('tmarketing_info d on a.batch_id=d.id')
            ->where($where)
            ->order('d.add_time DESC')
            ->limit(8)
            ->select();
        // 获取图片路径
        $imgName = C('O2O_DEFULT_IMG');
        foreach ($batchList as $k => $v) {
            // $pregResult =
            // preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$v['wap_info'],$matches);
            if (! empty($v['push_img'])) {
                $batchList[$k]['img'] = C('adminUploadImgUrl') . $v['push_img'];
            } elseif (! empty($v['bg_pic'])) {
                $batchList[$k]['img'] = get_upload_url($v['bg_pic']);
            } else { // 默认图片
                if (empty($imgName[$v['batch_type']])) {
                    $imgName[$v['batch_type']] = 'batch_defult';
                }
                $batchList[$k]['img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                     '/Image/new_pic/' . $imgName[$v['batch_type']] . '.jpg';
            }
            
            // 查询收藏的数量
            $collectWhere['label_id'] = $v['id'];
            $colcount = M()->table('tuser_batch_collect a')
                ->where($collectWhere)
                ->count();
            $batchList[$k]['collect_count'] = $colcount;
            
            // 查询评论数量
            $guestCount = M()->table('tbatch_guestbook')
                ->where("label_id='" . $v['id'] . "' AND pid=0 AND status=0")
                ->count();
            $batchList[$k]['guestCount'] = $guestCount;
            
            unset($batchList[$k]['wap_info']);
            unset($batchList[$k]['bg_pic']);
        }
        
        // 查询评论数
        
        // 查询评论
        import('@.ORG.Util.Page'); // 导入分页类
        $guestCount = M()->table('tbatch_guestbook')
            ->where("label_id='" . $id . "' AND pid=0 AND status=0")
            ->count();
        $Page = new Page($guestCount, 5); // 实例化分页类 传入总记录数和每页显示的记录数
        
        $Page->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $Page->setConfig('prev', '«');
        $Page->setConfig('next', '»');
        $show = $Page->show(); // 分页显示输出
        
        $queryList = M()->table('tbatch_guestbook a')
            ->field("a.*,n.node_name,n.head_photo")
            ->join('tnode_info n on n.node_id=a.node_id ')
            ->where("label_id='" . $id . "' AND pid=0 AND a.status=0")
            ->order('a.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // echo M()->getLastSql();die;
        
        $batchArr = C("BATCH_TYPE_NAME");
        $batchtypename = $batchArr[$batchData['batch_type']];
        
        if (! empty($queryList)) {
            
            foreach ($queryList as $k => $val) {
                
                // echo $val['id'];
                $res = $this->get_tree($val['id'] . ",");
                $queryList[$k]['count'] = count($res[0]);
                
                $queryList[$k]['child'] = $res;
            }
        }
        $isready = M('tbatch_guestbook')->where(
            array(
                'ck_status' => '2', 
                'id' => $hid, 
                'touser' => $this->userInfo['node_id']))->count();
        if ($isready > 0) {
            M('tbatch_guestbook')->where(
                array(
                    'id' => $hid))->save(
                array(
                    'ck_status' => '1'));
            $sysmsgcunt = M('tmessage_stat')->field('new_message_cnt')
                ->where(
                array(
                    'node_id' => $this->userInfo['node_id'], 
                    'message_type' => 2))
                ->find();
            $sysmsgcunt = $sysmsgcunt['new_message_cnt'];
            if ($sysmsgcunt > 0) {
                M()->query(
                    'UPDATE `tmessage_stat` 
				SET `last_time`="' .
                         date('YmdHis') . '",`new_message_cnt`=`new_message_cnt`-1 WHERE 
				( `node_id` = "' .
                         $this->userInfo['node_id'] .
                         '" ) AND ( `message_type` = 2 )');
            }
        }
        
        // print_r($queryList);
        
        $this->add_pv();
        
        $this->assign('batchData', $batchData);
        $this->assign('userInfo', $this->userInfo);
        
        $this->assign('label_id', $id);
        $this->assign('batchtypename', $batchtypename);
        $this->assign('guestCount', $guestCount);
        $this->assign('batchList', $batchList);
        $this->assign('queryList', $queryList);
        $this->assign('guestCount', $guestCount);
        $this->assign('page', $show);
        $this->display();
    }

    public function setCollect() {
        $label_id = I("label_id");
        
        if ($label_id == "") {
            $this->error("活动收藏失败，参数不能为空！");
        }
        
        if ($this->userInfo['user_id'] == "") {
            $this->error("活动收藏失败，您尚未登录！");
        }
        
        $data = array(
            "node_id" => $this->userInfo['node_id'], 
            "user_id" => $this->userInfo['user_id'], 
            "label_id" => $label_id, 
            "add_time" => date('YmdHis'));
        $insertok = M('tuser_batch_collect')->add($data);
        if ($insertok) {
            $this->success("活动收藏成功！");
        } else {
            
            $this->error("活动收藏失败，入库异常！");
        }
    }

    public function cancelCollect() {
        $label_id = I("label_id");
        
        if ($label_id == "") {
            $this->error("活动取消收藏失败，参数不能为空！");
        }
        
        if ($this->userInfo['user_id'] == "") {
            $this->error("活动取消收藏失败，您尚未登录！");
        }
        
        $where = array(
            "node_id" => $this->userInfo['node_id'], 
            "label_id" => $label_id);
        $delok = M('tuser_batch_collect')->where($where)->delete();
        $s = M('tuser_batch_collect')->getLastSql();
        if ($delok) {
            $this->success("活动取消收藏成功！");
        } else {
            
            $this->error("活动取消收藏失败，数据库异常！" . $s);
        }
    }

    public function change_show() {
        $channel_id = I("channel_id");
        $is_show = I("is_show");
        
        if ($channel_id == "") {
            $this->error("操作失败，参数不能为空！");
        }
        
        // 判断如果是我的活动渠道，则需要发布到O2O渠道绑定
        
        $updata = array(
            "status" => $is_show);
        $upok = M('tbatch_channel')->where("id='" . $channel_id . "'")->save(
            $updata);
        
        if ($upok === false) {
            
            $this->error("操作失败，更新数据库异常！");
        } else {
            $this->success("操作成功！");
        }
    }
    
    // 赞
    public function support() {
        $id = I("id");
        
        if ($id == "") {
            $this->error("点赞失败，参数不能为空！");
        }
        
        if ($this->userInfo['user_id'] == "") {
            $this->error("点赞失败，您尚未登录！");
        }
        $insertok = M('tbatch_guestbook')->where("id='" . $id . "'")->setInc(
            'support', 1);
        if ($insertok) {
            $this->success("点赞成功！");
        } else {
            
            $this->error("点赞成功，入库异常！");
        }
    }
    
    // 活动点赞
    public function batch_support() {
        $id = I("id");
        
        if ($id == "") {
            $this->error("点赞失败，参数不能为空！");
        }
        
        // 查询label_id所属机构
        $channelInfo = M('tbatch_channel')->field('batch_id')
            ->where("id='{$id}'")
            ->find();
        
        // 查询活动点赞数据是否存在，不存在插入，否则增加
        $loveInfo = M('tbatch_o2o_love')->field('id')
            ->where("m_id='{$channelInfo['batch_id']}'")
            ->find();
        
        if ($loveInfo['id'] != "") {
            $res = M('tbatch_o2o_love')->where(
                "m_id='" . $channelInfo['batch_id'] . "'")->setInc('love', 1);
            if ($res !== false) {
                $this->success("点赞成功！");
            } else {
                $this->error("点赞失败，更新数据库错误！");
            }
        } else {
            $data = array(
                "m_id" => $channelInfo['batch_id'], 
                "label_id" => $id, 
                "love" => 1);
            $insertok = M('tbatch_o2o_love')->add($data);
            if ($insertok) {
                $this->success("点赞成功！");
            } else {
                
                $this->error("点赞成功，入库异常！");
            }
        }
    }

    public function submit_guestbook() {
        $content = I("content");
        $label_id = I("label_id");
        $hd = M('tbatch_channel')->where(
            array(
                'id' => $label_id))->getField('batch_id');
        // echo $hd;die;
        
        if (empty($this->userInfo)) {
            
            $returnArr = array(
                "status" => 2, 
                "info" => '评论失败，请先登录！');
            echo json_encode($returnArr);
            exit();
        }
        if ($content == "") {
            $this->error("评论内容不能为空！");
        }
        
        // 查询label_id所属机构
        $channelInfo = M('tbatch_channel')->field('node_id')
            ->where("id={$label_id}")
            ->find();
        
        if ($this->userInfo['node_id'] == $channelInfo['node_id']) {
            $this->error("评论失败，自己不能评论自己的活动！");
        }
        
        $data = array(
            "node_id" => $this->userInfo['node_id'], 
            "user_id" => $this->userInfo['user_id'], 
            "pid" => 0, 
            "m_id" => $hd, 
            "label_id" => $label_id, 
            "ck_status" => '2', 
            "status" => '0', 
            "path" => '', 
            "content" => $content, 
            "support" => 0, 
            "replycount" => 0, 
            "touser" => $channelInfo['node_id'], 
            "add_time" => date('YmdHis'));
        $insertok = M('tbatch_guestbook')->add($data);
        
        if ($insertok) {
            // if($this->userInfo['node_id']!=$channelInfo['node_id'])//回复自己de帖子新消息无效
            add_msgstat(
                array(
                    'node_id' => $channelInfo['node_id'], 
                    'message_type' => 2));
            $updata = array(
                "path" => $insertok . ",");
            $pathok = M('tbatch_guestbook')->where("id='" . $insertok . "'")->save(
                $updata);
            M()->commit();
            $task_info = '';
            // 调用评论任务
            // 这儿调用任务服务开始
            $task = D('Task', 'Service')->getTask('guestbook_submit');
            if ($task) {
                $taskResult = $task->start($content);
                log::write('任务结果' . print_r($taskResult, true));
                if ($taskResult && $taskResult['code'] == '0') {
                    $task_info = $taskResult['msg'];
                }
            }
            // 调用任务结束
            $this->success(
                array(
                    'info' => '评论成功', 
                    'task_info' => $task_info));
        } else {
            
            $this->error("评论提交失败，入库异常！");
        }
    }

    public function replychild() {
        $pid = I("pid");
        $label_id = I("show_id");
        $childcontent = I("childcontent");
        $hd = M('tbatch_channel')->where(
            array(
                'id' => $label_id))->getField('batch_id');
        if (empty($this->userInfo)) {
            $this->error("评论失败，请先登录！");
        }
        if ($childcontent == "") {
            $this->error("评论内容不能为空！2");
        }
        
        // 查询pid数据
        
        $pathData = M()->table('tbatch_guestbook a ')
            ->field("a.*,n.node_name,n.head_photo")
            ->join('tnode_info n on n.node_id=a.node_id ')
            ->where("a.id='" . $pid . "'")
            ->find();
        
        if ($this->userInfo['node_id'] == $pathData['node_id']) {
            $this->error("评论失败，自己不能评论自己内容！");
        }
        
        $addtime = date('YmdHis');
        
        $data = array(
            "node_id" => $this->userInfo['node_id'], 
            "user_id" => $this->userInfo['user_id'], 
            "pid" => $pid, 
            "m_id" => $hd, 
            "label_id" => $label_id, 
            "ck_status" => '2', 
            "status" => '0', 
            "path" => '', 
            "content" => $childcontent, 
            "support" => 0, 
            "replycount" => 0, 
            "touser" => $pathData['node_id'], 
            "add_time" => $addtime);
        $insertok = M('tbatch_guestbook')->add($data);
        if ($insertok) {
            /* if($pathData['node_id']!=$this->userInfo['node_id']) */
            add_msgstat(
                array(
                    'node_id' => $pathData['node_id'], 
                    'message_type' => 2));
            // $this->success("评论提交成功！");
            // 更新path
            $updata = array(
                "path" => $pathData['path'] . $insertok . ",");
            $pathok = M('tbatch_guestbook')->where("id='" . $insertok . "'")->save(
                $updata);
            if ($pathok === false) {
                
                $this->error("评论提交失败，更新节点失败！");
                
                // $this->success("评论提交成功！");
            } else {
                
                // 更新父节点评论次数
                $upok = M('tbatch_guestbook')->where("id='" . $pid . "'")->setInc(
                    'replycount', 1);
                
                // 查询商户信息
                $nodeData = M()->table('tnode_info n ')
                    ->field("n.node_name,n.head_photo")
                    ->where("node_id='" . $this->userInfo['node_id'] . "'")
                    ->find();
                
                $returnArr = array(
                    "id" => $insertok, 
                    "pid" => $pathData['pid'], 
                    "node_id" => $this->userInfo['node_id'], 
                    "m_id" => $hd, 
                    "label_id" => $label_id, 
                    "ck_status" => '2', 
                    "status" => '0', 
                    "head_photo" => get_upload_url($nodeData['head_photo']), 
                    "node_name" => $nodeData['node_name'], 
                    "content" => $childcontent, 
                    "add_time" => dateformat($addtime), 
                    "info" => "提交成功", 
                    "status" => 1);
                
                // 调用评论任务
                $task_info = '';
                // 这儿调用任务服务开始
                $task = D('Task', 'Service')->getTask('guestbook_submit');
                if ($task) {
                    $taskResult = $task->start($childcontent);
                    log::write('任务结果' . print_r($taskResult, true));
                    if ($taskResult && $taskResult['code'] == '0') {
                        $task_info = $taskResult['msg'];
                    }
                }
                $returnArr['task_info'] = $task_info;
                // 调用任务结束
                
                echo json_encode($returnArr);
                exit();
            }
        } else {
            
            $this->error("评论提交失败，入库异常！");
        }
    }

    public function contact_node() {
        $contact_node = I("contact_node");
        $this->assign('contact_node', $contact_node);
        $this->display();
    }

    public function submit_contactnode() {
        // 被联系机构号
        $contact_node = I("contact_node");
        $current_node = $this->userInfo['node_id'];
        $contact_msg = I("contact_msg");
        $mid = I("m_id"); // 活动号
        $hd = M('tbatch_channel')->where(array(
            'id' => $mid))->getField('batch_id');
        if (empty($this->userInfo)) {
            $this->error("请先登录后提交留言！");
        }
        
        $screen = M('tnode_info')->where(
            array(
                'node_id' => $current_node))->getField('screen');
        if ($screen == '1') {
            // exit(json_encode(array('code'=>'0','codeText'=>'您已经被禁言了！！！')));
            $this->error("您已经被禁言了！！！");
        }
        
        if ($contact_node == "") {
            $this->error("机构参数错误！");
        }
        
        if ($current_node == "") {
            $this->error("机构参数错误！");
        }
        
        // 查询被联系机构用户ID
        $userID = M()->table('tuser_info n ')
            ->field("n.user_id")
            ->where("node_id='" . $contact_node . "'")
            ->find();
        
        $data = array(
            "message_text" => $contact_msg, 
            "send_node_id" => $this->userInfo['node_id'], 
            "send_user_id" => $this->userInfo['user_id'], 
            "receive_node_id" => $contact_node, 
            "receive_user_id" => $userID['user_id'], 
            "m_id" => $hd, 
            "ck_status" => '2', 
            "status" => '4', 
            "reply_id" => time(),  // 会话ID
            "laiyuan_type" => '1', 
            "add_time" => date('YmdHis'));
        $insertok = M('tmessage_info')->add($data);
        if ($insertok) {
            $this->success("发送消息成功！");
        } else {
            $this->error("发送消息失败，入库错误！");
        }
    }

    public function caseshow1() {
        $this->assign("select", 1);
        $this->display();
    }

    public function caseshow2() {
        $this->assign("select", 2);
        $this->display();
    }

    public function caseshow3() {
        $this->assign("select", 3);
        $this->display();
    }

    public function dialogCity() {
        
        // 有缓存
        if (S('city')) {
            $citylist = S('city');
        } else {
            $citylist = $this->cityPy();
            S('city', $citylist, 3600);
        }
        
        // 分组排序城市
        $newcity = array();
        if (! empty($citylist)) {
            
            foreach ($citylist as $ck => $cal) {
                if ($cal['py_first'] != "") {
                    $newcity[$cal['py_first']][$ck] = $cal;
                }
            }
        }
        
        // print_r($newcity);
        
        // ABCD
        $ABCD_Arr = array();
        // EFGH
        $EFGH_Arr = array();
        // JKLM
        $JKLM_Arr = array();
        // NOPQRS
        $NOPQRS_Arr = array();
        // TUVWX
        $TUVWX_Arr = array();
        // YZ
        $YZ_Arr = array();
        
        // 按字母排序
        if (! empty($newcity)) {
            foreach ($newcity as $k => $v) {
                
                if ($k == 'a' || $k == 'b' || $k == 'c' || $k == 'd') {
                    $k = strtoupper($k);
                    $ABCD_Arr[$k] = $v;
                } elseif ($k == 'e' || $k == 'f' || $k == 'g' || $k == 'h') {
                    $k = strtoupper($k);
                    $EFGH_Arr[$k] = $v;
                } elseif ($k == 'j' || $k == 'k' || $k == 'l' || $k == 'm') {
                    $k = strtoupper($k);
                    $JKLM_Arr[$k] = $v;
                } elseif ($k == 'n' || $k == 'o' || $k == 'p' || $k == 'q' ||
                     $k == 'r' || $k == 's') {
                    $k = strtoupper($k);
                    $NOPQRS_Arr[$k] = $v;
                } elseif ($k == 't' || $k == 'u' || $k == 'v' || $k == 'w' ||
                     $k == 'x') {
                    $k = strtoupper($k);
                    $TUVWX_Arr[$k] = $v;
                } elseif ($k == 'y' || $k == 'z') {
                    $k = strtoupper($k);
                    $YZ_Arr[$k] = $v;
                }
            }
        }
        
        ksort($ABCD_Arr);
        ksort($EFGH_Arr);
        ksort($JKLM_Arr);
        ksort($NOPQRS_Arr);
        ksort($TUVWX_Arr);
        ksort($YZ_Arr);
        
        $this->assign("ABCD_Arr", $ABCD_Arr);
        $this->assign("EFGH_Arr", $EFGH_Arr);
        $this->assign("JKLM_Arr", $JKLM_Arr);
        $this->assign("NOPQRS_Arr", $NOPQRS_Arr);
        $this->assign("TUVWX_Arr", $TUVWX_Arr);
        $this->assign("YZ_Arr", $YZ_Arr);
        
        $this->assign("city_arr", $this->city_arr);
        $this->display();
    }

    public function cityPy() {
        $list = D('tcity_code')->field('city_code, city')
            ->where("city_level = '2'")
            ->select();
        $arr = array();
        
        foreach ($list as $info) {
            $info['city'] = str_replace('市', '', $info['city']);
            $city = $info['city'];
            $len = strlen($city);
            for ($i = 0; $i < $len; $i = $i + 3) {
                $word = substr($city, $i, 3);
                $py = Pinyin(substr($city, $i, 3), 1);
                $fpy = substr($py, 0, 1);
                if ($i == 0) {
                    $info['py_first'] = $fpy;
                }
                $info['py'] .= $py;
                $info['py_short'] .= $fpy;
            }
            $arr[] = $info;
        }
        return $arr;
    }

    public function get_tree($node) {
        $nodelen = strlen($node);
        $data = M()->table('tbatch_guestbook a ')
            ->field("a.*,n.node_name,n.head_photo")
            ->join('tnode_info n on n.node_id=a.node_id ')
            ->where(
            "left(path," . $nodelen . ")='" . $node .
                 "' AND pid<>0 AND a.status=0")
            ->order("id DESC ")
            ->select();
        
        foreach ($data as $row) {
            $volume[] = $row['path'];
        }
        
        array_multisort($volume, SORT_DESC, $data);
        
        $newData = array();
        foreach ($data as &$v) {
            $split = explode(',', $v['path']);
            $i = $split[0];
            $level = count($split) - 1;
            $v['lv'] = $level;
            $newData[$i][] = $v;
        }
        
        $newData = array_values($newData);
        
        return $newData;
        // print_r($newData);
    }

    public function get_city() {
        $curr_city = session("current_city");
        
        if ($curr_city != "") {
            return $curr_city;
        }
        
        // 查询所在城市
        $userIp = get_client_ip();
        
        // 2秒超时
        $ctx = stream_context_create(
            array(
                'http' => array(
                    'timeout' => 2)));
        
        $result = @file_get_contents(
            'http://int.dpool.sina.com.cn/iplookup/iplookup.php?ip=' . $userIp, 
            0, $ctx);
        if ($result === false)
            return false;
        
        $info = explode("\t", $result);
        if ($info[0] == - 1 || ! isset($info[5]))
            return false;
        
        $city = iconv('GBK', 'UTF-8', $info[5]);
        
        session("current_city", $city);
        
        return $city;
    }
}