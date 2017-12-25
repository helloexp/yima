<?php

/*
 * 新浪微博 @auther: 蒋典平 @last edit :tr 2015.1.9
 */
class WeiboAction extends BaseAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Weibo/'; // 设置附件上传目录
        $this->uploadTmpPath = './Home/Upload/img_tmp/'; // 设置附件上传目录
        $this->xiuxiu_upload_path = 'http://test.wangcaio2o.com/index.php?g=LabelAdmin&m=Weibo&a=upload_pic&type=img';
        
        // 新浪渠道类型
        $this->batch_type = 1;
        $this->sns_type = 1;
    }

    public function beforeCheckAuth() {
        if ($this->wc_version == 'v0') {
            $this->error("尊敬的旺财用户，您没有使用该功能的权限。需要您完成下面的认证信息！", 
                array(
                    '立即认证' => U('Home/AccountInfo/index')));
        }
    }
    // pdf
    public function htmltopdf($headercont, $content) {
        import('@.Vendor.tcpdf.examples.tcpdf_include', '', '.php');
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 
            'UTF-8', false);
        
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('TCPDF');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
        
        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 
            PDF_HEADER_TITLE . ' 006', PDF_HEADER_STRING);
        
        // set header and footer fonts
        $pdf->setHeaderFont(
            Array(
                PDF_FONT_NAME_MAIN, 
                '', 
                PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(
            Array(
                PDF_FONT_NAME_DATA, 
                '', 
                PDF_FONT_SIZE_DATA));
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once (dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->SetFont('stsongstdlight', '', 20);
        $pdf->AddPage();
        $html = $headercont . $content;
        // output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        // reset pointer to the last page
        $pdf->lastPage();
        $dirname = './Home/Upload/pdf/' . $this->node_id . '/';
        $realdir = realpath($dirname);
        if (empty($realdir) || ! is_dir($realdir))
            mkdir($dirname);
        $filename = $dirname . 'tpl.pdf';
        $realfile = realpath($filename);
        copy(realpath('./Home/Upload/pdf/tpl.pdf'), $filename);
        $pdf->Output(realpath($filename), 'F');
        $im = new Imagick();
        $im->readImage(realpath($filename));
        $im->resetIterator();
        $imgs = $im->appendImages(true);
        $imgs->setImageFormat("png");
        if (is_dir('./Home/Upload/img_tmp/' . $this->node_id))
            mkdir('./Home/Upload/img_tmp/' . $this->node_id, 0777);
        $imgshortname = $this->node_id . '/' . time() . '.png';
        $img_name = './Home/Upload/img_tmp/' . $imgshortname;
        $result = $imgs->writeImage($img_name);
        $imgs->clear();
        $imgs->destroy();
        $im->clear();
        $im->destroy();
        if ($result)
            copy($img_name, 
                './Home/Upload/img_tmp/' . $this->node_id . '/thumb_' . time() .
                     '.png');
        return array(
            'result' => $result, 
            'shortpath' => $imgshortname, 
            'imgpath' => 'http://' . $_SERVER[HTTP_HOST] . trim($img_name, '.'));
    }
    
    // 微信账号绑定
    public function index() {
        $htmlid = I('htmlid');
        // 用户openid
        $pid = I("pid");
        // 1 发送历史记录 2 定时发送 3 我的草稿
        $tab_idx = I('tab_idx', 1, 'intval');
        // 读取机构下所有微博账号
        // $weibo_account_info =
        // M('tnode_weibo')->where(array('node_id'=>$this->node_id,'weibo_type'=>'1'))->order("add_time
        // desc")->select();
        // $this->assign('weibo_account_info',$weibo_account_info);
        // 如果没选择用户，获取最近的登录账号
        if ($pid == "") {
            if (! empty($_SESSION['publish_token'])) {
                
                $pid = $_SESSION['publish_token']['openid'];
                
                $openidInfo = M('tnode_weibo')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'openid' => $pid))->find();
                session('publish_token', null);
                session('publish_token', $openidInfo);
            } else {
                
                $openidInfo = M('tnode_weibo')->where(
                    array(
                        'node_id' => $this->node_id))
                    ->order("update_time desc")
                    ->find();
                $pid = $openidInfo['openid'];
                session('publish_token', null);
                session('publish_token', $openidInfo);
            }
        } else {
            $time = date('YmdHis', time());
            $updatetime = M('tnode_weibo')->where(
                array(
                    'node_id' => $this->node_id, 
                    'openid' => $pid))->setField('update_time', $time);
            $openidInfo = M('tnode_weibo')->where(
                array(
                    'node_id' => $this->node_id, 
                    'openid' => $pid))->find();
            session('publish_token', null);
            session('publish_token', $openidInfo);
        }
        
        // print_r($openidInfo);
        $token['access_token'] = $openidInfo['access_token'];
        // todo debug
        $_userinfo = & $_SESSION['WEIBO.' . $this->node_id];
        // 根据pid去接口取值
        if (empty($_userinfo) || $_userinfo['pid'] != $pid ||
             empty($_userinfo['info'])) {
            import("ORG.ThinkSDK.ThinkOauth");
            $sns = ThinkOauth::getInstance("SINA2", $token);
            log_write(print_r($sns, true));
            // 查询用户信息
            $userdata['uid'] = $pid;
            $userinfo = $sns->call('users/show', $userdata, 'get');
            $_userinfo = array(
                'pid' => $pid, 
                'info' => $userinfo);
            log_write(print_r($userinfo, true));
        } else {
            $userinfo = $_userinfo['info'];
            log_write(print_r($userinfo, true));
        }
        
        if ($openidInfo['update_time'] != "") {
            $start_time = $openidInfo['update_time'];
        } else {
            $start_time = $openidInfo['add_time'];
        }
        
        $expireday = floor($openidInfo['expires_in'] / (24 * 3600));
        
        // 过期日期
        $expire = date('Y-m-d', 
            strtotime("+$expireday day", strtotime(substr($start_time, 0, 8))));
        
        // 判断是否过期 1过期 0没过期
        if (date('YmdHis', strtotime("+$expireday day", strtotime($start_time))) <=
             date('YmdHis') && '' != $start_time) {
            $past = 1;
        } else {
            $past = 0;
        }
        $this->assign('userinfo', $userinfo);
        $this->assign('openidInfo', $openidInfo);
        $this->assign('expire', $expire);
        $this->assign('past', $past);
        
        // 查询单个用户信息
        
        // 查询历史发送记录
        $weixin_info = session('publish_token', "");
        $uid = $weixin_info['openid'];
        if (! in_array($tab_idx, 
            array(
                1, 
                2, 
                3)))
            $tab_idx = 1;
        $where = " w.node_id = '{$this->node_id}' and w.uid = '{$uid}'";
        $order = " w.send_time desc";
        if ($tab_idx == 1)
            $where .= " and (w.status = '1' or (w.status = '2' and w.send_status = '2'))";
        else if ($tab_idx == 2)
            $where .= " and w.status in ('2') and (w.send_time is null or w.send_time = '') ";
        else if ($tab_idx == 3) {
            $where .= " and w.status = '3'";
            $order = 'w.add_time desc';
        }
        
        import('ORG.Util.Page'); // 导入分页类
        $count = M()->table('tweibo_info w ')
            ->where($where)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        
        $tab_list = M()->table('tweibo_info w ')
            ->field('w.*,t.name ')
            ->join(" tnode_weibo t on w.node_id=t.node_id and w.uid=t.openid ")
            ->where($where)
            ->order($order)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        if (! empty($tab_list)) {
            
            foreach ($tab_list as $k => $v) {
                if ($v['content_pic'] != "") {
                    $fileArr = explode("/", $v['content_pic']);
                    $nameArr = explode(".", $fileArr[1]);
                    $thumb_name = $fileArr[0] . "/" . "thumb_" . $nameArr[0] .
                         "." . $nameArr[1];
                    $tab_list[$k]['thumb_img'] = $thumb_name;
                }
            }
        }
        
        $this->assign('tab_idx', $tab_idx);
        $this->assign('tab_list', $tab_list);
        $this->assign('page', $pageShow);
        $this->assign('pid', $pid);
        $this->assign('node_id', $this->node_id);
        $this->assign('htmlid', $htmlid);
        $this->assign('xiuxiu_upload_path', $this->xiuxiu_upload_path);
        if ($htmlid != '' and $htmlid == '25') {
            $this->display('index2');
        } else {
            $this->display();
        }
    }

    /**
     * 授权开始
     */
    public function authorize() {
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance("SINA2");
        
        // 跳转到授权页面
        redirect($sns->getRequestCodeURL());
    }

    public function sns_Callback($type = null, $code = null) {
        $err = I("error");
        
        if ($err == "") {
            // 加载ThinkOauth类并实例化一个对象
            import("ORG.ThinkSDK.ThinkOauth");
            $sns = ThinkOauth::getInstance($type);
            $extend = null;
            $token = $sns->getAccessToken($code, $extend);
            
            if (! empty($token)) {
                
                // token记录SESSION
                session('publish_token', $token);
                
                // 再根据uid查找用户信息
                $userdata['uid'] = $token['openid'];
                $userinfo = $sns->call('users/show', $userdata, 'get');
                // print_r($userinfo);
                // 判断用户是否存在，存在则更新，不存在插入
                
                $account_info = M('tnode_weibo')->where(
                    array(
                        'node_id' => $this->node_id, 
                        'weibo_type' => '1', 
                        'openid' => $token['openid']))->find();
                
                $nowtime = date('YmdHis');
                
                if (! empty($account_info['openid'])) {
                    // 更新
                    $data = array(
                        'name' => $userinfo['name'], 
                        'head_photo' => $userinfo['profile_image_url'], 
                        'access_token' => $token['access_token'], 
                        'expires_in' => $token['expires_in'], 
                        'openid' => $token['openid'], 
                        'openkey' => $token['openkey'], 
                        'update_time' => $nowtime);
                    $result = M('tnode_weibo')->where(
                        "openid={$token['openid']} and node_id='" .
                             $this->node_id . "'")->save($data);
                } else {
                    // 插入
                    $data = array(
                        'node_id' => $this->node_id, 
                        'weibo_type' => '1', 
                        'name' => $userinfo['name'], 
                        'head_photo' => $userinfo['profile_image_url'], 
                        'access_token' => $token['access_token'], 
                        'expires_in' => $token['expires_in'], 
                        'openid' => $token['openid'], 
                        'openkey' => $token['openkey'], 
                        'add_time' => $nowtime);
                    $result = M('tnode_weibo')->add($data);
                }
                header("location:index.php?g=LabelAdmin&m=Weibo&a=index");
                exit();
            } else {
                
                $this->error("授权失败！");
            }
        } else {
            
            header("location:index.php?g=LabelAdmin&m=Weibo&a=index");
            exit();
        }
    }
    
    // 发布长微博
    public function publish_long() {
        $call_back = I("call_back");
        $this->assign("call_back", $call_back);
        $this->assign('long_weibo_header', $_COOKIE['long_weibo_header']);
        $this->assign('long_weibo_content', $_COOKIE['long_weibo_content']);
        $this->display();
    }
    
    // 切换账号
    public function change_account() {
        $pid = I("pid");
        // 查询该机构下的微博用户
        $weibo_list = M('tnode_weibo')->where(
            array(
                'node_id' => $this->node_id, 
                'weibo_type' => '1'))
            ->order("add_time desc")
            ->select();
        $this->assign("weibo_list", $weibo_list);
        $this->assign("pid", $pid);
        $this->display();
    }
    
    // 删除账号
    public function del_pid() {
        $node_id = I("node_id");
        $pid = I("pid");
        // 删除
        $weibo_del = M('tnode_weibo')->where(
            array(
                'node_id' => $node_id, 
                'weibo_type' => '1', 
                'openid' => $pid))->delete();
        if ($weibo_del) {
            $this->success("微博账号删除成功！");
        } else {
            
            $this->error("微博账号删除失败，数据库异常！");
        }
    }
    
    // 自定义来源
    public function modify_weibo() {
        $pid = I("pid");
        $node_id = I("node_id");
        $account_info = M('tnode_weibo')->where(
            array(
                'node_id' => $node_id, 
                'weibo_type' => '1', 
                'openid' => $pid))->find();
        $this->assign("account_info", $account_info);
        $this->assign("pid", $pid);
        $this->assign("node_id", $node_id);
        $this->display();
    }
    
    // 提交自定义来源
    public function modify_weibo_post() {
        $pid = I("pid");
        $node_id = I("node_id");
        $app_name = I("app_name");
        $app_key = I("app_key");
        $app_secret = I("app_secret");
        $app_domain = I("app_domain");
        
        $openidInfo = M('tnode_weibo')->where(
            array(
                'node_id' => $node_id, 
                'openid' => $pid))->find();
        if (empty($openidInfo)) {
            $this->error("查询用户不存在！");
        }
        
        $nowtime = date("Ymdhis");
        $data = array(
            'app_name' => $app_name, 
            'app_key' => $app_key, 
            'app_secret' => $app_secret, 
            'app_domain' => $app_domain, 
            'update_time' => $nowtime);
        $result = M('tnode_weibo')->where(
            "openid='{$pid}' and node_id='" . $node_id . "'")->save($data);
        if ($result) {
            
            $this->success("操作成功！");
        } else {
            
            $this->error("入库异常！");
        }
        
        $this->display();
    }

    public function show_auth() {
        $pid = I("pid");
        $node_id = I("node_id");
        // 重新授权
        $this->assign("pid", $pid);
        $this->assign("node_id", $node_id);
        
        $this->display("show_auth");
    }
    
    // 自定义来源保存返回code,并获取token
    public function save_auth_code() {
        $callback = I("callback");
        $pid = I("pid");
        $node_id = I("node_id");
        if (empty($callback) || empty($pid)) {
            $this->error("参数错误！");
        }
        
        $openidInfo = M('tnode_weibo')->where(
            array(
                'node_id' => $node_id, 
                'openid' => $pid))->find();
        if (empty($openidInfo)) {
            $this->error("查询用户不存在！");
        }
        
        $conf['app_key'] = $openidInfo['app_key'];
        $conf['app_secret'] = $openidInfo['app_secret'];
        $conf['callback'] = $openidInfo['app_domain'];
        $conf['authorize'] = "forcelogin=true";
        
        // 拆分code
        $codeArr = explode("=", $callback);
        
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance('SINA2', '', $conf);
        $extend = null;
        $token = $sns->getAccessToken($codeArr[1], $extend, $conf);
        
        if (! empty($token)) {
            // 更新数据
            $data = array(
                'access_token' => $token['access_token'], 
                'expires_in' => $token['expires_in'], 
                'openid' => $token['openid'], 
                'openkey' => $token['openkey']);
            $result = M('tnode_weibo')->where(
                "openid='{$pid}' and node_id='" . $node_id . "'")->save($data);
            if ($result) {
                $this->success("操作成功！" . $token['access_token']);
            } else {
                $this->error("操作失败！");
            }
        } else {
            $this->error("获取token失败！");
        }
    }

    /**
     * 修改自定义来源授权开始
     */
    public function authorize2() {
        $pid = I("pid");
        $node_id = I("node_id");
        
        if ($pid == "" || $node_id == "") {
            $this->error("参数错误！");
        }
        
        $openidInfo = M('tnode_weibo')->where(
            array(
                'node_id' => $node_id, 
                'openid' => $pid))->find();
        if (empty($openidInfo)) {
            $this->error("用户不存在！");
        }
        
        $conf['app_key'] = $openidInfo['app_key'];
        $conf['app_secret'] = $openidInfo['app_secret'];
        $conf['callback'] = $openidInfo['app_domain'];
        $conf['authorize'] = "forcelogin=true";
        
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance("SINA2", '', $conf);
        
        // 跳转到授权页面
        redirect($sns->getRequestCodeURL($conf));
    }

    public function publish_long_submit() {
        $long_title = I("long_title");
        $long_content = I("long_content");
        $bg_type = I("bg_type");
        $submit_type = I("submit_type");
        
        // 判断是否输入
        if (empty($long_title) || $long_title == "请在这里输入标题") {
            $this->error("请输入长微博标题！");
        }
        if (empty($long_content) || $long_content == "请在这里输入正文内容") {
            $this->error("请输入长微博内容！");
        }
        $long_content = htmlspecialchars_decode($long_content);
        if (! empty($_REQUEST['htmlcontent']) &&
             $_REQUEST['htmlcontent'][0] != null)
            $bodycss = $_REQUEST['htmlcontent'][0];
        $data = $this->htmltopdf(
            $bodycss . '<body><div>' . $long_title . '</div>', 
            $long_content . '</body>');
        $data['weibolong'] = 1;
        if ($data['result']) {
            if (! empty($data['imgpath'])) {
                setcookie('long_weibo_header', $long_title, 0, '/', 
                    $_SERVER['HTTP_HOST']);
                setcookie('long_weibo_content', $long_content, 0, '/', 
                    $_SERVER['HTTP_HOST']);
            } else
                $this->error($data);
            $this->success($data);
        } else
            $this->error($data);
    }

    function getc() {
        dump($_COOKIE);
    }

    function autowrap($fontsize, $angle, $fontface, $string, $width) {
        // 这几个变量分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
        $content = "";
        // 将字符串拆分成一个个单字 保存到数组 letter 中
        for ($i = 0; $i < mb_strlen($string); $i ++) {
            $letter[] = mb_substr($string, $i, 1);
        }
        foreach ($letter as $l) {
            $teststr = $content . " " . $l;
            $testbox = imagettfbbox($fontsize, $angle, $fontface, $teststr);
            // 判断拼接后的字符串是否超过预设的宽度
            if (($testbox[2] > $width) && ($content !== "")) {
                $content .= "\n";
            }
            $content .= $l;
        }
        return $content;
    }

    public function texttoimg($title, $long_content, $data) {
        set_time_limit(0);
        // 创建头部图片
        $headpic = imageCreatetruecolor($data['width'], $data['title_height']);
        imagesavealpha($headpic, true);
        $color = imagecolorallocate($headpic, $data['title_bg_color'][0], 
            $data['title_bg_color'][1], $data['title_bg_color'][2]);
        imagecolortransparent($headpic, $color);
        imagefill($headpic, 0, 0, $color);
        // 写入文字,判断标题文字长度
        $b = imagettfbbox($data['title_font_size'], 0, $data['font'], $title);
        $w = abs($b[2] - $b[0]);
        $h = abs($b[5] - $b[3]);
        $x = ceil(($data['width'] - $w) / 2); // 计算文字的水平位置
        
        $white_color = imagecolorallocate($headpic, 
            $data['title_font_color'][0], $data['title_font_color'][1], 
            $data['title_font_color'][2]);
        imagettftext($headpic, $data['title_font_size'], 0, $x, 
            $h + $data['title_top_height'], $white_color, $data['font'], $title); // 文字位置
                                                                                  
        // 判断是否绘制下划线
        if (! empty($data['title_line_color'])) {
            
            $line_color = imagecolorallocate($headpic, 
                $data['title_line_color'][0], $data['title_line_color'][1], 
                $data['title_line_color'][2]);
            imageline($headpic, 10, $data['title_height'] - 8, 
                $data['width'] - 10, $data['title_height'] - 8, $line_color);
        }
        
        mb_internal_encoding("UTF-8");
        $text = $this->autowrap($data['fontsize'], 0, $data['font'], 
            $long_content, $data['width'] - 20); // 自动换行处理
        $uu = count(explode("\n", $text));
        $content_height = $uu * 20;
        $bg = imagecreatetruecolor($data['width'], $content_height + 10); // 创建画布
        imagesavealpha($bg, true);
        $color = imagecolorallocate($bg, $data['bg_color'][0], 
            $data['bg_color'][1], $data['bg_color'][2]);
        imagecolortransparent($bg, $color);
        imagefill($bg, 0, 0, $color);
        
        // 文字
        $font_color = imagecolorallocate($bg, $data['font_color'][0], 
            $data['font_color'][1], $data['font_color'][2]); // 创建白色
        imagettftext($bg, $data['fontsize'], 0, 8, 20, $font_color, 
            $data['font'], $text);
        
        // 合并头部和正文
        $all_height = $content_height + $data['title_height'] + 10;
        // 创建空白图片合并图片
        $lastpic = imageCreatetruecolor($data['width'], $all_height);
        imagesavealpha($lastpic, true);
        $color = imagecolorallocate($lastpic, $data['bg_color'][0], 
            $data['bg_color'][1], $data['bg_color'][2]);
        imagecolortransparent($lastpic, $color);
        imagefill($lastpic, 0, 0, $color);
        imagecopyresampled($lastpic, $headpic, 0, 0, 0, 0, imagesx($headpic), 
            imagesy($headpic), imagesx($headpic), imagesy($headpic));
        imagecopyresampled($lastpic, $bg, 0, $data['title_height'], 0, 0, 
            imagesx($bg), imagesy($bg), imagesx($bg), imagesy($bg));
        
        $filename = time() . sprintf('%04s', mt_rand(0, 1000));
        $genpath = $this->uploadTmpPath . $this->nodeId . '/' . $filename .
             ".png";
        $genshort = $this->nodeId . '/' . $filename . ".png";
        
        if ($data['submit_type'] == 1) {
            // 预览
            header("Content-Type: image/png");
            imagepng($lastpic);
            imagedestroy($lastpic);
        } else {
            // 生成
            imagepng($lastpic, $genpath);
            imagedestroy($lastpic);
            
            $thumb_path = $this->uploadTmpPath . $this->nodeId . '/' . "thumb_" .
                 $filename . ".png";
            
            // 生成缩略图
            import('ORG.Util.Image');
            $imageobj = new Image(); // 实例化上传类
            $ok = $imageobj->thumb($genpath, $thumb_path, 'png', 
                $maxWidth = 200, $maxHeight = 200);
            
            $result_data = array(
                'status' => 1, 
                'pic_url' => $genpath, 
                'pic_short' => $genshort, 
                'thumb_path' => $thumb_path, 
                'error' => '', 
                'info' => '生成成功');
            echo json_encode($result_data);
            exit();
        }
    }
    
    // 未发送（未执行的定时发送和草稿）的微博删除
    public function weibo_delete() {
        $id = I('id', 0, 'intval');
        $model = M('tweibo_info');
        $info = $model->where("node_id = '{$this->node_id}'")->find($id);
        if (! $info) {
            $this->error('删除失败！');
        }
        
        if ($info['status'] == '3' || $info['status'] == '2') {
            if ($info['status'] == '2' &&
                 ($info['send_status'] == '1' || $info['send_status'] == '2')) {
                $this->error('删除失败！');
            }
            
            $model->where("node_id = '{$this->node_id}' and id = '{$id}'")->delete();
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }
    
    // 发布草稿
    public function weibo_draft_submit() {
        $nowtime = date('YmdHis');
        $draft_id = I('id', 0, 'intval');
        $model = M('tweibo_info');
        $info = $model->where(
            "node_id = '" . $this->node_id . "' and id='" . $draft_id . "'")->find();
        if (! $info) {
            $this->error('发布失败！');
        }
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance("SINA2", $_SESSION['publish_token']);
        
        // 判断是否带图片上传
        if ($info['content_pic'] == "") {
            $data = array(
                'status' => $info['content']);
            $result = $sns->call('statuses/update', $data, 'POST');
            Log::write(
                '机构号:' . $this->node_id . '草稿发布,请求发文字微博：' . $info['content']);
            Log::write(
                '机构号:' . $this->node_id . '草稿发布,请求发文字微博：' . $info['content'] .
                     ",返回码：" . $result['error_code'] . "," . $result['error']);
        } else {
            
            $fullpic = './Home/Upload/Weibo/' . $info['content_pic'];
            $data = array(
                'status' => $info['content'], 
                'url' => $fullpic);
            // $result = $sns->call('statuses/upload_url_text',$data,'POST');
            $result = $sns->upload($info['content'], $fullpic);
            Log::write(
                '机构号:' . $this->node_id . '草稿发布,请求发图片微博：' . $info['content'] .
                     ",图片地址：" . $fullpic);
            Log::write(
                '机构号:' . $this->node_id . '草稿发布,请求发图片微博：' . $info['content'] .
                     ",返回码：" . $result['error_code'] . "," . $result['error']);
        }
        
        if ($result['error_code'] == 0) {
            // 变更草稿为即时发送
            $data = array(
                'status' => '1', 
                'send_time' => $nowtime, 
                'send_status' => '2');
            $result = M('tweibo_info')->where("id = '{$draft_id}'")->save($data);
            $this->success('发布成功');
        } else {
            
            if ($result['error_code'] == '21327') {
                $this->error("新浪token已过期，请重新授权");
            } else {
                
                $this->error("调用新浪接口失败！");
            }
        }
    }
    
    // 从草稿转到定时发送
    public function weibo_time_submit() {
        $id = I('id', 0, 'intval');
        $time = I('choosetime');
        // $this->success($time);
        // 时间转换
        $settime = date('YmdHis', strtotime($time));
        $model = M('tweibo_info');
        $info = $model->where(
            "node_id = '" . $this->node_id . "' and id='" . $id . "'")->find();
        if (! $info) {
            $this->error('发布失败！');
        }
        $data = array(
            'status' => '2', 
            'set_time' => $settime, 
            'send_status' => '0');
        $result = M('tweibo_info')->where("id = '{$id}'")->save($data);
        if ($result > 0) {
            $this->success('存入成功');
        } else {
            $this->error('存入失败！');
        }
    }
    
    // 提交发布
    public function weibo_public_submit() {
        
        // 判断是否授权登录
        if (empty($_SESSION['publish_token'])) {
            $this->error("请先授权登录！");
        }
        
        $nowtime = date('YmdHis');
        $content = I('content');
        $content_pic = I('content_pic');
        $publish_flag = I('publish_flag'); // 发布标志
        $settime = I('settime');
        $save_type = I('save_type'); // 存入草稿判断
        
        if ($content == "") {
            $this->error("微博内容不能为空！");
        }
        
        // 判断发布内容是否已经发布过
        $Weiboinfo = M('tweibo_info')->where(
            array(
                'node_id' => $this->node_id, 
                'content' => $content, 
                'uid' => $_SESSION['publish_token']['openid']))->find();
        if (! empty($Weiboinfo)) {
            $this->error("微博内容已经发布过了！");
        }
        
        // 如果是定时发送则判断时间是否为空
        if ($publish_flag == 2) {
            
            if ($settime == "") {
                $this->error("定时发送时间不能为空！");
            }
            // 时间转换
            $settime = date('YmdHis', strtotime($settime));
        }
        
        // 存入草稿判断
        if ($save_type == '1') {
            $publish_flag = 3;
        }
        
        if ($content_pic != "") {
            // 复制图片到正式目录
            if (! is_dir(APP_PATH . '/Upload/Weibo')) {
                mkdir(APP_PATH . '/Upload/Weibo', 0777);
            }
            if (! is_dir(APP_PATH . '/Upload/Weibo/' . $this->node_id)) {
                mkdir(APP_PATH . '/Upload/Weibo/' . $this->node_id, 0777);
            }
            $old_image_url = APP_PATH . '/Upload/img_tmp/' . $content_pic;
            $new_image_url = APP_PATH . '/Upload/Weibo/' . $content_pic;
            
            $full_pic = './Home/Upload/Weibo/' . $content_pic;
            $flag = copy($old_image_url, $new_image_url);
            
            // 复制缩略图文件
            $fileArr = explode("/", $content_pic);
            $nameArr = explode(".", $fileArr[1]);
            
            $old_thumb_url = APP_PATH . '/Upload/img_tmp/' . $fileArr[0] . "/" .
                 "thumb_" . $nameArr[0] . "." . $nameArr[1];
            
            // 判断文件存在复制
            if (file_exists($old_thumb_url)) {
                $new_thumb_url = APP_PATH . '/Upload/Weibo/' . $fileArr[0] . "/" .
                     "thumb_" . $nameArr[0] . "." . $nameArr[1];
                $flag = copy($old_thumb_url, $new_thumb_url);
            }
            
            if (! $flag) {
                $this->error("复制图片异常！");
            }
        }
        
        // 判断发布模式 1 立即发送 2 定时发送 3 草稿
        if ($publish_flag == 1) {
            
            import("ORG.ThinkSDK.ThinkOauth");
            $sns = ThinkOauth::getInstance("SINA2", $_SESSION['publish_token']);
            
            // 判断是否带图片上传
            if ($content_pic == "") {
                $data = array(
                    'status' => $content);
                $result = $sns->call('statuses/update', $data, 'POST');
                Log::write('机构号:' . $this->node_id . '请求发文字微博：' . $content);
                Log::write(
                    '机构号:' . $this->node_id . '请求发文字微博：' . $content . ",返回码：" .
                         $result['error_code'] . "," . $result['error']);
            } else {
                
                // 上传图片拼参数
                /*
                 * $boundary = uniqid('------------------'); $MPboundary =
                 * '--'.$boundary; $endMPboundary = $MPboundary. '--'; //
                 * 需要上传的图片所在路径 $filename = $new_image_url; $file =
                 * file_get_contents($filename); $multipartbody .= $MPboundary .
                 * "\r\n"; $multipartbody .= 'Content-Disposition: form-data;
                 * name="pic"; filename="wiki.png"'. "\r\n"; $multipartbody .=
                 * 'Content-Type: image/png'. "\r\n\r\n"; $multipartbody .=
                 * $file. "\r\n"; $k = "source"; // 这里改成 appkey $v =
                 * "4294557489"; $multipartbody .= $MPboundary . "\r\n";
                 * $multipartbody.='content-disposition: form-data;
                 * name="'.$k."\"\r\n\r\n"; $multipartbody.=$v."\r\n"; $k =
                 * "status"; $v = $content; $multipartbody .= $MPboundary .
                 * "\r\n"; $multipartbody.='content-disposition: form-data;
                 * name="'.$k."\"\r\n\r\n"; $multipartbody.=$v."\r\n";
                 * $multipartbody .= "\r\n". $endMPboundary;
                 */
                
                /*
                 * $data = array( 'status' => $content, 'url'=>$full_pic );
                 * $result = $sns->call('statuses/upload',$data,'POST',true);
                 */
                
                $result = $sns->upload($content, $full_pic);
                
                // $result =
                // $sns->call('statuses/upload',$multipartbody,'POST',true,array("Content-Type:
                // multipart/form-data; boundary=$boundary"));
                Log::write(
                    '机构号:' . $this->node_id . '请求发图片微博：' . $content . ",图片地址：" .
                         $full_pic);
                Log::write(
                    '机构号:' . $this->node_id . '请求发图片微博：返回内容' .
                         print_r($result, true));
                Log::write(
                    '机构号:' . $this->node_id . '请求发图片微博：' . $content . ",返回码：" .
                         $result['error_code'] . "," . $result['error']);
            }
            
            if ($result['error_code'] == 0) {
                // 插入数据库
                $data = array(
                    'node_id' => $this->node_id, 
                    'uid' => $_SESSION['publish_token']['openid'], 
                    'token' => $_SESSION['publish_token']['access_token'], 
                    'content' => $content, 
                    'content_pic' => $content_pic, 
                    'status' => $publish_flag, 
                    'set_time' => $settime, 
                    'send_time' => $nowtime, 
                    'send_status' => '2', 
                    'add_time' => $nowtime);
                $re = M('tweibo_info')->add($data);
                if (! empty($re)) {
                    $this->success('发布成功');
                } else {
                    $this->error('发布失败，入库异常!');
                }
            } else {
                
                if ($result['error_code'] == '21327') {
                    
                    $this->error("新浪token已过期，请重新授权");
                } else {
                    
                    node_log(
                        "机构" . $this->node_id . "=返回码" . $result['error_code'] .
                             "url地址=" . $full_pic);
                    $this->error("调用新浪接口失败！" . $result['error_code']);
                }
            }
        } else {
            // 草稿或者定时发送插入数据库
            $data = array(
                'node_id' => $this->node_id, 
                'uid' => $_SESSION['publish_token']['openid'], 
                'token' => $_SESSION['publish_token']['access_token'], 
                'content' => $content, 
                'content_pic' => $content_pic, 
                'status' => $publish_flag, 
                'set_time' => $settime, 
                'send_time' => '', 
                'send_status' => '0', 
                'add_time' => $nowtime);
            $result = M('tweibo_info')->add($data);
            if ($result) {
                $this->success('存入成功！');
            } else {
                $this->success('存入失败，入库异常！');
            }
        }
    }
    
    // 上传图片
    public function upload_pic() {
        $type = I('get.type', null, 'trim');
        ini_set('memory_limit', '1024M');
        if ($type == 'img') {
            if ($type == "img") {
                $this->maxSize = 3072 * 1024; // 设置附件上传大小
                $this->allowExts = array(
                    "gif", 
                    "jpg", 
                    "jpeg", 
                    "png"); // 设置附件上传类型
            }
            
            import('ORG.Net.UploadFile');
            $upload = new UploadFile(); // 实例化上传类
            $upload->maxSize = $this->maxSize;
            $upload->thumb = true;
            $upload->thumbMaxWidth = 200;
            $upload->thumbMaxHeight = 200;
            $upload->allowExts = $this->allowExts;
            $upload->savePath = $this->uploadTmpPath . $this->nodeId . '/'; // 设置附件
            $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
            if (! $upload->upload()) { // 上传错误提示错误信息
                $this->errormsg = $upload->getErrorMsg();
            } else { // 上传成功 获取上传文件信息
                $info = $upload->getUploadFileInfo();
                $this->imgurl = $info[0]['savename'];
            }
            
            $thumb_path = $this->uploadTmpPath . $this->nodeId . '/' . "thumb_" .
                 $this->imgurl;
            
            $arr = array(
                'msg' => '0000',  // 通信是否成功
                'error' => $this->errormsg,  // 返回错误
                'imgurl' => $this->uploadTmpPath . $this->nodeId . '/' .
                     $this->imgurl,  // 返回图片名
                    'pic_short_path' => $this->nodeId . '/' . $this->imgurl, 
                    'thumb_pic' => $thumb_path);
            echo json_encode($arr);
            exit();
        }
    }

    public function addchannel() {
        $channel_model = M('tchannel');
        $onemap = array(
            'node_id' => $this->node_id, 
            'sns_type' => $this->sns_type);
        $channel_id = $channel_model->where($onemap)
            ->limit('1')
            ->getField('id');
        if (! $channel_id) {
            // 微博渠道存在不存在
            $channel_arr = array(
                'name' => '新浪微博', 
                'type' => $this->batch_type, 
                'sns_type' => $this->sns_type, 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            
            $query = $channel_model->add($channel_arr);
            if (! $query) {
                $this->error('添加新浪微博渠道失败！');
            }
            return $query;
        } else {
            return $channel_id;
        }
    }
    
    // 获取活动发布渠道的id
    public function get_batch_channel($batch_id, $batch_type) {
        $channel_id = $this->addchannel();
        if (! $channel_id)
            $this->error('渠道号获取失败');
        
        $batch_channel_model = M('tbatch_channel');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_id' => $batch_id, 
            'channel_id' => $channel_id, 
            'batch_type' => $batch_type);
        $batch_channel_count = $batch_channel_model->where($map)->count();
        if ($batch_channel_count < 1) {
            $data_arr = array(
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id, 
                'channel_id' => $channel_id, 
                'status' => '1', 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            $query = $batch_channel_model->add($data_arr);
            $url = U('Label/Label/index', 
                array(
                    'id' => $query), '', '', true);
            if (! $query) {
                $arr = array(
                    'status' => '0', 
                    'error' => '生成标签失败！', 
                    'label_url' => '');
                echo json_encode($arr);
                exit();
            }
            
            // 长链接转换
            $short_url = $this->get_short_url($url);
            if ($short_url == "") {
                $arr = array(
                    'status' => '0', 
                    'error' => '调用接口失败！', 
                    'label_url' => '');
                echo json_encode($arr);
                exit();
            } else {
                
                $arr = array(
                    'status' => '1', 
                    'error' => '', 
                    'label_url' => $short_url);
                echo json_encode($arr);
                exit();
            }
        } else {
            
            $id = $batch_channel_model->where($map)
                ->limit('1')
                ->getField('id');
            $url = U('Label/Label/index', 
                array(
                    'id' => $id), '', '', true);
            $short_url = $this->get_short_url($url);
            if ($short_url == "") {
                $arr = array(
                    'status' => '0', 
                    'error' => '调用接口失败！', 
                    'label_url' => '');
                echo json_encode($arr);
                exit();
            } else {
                
                $arr = array(
                    'status' => '1', 
                    'error' => '', 
                    'label_url' => $short_url);
                echo json_encode($arr);
                exit();
            }
        }
    }

    function get_short_url($url) {
        
        // 长链接转换
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance("SINA2", $_SESSION['publish_token']);
        $data = array(
            'url_long' => $url, 
            'source' => C('THINK_SDK_SINA2.APP_KEY'));
        $res = $sns->call('short_url/shorten', $data, 'GET');
        
        $short_url = "";
        
        if ($res['error_code'] == 0) {
            if (! empty($res)) {
                foreach ($res['urls'] as $k => $v) {
                    $short_url = $v['url_short'];
                    break;
                }
            }
        } else {
            
            return '';
        }
        
        return $short_url;
    }

    public function string_chunk_to_array($string, $data) {
        $font = $data['font'];
        $size = $data['size'];
        $width = $data['width'];
        $padding = $data['padding-top'];
        $limit = $width - 2 * 7;
        $result = array();
        $temp = $string;
        
        while (1) {
            $box = imageftbbox($size, 0, $font, $temp);
            $length = mb_strlen($temp, 'utf-8');
            // echo $box;
            
            // print_r($box);
            if ($box[2] - $box[0] > $limit) {
                $temp = mb_substr($temp, 0, $length - 2, 'utf-8');
            } else {
                $result[] = $temp;
                // echo "tmp========".$temp;
                // echo "<br>";
                $string_length = mb_strlen($string, 'utf-8');
                // echo "streng====".$string_length;
                // echo "<br>";
                // echo "length====".$length;
                // exit;
                
                $string = mb_substr($string, $length, $string_length - 4, 
                    'utf-8');
                $temp = $string;
            }
            if ($string == '') {
                break;
            }
        }
        return $result;
    }

    public function text2image($data) {
        $string = $data['string'];
        $title = $data['title'];
        $title_bg = $data['title_bg'];
        $background = $data['background']; // 文章背景色
        $text_color = $data['text_color']; // 文章文字颜色
        $title_text_color = $data['title_text_color']; // 文章标题颜色
        $font = $data['font']; // 字体
        $size = $data['size']; // 文章文字大小
        $title_size = $data['title_size']; // 标题文字大小
        $width = $data['width']; // 宽度
        $padding_left = $data['padding_left']; // 左边编剧
        $padding_top = $data['padding_top']; // 行间距
        
        $title_top_height = $data['title_top_height'];
        $title_height = $data['title_height']; // 标题高度
        $text_line_height = $data['text_line_height']; // 横线高度
        $text_line_color = $data['text_line_color']; // 横线颜色
        $title_line_color = $data['title_line_color']; // 标题横线颜色
        
        $text = array();
        $line = explode(PHP_EOL, $string);
        
        foreach ($line as $v) {
            $v = trim($v);
            if ($v != '') {
                $temp = $this->string_chunk_to_array($v, $data);
                // array_push($temp,'');
                $text = array_merge($text, $temp);
            }
        }
        
        $fontpath = $font; // 字体
        $fontsize = $size; // 字体大小
        
        if (! empty($title_line_color)) {
            // 为标题横线增加高度
            $title_height = $title_height + 10;
        }
        
        // 创建头部图片
        $headpic = imageCreatetruecolor($width, $title_height);
        imagesavealpha($headpic, true);
        $color = imagecolorallocate($headpic, $title_bg[0], $title_bg[1], 
            $title_bg[2]);
        imagecolortransparent($headpic, $color);
        imagefill($headpic, 0, 0, $color);
        // 写入文字,判断标题文字长度
        $b = imagettfbbox($title_size, 0, $fontpath, $title);
        $w = abs($b[2] - $b[0]);
        $h = abs($b[5] - $b[3]);
        // 计算标题起始位置
        $x = ceil(($width - $w) / 2); // 计算文字的水平位置
        $white_color = imagecolorallocate($headpic, $title_text_color[0], 
            $title_text_color[1], $title_text_color[2]);
        imagettftext($headpic, $title_size, 0, $x, $h + $title_top_height, 
            $white_color, $fontpath, $title); // 文字位置
                                              
        // 判断是否绘制下划线
        if (! empty($title_line_color)) {
            
            $line_color = imagecolorallocate($headpic, $title_line_color[0], 
                $title_line_color[1], $title_line_color[2]);
            imageline($headpic, $padding_left, $title_height - 8, $width - 10, 
                $title_height - 8, $line_color);
        }
        
        // 创建正文图片填充背景色
        $height = count($text) * $padding_top + count($text) * $text_line_height +
             20; // 行数*高度+横线高度+底部20高度
        
        $arbg = imageCreatetruecolor($width, $height);
        imagesavealpha($arbg, true);
        $color = imagecolorallocate($arbg, $background[0], $background[1], 
            $background[2]);
        imagecolortransparent($arbg, $color);
        imagefill($arbg, 0, 0, $color);
        
        // 填充文字黑色
        $black = imagecolorallocate($arbg, $text_color[0], $text_color[1], 
            $text_color[2]);
        
        // 横线颜色
        $line_color = imagecolorallocate($arbg, $text_line_color[0], 
            $text_line_color[1], $text_line_color[2]);
        
        foreach ($text as $k => $v) {
            imagettftext($arbg, $fontsize, 0, $padding_left, $padding_top, 
                $black, $fontpath, $v); // 文字位置
                                        // 为横线添加高度
            $padding_top = $padding_top + $text_line_height;
            // 画横线
            // 判断画虚线还是直线
            if ($data['bg_type'] == 'longWb-clear') {
                $white = imagecolorallocate($arbg, 255, 255, 255);
                $gray = imagecolorallocate($arbg, 188, 188, 188);
                $style = array(
                    $gray, 
                    $gray, 
                    $white, 
                    $white);
                imagesetstyle($arbg, $style);
                imageline($arbg, $padding_left, $padding_top, $width - 10, 
                    $padding_top, IMG_COLOR_STYLED);
            } else {
                imageline($arbg, $padding_left, $padding_top, $width - 10, 
                    $padding_top, $line_color);
            }
            
            $padding_top = $padding_top + 30;
        }
        
        // 合并头和正文图片
        $height = $height + $title_height;
        // 创建空白图片合并图片
        $lastpic = imageCreatetruecolor($width, $height);
        imagesavealpha($lastpic, true);
        $color = imagecolorallocate($lastpic, $background[0], $background[1], 
            $background[2]);
        imagecolortransparent($lastpic, $color);
        imagefill($lastpic, 0, 0, $color);
        imagecopyresampled($lastpic, $headpic, 0, 0, 0, 0, imagesx($headpic), 
            imagesy($headpic), imagesx($headpic), imagesy($headpic));
        imagecopyresampled($lastpic, $arbg, 0, $title_height, 0, 0, 
            imagesx($arbg), imagesy($arbg), imagesx($arbg), imagesy($arbg));
        
        $filename = time() . sprintf('%04s', mt_rand(0, 1000));
        $genpath = $this->uploadTmpPath . $this->nodeId . '/' . $filename .
             ".png";
        $genshort = $this->nodeId . '/' . $filename . ".png";
        
        if ($data['submit_type'] == 1) {
            // 预览
            header("Content-Type: image/png");
            imagepng($lastpic);
            imagedestroy($lastpic);
        } else {
            // 生成
            imagepng($lastpic, $genpath);
            imagedestroy($lastpic);
            
            $result_data = array(
                'status' => 1, 
                'pic_url' => $genpath, 
                'pic_short' => $genshort, 
                'error' => '', 
                'info' => '生成成功');
            echo json_encode($result_data);
            exit();
        }
    }

    /**
     *
     * @name cwb php生成长微博
     * @param $str 格式化后的html，仅支持p标签 $size 字体大小 $font_path字体路径 $save_path 图片保存路径
     * @todo 增加图片支持
     * @author leo108 root@leo108.com
     */
    public function cwb($str, $size, $font_path, $save_path) {
        $str = strip_tags($str, '<p>');
        $matches = array();
        preg_match_all("/<p[\s\S]+?<\/p>/", $str, $matches);
        print_r($matches[0]);
        foreach ($matches[0] as $key => $value) {
            $matches[0][$key] = preg_replace("/<p[^>]*>/", "", 
                $matches[0][$key]);
            $matches[0][$key] = str_replace('</p>', '', $matches[0][$key]);
            $matches[0][$key] = trim($matches[0][$key]);
        }
        
        $newrows = array();
        foreach ($matches[0] as $key => $str) {
            $strlen = mb_strlen($str, 'utf-8');
            if ($strlen == 0) {
                continue;
            }
            $text = '';
            for ($i = 0; $i < $strlen; $i ++) {
                $char = mb_substr($str, $i, 1, 'utf-8');
                $text . $char;
                $bbox = imagettfbbox($size, 0, $font_path, $text . $char);
                if ($bbox[2] > 320) {
                    $newrows[] = $text;
                    $text = $char;
                } else {
                    $text .= $char;
                }
            }
            $newrows[] = $text;
            $newrows[] = '';
        }
        $height = count($newrows) * 16 + 30;
        $im = imagecreatetruecolor(360, $height);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefill($im, 0, 0, $white);
        imagecopyresampled($im, $thumb_im, 20, 10, 0, 0, $pic_width, 
            $pic_height, $pic_width, $pic_height);
        $curheight = $pic_height + 30;
        foreach ($newrows as $key => $value) {
            imagettftext($im, $size, 0, 20, $curheight, $black, $font_path, 
                $value);
            $curheight += 16;
        }
        header("Content-Type: image/png");
        imagepng($im);
    }
}