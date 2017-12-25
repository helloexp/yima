<?php

/**
 * @2015/01/13
 */
class KeChengAction extends Action {

    private $index;

    public $userArr = array();

    public function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->userArr = $userInfo;
        $this->assign("userInfo", $userInfo);
        $this->assign('img_way_ip', C('adminUploadImgUrl'));
    }

    public function index() {
        $keyword = I('post.keyword');
        $recently = M('tmessage_tmp')->cache(true, 600, 'file')
            ->
        // ->where(array('apply_time'=>array('egt',date('YmdHis',strtotime('-1
        // days')))))
        where(
            array(
                '_string' => "substr(apply_time,1,8) >= " . date('Ymd')))
            ->order('apply_time')
            ->limit(4)
            ->select();
        import('ORG.Util.Page');
        
        $wh = array(
            'apply_time' => array(
                'lt', 
                date('YmdHis')));
        
        if ($keyword != '') {
            $wh['_string'] = "lector_name like '%$keyword%' or lector_jieshao like '%$keyword%' or kecheng_content like '%$keyword%' or content like '%$keyword%' or apply_time like '%$keyword%'";
        }
        
        $count = M('tmessage_tmp')->where($wh)->count();
        
        $Page = new Page($count, 4);
        
        $thereafter = M('tmessage_tmp')->cache(true, 600, 'file')
            ->where($wh)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('apply_time desc')
            ->select();
        
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('recently', $recently);
        $this->assign('thereafter', $thereafter);
        $this->display();
    }

    public function o2ozixun() {
        $this->display();
    }

    public function moveImage() {
        $attitudePath = '/www/wcadmin/upload/';
        $attitudePath .= '00000000/';
        if (! is_dir($attitudePath)) {
            mkdir($attitudePath);
        }
        $attitudePath .= date('Y') . '/';
        if (! is_dir($attitudePath)) {
            mkdir($attitudePath);
        }
        $attitudePath .= date('m') . '/';
        if (! is_dir($attitudePath)) {
            mkdir($attitudePath);
        }
        $attitudePath .= date('d') . '/';
        if (! is_dir($attitudePath)) {
            mkdir($attitudePath);
        }
        
        $savePath = explode('upload/', $attitudePath);
        
        $replaceUrl = C('adminUploadImgUrl') . $savePath[1];
        
        // $sql = 'SELECT push_img, id FROM tmarketing_info where push_img is
        // not null';
        // $imageInfo = M()->query($sql);
        // foreach($imageInfo as $val){
        // $saveData = array();
        // $orginImagePath = './Home/Upload/oto/'.$val['push_img'];
        // $newImagePath = $attitudePath.$val['push_img'];
        // if(copy($orginImagePath,$newImagePath)){
        // $saveData['push_img'] = $savePath[1].$val['push_img'];
        // // unlink($orginImagePath);
        // };
        //
        // if(!empty($saveData)){
        // print_r($saveData);
        // //
        // M('tmarketing_info')->where(array('id'=>$val['id']))->save($saveData);
        // }
        // }
        
        // $sql = 'SELECT a.id,d.name,d.push_img FROM tbatch_channel a INNER
        // JOIN (SELECT id FROM tchannel b WHERE b.type=1 AND b.sns_type=13) b
        // ON
        // a.channel_id=b.id LEFT JOIN tnode_info c ON a.node_id=c.node_id LEFT
        // JOIN
        // tmarketing_info d ON a.batch_id=d.id ORDER BY d.batch_o2o_push_time
        // DESC,a.id DESC';
        // $adminCodeImage = M()->query($sql);
        // foreach ($adminCodeImage as $val){
        // $saveData = array();
        // $saveData['admin_query_code_img'] =
        // $savePath[1].'BatchCode/'.$val['id'].'.png';
        // if(!empty($saveData)){
        // print_r($saveData);
        // M('tbatch_channel')->where(array('id'=>$val['id']))->save($saveData);
        // }
        // }
        
        // $sql = "SELECT news_id, small_img, news_img FROM `tym_news` WHERE
        // class_id in ('19', '56' ) ";
        // $newsLogo = M()->query($sql);
        // // print_r($newsLogo);
        // foreach ($newsLogo as $val){
        // $saveData = array();
        // $orginImagePath = '/www/wcadmin/upload/'.$val['small_img'];
        // $name = explode('/', $val['small_img']);
        // $fileName = array_pop($name);
        // $newImagePath = $attitudePath.$fileName;
        // if(copy($orginImagePath,$newImagePath)){
        // $saveData['small_img'] = $savePath[1].$fileName;
        // $saveData['news_img'] = $savePath[1].$fileName;
        // // unlink($orginImagePath);
        // };
        // if(!empty($saveData)){
        // print_r($saveData);
        // M('tym_news')->where(array('news_id'=>$val['news_id']))->save($saveData);
        // }
        // }
        
        // $sql = "SELECT news_id, content FROM `tym_news` WHERE class_id = 59";
        // $configContent = M()->query($sql);
        // foreach($configContent as $val){
        // if($val['content'] != ''){
        // $saveData = array();
        // $str = str_replace('t.wangcaio2o.com/wcadmin/upload/imageco',
        // 't-static.wangcaio2o.com/wcadmin/upload/00000000/2015/08/05',
        // $val['content']);
        // $saveData['content'] = $str;
        // }
        // if(!empty($saveData)){
        // print_r($saveData);
        // M('tym_news')->where(array('news_id'=>$val['news_id']))->save($saveData);
        // }
        // }
        //
        // $sql = "SELECT news_id, content FROM `tym_news` WHERE class_id = 58";
        // $configContent = M()->query($sql);
        // foreach($configContent as $val){
        // if($val['content'] != ''){
        // $saveData = array();
        // $str =
        // str_replace('t.wangcaio2o.com/wcadmin/style/js/Ueditor/php/upload1',
        // 't-static.wangcaio2o.com/wcadmin/upload/00000000/2015/09/02',
        // $val['content']);
        // $saveData['content'] = $str;
        // }
        // if(!empty($saveData)){
        // print_r($saveData);
        // M('tym_news')->where(array('news_id'=>$val['news_id']))->save($saveData);
        // }
        // }
        
        // $sql = "SELECT news_id, news_img FROM `tym_news` WHERE class_id =
        // 31";
        // $bannerInfo = M()->query($sql);
        // foreach($bannerInfo as $val){
        // $saveData = array();
        // $orginImagePath = '/www/wcadmin/upload/'.$val['news_img'];
        // $name = explode('/', $val['news_img']);
        // $fileName = array_pop($name);
        // $newImagePath = $attitudePath.$fileName;
        // if(copy($orginImagePath,$newImagePath)){
        // $saveData['small_img'] = $savePath[1].$fileName;
        // $saveData['news_img'] = $savePath[1].$fileName;
        // // unlink($orginImagePath);
        // };
        // if(!empty($saveData)){
        // print_r($saveData);
        // M('tym_news')->where(array('news_id'=>$val['news_id']))->save($saveData);
        // }
        // }
    }
}
