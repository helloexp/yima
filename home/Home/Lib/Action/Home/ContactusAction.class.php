<?php

class ContactusAction extends Action {

    public function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->assign("userInfo", $userInfo);
        
        // 显示分类
        $class_arr = M('tym_class_column')->where(
            "parent_class_id=0 and id in(1,2,3,4,5,6,7,8,52)")->select();
        // 翼码简介
        $imageco_arr = M('tym_news')->where(
            "parent_class_id=18 and class_id=18 and status=1")->find();
        
        $class_id = I('class_id');
        $this_class_arr = M('tym_class_column')->where("id='" . $class_id . "'")->find();
        $this->assign('this_class_arr', $this_class_arr);
        $this->assign('class_id', $class_id);
        
        $this->assign('imageco_arr', $imageco_arr);
        $this->assign('class_arr', $class_arr);
    }
    
    // 成功案例-----------之前版本，现改成与新闻动态界面类似的功能页面
   /* public function successCase() {
        $class_id = I('class_id');
        $Keywords = 'O2O案例,O2O营销案例,微信营销案例,二维码营销案例';
        $Description = '翼码成功案例：翼码旺财成功应用于多个行业和领域，打造多起O2O营销经典案例，为各领域提供全面的O2O营销服务。';
        $son_arr = M('tym_class_column')->where(
            "parent_class_id='" . $class_id . "'")->select();
        $news_arr = array();
        foreach ($son_arr as $cac) {
            $suseeid = M('tym_news')->where(
                "class_id='" . $cac['id'] . "' and status=1")
                ->limit(7)
                ->select();
            
            $news_arr[$cac['id']] = $cac;
            $news_arr[$cac['id']]['news'] = $suseeid;
        }
        $this->assign('Keywords', $Keywords);
        $this->assign('Description', $Description);
        $this->assign('news_arr', $news_arr);
        $this->display();
    }*/
    
    // 解决方案
    public function solution() {
        $class_id = I('class_id');
        if ($class_id == '4') {
            $Keywords = 'O2O解决方案,金融O2O解决方案,餐饮O2O解决方案,零售O2O解决方案,电子商务O2O解决方案';
            $Description = '翼码解决方案：翼码旺财提供各类行业O2O解决方案，覆盖金融O2O,烘培O2O,餐饮O2O,连锁行业O2O，运营商O2O等。';
        }
        
        // $son_arr=M('tym_class_column')->where("parent_class_id='".$class_id."'")->select();
        $son_arr = M()->table('tym_class_column a')
            ->join('tym_news b on a.id =b.class_id')
            ->where("a.parent_class_id='" . $class_id . "' and b.status=1")
            ->group("a.id")
            ->select();
        $this->assign('Keywords', $Keywords);
        $this->assign('Description', $Description);
        $this->assign('son_arr', $son_arr);
        $this->display();
    }

    public function index() {
        $this->display();
    }
    
    // 查询详情
    public function info() {
        $class_id = I('class_id');
        if ($class_id == '8') {
            $Keywords = '翼码招聘,O2O企业招聘';
            $Description = '翼码招贤纳士：上海新大陆翼码信息科技股份有限公司是国内最大的二维码营销服务公司，欢迎加入翼码，一起高速成长。';
        } else if ($class_id == '6') {
            $Keywords = '翼码荣誉,翼码资质';
            $Description = '翼码荣誉资质：翼码是上海市高新技术企业、知识产权培育企业，承担过多项国家级和上海市专项基金项目，并通过ISO9001质量管理体系认证和ISO/ICE27001信息安全管理体系认证。';
        } else if ($class_id == '5') {
            $Keywords = '翼码服务,翼码客服';
            $Description = '翼码服务：翼码为你提供专业的O2O软硬件实施及专家咨询服务，助你顺利开展O2O营销业务。';
        } else {
            $Keywords = '新大陆翼码,翼码旺财,O2O资讯,O2O解决方案,O2O成功案例';
            $Description = '上海新大陆翼码信息科技股份有限公司是国内最大的二维码营销服务公司，业务范围覆盖全国31个省、市、自治区，业务涉及30多个行业和领域。';
        }
        
        $news_id = I('news_id');
        $this_class_arr = M('tym_class_column')->where("id='" . $class_id . "'")->find();
        $map = array(
            'class_id' => $class_id,
            'status' => '1');
        // 'check_status'=>'2'
        
        if (! empty($news_id)) {
            $map['news_id'] = $news_id;
        }
        $info = M('tym_news')->where($map)->find();
        $this->assign('info', $info);
        $this->assign('Keywords', $Keywords);
        $this->assign('Description', $Description);
        $this->assign('this_class_arr', $this_class_arr);
        $this->display();
    }

    // 查询详情
    public function view() {
        $class_id = I('class_id');
        $news_id = I('news_id');
        $this_class_arr = M('tym_class_column')->where("id='" . $class_id . "'")->find();
        $map = array(
                'class_id' => $class_id,
                'status' => '1',
                'news_id' => $news_id);
        if ($class_id == '1' || $class_id == '2') {
            $map['check_status'] = '2';
        }
        $info = M('tym_news')->where($map)->find();

        if ($class_id == 3) {
            $parent_class_id = $info['parent_class_id'];
            $map = array(
                    'parent_class_id' => $parent_class_id,
                    'status' => '1',
                    'news_id' => array(
                            'lt',
                            $info['news_id']));
        } else {
            $map = array(
                    'class_id' => $class_id,
                    'status' => '1',
                    'news_id' => array(
                            'lt',
                            $info['news_id']));
        }

        if ($class_id == '1' || $class_id == '2') {
            $map['check_status'] = '2';
        }
        // 上一篇
        $nextInfo = M('tym_news')->where($map)->order('news_id desc')->find();

        // 下一篇
        $map['news_id'] = array(
                'gt',
                $info['news_id']);
        $lastInfo = M('tym_news')->where($map)->order('news_id asc')->find();

        // 相关推荐
        /*
         * if($info['key_word']){ $wh_arr = array(); $keyarr =
         * explode('|',$info['key_word']); foreach($keyarr as $v){ $wh_arr[]=
         * '%".$v."%'; } $map_like = array( 'class_id'=>$class_id,
         * 'key_word'=>array('like',$wh_arr,'OR') ); $like_arr =
         * M('tym_news')->where($map_like)->select();
         * $this->assign('like_arr',$like_arr); }
         */

        $this->assign('lastInfo', $lastInfo);
        $this->assign('nextInfo', $nextInfo);
        $this->assign('info', $info);
        $this->assign('Keywords', $Keywords);
        $this->assign('Description', $Description);
        $this->assign('this_class_arr', $this_class_arr);
        $this->display();
    }
    
    // 查询列表
    public function slist() {
        // 推荐
        $commend_arr = M('tym_news')->where(
            array(
                'class_id' => '1', 
                'status' => '1', 
                'check_status' => '2', 
                'is_commend' => '1'))
            ->order('add_time desc')
            ->find();
        //经典案例推荐
        $commend_exp = M('tym_news')->where(
                array(
                        'parent_class_id' => '3',
                        'status' => '1',
                        'check_status' => '2',
                        'is_commend' => '1'))
                ->order('add_time desc')
                ->find();

        $class_id = I('class_id');
        if ($class_id == '1') {
            $Keywords = 'O2O资讯,O2O案例,O2O营销,二维码营销,微信营销';
            $Description = '翼码最新动态：关注国内O2O动态，准时为你报道最新O2O行业新闻，分析O2O营销模式，分享O2O营销案例。';
        } else if ($class_id == '2') {
            $Keywords = '翼码新闻,翼码O2O,O2O峰会,O2O论坛';
            $Description = '翼码媒体报道：通过营销案例分享最新O2O营销、微信营销、移动互联网营销等实战经验与技巧。';
        } else if ($class_id == '3'){
            $Keywords = 'O2O案例,O2O营销案例,微信营销案例,二维码营销案例';
            $Description = '翼码成功案例：翼码旺财成功应用于多个行业和领域，打造多起O2O营销经典案例，为各领域提供全面的O2O营销服务。';
        } else {
            $Keywords = '翼码张波,O2O专家,O2O企业,O2O行业';
            $Description = '新大陆翼码从2006年公司成立以来，始终专注于二维码O2O营销业务场景的研究与推广，汇集国内O2O顶尖专家，打造O2O行业顶尖队伍。';
        }
        // $keyword = I('keyword');
        $model = M('tym_news');

        $map = array(
                'status'=>1,
                'parent_class_id'=>$class_id,
        );
        if ($class_id == '1' || $class_id == '2') {
            $map['check_status'] = '2';
            // 20150129因测试部反应和首页数据不一样，故注释此条件
            // $map['is_commend']= '0';
        }
        if (! empty($keyword)) {
            $map['key_word'] = array(
                'like', 
                "%" . $keyword . "%");
        }


        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 9); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $model->where($map)
            ->order('add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        //var_dump($list);exit;

        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('Keywords', $Keywords);
        $this->assign('Description', $Description);
        $this->assign('commend_arr', $commend_arr);
        $this->assign('commend_exp', $commend_exp);
        $this->assign('class_id', $class_id);
        $this->display();
    }
    
    // 合作伙伴
    public function partners() {
        $class_id = I('class_id');
        $info = M('tym_news')->where(
            array(
                'class_id' => $class_id))->select();
        $this->assign('info', $info);
        $this->display();
    }
    
    // 网站地图
    public function map() {
        $ncc = M('tym_news');
        $class = $ncc->where('news_id=93')->find();
        
        $this->assign('class', $class);
        
        $this->display();
    }

    public function arrond() {
        $class_id = I('class_id');
        $top = M('twc_zhuanqu')->where(
            array(
                'zq_status' => '0'))
            ->order("zq_top DESC,add_time DESC")
            ->limit(2)
            ->select();
        
        $arrond = I('arrond');
        
        $map = array(
            'zq_status' => '0');
        
        if (! empty($arrond)) {
            $map['_string'] = "zq_title like '%$arrond%' or zq_content like '%$arrond%'";
        }
        
        import('ORG.Util.Page');
        $mapcount = M('twc_zhuanqu')->where($map)
            ->order("zq_top DESC,add_time DESC")
            ->count();
        // $mapcount = $model->where($map)->count();
        $Page = new Page($mapcount, 6);
        $show = $Page->show();
        
        $list = M('twc_zhuanqu')->where($map)
            ->order("zq_top DESC,add_time DESC")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('page', $show);
        $this->assign('img_way_ip', C('adminUploadImgUrl'));
        $this->assign('list', $list);
        $this->assign('top', $top);
        $this->display();
    }

    public function arrondMore() {
        $class_id = I('class_id');
        $id = I('id');
        $row = M('twc_zhuanqu')->where(array(
            'id' => $id))->find();
        
        $this->assign('row', $row);
        $this->display();
    }
}